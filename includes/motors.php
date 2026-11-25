<?php
/** Daftar foto motor (PNG/WebP transparan) di assets/img/motors/ untuk galeri.
 *  $prefix = jalur dari halaman yang memanggil ke folder utama proyek, misalnya '../'. */
function daftar_motor(string $prefix): array {
    $motors = [];
    foreach (glob(__DIR__ . '/../assets/img/motors/*.{png,webp,PNG,WEBP}', GLOB_BRACE) ?: [] as $file) {
        $motors[] = [
            'src'  => $prefix . 'assets/img/motors/' . rawurlencode(basename($file)),
            'name' => ucwords(str_replace(['-', '_'], ' ', pathinfo($file, PATHINFO_FILENAME))),
        ];
    }
    return $motors;
}