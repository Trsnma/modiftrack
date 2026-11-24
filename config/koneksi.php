<?php
/** Koneksi database + helper prepared statement (mysqli_prepare). */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

define('DB_HOST', 'localhost');
define('DB_USER', 'root');      // default XAMPP
define('DB_PASS', '');
define('DB_NAME', 'db_modiftrack');

try {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $ex) {
    error_log('DB error: ' . $ex->getMessage());      // detail hanya ke log server
    http_response_code(500);
    exit('Koneksi database gagal. Pastikan MySQL aktif dan database sudah diimpor.');
}

/** Jalankan query berparameter. $types contoh: 'sii'. */
function q(string $sql, string $types = '', array $params = []): mysqli_stmt {
    global $conn;
    $st = mysqli_prepare($conn, $sql);
    if ($types !== '') mysqli_stmt_bind_param($st, $types, ...$params);
    mysqli_stmt_execute($st);
    return $st;
}
/** Ambil semua baris sebagai array asosiatif. */
function rows(string $sql, string $types = '', array $params = []): array {
    return mysqli_fetch_all(mysqli_stmt_get_result(q($sql, $types, $params)), MYSQLI_ASSOC);
}
/** Ambil satu baris (atau null). */
function row(string $sql, string $types = '', array $params = []): ?array {
    return rows($sql, $types, $params)[0] ?? null;
}
