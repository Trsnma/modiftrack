<?php
/** Master merk & model kendaraan: dipakai Garasi, Admin Katalog, dan Import CSV. */

/** Peta [merk => [model, model, ...]] untuk dropdown bertingkat. */
function model_map(): array {
    $map = [];
    foreach (rows('SELECT merk, model FROM vehicle_models ORDER BY merk, model') as $r) {
        $map[$r['merk']][] = $r['model'];
    }
    return $map;
}

/** Cari pasangan merk+model di master. Return versi baku (huruf besar/kecil sesuai master) atau null. */
function model_find(string $merk, string $model): ?array {
    return row('SELECT merk, model FROM vehicle_models WHERE merk=? AND model=?', 'ss', [$merk, $model]);
}