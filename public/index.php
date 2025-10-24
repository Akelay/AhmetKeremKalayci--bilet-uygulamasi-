<?php
declare(strict_types=1);
require __DIR__ . '/../app/init.php';

page_header('Ana Sayfa');
if (isLoggedIn()) {
  $u = user();
  echo "<p>Merhaba, <strong>".e($u['full_name'])."</strong> (".e($u['role']).")</p>";
}
$flash = get_flash();
if ($flash) echo "<p class='flash {$flash['type']}'>".e($flash['msg'])."</p>";

echo "<h2>Hoş geldin</h2>";
echo "<p>Sefer aramak için <a href='/trips.php'>buraya tıkla</a>.</p>";

page_footer();
