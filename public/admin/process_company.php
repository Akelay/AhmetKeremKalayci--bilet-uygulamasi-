<?php
declare(strict_types=1);

require_once __DIR__ . "/../../app/init.php";
require_once __DIR__ . "/../../app/Support/uuid.php";

if (!isLoggedIn() || !in_array(role(), ['admin'], true)) {
    set_flash('Admin paneline erişim yok','error'); redirect('/');
}

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$op     = $_POST['op'] ?? '';
$editId = isset($_GET['edit']) ? (string)$_GET['edit'] : '';

// Ekleme
if ($op === 'create') {
    $name      = trim((string)($_POST['name'] ?? ''));
    $logo_path = trim((string)($_POST['logo_path'] ?? ''));
    if ($name !== '') {
        $id   = uuid();
        $stmt = $pdo->prepare("INSERT INTO `Bus_Company` (id, name, logo_path) VALUES (:id, :name, :logo_path)");
        try {
            $stmt->execute([
                ':id'        => $id,
                ':name'      => $name,
                ':logo_path' => ($logo_path !== '' ? $logo_path : null),
            ]);
            set_flash('Firma Eklendi.','success');
        } catch (PDOException $e) {
            set_flash('Firma adı zaten kayıtlı.','error');
        }
    } else {
        set_flash('Firma adı zorunludur.','error');
    }
}

// Güncelleme
if ($op === 'update') {
    $id        = (string)($_POST['id'] ?? '');
    $name      = trim((string)($_POST['name'] ?? ''));
    $logo_path = trim((string)($_POST['logo_path'] ?? ''));
    if ($id !== '' && $name !== '') {
        $stmt = $pdo->prepare("UPDATE `Bus_Company` SET name = :name, logo_path = :logo_path WHERE id = :id");
        try {
            $stmt->execute([
                ':id'        => $id,
                ':name'      => $name,
                ':logo_path' => ($logo_path !== '' ? $logo_path : null),
            ]);
            set_flash('Firma güncellendi.','success');
        } catch (PDOException $e) {
            set_flash('Firma güncelleme işlemi başarısız.','error');
        }
    } else {
        set_flash('Geçersiz veri.','error');
    }
}

// Silme
if ($op === 'delete') {
    $id = (string)($_POST['id'] ?? '');
    if ($id !== '') {
        $stmt = $pdo->prepare("DELETE FROM `Bus_Company` WHERE id = :id");
        try {
            $stmt->execute([':id' => $id]);
            set_flash('Firma Silindi.','success');
        } catch (PDOException $e) {
            set_flash('Silme işlemi Başarısız.','error');
        }
    } else {
        set_flash('Hatalı işlem.','error');
    }
}

page_header('Firmalar');
$flash = get_flash();
if ($flash) echo "<p class='flash {$flash['type']}'>".e($flash['msg'])."</p>";

// Listeleme
$stmt = $pdo->query("SELECT id, name, logo_path, created_at FROM `Bus_Company` ORDER BY created_at DESC");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Firma Oluştur</h2>
<form method="post" action="">
  <input type="hidden" name="op" value="create">

  <label>Firma Adı</label><br>
  <input name="name" required><br><br>

  <label>Logo Yolu (opsiyonel)</label><br>
  <input name="logo_path" placeholder="/uploads/logo.png"><br><br>

  <button type="submit">Kaydet</button>
</form>

<hr>

<h2>Firma Listesi</h2>
<table border="1" cellpadding="8" cellspacing="0">
  <thead>
    <tr>
      <th>ID</th>
      <th>Ad</th>
      <th>Logo</th>
      <th>Oluşturulma</th>
      <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($companies as $c): ?>
    <tr>
      <td><?= e($c['id']) ?></td>
      <td><?= e($c['name']) ?></td>
      <td><?= e($c['logo_path'] ?? '') ?></td>
      <td><?= e($c['created_at']) ?></td>
      <td>
        <a href="/admin/process_company.php?edit=<?= urlencode($c['id']) ?>"
           style="display:inline-block; padding:4px 10px; background:#d9d9d9; color:black; text-decoration:none; border:1px solid #ccc; border-radius:4px;">
           Düzenle
        </a>
        <form method="post" action="" style="display:inline" onsubmit="return confirm('Silinsin mi?');">
          <input type="hidden" name="op" value="delete">
          <input type="hidden" name="id" value="<?= e($c['id']) ?>">
          <button type="submit">Sil</button>
        </form>
      </td>
    </tr>

    <?php if ($editId && $editId === $c['id']): ?>
      <tr>
        <td colspan="5" style="background:#fafafa;">
          <form method="post" action="" style="padding:10px; border:1px solid #ddd;">
            <input type="hidden" name="op" value="update">
            <input type="hidden" name="id" value="<?= e($c['id']) ?>">

            <label>Firma Adı</label><br>
            <input name="name" value="<?= e($c['name']) ?>" required><br><br>

            <label>Logo Yolu (opsiyonel)</label><br>
            <input name="logo_path" value="<?= e($c['logo_path'] ?? '') ?>"><br><br>

            <button type="submit">Kaydet</button>
            <a href="/admin/process_company.php">Vazgeç</a>
          </form>
        </td>
      </tr>
    <?php endif; ?>

  <?php endforeach; ?>
  </tbody>
</table>
