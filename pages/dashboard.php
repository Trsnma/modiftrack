<?php
require_once '../includes/auth.php';
require_login();
$uid = $_SESSION['user_id'];

$cars = rows(
  'SELECT v.id, v.merk, v.model, v.tahun_rilis, COUNT(b.id) AS jml, COALESCE(SUM(p.harga_part+p.estimasi_jasa),0) AS total
   FROM vehicles v
   LEFT JOIN build_plans b ON b.vehicle_id=v.id
   LEFT JOIN parts_catalog p ON p.id=b.part_id
   WHERE v.user_id=? GROUP BY v.id ORDER BY v.id DESC', 'i', [$uid]);
$sumParts = array_sum(array_column($cars, 'jml'));
$sumTotal = array_sum(array_column($cars, 'total'));

$title = 'Dashboard'; $active = 'dashboard';
include '../includes/header.php';
?>
<section class="hero">
  <h1>Halo, <?= e($_SESSION['username']) ?>.</h1>
  <p>Ringkasan garasi dan estimasi biaya modifikasimu.</p>
  <a class="btn" href="katalog.php">Cari part</a>
</section>
<section class="panel stats">
  <div class="stat"><b><?= count($cars) ?></b>Kendaraan di garasi</div>
  <div class="stat"><b><?= (int)$sumParts ?></b>Part dalam build</div>
  <div class="stat"><b><?= rupiah($sumTotal) ?></b>Total estimasi (part + jasa)</div>
</section>
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
<?php include '../includes/footer.php'; ?>
