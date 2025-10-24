<?php
declare(strict_types=1);
require __DIR__ . "/../../app/init.php";
require __DIR__ . "/../../app/support/uuid.php";

if (!isLoggedIn() || !in_array(role(), ['admin'], true)) {
    set_flash('Admin paneline erişim yok','error'); redirect('/');
}

$pdo = DB::conn();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Mod durumunu GET ile yönet (JS yok)
$isCompany = (isset($_GET['is_company']) && $_GET['is_company'] === '1');

// Edit modu ve op
$editId = isset($_GET['edit']) ? (string)$_GET['edit'] : '';
$op = $_POST['op'] ?? '';

// Ekleme
if ($op === 'create') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = strtolower(trim($_POST['email'] ?? ''));
    $password  = $_POST['password'] ?? '';
    $id        = uuid();

    // POST'ta hidden ile gelir (firma modundaysa)
    $role = isset($_POST['is_company']) ? 'company' : 'user';
    $company_id = null;

    if ($role === 'company') {
        $company_id = trim($_POST['company_id'] ?? '');
        if ($company_id === '') {
            $company_id = null;
        }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("
        INSERT INTO `User` (id, full_name, email, role, password, company_id)
        VALUES (:id, :full_name, :email, :role, :password, :company_id)
    ");

    try {
        $stmt->execute([
            ':id'         => $id,
            ':full_name'  => $full_name,
            ':email'      => $email,
            ':role'       => $role,
            ':password'   => $hash,
            ':company_id' => $company_id
        ]);
        set_flash('Kayıt başarılı!','success');
    } catch (PDOException $ex) {
        set_flash('Bu e-posta zaten kayıtlı.','error');
    }
}

// Güncelleme
if ($op === 'update') {
    $id         = (string)($_POST['id'] ?? '');
    $afull_name = trim($_POST['full_name'] ?? '');
    $aemail     = strtolower(trim($_POST['email'] ?? ''));
    $apassword  = $_POST['password'] ?? '';

    if ($id !== '') {
        if ($apassword !== '') {
            $ahash = password_hash($apassword, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE `User`
                                   SET full_name = :full_name, email = :email, password = :password
                                   WHERE id = :id");
            try {
                $stmt->execute([
                    ':full_name' => $afull_name,
                    ':email'     => $aemail,
                    ':password'  => $ahash,
                    ':id'        => $id
                ]);
                set_flash('Düzenleme başarılı!','success');
            } catch (PDOException $ex) {
                set_flash('E-posta güncellenemedi (zaten kayıtlı olabilir).','error');
            }
        } else {
            $stmt = $pdo->prepare("UPDATE `User`
                                   SET full_name = :full_name, email = :email
                                   WHERE id = :id");
            try {
                $stmt->execute([
                    ':full_name' => $afull_name,
                    ':email'     => $aemail,
                    ':id'        => $id
                ]);
                set_flash('Düzenleme başarılı!','success');
            } catch (PDOException $ex) {
                set_flash('E-posta güncellenemedi (zaten kayıtlı olabilir).','error');
            }
        }
    }
}

// Silme
if ($op === 'delete') {
    $id = (string)trim($_POST['id'] ?? '');
    if ($id !== '') {
        $stmt = $pdo->prepare("DELETE FROM `User` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        set_flash('Kullanıcı silindi.','success');
    }
}

page_header('Kullanıcı Kayıt Et');
$flash = get_flash();
if ($flash) echo "<p class='flash {$flash['type']}'>".e($flash['msg'])."</p>";

$stmt = $pdo->query("SELECT id, full_name, email, role, created_at FROM `User` ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Kayıt Ol</h2>

<form method="post" action="<?= e($_SERVER['PHP_SELF']) . ($isCompany ? '?is_company=1' : '') ?>">
  <input type="hidden" name="op" value="create">

  <!-- Rol seçimi (JS yok; link ile toggle) -->
  <div style="margin-bottom:12px;">
    <?php if (!$isCompany): ?>
      <a href="<?= e($_SERVER['PHP_SELF']) ?>?is_company=1">Firma admin ekle</a>
    <?php else: ?>
      <strong>Rol: Firma</strong> ·
      <a href="<?= e($_SERVER['PHP_SELF']) ?>">Normal kullanıcı yap</a>
      <input type="hidden" name="is_company" value="1">
    <?php endif; ?>
  </div>

  <?php if ($isCompany): ?>
    <label>Firma ID</label><br>
    <input name="company_id" placeholder="Bus_Company tablosundaki id" value="<?= e($_POST['company_id'] ?? '') ?>"><br><br>
  <?php endif; ?>

  <label>Ad Soyad</label><br>
  <input name="full_name" required><br><br>

  <label>E-posta</label><br>
  <input name="email" type="email" required><br><br>

  <label>Şifre</label><br>
  <input name="password" type="password" required><br><br>

  <button type="submit">Kayıt Ol</button>
</form>

<hr>
<h2>Kullanıcı Listesi</h2>
<table border="1" cellpadding="8" cellspacing="0">
  <thead>
    <tr>
      <th>ID</th>
      <th>Ad Soyad</th>
      <th>E-posta</th>
      <th>Rol</th>
      <th>Oluşturulma</th>
      <th>İşlem</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($users as $user): ?>
      <tr>
        <td><?= e($user['id']) ?></td>
        <td><?= e($user['full_name']) ?></td>
        <td><?= e($user['email']) ?></td>
        <td><?= e($user['role']) ?></td>
        <td><?= e($user['created_at']) ?></td>
        <td>
          <a href="<?= e($_SERVER['PHP_SELF']) ?>?edit=<?= urlencode($user['id']) ?>"
             style="display:inline-block; padding:4px 10px; background:#d9d9d9; color:black; text-decoration:none; border:1px solid #ccc; border-radius:4px;">
             Düzenle
          </a>

          <form method="post" action="<?= e($_SERVER['PHP_SELF']) ?>"
                style="display:inline" onsubmit="return confirm('Silinsin mi?');">
            <input type="hidden" name="op" value="delete">
            <input type="hidden" name="id" value="<?= e($user['id']) ?>">
            <button type="submit">Sil</button>
          </form>
        </td>
      </tr>

      <?php if ($editId && $editId === $user['id']): ?>
        <tr>
          <td colspan="6" style="background:#fafafa;">
            <form method="post" action="<?= e($_SERVER['PHP_SELF']) ?>" style="padding:10px; border:1px solid #ddd;">
              <input type="hidden" name="op" value="update">
              <input type="hidden" name="id" value="<?= e($user['id']) ?>">

              <label>Ad Soyad</label><br>
              <input name="full_name" value="<?= e($user['full_name']) ?>" required><br><br>

              <label>E-posta</label><br>
              <input name="email" type="email" value="<?= e($user['email']) ?>" required><br><br>

              <label>Yeni Şifre </label><br>
              <input name="password" type="password" placeholder="Opsiyonel"><br><br>

              <button type="submit">Kaydet</button>
              <a href="<?= e($_SERVER['PHP_SELF']) ?>">Vazgeç</a>
            </form>
          </td>
        </tr>
      <?php endif; ?>

    <?php endforeach; ?>
  </tbody>
</table>
