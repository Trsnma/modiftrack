<?php
require_once 'includes/auth.php';

$base = '';
$public = true;
$title = 'Stay Calm & Always Classy';
$loggedIn = !empty($_SESSION['user_id']);
$hasImage = file_exists(__DIR__ . '/assets/img/hero.png');

// Galeri About: semua PNG/WebP di assets/img/motors/ (nama file = nama motor)
$motors = [];
foreach (glob(__DIR__ . '/assets/img/motors/*.{png,webp,PNG,WEBP}', GLOB_BRACE) ?: [] as $file) {
    $motors[] = [
        'src'  => 'assets/img/motors/' . rawurlencode(basename($file)),
        'name' => ucwords(str_replace(['-', '_'], ' ', pathinfo($file, PATHINFO_FILENAME))),
    ];
}

// Statistik singkat di bawah hero (ubah teksnya sesuai kebutuhan)
$stats = [
    ['100+', 'Part terverifikasi'],
    ['99%',  'Estimasi akurat'],
    ['∞',    'Manajemen garasi tanpa batas'],
];

// Showcase komunitas: contoh konten, ganti dengan data asli
$projects = [
    ['nama' => 'Rizky', 'motor' => 'Honda Vario 150', 'total' => 4600000,
     'kutipan' => 'Estimasinya pas, tidak ada biaya kejutan saat pasang di bengkel.'],
    ['nama' => 'Dewi',  'motor' => 'Yamaha NMAX',     'total' => 2885000,
     'kutipan' => 'Struk estimasinya jelas, jadi gampang menunjukkan rencana ke bengkel.'],
    ['nama' => 'Bagas', 'motor' => 'Vespa Primavera', 'total' => 3250000,
     'kutipan' => 'Bisa beli part bertahap tanpa lupa mana yang sudah terpasang.'],
];

include 'includes/header.php';
?>
<header class="lp-nav site-nav">
  <a class="lp-logo" href="#home" aria-label="ModifTrack">MT</a>
  <nav aria-label="Navigasi utama">
    <a href="#home">Home</a>
    <a href="#about">About</a>
    <a href="#tips">Tips</a>
    <a href="#showcase">Showcase</a>
  </nav>
  <a class="lp-login" href="<?= $loggedIn ? 'pages/dashboard.php' : 'auth/login.php' ?>">
    <?= $loggedIn ? 'Dashboard' : 'Log in' ?>
  </a>
</header>

<div class="land">

  <!-- HOME -->
  <section id="home" class="sec land-hero">
    <div class="lp-top">
      <h1>Welcome to<br>Modiftrack</h1>
      <div class="lp-colors">
        <span>Colors</span>
        <div class="swatches" role="group" aria-label="Pilih warna">
          <button type="button" class="swatch" data-color="#5C3F6F" aria-label="Ungu"></button>
          <button type="button" class="swatch" data-color="#F9D13C" aria-label="Kuning" aria-pressed="true"></button>
          <button type="button" class="swatch" data-color="#FDF6EE" aria-label="Krem"></button>
        </div>
        <small>choose your favorite color</small>
      </div>
    </div>

    <div class="lp-stage">
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
        <img class="scooter" src="assets/img/hero.png" alt="Motor modifikasi">
      <?php endif; ?>
    </div>

    <div class="land-stats">
      <?php foreach ($stats as [$angka, $label]): ?>
        <div class="land-stat"><b><?= e($angka) ?></b><span><?= e($label) ?></span></div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ABOUT -->
  <section id="about" class="sec sec--cream">
    <h2>About</h2>
    <?php if ($motors): ?>
      <!-- data-rel diisi PHP: 0 = di depan, 1 = berikutnya, 2 = tersembunyi (JS yang menggerakkan) -->
      <div class="fleet" aria-label="Galeri motor">
        <?php foreach ($motors as $i => $m): ?>
          <img class="fleet-item" data-rel="<?= min($i, 2) ?>" src="<?= e($m['src']) ?>" alt="<?= e($m['name']) ?>">
        <?php endforeach; ?>
      </div>
      <p class="fleet-name" aria-live="polite"><?= e($motors[0]['name']) ?></p>
    <?php else: ?>
      <p class="muted">Galeri motor belum tersedia. Taruh foto PNG/WebP transparan di folder assets/img/motors/.</p>
    <?php endif; ?>
    <p class="about-text">
      ModifTrack membantu pemilik kendaraan merencanakan modifikasi: dari memilih part yang cocok sampai menghitung total biaya.
      Simpan kendaraan di garasi, saring katalog sesuai merk, model, dan tahun, lalu lihat rincian biaya part dan jasa pada struk estimasi.
    </p>
  </section>

  <!-- TIPS -->
  <section id="tips" class="sec">
    <h2>Tips</h2>
    <div class="tip-grid">
      <article class="tip">
        <div class="tip-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
        </div>
        <h3>Cek kompatibilitas</h3>
        <p>Pastikan part sesuai tahun rilis kendaraan sebelum dibeli.</p>
      </article>
      <article class="tip">
        <div class="tip-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><rect x="8" y="5.5" width="8" height="4" rx="1"/><path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/></svg>
        </div>
        <h3>Hitung jasa bengkel</h3>
        <p>Biaya pasang menambah total, masukkan ke estimasi sejak awal.</p>
      </article>
      <article class="tip">
        <div class="tip-ico" aria-hidden="true">
          <svg viewBox="0 0 24 24"><path d="M9 6h11M9 12h11M9 18h11M3.5 6l1.5 1.5 2.5-3M3.5 12l1.5 1.5 2.5-3M3.5 18l1.5 1.5 2.5-3"/></svg>
        </div>
        <h3>Beli bertahap</h3>
        <p>Tandai status Rencana, Sudah Dibeli, atau Terpasang agar progres jelas.</p>
      </article>
    </div>
  </section>

  <!-- SHOWCASE -->
  <section id="showcase" class="sec sec--cream">
    <h2>Showcase komunitas</h2>
    <div class="tip-grid">
      <?php foreach ($projects as $p): ?>
      <article class="proj">
        <div class="proj-head">
          <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($p['nama'], 0, 1))) ?></span>
          <div>
            <h3><?= e($p['nama']) ?></h3>
            <span class="proj-bike"><?= e($p['motor']) ?></span>
          </div>
        </div>
        <blockquote><?= e($p['kutipan']) ?></blockquote>
        <p class="proj-total"><span>Total build</span><b><?= rupiah($p['total']) ?></b></p>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="land-foot">
    <div class="foot-grid">
      <div>
        <p class="foot-brand">ModifTrack</p>
        <p>Katalog dan estimasi biaya modifikasi kendaraan.</p>
      </div>
      <nav aria-label="Quick links">
        <h3>Quick links</h3>
        <ul>
          <li><a href="#home">Home</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#tips">Tips</a></li>
          <li><a href="#showcase">Showcase</a></li>
        </ul>
      </nav>
      <nav aria-label="Aplikasi">
        <h3>Aplikasi</h3>
        <ul>
          <li><a href="pages/katalog.php">Katalog part</a></li>
          <?php if ($loggedIn): ?>
            <li><a href="pages/dashboard.php">Dashboard</a></li>
          <?php else: ?>
            <li><a href="auth/login.php">Masuk</a></li>
            <li><a href="auth/register.php">Daftar</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
    <p class="foot-copy">Copyright &copy; <?= date('Y') ?> ModifTrack. All rights reserved.</p>
  </footer>

</div>
<?php include 'includes/footer.php'; ?>