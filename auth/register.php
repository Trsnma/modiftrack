<?php
require_once '../includes/auth.php';
if (!empty($_SESSION['user_id'])) redirect('../pages/dashboard.php');

$err = ''; $username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $conf = $_POST['confirm'] ?? '';

    if (!preg_match('/^[A-Za-z0-9_]{4,30}$/', $username)) {
        $err = 'Username 4-30 karakter: huruf, angka, atau garis bawah.';
    } elseif (strlen($pass) < 8 || !preg_match('/[A-Za-z]/', $pass) || !preg_match('/\d/', $pass)) {
        $err = 'Password minimal 8 karakter dan mengandung huruf serta angka.';
    } elseif ($pass !== $conf) {
        $err = 'Konfirmasi password tidak sama.';
    } elseif (row('SELECT id FROM users WHERE username=?', 's', [$username])) {
        $err = 'Username sudah dipakai.';
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        // Role selalu 'user': admin tidak bisa dibuat lewat form publik
        q("INSERT INTO users (username,password,role) VALUES (?,?,'user')", 'ss', [$username, $hash]);
        flash('ok', 'Akun dibuat. Silakan masuk.');
        redirect('login.php');
    }
}
$base = '../'; $public = true; $title = 'Daftar';
$hasImage = file_exists(__DIR__ . '/../assets/img/hero.png');
include '../includes/header.php';
?>
<div class="auth">
  <section class="auth-side">
    <p class="auth-brand">ModifTrack</p>
    <h1>Mulai garasimu.</h1>
    <?php if ($hasImage): ?>
      <img class="auth-bike" src="../assets/img/hero.png" alt="Motor modifikasi">
    <?php endif; ?>
    <p>Daftar gratis untuk menyimpan kendaraan dan rencana modifikasi.</p>
    <div class="dots" aria-hidden="true"><i></i><i></i><i></i></div>
  </section>
  <section class="auth-form">
    <form method="post" autocomplete="off">
      <h2>Buat akun</h2>
      <?php if ($err): ?><div class="flash err" role="alert"><?= e($err) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <input id="username" name="username" value="<?= e($username) ?>" required maxlength="30">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" required minlength="8">
      <label for="confirm">Ulangi password</label>
      <input id="confirm" type="password" name="confirm" required minlength="8">
      <p><button class="btn btn-gold" type="submit">Daftar</button></p>
      <p class="muted">Sudah punya akun? <a href="login.php">Masuk</a></p>
    </form>
  </section>
</div>
<?php include '../includes/footer.php'; ?>
