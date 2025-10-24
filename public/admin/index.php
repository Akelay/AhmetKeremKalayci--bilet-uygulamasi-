<?php
declare(strict_types=1);

require __DIR__ . '/../../app/init.php';

if (!hasAnyRole(['admin'])) {
  set_flash('Admin paneline erişim yok.','error'); redirect('/'); exit;
}

// SAYFA
page_header('Admin Paneli');

if ($f = get_flash()) {
  echo "<div class='flash {$f['type']}'>".e($f['msg'])."</div>";
}

echo "<h2>Admin Paneli</h2>";


echo "<ul>
  <li><a href='/admin/process_company_admin.php'>Kullanıcı İşlemleri</a></li>
  <li><a href='/admin/process_company.php'>Firma İşlemleri</a></li>
  <li><a href='/admin/coupons.php'>Kupon İşlemleri</a></li>
</ul>";

page_footer();
