<?php
declare(strict_types=1);
require __DIR__ . '/../app/init.php';

$pdo = DB::conn();

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to'] ?? '');
$date = trim($_GET['date'] ?? '');

$where=[]; $params=[];
if ($from !== '') { $where[]='t.departure_city LIKE :from'; $params[':from']=$from.'%'; }
if ($to   !== '') { $where[]='t.destination_city LIKE :to';  $params[':to']=$to.'%'; }
if ($date !== '') { $where[]='date(t.departure_time)=:d';     $params[':d']=$date; }

$sql = "SELECT t.*, c.name AS company_name
        FROM Trips t
        JOIN Bus_Company c ON c.id=t.company_id";
if ($where) $sql .= " WHERE ".implode(' AND ',$where);
$sql .= " ORDER BY t.departure_time ASC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$trips = $stmt->fetchAll();

page_header('Seferler');
?>
<h2>Sefer Ara</h2>
<form method="get" action="/trips.php">
  <label>Kalkış</label><br>
  <input name="from" value="<?= e($from) ?>" placeholder="İstanbul"><br><br>

  <label>Varış</label><br>
  <input name="to" value="<?= e($to) ?>" placeholder="Ankara"><br><br>

  <label>Tarih</label><br>
  <input name="date" type="date" value="<?= e($date) ?>"><br><br>

  <button type="submit">Seferleri Listele</button>
</form>

<hr>
<h3>Sonuçlar</h3>
<?php if (!$trips): ?>
  <p>Sefer bulunamadı. (Filtreleri boş bırakıp tekrar deneyebilirsin.)</p>
<?php else: ?>
  <div class="grid">
    <?php foreach ($trips as $t): ?>
      <div class="card">
        <div style="font-weight:bold;"><?= e($t['company_name']) ?></div>
        <div><?= e($t['departure_city']) ?> → <?= e($t['destination_city']) ?></div>
        <div>Kalkış: <strong><?= e(dtfmt($t['departure_time'])) ?></strong></div>
        <div>Varış: <?= e(dtfmt($t['arrival_time'])) ?></div>
        <div>Fiyat: <strong><?= e(money_tl((int)$t['price'])) ?></strong> | Kapasite: <?= (int)$t['capacity'] ?></div>

        <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
          <?php if (hasAnyRole(['user'])): ?>
            <a href="/buy_ticket.php?trip_id=<?= urlencode($t['id']) ?>" style="padding:6px 10px;border:1px solid #ccc;border-radius:6px;text-decoration:none;">Satın Al</a>
          <?php endif; ?>
          <?php if (hasAnyRole(['company','admin'])): ?>
            <a href="/company.php" style="padding:6px 10px;border:1px solid #ccc;border-radius:6px;text-decoration:none;">Seferi Düzenle</a>
          <?php endif; ?>
          <?php if (!isLoggedIn()): ?>
            <a href="/login.php" style="padding:6px 10px;border:1px solid #ccc;border-radius:6px;text-decoration:none;">Giriş yapınca satın al</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php
page_footer();
