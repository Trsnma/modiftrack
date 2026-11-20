<?php
require_once '../includes/auth.php';
require_once '../includes/upload.php';
require_once '../includes/models.php';
require_once '../includes/csv.php';
require_admin();

const IMPORT_DIR    = __DIR__ . '/../uploads/import/';
const CSV_MAX_BYTES = 1024 * 1024;     // 1 MB
const CSV_MAX_ROWS  = 500;
const KATEGORI      = ['Eksterior', 'Interior', 'Kaki-kaki', 'Performa'];
const KOLOM_WAJIB   = ['nama_part', 'kategori', 'merk', 'model', 'tahun_min', 'tahun_max', 'harga_part', 'estimasi_jasa'];

// Unduh template CSV (pemisah ; agar terbuka rapi di Excel Indonesia)
if (isset($_GET['template'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="template_parts.csv"');
    echo "\xEF\xBB\xBF";
    echo "nama_part;kategori;merk;model;tahun_min;tahun_max;harga_part;estimasi_jasa;foto\r\n";
    echo "Knalpot Racing Slip-on;Performa;Honda;Vario 150;2018;2024;1850000;150000;knalpot.jpg\r\n";
    echo "Jok Custom Kulit;Interior;Vespa;Primavera;2014;2024;1200000;100000;\r\n";
    exit;
}

/** Validasi satu baris. Return [data, pesanError]. */
function validasi_baris(array $d): array {
    $int = fn($v, $min = null) => filter_var($v, FILTER_VALIDATE_INT, $min === null ? [] : ['options' => ['min_range' => $min]]);
    $tmin = $int($d['tahun_min']); $tmax = $int($d['tahun_max']);
    $harga = $int($d['harga_part'], 0); $jasa = $int($d['estimasi_jasa'], 0);

    if ($d['nama_part'] === '' || mb_strlen($d['nama_part']) > 100) return [null, 'nama_part kosong atau lebih dari 100 karakter'];
    if (!in_array($d['kategori'], KATEGORI, true))                   return [null, 'kategori harus: ' . implode(', ', KATEGORI)];
    if ($d['merk'] === '' || mb_strlen($d['merk']) > 50)             return [null, 'merk kosong atau lebih dari 50 karakter'];
    if ($d['model'] === '' || mb_strlen($d['model']) > 50)           return [null, 'model kosong atau lebih dari 50 karakter'];
    if ($tmin === false || $tmax === false || $tmin > $tmax)         return [null, 'tahun_min/tahun_max tidak valid (min tidak boleh lebih besar dari max)'];
    if ($harga === false || $jasa === false)                         return [null, 'harga_part/estimasi_jasa harus angka bulat tanpa titik atau koma'];

    $foto = $d['foto'] ?? '';
    if ($foto !== '' && !preg_match('/^[A-Za-z0-9_\-]+\.(jpe?g|png|webp)$/i', $foto)) {
        return [null, 'nama file foto tidak valid (huruf/angka/_/-, ekstensi jpg, png, atau webp)'];
    }
    return [[$d['nama_part'], $d['kategori'], $d['merk'], $d['model'], $tmin, $tmax, $harga, $jasa, $foto], null];
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = $_FILES['csv'] ?? null;
    $mime = ($f && is_uploaded_file($f['tmp_name'] ?? '')) ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';

    if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        $errors[] = 'Upload file gagal. Pilih file CSV lalu coba lagi.';
    } elseif ($f['size'] > CSV_MAX_BYTES) {
        $errors[] = 'Ukuran file CSV maksimal 1 MB.';
    } elseif (!preg_match('/\.csv$/i', $f['name']) || !(str_starts_with($mime, 'text/') || in_array($mime, ['application/csv', 'application/vnd.ms-excel'], true))) {
        $errors[] = 'File harus berformat CSV.';
    } else {
        $data = baca_csv($f['tmp_name']);
        $header = array_map('strtolower', array_shift($data) ?? []);
        $kurang = array_diff(KOLOM_WAJIB, $header);

        if ($kurang) {
            $errors[] = 'Kolom wajib tidak ditemukan: ' . implode(', ', $kurang) . '. Gunakan template yang disediakan.';
        } elseif (!$data) {
            $errors[] = 'File tidak berisi data.';
        } elseif (count($data) > CSV_MAX_ROWS) {
            $errors[] = 'Maksimal ' . CSV_MAX_ROWS . ' baris per import.';
        } else {
            // Tahap 1: validasi semua baris dulu (semua atau tidak sama sekali)
            $siap = [];
            foreach ($data as $i => $r) {
                $baris = array_combine($header, array_pad(array_slice($r, 0, count($header)), count($header), ''));
                [$ok, $pesan] = validasi_baris($baris);
                if (!$pesan) {
                    $mm = model_find($ok[2], $ok[3]);     // merk & model harus ada di master
                    if (!$mm) {
                        $pesan = 'merk/model "' . $ok[2] . ' ' . $ok[3] . '" belum ada di Kelola merk & model';
                    } else {
                        $ok[2] = $mm['merk']; $ok[3] = $mm['model'];   // pakai penulisan baku dari master
                    }
                }
            }

            // Tahap 2: simpan dalam satu transaksi
            if (!$errors) {
                $baru = []; $sumber = []; $lewati = [];
                try {
                    mysqli_begin_transaction($conn);
                    foreach ($siap as $d) {
                        $foto = null;
                        if ($d[8] !== '') {
                            $path = IMPORT_DIR . basename($d[8]);
                            if (is_file($path) && !is_link($path)) {
                                try {
                                    $foto = proses_foto($path);        // pengecekan + gambar ulang yang sama dengan upload
                                    $baru[] = $foto; $sumber[] = $path;
                                } catch (RuntimeException $ex) {
                                    $lewati[] = $d[8] . ' (' . $ex->getMessage() . ')';
                                }
                            } else {
                                $lewati[] = $d[8] . ' (file tidak ada di uploads/import)';
                            }
                        }
                        q('INSERT INTO parts_catalog (nama_part,kategori,kompatibel_merk,kompatibel_model,tahun_min,tahun_max,harga_part,estimasi_jasa,foto) VALUES (?,?,?,?,?,?,?,?,?)',
                          'ssssiiiis', [$d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $d[6], $d[7], $foto]);
                    }
                    mysqli_commit($conn);
                    foreach (array_unique($sumber) as $s) @unlink($s);  // bersihkan file sumber setelah sukses
                    $pesan = count($siap) . ' part berhasil ditambahkan.';
                    if ($lewati) $pesan .= ' Foto dilewati: ' . implode('; ', array_slice($lewati, 0, 5)) . (count($lewati) > 5 ? ' dan lainnya.' : '');
                    flash($lewati ? 'err' : 'ok', $pesan);
                    redirect('admin_katalog.php');
                } catch (Throwable $ex) {
                    mysqli_rollback($conn);
                    foreach ($baru as $b) hapus_foto($b);               // buang foto yang sempat dibuat
                    error_log('Import CSV gagal: ' . $ex->getMessage());
                    $errors[] = 'Terjadi kesalahan saat menyimpan. Tidak ada data yang masuk.';
                }
            }
        }
    }
}

$title = 'Import Data Part'; $active = 'admin';
include '../includes/header.php';
?>
<h1>Import data part</h1>

<div class="card">
  <h3>Cara pakai</h3>
  <p>1. <a href="?template=1">Unduh template CSV</a> dan buka di Excel.<br>
     2. Isi satu part per baris. Kategori harus salah satu dari: <b>Eksterior, Interior, Kaki-kaki, Performa</b>. Harga ditulis angka tanpa titik (contoh <code>1850000</code>).<br>
     3. Kolom <code>foto</code> boleh kosong. Kalau diisi (misalnya <code>knalpot.jpg</code>), salin dulu fotonya ke <code>htdocs/modiftrack/uploads/import/</code>.<br>
     4. Simpan sebagai <b>CSV</b> (Excel: File, Save As, <i>CSV UTF-8</i>), lalu upload di bawah. Maksimal <?= CSV_MAX_ROWS ?> baris dan 1 MB.</p>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label for="csv">File CSV</label>
    <input id="csv" type="file" name="csv" accept=".csv,text/csv" required>
    <p><button class="btn" type="submit">Import</button>
       <a class="btn btn-ghost" href="admin_katalog.php">Kembali</a></p>
  </form>
</div>

<?php if ($errors): ?>
<div class="flash err" role="alert">
  <b>Import dibatalkan, belum ada data yang disimpan.</b>
  <ul>
    <?php foreach (array_slice($errors, 0, 30) as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
  </ul>
  <?php if (count($errors) > 30): ?><p>dan <?= count($errors) - 30 ?> kesalahan lainnya.</p><?php endif; ?>
</div>
<?php endif; ?>
<?php include '../includes/footer.php'; ?>