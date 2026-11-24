<?php
require_once '../includes/auth.php';
<<<<<<< HEAD
=======
require_once '../includes/models.php';
>>>>>>> 397324069be6e4cfbb5db59296b77ea9a152e5a7
require_login();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['aksi'] ?? '';
    if ($act === 'tambah') {
<<<<<<< HEAD
        $merk = trim($_POST['merk'] ?? ''); $model = trim($_POST['model'] ?? '');
        $thn = filter_var($_POST['tahun'] ?? '', FILTER_VALIDATE_INT);
        if ($merk === '' || $model === '' || mb_strlen($merk) > 50 || mb_strlen($model) > 50
            || $thn === false || $thn < 1980 || $thn > (int)date('Y') + 1) {
            flash('err', 'Data tidak valid. Periksa merk, model, dan tahun.');
        } else {
            q('INSERT INTO vehicles (user_id,merk,model,tahun_rilis) VALUES (?,?,?,?)', 'issi', [$uid, $merk, $model, $thn]);
=======
        // Merk & model harus ada di master (dropdown); tahun bebas diisi user
        $mm  = model_find(trim($_POST['merk'] ?? ''), trim($_POST['model'] ?? ''));
        $thn = filter_var($_POST['tahun'] ?? '', FILTER_VALIDATE_INT);
        if (!$mm) {
            flash('err', 'Pilih merk dan model dari daftar.');
        } elseif ($thn === false || $thn < 1980 || $thn > (int)date('Y') + 1) {
            flash('err', 'Tahun rilis tidak valid.');
        } else {
            q('INSERT INTO vehicles (user_id,merk,model,tahun_rilis) VALUES (?,?,?,?)', 'issi', [$uid, $mm['merk'], $mm['model'], $thn]);
>>>>>>> 397324069be6e4cfbb5db59296b77ea9a152e5a7
            flash('ok', 'Kendaraan ditambahkan ke garasi.');
        }
    } elseif ($act === 'hapus') {
        // user_id di WHERE: user tidak bisa menghapus data milik orang lain (IDOR)
        q('DELETE FROM vehicles WHERE id=? AND user_id=?', 'ii', [(int)($_POST['id'] ?? 0), $uid]);
        flash('ok', 'Kendaraan dihapus beserta rencana build-nya.');
    }
    redirect('kendaraan.php');
}
$cars = rows('SELECT * FROM vehicles WHERE user_id=? ORDER BY id DESC', 'i', [$uid]);
<<<<<<< HEAD
=======
$map  = model_map();
>>>>>>> 397324069be6e4cfbb5db59296b77ea9a152e5a7
$title = 'Garasi'; $active = 'kendaraan';
include '../includes/header.php';
?>
<h1>Garasi</h1>
<form method="post" class="card">
  <h3>Tambah kendaraan</h3>
<<<<<<< HEAD
  <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
  <div class="form-row">
    <div><label for="merk">Merk</label><input id="merk" name="merk" maxlength="50" required placeholder="Honda"></div>
    <div><label for="model">Model</label><input id="model" name="model" maxlength="50" required placeholder="Vario 150"></div>
    <div><label for="tahun">Tahun rilis</label><input id="tahun" type="number" name="tahun" min="1980" max="<?= date('Y') + 1 ?>" required></div>
    <div><button class="btn" type="submit">Simpan</button></div>
  </div>
=======
  <?php if (!$map): ?>
    <p class="muted">Daftar merk dan model belum tersedia. Hubungi admin untuk menambahkannya.</p>
  <?php else: ?>
  <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
  <div class="form-row">
    <div>
      <label for="merk">Merk</label>
      <select id="merk" name="merk" required data-target="model"
              data-models="<?= e(json_encode($map, JSON_UNESCAPED_UNICODE)) ?>">
        <option value="">Pilih merk</option>
        <?php foreach (array_keys($map) as $m): ?><option><?= e($m) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="model">Model</label>
      <select id="model" name="model" required disabled><option value="">Pilih merk dulu</option></select>
    </div>
    <div>
      <label for="tahun">Tahun rilis</label>
      <input id="tahun" type="number" name="tahun" min="1980" max="<?= date('Y') + 1 ?>" required>
    </div>
    <div><button class="btn" type="submit">Simpan</button></div>
  </div>
  <?php endif; ?>
>>>>>>> 397324069be6e4cfbb5db59296b77ea9a152e5a7
</form>
<div class="card table-scroll">
<?php if (!$cars): ?><p>Belum ada kendaraan.</p><?php else: ?>
<table>
  <tr><th>Merk</th><th>Model</th><th>Tahun</th><th></th></tr>
  <?php foreach ($cars as $c): ?>
  <tr>
    <td><?= e($c['merk']) ?></td><td><?= e($c['model']) ?></td><td><?= (int)$c['tahun_rilis'] ?></td>
    <td class="num">
      <a class="btn btn-ghost btn-sm" href="katalog.php?v=<?= (int)$c['id'] ?>">Cari part</a>
      <form method="post" class="inline">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
        <button class="btn btn-danger btn-sm" data-confirm="Hapus kendaraan ini dan semua rencana build-nya?">Hapus</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<<<<<<< HEAD
<?php include '../includes/footer.php'; ?>
=======
<?php include '../includes/footer.php'; ?>
>>>>>>> 397324069be6e4cfbb5db59296b77ea9a152e5a7
