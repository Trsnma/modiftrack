<?php
require_once '../includes/auth.php';
require_login();
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['aksi'] ?? '';
    if ($act === 'tambah') {
        $merk = trim($_POST['merk'] ?? ''); $model = trim($_POST['model'] ?? '');
        $thn = filter_var($_POST['tahun'] ?? '', FILTER_VALIDATE_INT);
        if ($merk === '' || $model === '' || mb_strlen($merk) > 50 || mb_strlen($model) > 50
            || $thn === false || $thn < 1980 || $thn > (int)date('Y') + 1) {
            flash('err', 'Data tidak valid. Periksa merk, model, dan tahun.');
        } else {
            q('INSERT INTO vehicles (user_id,merk,model,tahun_rilis) VALUES (?,?,?,?)', 'issi', [$uid, $merk, $model, $thn]);
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
$title = 'Garasi'; $active = 'kendaraan';
include '../includes/header.php';
?>
<h1>Garasi</h1>
<form method="post" class="card">
  <h3>Tambah kendaraan</h3>
  <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
  <div class="form-row">
    <div><label for="merk">Merk</label><input id="merk" name="merk" maxlength="50" required placeholder="Honda"></div>
    <div><label for="model">Model</label><input id="model" name="model" maxlength="50" required placeholder="Vario 150"></div>
    <div><label for="tahun">Tahun rilis</label><input id="tahun" type="number" name="tahun" min="1980" max="<?= date('Y') + 1 ?>" required></div>
    <div><button class="btn" type="submit">Simpan</button></div>
  </div>
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
<?php include '../includes/footer.php'; ?>
