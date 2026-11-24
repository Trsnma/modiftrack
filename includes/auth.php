<?php
/** Session, keamanan, dan helper umum. Di-include di semua halaman. */
require_once __DIR__ . '/../config/koneksi.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:; script-src 'self'; form-action 'self'; frame-ancestors 'none'");

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}

const SESSION_TIMEOUT = 1800; // 30 menit tidak aktif

/** Escape output (anti-XSS). */
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n): string { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Token keamanan tidak valid. Muat ulang halaman.');
    }
}

function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function redirect(string $url): void { header('Location: ' . $url); exit; }

/** Wajib login: cek user_id, fingerprint browser, dan timeout. */
function require_login(): void {
    $fp = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $valid = !empty($_SESSION['user_id'])
        && hash_equals($_SESSION['fp'] ?? '', $fp)
        && (time() - ($_SESSION['last'] ?? 0)) < SESSION_TIMEOUT;
    if (!$valid) {
        $_SESSION = [];
        session_destroy();
        redirect('../auth/login.php');
    }
    $_SESSION['last'] = time();
}
function require_admin(): void {
    require_login();
    if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('Akses ditolak: khusus admin.'); }
}
