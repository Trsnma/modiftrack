<?php
require_once '../includes/auth.php';
require_once '../includes/upload.php';
require_login();
$uid = $_SESSION['user_id'];
$kats = ['Eksterior', 'Interior', 'Kaki-kaki', 'Performa'];
$cars = rows('SELECT * FROM vehicles WHERE user_id=? ORDER BY id DESC', 'i', [$uid]);

// Tambah ke build: validasi kepemilikan kendaraan + kompatibilitas di sisi server
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $vid = (int)($_POST['vehicle_id'] ?? 0); $pid = (int)($_POST['part_id'] ?? 0);
    $ok = row('SELECT p.id FROM parts_catalog p JOIN vehicles v ON v.id=? AND v.user_id=?
               WHERE p.id=? AND p.kompatibel_merk=v.merk AND p.kompatibel_model=v.model
               AND v.tahun_rilis BETWEEN p.tahun_min AND p.tahun_max', 'iii', [$vid, $uid, $pid]);
    if ($ok) {
        q('INSERT INTO build_plans (user_id,vehicle_id,part_id) VALUES (?,?,?)', 'iii', [$uid, $vid, $pid]);
        flash('ok', 'Part ditambahkan ke build plan.');
    } else {
        flash('err', 'Part tidak cocok dengan kendaraan yang dipilih.');
    }
    redirect('katalog.php?v=' . $vid);
}

// Filter: kendaraan terpilih (harus milik user) + kategori
$vid = (int)($_GET['v'] ?? 0);
$sel = null;
foreach ($cars as $c) if ((int)$c['id'] === $vid) $sel = $c;
if (!$sel && $cars && !isset($_GET['v'])) $sel = $cars[0];   // default: kendaraan terbaru
$kat = in_array($_GET['kat'] ?? '', $kats, true) ? $_GET['kat'] : '';

$where = []; $types = ''; $par = [];
if ($sel) {
    $where[] = 'kompatibel_merk=? AND kompatibel_model=? AND ? BETWEEN tahun_min AND tahun_max';
    $types .= 'ssi'; array_push($par, $sel['merk'], $sel['model'], (int)$sel['tahun_rilis']);
}
if ($kat !== '') { $where[] = 'kategori=?'; $types .= 's'; $par[] = $kat; }
$parts = rows('SELECT * FROM parts_catalog' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY kategori, nama_part', $types, $par);

$title = 'Katalog'; $active = 'katalog';
include '../includes/header.php';
?>
<h1>Katalog part</h1>
<form method="get" class="card">
  <div class="form-row">
    <div>
      <label for="v">Kendaraan</label>
      <select id="v" name="v">
        <option value="0" <?= !$sel ? 'selected' : '' ?>>Semua part (tanpa filter)</option>
        <?php foreach ($cars as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= $sel && $sel['id'] == $c['id'] ? 'selected' : '' ?>>
            <?= e($c['merk'] . ' ' . $c['model'] . ' (' . $c['tahun_rilis'] . ')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="kat">Kategori</label>
      <select id="kat" name="kat">
        <option value="">Semua</option>
        <?php foreach ($kats as $k): ?><option <?= $kat === $k ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div><button class="btn" type="submit">Terapkan</button></div>
  </div>
  <?php if (!$cars): ?><p class="muted">Tambahkan kendaraan di <a href="kendaraan.php">garasi</a> agar part bisa disaring dan ditambahkan ke build.</p><?php endif; ?>
</form>
<?php if (!$parts): ?>
  <div class="card">Belum ada part yang cocok<?= $sel ? ' untuk ' . e($sel['merk'] . ' ' . $sel['model']) : '' ?>.</div>
<?php else: ?>
<div class="grid">
  <?php foreach ($parts as $p): $img = foto_url($p['foto']); ?>
  <article class="card">
    <?php if ($img): ?>
      <img class="thumb" src="<?= e($img) ?>" alt="<?= e($p['nama_part']) ?>" loading="lazy">
    <?php else: ?>
      <div class="thumb thumb-empty">Tanpa foto</div>
    <?php endif; ?>
    <span class="tag"><?= e($p['kategori']) ?></span>
    <h3><?= e($p['nama_part']) ?></h3>
    <p class="muted"><?= e($p['kompatibel_merk'] . ' ' . $p['kompatibel_model']) ?> · <?= (int)$p['tahun_min'] ?>-<?= (int)$p['tahun_max'] ?></p>
    <p>Part <b><?= rupiah($p['harga_part']) ?></b><br>Jasa <b><?= rupiah($p['estimasi_jasa']) ?></b></p>
    <?php if ($sel): ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="vehicle_id" value="<?= (int)$sel['id'] ?>">
      <input type="hidden" name="part_id" value="<?= (int)$p['id'] ?>">
      <button class="btn btn-gold btn-sm" type="submit"
              data-confirm="Apakah Anda yakin ingin menambahkan <?= e($p['nama_part']) ?> ke build plan <?= e($sel['merk'] . ' ' . $sel['model']) ?>?"
              data-confirm-title="Tambah ke build?"
              data-confirm-ok="Ya, tambahkan"
              data-confirm-tone="primary">Tambah ke Build</button>
    </form>
    <?php endif; ?>
  </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>