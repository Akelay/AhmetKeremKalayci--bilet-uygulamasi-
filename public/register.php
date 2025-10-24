<?php
declare(strict_types=1);
require __DIR__ . '/../app/init.php';
require __DIR__ . '/../app/Support/uuid.php';

$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name'] ?? '');
  $email     = strtolower(trim($_POST['email'] ?? ''));
  $password  = $_POST['password'] ?? '';

  if ($full_name==='' || $email==='' || $password==='') {
    set_flash('Lütfen tüm alanları doldurun','error'); redirect('/register.php');
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('E-posta formatı hatalı','error'); redirect('/register.php');
  }
  if (strlen($password) < 6) {
    set_flash('Şifre en az 6 karakter olmalı','error'); redirect('/register.php');
  }

  $id   = uuid();
  $hash = password_hash($password, PASSWORD_BCRYPT);
  $role = 'user';

  $stmt = $pdo->prepare(
    "INSERT INTO `User` (id, full_name, email, role, password)
     VALUES (:id, :full_name, :email, :role, :password)"
  );

  try {
    $stmt->execute([
      ':id'        => $id,
      ':full_name' => $full_name,
      ':email'     => $email,
      ':role'      => $role,      // <-- eklendi
      ':password'  => $hash,
    ]);
  } catch (PDOException $ex) {
    // unique ihlali vs.
    set_flash('Bu e-posta zaten kayıtlı.','error'); redirect('/register.php');
  }

  set_flash('Kayıt başarılı! Giriş yapabilirsiniz.','success');
  redirect('/login.php');
}

page_header('Kayıt Ol');
$flash = get_flash();
if ($flash) echo "<p class='flash {$flash['type']}'>".e($flash['msg'])."</p>";
?>
<h2>Kayıt Ol</h2>
<form method="post" action="/register.php">
  <label>Ad Soyad</label><br>
  <input name="full_name" required><br><br>

  <label>E-posta</label><br>
  <input name="email" type="email" required><br><br>

  <label>Şifre</label><br>
  <input name="password" type="password" required><br><br>

  <button type="submit">Kayıt Ol</button>
</form>
<p><a href="/login.php">Giriş yap</a></p>
<?php page_footer(); ?>
