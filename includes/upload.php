<?php
/** Upload foto aman: validasi isi file, gambar ulang lewat GD, nama acak. */
const UPLOAD_DIR     = __DIR__ . '/../uploads/parts/';
const FOTO_MAX_BYTES = 2 * 1024 * 1024;               // 2 MB
const FOTO_MAX_SISI  = 1000;                          // px, sisi terpanjang hasil akhir
const FOTO_REGEX     = '/^[a-f0-9]{32}\.(jpg|png|webp)$/';

/** URL foto untuk <img>, atau null jika nama tidak valid / kosong. */
function foto_url(?string $nama, string $base = '../'): ?string {
    return ($nama && preg_match(FOTO_REGEX, $nama)) ? $base . 'uploads/parts/' . $nama : null;
}

/** Hapus file foto (nama divalidasi regex dulu: anti path traversal). */
function hapus_foto(?string $nama): void {
    if ($nama && preg_match(FOTO_REGEX, $nama) && is_file(UPLOAD_DIR . $nama)) {
        unlink(UPLOAD_DIR . $nama);
    }
}

/** Upload dari form: cek status upload, lalu serahkan ke proses_foto(). */
function simpan_foto(array $f): string {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('Upload foto gagal. Coba lagi.');
    }
    return proses_foto($f['tmp_name']);
}

/** Validasi isi file, gambar ulang, simpan dengan nama acak. Return nama file baru. */
function proses_foto(string $path): string {
    if (filesize($path) > FOTO_MAX_BYTES) {
        throw new RuntimeException('Ukuran foto maksimal 2 MB.');
    }

    // Tipe ditentukan dari ISI file, bukan dari nama/ekstensi
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    $info = getimagesize($path);
    $tipe = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$tipe || !$info || $info['mime'] !== $mime) {
        throw new RuntimeException('Format foto harus JPG, PNG, atau WebP.');
    }
    [$w, $h] = $info;
    if ($w * $h > 25000000) {
        throw new RuntimeException('Dimensi foto terlalu besar.');
    }

    $loader = ['jpg' => 'imagecreatefromjpeg', 'png' => 'imagecreatefrompng', 'webp' => 'imagecreatefromwebp'][$tipe];
    $src = function_exists($loader) ? @$loader($path) : false;
    if (!$src) {
        throw new RuntimeException('Foto rusak atau format tidak didukung server.');
    }

    // Gambar ulang ke kanvas baru: payload/EXIF/kode tersembunyi ikut terbuang
    $skala = min(1, FOTO_MAX_SISI / max($w, $h));
    $nw = max(1, (int)round($w * $skala));
    $nh = max(1, (int)round($h * $skala));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($tipe === 'jpg') {
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    } else {                                           // pertahankan transparansi PNG/WebP
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $nama = bin2hex(random_bytes(16)) . '.' . $tipe;   // nama & ekstensi dibuat server
    $tujuan = UPLOAD_DIR . $nama;
    $ok = match ($tipe) {
        'jpg'  => imagejpeg($dst, $tujuan, 85),
        'png'  => imagepng($dst, $tujuan, 6),
        'webp' => imagewebp($dst, $tujuan, 85),
    };
    if (!$ok) throw new RuntimeException('Gagal menyimpan foto.');
    chmod($tujuan, 0644);
    return $nama;
}