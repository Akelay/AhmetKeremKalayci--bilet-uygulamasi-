<?php
require __DIR__ . '/../app/Models/DB.php';
require __DIR__ . '/../app/Support/uuid.php';


$email = $argv[1] ?? null;
$pass  = $argv[2] ?? null;
if (!$email || !$pass) {
    fwrite(STDERR, "Kullanım: php scripts/make_admin.php admin@example.com SIFRE\n");
    exit(1);
}

$pdo = DB::conn();
$pdo->prepare("INSERT INTO User (id, full_name, email, role, password, company_id, balance, created_at)
VALUES (:id, :fn, :em, 'admin', :ph, NULL, 0, :now)")
->execute([
    ':id'=>uuid(),
    ':fn'=>'Platform Admin',
    ':em'=>strtolower($email),
    ':ph'=>password_hash($pass, PASSWORD_ARGON2ID),
    ':now'=>date('Y-m-d H:i:s'),
]);

echo "Admin oluşturuldu: $email\n";
