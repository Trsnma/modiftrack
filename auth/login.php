<?php
require_once '../includes/auth.php';
if (!empty($_SESSION['user_id'])) redirect('../pages/dashboard.php');

const MAX_TRY = 5; const LOCK_SEC = 60;
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // Pembatasan percobaan login (dasar anti brute-force)
    if (($_SESSION['tries'] ?? 0) >= MAX_TRY && time() - ($_SESSION['try_at'] ?? 0) < LOCK_SEC) {
        $err = 'Terlalu banyak percobaan. Coba lagi dalam 1 menit.';
    } else {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        $user = row('SELECT id,username,password,role FROM users WHERE username=?', 's', [$u]);
        // Hash palsu agar waktu respons sama walau username tidak ada
        $hash = $user['password'] ?? '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234';
        if (password_verify($p, $hash) && $user) {
            session_regenerate_id(true);                 // cegah session fixation
            $_SESSION = [
                'user_id' => (int)$user['id'], 'username' => $user['username'], 'role' => $user['role'],
                'fp' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''), 'last' => time(),
            ];
            redirect('../pages/dashboard.php');
        }
        $_SESSION['tries'] = ($_SESSION['tries'] ?? 0) + 1;
        $_SESSION['try_at'] = time();
        $err = 'Username atau password salah.';
    }
}

$base = '../'; $public = true; $title = 'Masuk';
$hasImage = file_exists(__DIR__ . '/../assets/img/hero.png');
include '../includes/header.php';
?>
<div class="auth">
  <section class="auth-side">
    <p class="auth-brand">ModifTrack</p>
    <h1>Stay calm &amp; build classy.</h1>
    <?php if ($hasImage): ?>
      <img class="auth-bike" src="../assets/img/hero.png" alt="Motor modifikasi">
    <?php endif; ?>
    <p>Rencanakan modifikasi tanpa kejutan biaya.</p>
    <div class="dots" aria-hidden="true"><i></i><i></i><i></i></div>
  </section>
  <section class="auth-form">
    <form method="post" autocomplete="off">
      <h2>Masuk</h2>
      <?php if ($err): ?><div class="flash err" role="alert"><?= e($err) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <input id="username" name="username" required maxlength="50">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" required>
      <p><button class="btn btn-gold" type="submit">Masuk</button></p>
      <p class="muted">Belum punya akun? <a href="register.php">Daftar</a></p>
    </form>
  </section>
</div>
<?php include '../includes/footer.php'; ?>