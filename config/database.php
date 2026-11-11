<?php
/**
 * Koneksi database aplikasi Lentera Digital Library.
 * Nilai sensitif dapat ditimpa melalui environment variable
 * (DB_HOST, DB_NAME, DB_USER, DB_PASS) tanpa mengubah kode.
 * File ini berada di luar document root (public/) sehingga tidak
 * dapat diakses langsung dari browser.
 */

declare(strict_types=1);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'digital_library';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Jangan tampilkan detail kredensial ke pengguna.
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Terjadi gangguan pada koneksi database. Silakan hubungi administrator.');
}
