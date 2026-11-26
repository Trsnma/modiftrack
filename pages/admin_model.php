<?php
require_once '../includes/auth.php';
require_once '../includes/models.php';
require_once '../includes/csv.php';
require_admin();

const CSV_MAX_BYTES = 512 * 1024;      // 512 KB
const CSV_MAX_ROWS  = 1000;

function bersih(string $s): string { return trim(preg_replace('/\s+/u', ' ', $s)); }
function nama_valid(string $s): bool { return $s !== '' && mb_strlen($s) <= 50 && !preg_match('/[[:cntrl:]]/u', $s); }

// Unduh template CSV
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_model.csv"');
    echo "\xEF\xBB\xBF";
    echo "merk;model\r\nHonda;Vario 150\r\nHonda;Beat\r\nYamaha;NMAX\r\n";
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $merk = bersih($_POST['merk'] ?? ''); $model = bersih($_POST['model'] ?? '');
        if (!nama_valid($merk) || !nama_valid($model)) {
            flash('err', 'Merk dan model wajib diisi (maksimal 50 karakter).');
        } elseif (model_find($merk, $model)) {
            flash('err', 'Kombinasi merk dan model itu sudah ada.');
        } else {
            q('INSERT INTO vehicle_models (merk, model) VALUES (?, ?)', 'ss', [$merk, $model]);
            flash('ok', 'Model ditambahkan.');
        }
        redirect('admin_model.php');

    } elseif ($aksi === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $m = row('SELECT m.id,
                    (SELECT COUNT(*) FROM vehicles v WHERE v.merk=m.merk AND v.model=m.model) AS kend,
                    (SELECT COUNT(*) FROM parts_catalog p WHERE p.kompatibel_merk=m.merk AND p.kompatibel_model=m.model) AS part
                  FROM vehicle_models m WHERE m.id=?', 'i', [$id]);
        if (!$m) {
            flash('err', 'Data tidak ditemukan.');
        } elseif ($m['kend'] > 0 || $m['part'] > 0) {
            flash('err', 'Model masih dipakai kendaraan atau part, tidak bisa dihapus.');
        } else {
            q('DELETE FROM vehicle_models WHERE id=?', 'i', [$id]);
            flash('ok', 'Model dihapus.');
        }
        redirect('admin_model.php');

    } elseif ($aksi === 'import') {
        $f = $_FILES['csv'] ?? null;
        $mime = ($f && is_uploaded_file($f['tmp_name'] ?? '')) ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';

        if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $errors[] = 'Upload file gagal. Pilih file CSV lalu coba lagi.';
        } elseif ($f['size'] > CSV_MAX_BYTES) {
            $errors[] = 'Ukuran file CSV maksimal 512 KB.';
        } elseif (!preg_match('/\.csv$/i', $f['name']) || !(str_starts_with($mime, 'text/') || in_array($mime, ['application/csv', 'application/vnd.ms-excel'], true))) {
            $errors[] = 'File harus berformat CSV.';
        } else {
            $data = baca_csv($f['tmp_name']);
            $header = array_map('strtolower', array_shift($data) ?? []);
            $iMerk = array_search('merk', $header, true);
            $iModel = array_search('model', $header, true);

            if ($iMerk === false || $iModel === false) {
                $errors[] = 'Kolom "merk" dan "model" tidak ditemukan. Gunakan template yang disediakan.';
            } elseif (!$data) {
                $errors[] = 'File tidak berisi data.';
            } elseif (count($data) > CSV_MAX_ROWS) {
                $errors[] = 'Maksimal ' . CSV_MAX_ROWS . ' baris per import.';
            } else {
                // Tahap 1: validasi semua baris (semua atau tidak sama sekali)
                $siap = [];
                foreach ($data as $i => $r) {
                    $merk = bersih($r[$iMerk] ?? ''); $model = bersih($r[$iModel] ?? '');
                    if (!nama_valid($merk) || !nama_valid($model)) {
                        $errors[] = 'Baris ' . ($i + 2) . ': merk/model kosong atau lebih dari 50 karakter';
                    } else {
                        $siap[] = [$merk, $model];
                    }
                }
                // Tahap 2: simpan dalam satu transaksi, duplikat dilewati
                if (!$errors) {
                    try {
                        mysqli_begin_transaction($conn);
                        $baru = 0;
                        foreach ($siap as $d) {
                            $st = q('INSERT IGNORE INTO vehicle_models (merk, model) VALUES (?, ?)', 'ss', $d);
                            $baru += max(0, mysqli_stmt_affected_rows($st));
                        }
                        mysqli_commit($conn);
                        $lewat = count($siap) - $baru;
                        flash('ok', $baru . ' model ditambahkan' . ($lewat ? ', ' . $lewat . ' sudah ada (dilewati).' : '.'));
                        redirect('admin_model.php');
                    } catch (Throwable $ex) {
                        mysqli_rollback($conn);
                        error_log('Import model gagal: ' . $ex->getMessage());
                        $errors[] = 'Terjadi kesalahan saat menyimpan. Tidak ada data yang masuk.';
                    }
                }
            }
        }
    }
}

