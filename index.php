<?php
require_once 'includes/auth.php';
$base = ''; $public = true; $title = 'Katalog & Estimasi Modifikasi';
include 'includes/header.php';
$img = file_exists(__DIR__ . '/assets/img/hero.png');
?>
<section class="hero">
  <h1>Racik modifikasi,<br>hitung biayanya.</h1>
  <p>Simpan kendaraan di garasi, pilih part yang cocok dengan tahun dan modelnya, lalu lihat total biaya part dan jasa bengkel dalam satu tempat.</p>
  <?php if (!empty($_SESSION['user_id'])): ?>
    <a class="btn" href="pages/dashboard.php">Buka dashboard</a>
  <?php else: ?>
    <a class="btn" href="auth/register.php">Buat akun</a> <a class="btn btn-ghost" href="auth/login.php">Masuk</a>
  <?php endif; ?>
  <div class="dots" aria-hidden="true"><i></i><i></i><i></i></div>
</section>
<section class="panel">
  <h2>Cocok untuk kendaraanmu</h2>
  <p>Katalog menyaring part otomatis berdasarkan merk, model, dan tahun rilis kendaraan di garasimu.</p>
  <?php if ($img): ?><img class="hero-img" src="assets/img/hero.png" alt="Kendaraan modifikasi"><?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
