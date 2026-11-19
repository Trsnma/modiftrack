<?php
require_once 'includes/auth.php';

$base = '';
$public = true;
$title = 'Welcome to Modiftrack';
$loggedIn = !empty($_SESSION['user_id']);
$hasImage = file_exists(__DIR__ . '/assets/img/hero.png');

include 'includes/header.php';
?>
<div class="lp">
  <header class="lp-nav">
    <a class="lp-logo" href="#home" aria-label="ModifTrack">MT</a>
    <nav aria-label="Navigasi utama">
      <a href="#home" class="on">Home</a>
      <a href="pages/katalog.php">Shop</a>
      <a href="#tips">Tips</a>
      <a href="#about">About</a>
    </nav>
    <a class="lp-login" href="<?= $loggedIn ? 'pages/dashboard.php' : 'auth/login.php' ?>">
      <?= $loggedIn ? 'Dashboard' : 'Log in' ?>
    </a>
  </header>

  <section id="home" class="lp-top">
    <h1>Welcome to <br>Modiftrack</h1>
    <div class="lp-colors">
      <span>Colors</span>
      <div class="swatches" role="group" aria-label="Pilih warna">
        <button type="button" class="swatch" data-color="#5C3F6F" aria-label="Ungu"></button>
        <button type="button" class="swatch" data-color="#F9D13C" aria-label="Kuning" aria-pressed="true"></button>
        <button type="button" class="swatch" data-color="#FDF6EE" aria-label="Krem"></button>
      </div>
      <small>choose your favorite color</small>
    </div>
  </section>

  <section class="lp-stage">
    <div class="lp-panel">
      <div class="lp-model">
        <h2>Primavera S</h2>
        <p class="sub">Sprint Mode</p>
        <p>An iconic journey of style and freedom, crafted for the modern rider.</p>
      </div>
      <div class="lp-brand">
        <h2>R25</h2>
        <p>means that you are a good person and values.</p>
        <a class="lp-cta" href="<?= $loggedIn ? 'pages/katalog.php' : 'auth/register.php' ?>">Explore Now &rarr;</a>
      </div>
      <div class="lp-social" aria-label="Media sosial">
        <a href="#" aria-label="YouTube">YT</a>
        <a href="#" aria-label="Instagram">IG</a>
        <a href="#" aria-label="TikTok">TT</a>
        <a href="#" aria-label="WhatsApp">WA</a>
      </div>
    </div>

    <?php if ($hasImage): ?>
      <img class="scooter" src="assets/img/hero.png" alt="Vespa Primavera S">
    <?php else: ?>
      <svg class="scooter" viewBox="0 0 520 340" role="img" aria-label="Ilustrasi skuter Vespa">
        <circle cx="110" cy="272" r="56" fill="#1d1522"/>
        <circle cx="110" cy="272" r="28" fill="#5C3F6F" stroke="#FDF6EE" stroke-width="3"/>
        <circle cx="415" cy="272" r="56" fill="#1d1522"/>
        <circle cx="415" cy="272" r="28" fill="#5C3F6F" stroke="#FDF6EE" stroke-width="3"/>
        <path class="bike" stroke="#FDF6EE" stroke-width="3"
          d="M60 252C58 182 118 150 190 150H300C340 112 360 62 352 22L382 14C402 72 392 132 372 172L432 240L404 262L332 216H202C172 252 120 264 60 252Z"/>
        <path d="M92 142Q150 98 238 128L232 152L98 162Z" fill="#1d1522"/>
        <path d="M330 10L352 0L370 12L354 24Z" fill="#FDF6EE"/>
        <circle cx="372" cy="70" r="14" fill="#FDF6EE"/>
      </svg>
    <?php endif; ?>
  </section>
</div>

<section id="tips" class="lp-info">
  <h2>Tips</h2>
  <div class="grid">
    <article class="card"><h3>Cek kompatibilitas</h3><p>Pastikan part sesuai tahun rilis kendaraan sebelum dibeli.</p></article>
    <article class="card"><h3>Hitung jasa bengkel</h3><p>Biaya pasang menambah total, masukkan ke estimasi sejak awal.</p></article>
    <article class="card"><h3>Beli bertahap</h3><p>Tandai status Rencana, Sudah Dibeli, atau Terpasang agar progres jelas.</p></article>
  </div>
</section>

<section id="about" class="lp-info">
  <h2>About</h2>
  <p>ModifTrack membantu pemilik kendaraan merencanakan modifikasi: dari memilih part yang cocok sampai menghitung total biaya.</p>
</section>

<?php include 'includes/footer.php'; ?>