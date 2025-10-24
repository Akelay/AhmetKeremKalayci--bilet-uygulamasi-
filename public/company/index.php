<?php
declare(strict_types=1);

require __DIR__ . '/../../app/init.php';

if (!isLoggedIn() || !in_array(role(), ['company','admin'], true)) {
  set_flash('Firma paneline erişim yok.','error'); redirect('/'); exit;
}

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

page_header('Firma Paneli');

if ($f = get_flash()) {
  echo "<div class='flash {$f['type']}'>".e($f['msg'])."</div>";
}

echo "<h2>Firma Paneli</h2>";

if (role() === 'company') {
  $cid = user()['company_id'] ?? null;
  if ($cid) {
    $st = $pdo->prepare("SELECT name FROM Bus_Company WHERE id = :id LIMIT 1");
    $st->execute([':id' => $cid]);
    if ($name = $st->fetchColumn()) {
      echo "<p><strong>Firma:</strong> ".e($name)."</p>";
    }
  }
}

echo "<ul>
  <li><a href='/company/trips.php'>Seferler (CRUD)</a></li>
  <li><a href='/company/coupons.php'>Kuponlar</a></li>
  <li><a href='/company/tickets.php'>Biletler</a></li>
</ul>";

page_footer();
