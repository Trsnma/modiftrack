<?php
require_once '../includes/auth.php';
require_once '../includes/upload.php';
require_once '../includes/motors.php';
require_login();
$uid = $_SESSION['user_id'];

// Kendaraan + jumlah part + total biaya per kendaraan
$cars = rows(
  'SELECT v.id, v.merk, v.model, v.tahun_rilis, COUNT(b.id) AS jml, COALESCE(SUM(p.harga_part+p.estimasi_jasa),0) AS total
   FROM vehicles v
   LEFT JOIN build_plans b ON b.vehicle_id=v.id
   LEFT JOIN parts_catalog p ON p.id=b.part_id
   WHERE v.user_id=? GROUP BY v.id ORDER BY v.id DESC', 'i', [$uid]);
$sumParts = array_sum(array_column($cars, 'jml'));
$sumTotal = array_sum(array_column($cars, 'total'));

// Jumlah part per status (untuk progres)
$byStatus = ['Rencana' => 0, 'Sudah Dibeli' => 0, 'Terpasang' => 0];
foreach (rows('SELECT status_beli, COUNT(*) AS n FROM build_plans WHERE user_id=? GROUP BY status_beli', 'i', [$uid]) as $r) {
    $byStatus[$r['status_beli']] = (int)$r['n'];
}

// 5 part terakhir yang ditambahkan ke build
$recent = rows(
  'SELECT b.id, b.status_beli, p.nama_part, p.harga_part, p.estimasi_jasa, p.foto, v.merk, v.model
   FROM build_plans b
   JOIN parts_catalog p ON p.id=b.part_id
   JOIN vehicles v ON v.id=b.vehicle_id
   WHERE b.user_id=? ORDER BY b.id DESC LIMIT 5', 'i', [$uid]);

// Rekomendasi: part yang cocok dengan kendaraan user dan belum masuk build kendaraan itu
$ideas = rows(
  'SELECT p.id, p.nama_part, p.kategori, p.harga_part, p.estimasi_jasa, p.foto, v.id AS vid, v.model
   FROM parts_catalog p
   JOIN vehicles v ON v.user_id=? AND p.kompatibel_merk=v.merk AND p.kompatibel_model=v.model
                  AND v.tahun_rilis BETWEEN p.tahun_min AND p.tahun_max
   WHERE NOT EXISTS (SELECT 1 FROM build_plans b WHERE b.vehicle_id=v.id AND b.part_id=p.id)
   ORDER BY p.id DESC LIMIT 4', 'i', [$uid]);

$motors   = daftar_motor('../');
$hasImage = file_exists(__DIR__ . '/../assets/img/hero.png');
$title = 'Dashboard'; $active = 'dashboard';
include '../includes/header.php';
?>
<!-- Sambutan -->
<section class="dash-hero">
  <div class="dash-hello">
    <h1>Halo, <?= e($_SESSION['username']) ?>.</h1>
    <p>Ringkasan garasi dan estimasi biaya modifikasimu.</p>
    <div class="dash-cta">
      <a class="btn" href="katalog.php">Cari part</a>
      <a class="btn btn-ghost" href="kendaraan.php">Tambah kendaraan</a>
    </div>
  </div>

  <div class="dash-visual">
    <?php if ($motors): ?>
      <!-- data-rel diisi PHP: 0 = di depan, 1 = berikutnya, 2 = tersembunyi (app.js yang menggerakkan) -->
      <div class="fleet" aria-label="Galeri motor">
        <?php foreach ($motors as $i => $m): ?>
          <img class="fleet-item" data-rel="<?= min($i, 2) ?>" src="<?= e($m['src']) ?>" alt="<?= e($m['name']) ?>">
        <?php endforeach; ?>
      </div>
      <p class="fleet-name" aria-live="polite"><?= e($motors[0]['name']) ?></p>
    <?php elseif ($hasImage): ?>
      <img class="dash-single" src="../assets/img/hero.png" alt="Motor modifikasi">
    <?php else: ?>
      <p class="dash-quote">Stay calm &amp; build classy.</p>
    <?php endif; ?>
  </div>
</section>

<!-- Statistik -->
<section class="dash-stats" aria-label="Ringkasan">
  <div class="dash-stat"><b><?= count($cars) ?></b><span>Kendaraan di garasi</span></div>
  <div class="dash-stat"><b><?= (int)$sumParts ?></b><span>Part dalam build</span></div>
  <div class="dash-stat"><b><?= (int)$byStatus['Terpasang'] ?></b><span>Part sudah terpasang</span></div>
  <div class="dash-stat"><b><?= rupiah($sumTotal) ?></b><span>Total estimasi (part + jasa)</span></div>
</section>

<!-- Progres build -->
<section class="card dash-progress">
  <div class="dash-progress-head">
    <h3>Progres build</h3>
    <span class="muted"><?= (int)$byStatus['Terpasang'] ?> dari <?= (int)$sumParts ?> part terpasang</span>
  </div>
  <progress value="<?= (int)$byStatus['Terpasang'] ?>" max="<?= max(1, (int)$sumParts) ?>"></progress>
  <div class="dash-legend">
    <?php foreach ($byStatus as $label => $n): ?>
      <span class="tag"><?= e($label) ?>: <?= $n ?></span>
    <?php endforeach; ?>
  </div>
</section>

<!-- Garasi & build terbaru -->
<div class="dash-cols">
  <section>
    <h2>Garasi</h2>
    <?php if (!$cars): ?>
      <div class="card">Garasi masih kosong. <a href="kendaraan.php">Tambah kendaraan pertama</a>.</div>
    <?php else: ?>
    <div class="grid">
      <?php foreach ($cars as $c): ?>
      <article class="card">
        <h3><?= e($c['merk'] . ' ' . $c['model']) ?></h3>
        <p class="muted">Tahun <?= (int)$c['tahun_rilis'] ?> · <?= (int)$c['jml'] ?> part</p>
        <p><span class="tag"><?= rupiah($c['total']) ?></span></p>
        <a class="btn btn-ghost btn-sm" href="katalog.php?v=<?= (int)$c['id'] ?>">Lihat part cocok</a>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <section>
    <h2>Build terbaru</h2>
    <div class="card">
      <?php if (!$recent): ?>
        <p>Belum ada part di build plan. <a href="katalog.php">Pilih dari katalog</a>.</p>
      <?php else: ?>
      <ul class="recent">
        <?php foreach ($recent as $r): $img = foto_url($r['foto']); ?>
        <li>
          <?php if ($img): ?><img class="thumb-sm" src="<?= e($img) ?>" alt="" loading="lazy">
          <?php else: ?><div class="thumb-sm thumb-empty"></div><?php endif; ?>
          <div class="recent-info">
            <span class="recent-name"><?= e($r['nama_part']) ?></span>
            <span class="muted"><?= e($r['merk'] . ' ' . $r['model']) ?> · <?= e($r['status_beli']) ?></span>
          </div>
          <span class="recent-price"><?= rupiah($r['harga_part'] + $r['estimasi_jasa']) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <p><a class="btn btn-ghost btn-sm" href="build_plan.php">Buka build plan</a></p>
      <?php endif; ?>
    </div>
  </section>
</div>

<!-- Rekomendasi part -->
<?php if ($ideas): ?>
<section class="dash-ideas">
  <h2>Rekomendasi untuk garasimu</h2>
  <div class="grid">
    <?php foreach ($ideas as $p): $img = foto_url($p['foto']); ?>
    <article class="card">
      <?php if ($img): ?>
        <img class="thumb" src="<?= e($img) ?>" alt="<?= e($p['nama_part']) ?>" loading="lazy">
      <?php else: ?>
        <div class="thumb thumb-empty">Tanpa foto</div>
      <?php endif; ?>
      <span class="tag"><?= e($p['kategori']) ?></span>
      <h3><?= e($p['nama_part']) ?></h3>
      <p class="muted">Cocok untuk <?= e($p['model']) ?> · <?= rupiah($p['harga_part'] + $p['estimasi_jasa']) ?></p>
      <a class="btn btn-gold btn-sm" href="katalog.php?v=<?= (int)$p['vid'] ?>">Lihat di katalog</a>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>