<?php
require_once '../includes/auth.php';
require_once '../includes/upload.php';
require_once '../includes/models.php';
require_admin();
$kats = ['Eksterior', 'Interior', 'Kaki-kaki', 'Performa'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($act === 'hapus') {
        $lama = row('SELECT foto FROM parts_catalog WHERE id=?', 'i', [$id]);
        q('DELETE FROM parts_catalog WHERE id=?', 'i', [$id]);
        if ($lama) hapus_foto($lama['foto']);                 // bersihkan file foto juga
        flash('ok', 'Part dihapus.');

    } elseif ($act === 'simpan') {
        $nama = trim($_POST['nama_part'] ?? ''); $kat = $_POST['kategori'] ?? '';
        $mm   = model_find(trim($_POST['merk'] ?? ''), trim($_POST['model'] ?? ''));   // wajib ada di master
        $tmin = filter_var($_POST['tahun_min'] ?? '', FILTER_VALIDATE_INT);
        $tmax = filter_var($_POST['tahun_max'] ?? '', FILTER_VALIDATE_INT);
        $harga = filter_var($_POST['harga'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $jasa = filter_var($_POST['jasa'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $balik = 'admin_katalog.php' . ($id ? '?edit=' . $id : '');

        if ($nama === '' || mb_strlen($nama) > 100 || !in_array($kat, $kats, true) || !$mm
            || $tmin === false || $tmax === false || $tmin > $tmax || $harga === false || $jasa === false) {
            flash('err', 'Data tidak valid. Pilih merk dan model dari daftar, dan pastikan tahun min tidak lebih besar dari tahun max.');
            redirect($balik);
        }
        $merk = $mm['merk']; $model = $mm['model'];

        // Foto: opsional. Jika ada, wajib lolos validasi di includes/upload.php
        $lama = $id ? row('SELECT foto FROM parts_catalog WHERE id=?', 'i', [$id]) : null;
        $foto = $lama['foto'] ?? null;
        $baru = null;
        try {
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
                $baru = simpan_foto($_FILES['foto']);
                $foto = $baru;
            }
        } catch (RuntimeException $ex) {
            flash('err', $ex->getMessage());
            redirect($balik);
        }

        if ($id) {
            q('UPDATE parts_catalog SET nama_part=?,kategori=?,kompatibel_merk=?,kompatibel_model=?,tahun_min=?,tahun_max=?,harga_part=?,estimasi_jasa=?,foto=? WHERE id=?',
              'ssssiiiisi', [$nama, $kat, $merk, $model, $tmin, $tmax, $harga, $jasa, $foto, $id]);
            if ($baru && $lama) hapus_foto($lama['foto']);    // foto lama dibuang setelah DB berhasil
            flash('ok', 'Part diperbarui.');
        } else {
            q('INSERT INTO parts_catalog (nama_part,kategori,kompatibel_merk,kompatibel_model,tahun_min,tahun_max,harga_part,estimasi_jasa,foto) VALUES (?,?,?,?,?,?,?,?,?)',
              'ssssiiiis', [$nama, $kat, $merk, $model, $tmin, $tmax, $harga, $jasa, $foto]);
            flash('ok', 'Part ditambahkan.');
        }
    }
    redirect('admin_katalog.php');
}

$edit = isset($_GET['edit']) ? row('SELECT * FROM parts_catalog WHERE id=?', 'i', [(int)$_GET['edit']]) : null;
$f = $edit ?? ['id' => 0, 'nama_part' => '', 'kategori' => '', 'kompatibel_merk' => '', 'kompatibel_model' => '',
               'tahun_min' => '', 'tahun_max' => '', 'harga_part' => '', 'estimasi_jasa' => '', 'foto' => null];
$parts = rows('SELECT * FROM parts_catalog ORDER BY id DESC');
$map = model_map();
$title = 'Admin Katalog'; $active = 'admin';
include '../includes/header.php';
?>
<h1>Kelola katalog</h1>
<p>
  <a class="btn btn-ghost btn-sm" href="admin_model.php">Kelola merk &amp; model</a>
  <a class="btn btn-ghost btn-sm" href="admin_import_csv.php">Import data (CSV)</a>
</p>
<form method="post" enctype="multipart/form-data" class="card">
  <h3><?= $edit ? 'Ubah part #' . (int)$f['id'] : 'Tambah part' ?></h3>
  <?php if (!$map): ?>
    <p class="muted">Belum ada merk dan model. Tambahkan dulu di <a href="admin_model.php">Kelola merk &amp; model</a>.</p>
  <?php else: ?>
  <?= csrf_field() ?><input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
  <div class="form-row">
    <div><label for="nama_part">Nama part</label><input id="nama_part" name="nama_part" maxlength="100" required value="<?= e($f['nama_part']) ?>"></div>
    <div><label for="kategori">Kategori</label>
      <select id="kategori" name="kategori"><?php foreach ($kats as $k): ?><option <?= $f['kategori'] === $k ? 'selected' : '' ?>><?= e($k) ?></option><?php endforeach; ?></select></div>
    <div>
      <label for="merk">Merk kompatibel</label>
      <select id="merk" name="merk" required data-target="model"
              data-models="<?= e(json_encode($map, JSON_UNESCAPED_UNICODE)) ?>">
        <option value="">Pilih merk</option>
        <?php foreach (array_keys($map) as $m): ?><option <?= strcasecmp($f['kompatibel_merk'], $m) === 0 ? 'selected' : '' ?>><?= e($m) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="model">Model kompatibel</label>
      <select id="model" name="model" required disabled data-selected="<?= e($f['kompatibel_model']) ?>"><option value="">Pilih merk dulu</option></select>
    </div>
    <div><label for="tahun_min">Tahun min</label><input id="tahun_min" type="number" name="tahun_min" required value="<?= e($f['tahun_min']) ?>"></div>
    <div><label for="tahun_max">Tahun max</label><input id="tahun_max" type="number" name="tahun_max" required value="<?= e($f['tahun_max']) ?>"></div>
    <div><label for="harga">Harga part (Rp)</label><input id="harga" type="number" min="0" name="harga" required value="<?= e($f['harga_part']) ?>"></div>
    <div><label for="jasa">Estimasi jasa (Rp)</label><input id="jasa" type="number" min="0" name="jasa" required value="<?= e($f['estimasi_jasa']) ?>"></div>
    <div>
      <label for="foto">Foto part (JPG/PNG/WebP, maks 2 MB)</label>
      <input id="foto" type="file" name="foto" accept="image/jpeg,image/png,image/webp">
    </div>
  </div>
  <?php if ($edit && ($cur = foto_url($f['foto']))): ?>
    <p class="muted">Foto saat ini (kosongkan kolom foto untuk mempertahankannya):</p>
    <img class="thumb-sm" src="<?= e($cur) ?>" alt="Foto saat ini">
  <?php endif; ?>
  <p><button class="btn" type="submit">Simpan</button>
     <?php if ($edit): ?><a class="btn btn-ghost" href="admin_katalog.php">Batal</a><?php endif; ?></p>
  <?php endif; ?>
</form>
<div class="card table-scroll">
<table>
  <tr><th>Foto</th><th>Part</th><th>Kategori</th><th>Kompatibel</th><th class="num">Part</th><th class="num">Jasa</th><th></th></tr>
  <?php foreach ($parts as $p): $img = foto_url($p['foto']); ?>
  <tr>
    <td><?php if ($img): ?><img class="thumb-sm" src="<?= e($img) ?>" alt="" loading="lazy"><?php else: ?><div class="thumb-sm thumb-empty"></div><?php endif; ?></td>
    <td><?= e($p['nama_part']) ?></td><td><?= e($p['kategori']) ?></td>
    <td><?= e($p['kompatibel_merk'] . ' ' . $p['kompatibel_model']) ?> (<?= (int)$p['tahun_min'] ?>-<?= (int)$p['tahun_max'] ?>)</td>
    <td class="num"><?= rupiah($p['harga_part']) ?></td><td class="num"><?= rupiah($p['estimasi_jasa']) ?></td>
    <td class="num">
      <a class="btn btn-ghost btn-sm" href="?edit=<?= (int)$p['id'] ?>">Ubah</a>
      <form method="post" class="inline">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <button class="btn btn-danger btn-sm" data-confirm="Hapus part ini? Part juga hilang dari build plan user.">Hapus</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</div>
<?php include '../includes/footer.php'; ?>
