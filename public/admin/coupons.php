<?php
declare(strict_types=1);

require __DIR__ . "/../../app/init.php";
require __DIR__ . "/../../app/support/uuid.php";

if (!isLoggedIn() || !in_array(role(), ['admin'], true)) {
    set_flash('Admin paneline erişim yok.','error'); redirect('/');
}

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Edit modu için
$editId = isset($_GET['edit']) ? (string)$_GET['edit'] : '';

$op = $_POST['op'] ?? '';

// Ekleme
if ($op === 'create') {
    $code        = trim((string)($_POST['code'] ?? ''));
    $discount    = trim((string)($_POST['discount'] ?? '0'));
    $usage_limit = trim((string)($_POST['usage_limit'] ?? '0'));
    $expire_date = str_replace('T', ' ', trim((string)($_POST['expire_date'] ?? '')));
    if ($expire_date !== '' && strlen($expire_date) === 16) {
        $expire_date .= ':00';
    }

    $stmt = $pdo->prepare("INSERT INTO Coupons(id, code, discount, usage_limit, company_id, expire_date)
                           VALUES(:id, :code, :discount, :usage_limit, NULL, :expire_date)");
    try {
        $stmt->execute([
            ':id'          => uuid(),
            ':code'        => $code,
            ':discount'    => $discount,
            ':usage_limit' => $usage_limit,
            ':expire_date' => $expire_date
        ]);
        set_flash('Kupon eklendi.','success');
    } catch (PDOException $ex) {
        set_flash('Kupon eklenemedi.','error');
    }
}

// Güncelleme
if ($op === 'update') {
    $id          = trim((string)($_POST['id'] ?? ''));
    $code        = trim((string)($_POST['code'] ?? ''));
    $discount    = trim((string)($_POST['discount'] ?? '0'));
    $usage_limit = trim((string)($_POST['usage_limit'] ?? '0'));
    $expire_date = str_replace('T', ' ', trim((string)($_POST['expire_date'] ?? '')));
    if ($expire_date !== '' && strlen($expire_date) === 16) {
        $expire_date .= ':00';
    }

    $stmt = $pdo->prepare("UPDATE Coupons
                           SET code = :code,
                               discount = :discount,
                               usage_limit = :usage_limit,
                               company_id = NULL,
                               expire_date = :expire_date
                           WHERE id = :id");
    try {
        $stmt->execute([
            ':id'          => $id,
            ':code'        => $code,
            ':discount'    => $discount,
            ':usage_limit' => $usage_limit,
            ':expire_date' => $expire_date
        ]);
        set_flash('Kupon güncellendi.','success');
    } catch (PDOException $ex) {
        set_flash('Kupon güncellenemedi.','error');
    }
}

// Silme
if ($op === 'delete') {
    $id = trim((string)($_POST['id'] ?? ''));
    if ($id !== '') {
        $stmt = $pdo->prepare("DELETE FROM Coupons WHERE id = :id");
        $stmt->execute([':id' => $id]);
        set_flash('Kupon silindi.','success');
    } else {
        set_flash('İstek geçersiz.','error');
    }
}

// Listeleme
$st = $pdo->query("SELECT id, code, discount, usage_limit, expire_date, created_at, company_id
                   FROM Coupons
                   ORDER BY created_at DESC");
$coupons = $st->fetchAll(PDO::FETCH_ASSOC);

page_header('Kuponlar (Admin)');
$flash = get_flash();
if ($flash) echo "<p class='flash {$flash['type']}'>".e($flash['msg'])."</p>";
?>

<h2>Kupon Oluştur</h2>
<form method="post" action="/admin/coupons.php">
  <input type="hidden" name="op" value="create">
  <label>Kupon Kodu</label><br>
  <input name="code" required><br><br>

  <label>İndirim Oranı</label><br>
  <input name="discount" required><br><br>

  <label>Kullanım Limiti</label><br>
  <input name="usage_limit" required><br><br>

  <label>Son Geçerlilik Tarihi</label><br>
  <input name="expire_date" type="datetime-local" required><br><br>

  <button type="submit">Oluştur</button>
</form>

<hr>
<h2>Mevcut Kuponlar</h2>
<table border="1" cellpadding="8" cellspacing="0">
  <thead>
    <tr>
      <th>ID</th>
      <th>Kod</th>
      <th>İndirim</th>
      <th>Kullanım Limiti</th>
      <th>Son Geçerlilik</th>
      <th>Oluşturulma</th>
      <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($coupons)): ?>
      <tr><td colspan="7">Henüz kupon yok.</td></tr>
    <?php else: ?>
      <?php foreach ($coupons as $c): ?>
        <tr>
          <td><?= e($c['id']) ?></td>
          <td><?= e($c['code']) ?></td>
          <td><?= e((string)$c['discount']) ?></td>
          <td><?= e((string)$c['usage_limit']) ?></td>
          <td><?= e($c['expire_date']) ?></td>
          <td><?= e($c['created_at']) ?></td>
          <td>
            <a href="/admin/coupons.php?edit=<?= urlencode($c['id']) ?>"
               style="display:inline-block; padding:4px 10px; background:#d9d9d9; color:black; text-decoration:none; border:1px solid #ccc; border-radius:4px;">
               Düzenle
            </a>

            <form method="post" action="/admin/coupons.php" style="display:inline" onsubmit="return confirm('Silinsin mi?');">
              <input type="hidden" name="op" value="delete">
              <input type="hidden" name="id" value="<?= e($c['id']) ?>">
              <button type="submit">Sil</button>
            </form>
          </td>
        </tr>

        <?php if ($editId && $editId === $c['id']): ?>
          <tr>
            <td colspan="7" style="background:#fafafa;">
              <form method="post" action="/admin/coupons.php" style="padding:10px; border:1px solid #ddd;">
                <input type="hidden" name="op" value="update">
                <input type="hidden" name="id" value="<?= e($c['id']) ?>">

                <label>Kupon Kodu</label><br>
                <input name="code" value="<?= e($c['code']) ?>" required><br><br>

                <label>İndirim Oranı</label><br>
                <input name="discount" value="<?= e((string)$c['discount']) ?>" required><br><br>

                <label>Kullanım Limiti</label><br>
                <input name="usage_limit" value="<?= e((string)$c['usage_limit']) ?>" required><br><br>

                <label>Son Geçerlilik Tarihi</label><br>
                <?php
                  $dtVal = '';
                  if (!empty($c['expire_date'])) {
                      $ts = strtotime($c['expire_date']);
                      if ($ts !== false) {
                          $dtVal = date('Y-m-d\TH:i', $ts);
                      }
                  }
                ?>
                <input name="expire_date" type="datetime-local" value="<?= e($dtVal) ?>" required><br><br>

                <button type="submit">Kaydet</button>
                <a href="/admin/coupons.php">Vazgeç</a>
              </form>
            </td>
          </tr>
        <?php endif; ?>

      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php page_footer(); ?>
