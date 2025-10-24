<?php
declare(strict_types=1);
session_start();

require __DIR__ . '/Models/DB.php'; // PDO bağlantısı

// basit yardımcılar
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): never { header("Location: $path"); exit; }
function set_flash(string $msg, string $type='info'): void { $_SESSION['flash'] = ['msg'=>$msg,'type'=>$type]; }
function get_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function isLoggedIn(): bool { return isset($_SESSION['user']); }
function user($key = null) {
    if (!isset($_SESSION['user'])) {
        return null;
    }
    if ($key === null) {
        return $_SESSION['user'];
    }
    return $_SESSION['user'][$key] ?? null;
}

function role(): string { return isset($_SESSION['user']['role']) ? strtolower($_SESSION['user']['role']) : 'guest'; }
function money_tl(int $krs): string { return number_format($krs/100, 2, ',', '.') . ' ₺'; }
function dtfmt(string $s): string { return date('d.m.Y H:i', strtotime($s)); }
function hasAnyRole(array $roles): bool {
  if (!isLoggedIn()) return false;
  return in_array(role(), array_map('strtolower', $roles), true);
}

function page_header(string $title): void {
  $r = role(); // 'guest' | 'user' | 'company' | 'admin'

  echo "<!doctype html><meta charset='utf-8'><title>".e($title)."</title>
<style>
body{font-family:system-ui,Segoe UI,Arial;margin:24px}
input,button,select{padding:6px;font-size:15px}
.card{border:1px solid #ddd;padding:10px;border-radius:6px;margin:8px 0}
a{color:#0a58ca;text-decoration:none} a:hover{text-decoration:underline}
.flash{padding:8px;border-radius:6px;margin:8px 0}
.flash.success{background:#e7f7e7} .flash.error{background:#fde2e2} .flash.info{background:#eef}
</style>
<h1>Bilet Platformu</h1>
<nav>";


  $links = [
    ['/', 'Ana Sayfa'],
    ['/trips.php', 'Seferler'],
  ];

  if ($r === 'company') {
    $links[] = ['/company/index.php', 'Firma Paneli']; 
  } elseif ($r === 'admin') {
    $links[] = ['/admin/index.php', 'Admin Paneli'];   
  }



  if (isLoggedIn()) {
    $links[] = ['/my_ticket.php', 'Biletlerim'];
    $links[] = ['/logout.php', 'Çıkış'];
  } else {
    $links[] = ['/login.php', 'Giriş'];
    $links[] = ['/register.php', 'Kayıt'];
  }


  echo implode(' | ', array_map(fn($l) => "<a href='".e($l[0])."'>".e($l[1])."</a>", $links));
  echo "</nav><hr>";
}
function page_footer(): void { echo "<hr><small>Ahmet Kerem Kalaycı</small>"; }
