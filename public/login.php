<?php
declare(strict_types=1);
require __DIR__ . '/../app/init.php';

$pdo = DB::conn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    set_flash('E-posta ve şifre zorunlu','error'); redirect('/login.php');
  }

  $stmt = $pdo->prepare("SELECT id, full_name, email, role, password, company_id FROM User WHERE email = :e LIMIT 1");

  $stmt->execute([':e'=>$email]);
  $u = $stmt->fetch();

  if (!$u || !password_verify($password, $u['password'])) {
    set_flash('E-posta veya şifre hatalı','error'); redirect('/login.php');
  }

  session_regenerate_id(true);
  $_SESSION['user'] = [
    'id'=>$u['id'], 'full_name'=>$u['full_name'], 'email'=>$u['email'], 'role'=>$u['role'], 'company_id' => $u['company_id']
  ];
  set_flash('Giriş başarılı!','success');
  redirect('/');
}

page_header('Giriş Yap');
$flash = get_flash();
if ($flash) echo "<p class='flash {$flash['type']}'>".e($flash['msg'])."</p>";
?>
<h2>Giriş Yap</h2>
<form method="post" action="/login.php">
  <label>E-posta</label><br>
  <input name="email" type="email" required><br><br>

  <label>Şifre</label><br>
  <input name="password" type="password" required><br><br>

  <button type="submit">Giriş</button>
</form>
<p><a href="/register.php">Hesabın yok mu? Kayıt ol</a></p>
<?php page_footer(); ?>
