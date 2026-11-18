<?php
require_once '../includes/auth.php';
require_once '../includes/upload.php';
require_login();
$uid = $_SESSION['user_id'];
$statuses = ['Rencana', 'Sudah Dibeli', 'Terpasang'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    if (($_POST['aksi'] ?? '') === 'update') {
        $st = in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'Rencana';
        $note = mb_substr(trim($_POST['catatan'] ?? ''), 0, 500);
        q('UPDATE build_plans SET status_beli=?, catatan=? WHERE id=? AND user_id=?', 'ssii', [$st, $note, $id, $uid]);
        flash('ok', 'Build diperbarui.');
    } elseif (($_POST['aksi'] ?? '') === 'hapus') {
        q('DELETE FROM build_plans WHERE id=? AND user_id=?', 'ii', [$id, $uid]);
        flash('ok', 'Part dihapus dari build.');
    }
    redirect('build_plan.php');
}

$items = rows(
  'SELECT b.id, b.status_beli, b.catatan, v.id AS vid, v.merk, v.model, v.tahun_rilis,
          p.nama_part, p.kategori, p.harga_part, p.estimasi_jasa, p.foto
   FROM build_plans b
   JOIN vehicles v ON v.id=b.vehicle_id
   JOIN parts_catalog p ON p.id=b.part_id
   WHERE b.user_id=? ORDER BY v.id DESC, b.id', 'i', [$uid]);

// Kelompokkan per kendaraan + hitung total
$groups = []; $grand = 0;
foreach ($items as $it) {
    $groups[$it['vid']]['info'] = $it['merk'] . ' ' . $it['model'] . ' (' . $it['tahun_rilis'] . ')';
    $groups[$it['vid']]['rows'][] = $it;
    $groups[$it['vid']]['part'] = ($groups[$it['vid']]['part'] ?? 0) + $it['harga_part'];
    $groups[$it['vid']]['jasa'] = ($groups[$it['vid']]['jasa'] ?? 0) + $it['estimasi_jasa'];
    $grand += $it['harga_part'] + $it['estimasi_jasa'];
}
$title = 'Build Plan'; $active = 'build';
include '../includes/header.php';
?>
<h1>Build plan</h1>
<?php if (!$groups): ?>
  <div class="card">Belum ada part. Pilih dari <a href="katalog.php">katalog</a>.</div>
<?php endif; ?>
<?php foreach ($groups as $g): ?>
<section class="card">
  <h2><?= e($g['info']) ?></h2>
  <div class="table-scroll">
  <table>
    <tr><th>Part</th><th class="num">Harga part</th><th class="num">Jasa</th><th>Status</th><th>Catatan</th><th></th></tr>
    <?php foreach ($g['rows'] as $r): $fid = 'f' . (int)$r['id']; $img = foto_url($r['foto']); ?>
    <tr>
      <td>
        <div class="part-cell">
          <?php if ($img): ?><img class="thumb-sm" src="<?= e($img) ?>" alt="" loading="lazy">
          <?php else: ?><div class="thumb-sm thumb-empty"></div><?php endif; ?>
          <div><?= e($r['nama_part']) ?><br><span class="tag"><?= e($r['kategori']) ?></span></div>
        </div>
      </td>
      <td class="num"><?= rupiah($r['harga_part']) ?></td>
      <td class="num"><?= rupiah($r['estimasi_jasa']) ?></td>
      <td>
        <select name="status" form="<?= $fid ?>" aria-label="Status pembelian">
          <?php foreach ($statuses as $s): ?><option <?= $r['status_beli'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
        </select>
      </td>
      <td><input name="catatan" form="<?= $fid ?>" value="<?= e($r['catatan']) ?>" maxlength="500" aria-label="Catatan"></td>
      <td class="num">
        <form id="<?= $fid ?>" method="post" class="inline">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="btn btn-sm" name="aksi" value="update">Simpan</button>
          <button class="btn btn-danger btn-sm" name="aksi" value="hapus" data-confirm="Hapus part ini dari build?">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  </div>
  <div class="total">
    <span>Part <?= rupiah($g['part']) ?> + Jasa <?= rupiah($g['jasa']) ?></span>
    <span>Subtotal <?= rupiah($g['part'] + $g['jasa']) ?></span>
  </div>
</section>
<?php endforeach; ?>
<?php if ($groups): ?><div class="panel stats"><div class="stat"><b><?= rupiah($grand) ?></b>Total semua build</div></div><?php endif; ?>
<?php include '../includes/footer.php'; ?>