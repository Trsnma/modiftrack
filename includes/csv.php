<?php
/** Baca CSV menjadi array baris. Deteksi pemisah (; atau ,) dan encoding otomatis. */
function baca_csv(string $path): array {
    $isi = preg_replace('/^\xEF\xBB\xBF/', '', (string)file_get_contents($path));   // buang BOM
    if (!mb_check_encoding($isi, 'UTF-8')) {
        $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
    }
    $baris1 = strtok($isi, "\n");
    $pemisah = substr_count($baris1, ';') > substr_count($baris1, ',') ? ';' : ',';

    $fh = fopen('php://temp', 'r+');
    fwrite($fh, $isi);
    rewind($fh);
    $hasil = [];
    while (($r = fgetcsv($fh, 0, $pemisah, '"', '')) !== false) {
        if ($r === [null]) continue;                       // lewati baris kosong
        $hasil[] = array_map('trim', $r);
    }
    fclose($fh);
    return $hasil;
}