$daftar = rows(
  'SELECT m.id, m.merk, m.model,
     (SELECT COUNT(*) FROM vehicles v WHERE v.merk=m.merk AND v.model=m.model) AS kend,
     (SELECT COUNT(*) FROM parts_catalog p WHERE p.kompatibel_merk=m.merk AND p.kompatibel_model=m.model) AS part
   FROM vehicle_models m ORDER BY m.merk, m.model');
$merkAda = array_values(array_unique(array_column($daftar, 'merk')));

$title = 'Merk & Model'; $active = 'admin';
include '../includes/header.php';
?>
<h1>Merk &amp; model kendaraan</h1>
<p>
  <a class="btn btn-ghost btn-sm" href="admin_katalog.php">Kembali ke katalog</a>
</p>

<form method="post" class="card">
  <h3>Tambah manual</h3>
  <?= csrf_field() ?><input type="hidden" name="aksi" value="tambah">
  <div class="form-row">
    <div>
      <label for="merk">Merk</label>
      <input id="merk" name="merk" list="daftar-merk" maxlength="50" required placeholder="Honda">
      <datalist id="daftar-merk">
        <?php foreach ($merkAda as $m): ?><option value="<?= e($m) ?>"><?php endforeach; ?>
      </datalist>
    </div>
    <div>
      <label for="model">Model</label>
      <input id="model" name="model" maxlength="50" required placeholder="Vario 150">
    </div>
    <div>
      <button class="btn" type="submit"
              data-confirm="Apakah Anda yakin ingin menambahkan model ini ke daftar merk dan model?"
              data-confirm-title="Tambah model?"
              data-confirm-ok="Ya, tambahkan"
              data-confirm-tone="primary">Simpan</button>
    </div>
  </div>
</form>

<form method="post" enctype="multipart/form-data" class="card">
  <h3>Import banyak sekaligus (CSV)</h3>
  <p class="muted"><a href="?template=1">Unduh template</a>, isi kolom <code>merk</code> dan <code>model</code> satu pasang per baris,
     simpan sebagai CSV, lalu upload. Pasangan yang sudah ada otomatis dilewati. Maksimal <?= CSV_MAX_ROWS ?> baris dan 512 KB.</p>
  <?= csrf_field() ?><input type="hidden" name="aksi" value="import">
  <label for="csv">File CSV</label>
  <input id="csv" type="file" name="csv" accept=".csv,text/csv" required>
  <p>
    <button class="btn" type="submit"
            data-confirm="Apakah Anda yakin ingin mengimpor daftar merk dan model dari file ini? Pasangan yang sudah ada akan dilewati."
            data-confirm-title="Import merk &amp; model?"
            data-confirm-ok="Ya, import"
            data-confirm-tone="primary">Import</button>
  </p>
</form>

<?php if ($errors): ?>
<div class="flash err" role="alert">
  <b>Import dibatalkan, belum ada data yang disimpan.</b>
  <ul><?php foreach (array_slice($errors, 0, 30) as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
  <?php if (count($errors) > 30): ?><p>dan <?= count($errors) - 30 ?> kesalahan lainnya.</p><?php endif; ?>
</div>
<?php endif; ?>

<div class="card table-scroll">
<?php if (!$daftar): ?>
  <p>Belum ada merk dan model. Tambahkan lewat form di atas.</p>
<?php else: ?>
<table>
  <tr><th>Merk</th><th>Model</th><th class="num">Dipakai kendaraan</th><th class="num">Dipakai part</th><th></th></tr>
  <?php foreach ($daftar as $d): $dipakai = $d['kend'] > 0 || $d['part'] > 0; ?>
  <tr>
    <td><?= e($d['merk']) ?></td>
    <td><?= e($d['model']) ?></td>
    <td class="num"><?= (int)$d['kend'] ?></td>
    <td class="num"><?= (int)$d['part'] ?></td>
    <td class="num">
      <?php if ($dipakai): ?>
        <span class="tag">Dipakai</span>
      <?php else: ?>
        <form method="post" class="inline">
          <?= csrf_field() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
          <button class="btn btn-danger btn-sm"
                  data-confirm="Apakah Anda yakin ingin menghapus <?= e($d['merk'] . ' ' . $d['model']) ?> dari daftar?"
                  data-confirm-title="Hapus model?">Hapus</button>
        </form>
      <?php endif; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>