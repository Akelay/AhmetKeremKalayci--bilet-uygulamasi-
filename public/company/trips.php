<?php
declare(strict_types=1);

require __DIR__ . '/../../app/init.php';
require __DIR__ . '/../../app/support/uuid.php';

if (!isLoggedIn() || !in_array(role(), ['company','admin'], true)) {
    set_flash('Firma paneline erişim yok.','error'); redirect('/');
}

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


$company_id = (string) (user()['company_id'] ?? '');
if ($company_id === '') {
    set_flash('Hesabınıza firma atanmalı.','error'); redirect('/');
}


$editId = isset($_GET['edit']) ? (string)$_GET['edit'] : '';


$op = $_POST['op'] ?? '';

// Ekleme
if ($op === 'create') {
    $departure_city  = trim((string)($_POST['departure_city'] ?? ''));
    $destination_city= trim((string)($_POST['destination_city'] ?? ''));
    $departure_time  = str_replace('T',' ', trim((string)($_POST['departure_time'] ?? '')));
    $arrival_time    = str_replace('T',' ', trim((string)($_POST['arrival_time'] ?? '')));
    if ($departure_time !== '' && strlen($departure_time) === 16) { $departure_time .= ':00'; }
    if ($arrival_time   !== '' && strlen($arrival_time)   === 16) { $arrival_time   .= ':00'; }
    $price_tl      = trim((string)($_POST['price_tl'] ?? ''));
    $capacity      = trim((string)($_POST['capacity'] ?? '0'));

    if ($departure_city==='' || $destination_city==='' || $departure_time==='' || $arrival_time==='' || $price_tl==='' || (int)$capacity<=0) {
        set_flash('Tüm alanlar zorunlu.','error');
    } else {
        $price = (int) round((float) str_replace(',', '.', $price_tl) * 100);

        $stmt = $pdo->prepare("INSERT INTO Trips
                               (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity)
                               VALUES (:id, :company_id, :departure_city, :destination_city, :departure_time, :arrival_time, :price, :capacity)");
        try {
            $stmt->execute([
                ':id'               => uuid(),
                ':company_id'       => $company_id,
                ':departure_city'   => $departure_city,
                ':destination_city' => $destination_city,
                ':departure_time'   => $departure_time,
                ':arrival_time'     => $arrival_time,
                ':price'            => $price,
                ':capacity'         => (int)$capacity
            ]);
            set_flash('Sefer eklendi.','success');
        } catch (PDOException $ex) {
            set_flash('Sefer eklenemedi.','error');
        }
    }
}

// Güncelleme
if ($op === 'update') {
    $id               = trim((string)($_POST['id'] ?? ''));
    $departure_city   = trim((string)($_POST['departure_city'] ?? ''));
    $destination_city = trim((string)($_POST['destination_city'] ?? ''));
    $departure_time   = str_replace('T',' ', trim((string)($_POST['departure_time'] ?? '')));
    $arrival_time     = str_replace('T',' ', trim((string)($_POST['arrival_time'] ?? '')));
    if ($departure_time !== '' && strlen($departure_time) === 16) { $departure_time .= ':00'; }
    if ($arrival_time   !== '' && strlen($arrival_time)   === 16) { $arrival_time   .= ':00'; }
    $price_tl          = trim((string)($_POST['price_tl'] ?? ''));
    $capacity          = trim((string)($_POST['capacity'] ?? '0'));

    if ($id==='' || $departure_city==='' || $destination_city==='' || $departure_time==='' || $arrival_time==='' || $price_tl==='' || (int)$capacity<=0) {
        set_flash('Eksik bilgi.','error');
    } else {
        $price = (int) round((float) str_replace(',', '.', $price_tl) * 100);

        $stmt = $pdo->prepare("UPDATE Trips
                               SET departure_city = :departure_city,
                                   destination_city = :destination_city,
                                   departure_time = :departure_time,
                                   arrival_time = :arrival_time,
                                   price = :price,
                                   capacity = :capacity
                               WHERE id = :id AND company_id = :company_id");
        try {
            $stmt->execute([
                ':departure_city'   => $departure_city,
                ':destination_city' => $destination_city,
                ':departure_time'   => $departure_time,
                ':arrival_time'     => $arrival_time,
                ':price'            => $price,
                ':capacity'         => (int)$capacity,
                ':id'               => $id,
                ':company_id'       => $company_id
            ]);
            set_flash('Sefer güncellendi.','success');
        } catch (PDOException $ex) {
            set_flash('Sefer güncellenemedi.','error');
        }
    }
}

// Silme
if ($op === 'delete') {
    $id = trim((string)($_POST['id'] ?? ''));
    if ($id !== '') {
        $stmt = $pdo->prepare("DELETE FROM Trips WHERE id = :id AND company_id = :company_id");
        try {
            $stmt->execute([':id' => $id, ':company_id' => $company_id]);
            set_flash('Sefer silindi.','success');
        } catch (PDOException $ex) {
            set_flash('Sefer silinemedi.','error');
        }
    } else {
        set_flash('İstek geçersiz.','error');
    }
}

// Firma adı
$st = $pdo->prepare("SELECT name FROM Bus_Company WHERE id = :id");
$st->execute([':id' => $company_id]);
$company_name = (string)($st->fetchColumn() ?: 'Firma');

// Listeleme
$st = $pdo->prepare("SELECT id, departure_city, destination_city, departure_time, arrival_time, price, capacity, created_date
                     FROM Trips
                     WHERE company_id = :cid
                     ORDER BY departure_time DESC");
$st->execute([':cid' => $company_id]);
$trips = $st->fetchAll(PDO::FETCH_ASSOC);

page_header('Seferler');
$flash = get_flash();
if ($flash) echo "<p class='flash {$flash['type']}'>".e($flash['msg'])."</p>";
?>

<p><a href="/company/index.php">&larr; Panele geri dön</a></p>
<h2><?= e($company_name) ?> — Seferler</h2>

<h3>Yeni Sefer Ekle</h3>
<form method="post" action="/company/trips.php">
  <input type="hidden" name="op" value="create">

  <label>Kalkış</label><br>
  <input name="departure_city" required placeholder="İstanbul"><br><br>

  <label>Varış</label><br>
  <input name="destination_city" required placeholder="Ankara"><br><br>

  <label>Kalkış Zamanı</label><br>
  <input name="departure_time" type="datetime-local" required><br><br>

  <label>Varış Zamanı</label><br>
  <input name="arrival_time" type="datetime-local" required><br><br>

  <label>Fiyat (₺)</label><br>
  <input name="price_tl" required placeholder="250" type="number" step="0.01" min="0"><br><br>

  <label>Kapasite</label><br>
  <input name="capacity" required type="number" min="1" placeholder="40"><br><br>

  <button type="submit">Oluştur</button>
</form>

<hr>
<h3>Mevcut Seferler</h3>
<table border="1" cellpadding="8" cellspacing="0">
  <thead>
    <tr>
      <th>ID</th>
      <th>Rota</th>
      <th>Kalkış</th>
      <th>Varış</th>
      <th>Fiyat</th>
      <th>Kapasite</th>
      <th>Oluşturulma</th>
      <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($trips)): ?>
      <tr><td colspan="8">Henüz sefer yok.</td></tr>
    <?php else: ?>
      <?php foreach ($trips as $t): ?>
        <tr>
          <td><?= e($t['id']) ?></td>
          <td><?= e($t['departure_city']) ?> → <?= e($t['destination_city']) ?></td>
          <td><?= e(dtfmt($t['departure_time'])) ?></td>
          <td><?= e(dtfmt($t['arrival_time'])) ?></td>
          <td><?= e(money_tl((int)$t['price'])) ?></td>
          <td><?= (int)$t['capacity'] ?></td>
          <td><?= e((string)($t['created_date'] ?? '')) ?></td>
          <td>
            <a href="/company/trips.php?edit=<?= urlencode($t['id']) ?>"
               style="display:inline-block; padding:4px 10px; background:#d9d9d9; color:black; text-decoration:none; border:1px solid #ccc; border-radius:4px;">
               Düzenle
            </a>

            <form method="post" action="/company/trips.php" style="display:inline" onsubmit="return confirm('Silinsin mi?');">
              <input type="hidden" name="op" value="delete">
              <input type="hidden" name="id" value="<?= e($t['id']) ?>">
              <button type="submit">Sil</button>
            </form>
          </td>
        </tr>

        <?php if ($editId && $editId === $t['id']): ?>
          <tr>
            <td colspan="8" style="background:#fafafa;">
              <form method="post" action="/company/trips.php" style="padding:10px; border:1px solid #ddd;">
                <input type="hidden" name="op" value="update">
                <input type="hidden" name="id" value="<?= e($t['id']) ?>">

                <label>Kalkış</label><br>
                <input name="departure_city" value="<?= e($t['departure_city']) ?>" required><br><br>

                <label>Varış</label><br>
                <input name="destination_city" value="<?= e($t['destination_city']) ?>" required><br><br>

                <label>Kalkış Zamanı</label><br>
                <?php
                  $depVal = '';
                  if (!empty($t['departure_time'])) {
                      $ts = strtotime($t['departure_time']);
                      if ($ts !== false) { $depVal = date('Y-m-d\TH:i', $ts); }
                  }
                ?>
                <input name="departure_time" type="datetime-local" value="<?= e($depVal) ?>" required><br><br>

                <label>Varış Zamanı</label><br>
                <?php
                  $arrVal = '';
                  if (!empty($t['arrival_time'])) {
                      $ts2 = strtotime($t['arrival_time']);
                      if ($ts2 !== false) { $arrVal = date('Y-m-d\TH:i', $ts2); }
                  }
                ?>
                <input name="arrival_time" type="datetime-local" value="<?= e($arrVal) ?>" required><br><br>

                <label>Fiyat (₺)</label><br>
                <?php $prTL = number_format(((int)$t['price'])/100, 2, '.', ''); ?>
                <input name="price_tl" type="number" step="0.01" min="0" value="<?= e($prTL) ?>" required><br><br>

                <label>Kapasite</label><br>
                <input name="capacity" type="number" min="1" value="<?= (int)$t['capacity'] ?>" required><br><br>

                <button type="submit">Kaydet</button>
                <a href="/company/trips.php" style="margin-left:6px;">Vazgeç</a>
              </form>
            </td>
          </tr>
        <?php endif; ?>

      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php page_footer(); ?>
