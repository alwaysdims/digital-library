<?php
/**
 * Bootstrap aplikasi: konfigurasi, koneksi, sesi, helper, dan service.
 * File ini di-include oleh seluruh entry point di public/.
 */

declare(strict_types=1);

$appConfig = require __DIR__ . '/../config/app.php';

date_default_timezone_set($appConfig['timezone']);

require_once __DIR__ . '/helpers/functions.php';
require_once __DIR__ . '/helpers/view.php';
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/auth/csrf.php';
require_once __DIR__ . '/auth/guard.php';

$pdo = (function (): PDO {
    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbName = getenv('DB_NAME') ?: 'digital_library';
    $dbUser = getenv('DB_USER') ?: 'root';
    $dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

    try {
        return new PDO(
            "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Terjadi gangguan pada koneksi database. Silakan hubungi administrator.');
    }
})();

require_once __DIR__ . '/services/book_service.php';
require_once __DIR__ . '/services/category_service.php';
require_once __DIR__ . '/services/user_service.php';
require_once __DIR__ . '/services/borrowing_service.php';
require_once __DIR__ . '/services/upload_service.php';

app_start_session();

/**
 * Ambil konfigurasi aplikasi.
 *
 * Mendukung kunci bertingkat dengan notasi titik, mis. "upload.dir"
 * akan membaca $appConfig['upload']['dir']. Kunci datar (mis. "name")
 * tetap bekerja seperti sebelumnya.
 */
function app_config(?string $key = null, mixed $default = null): mixed
{
    global $appConfig;

    if ($key === null) {
        return $appConfig;
    }

    // Kunci datar lebih dahulu (perilaku lama dipertahankan).
    if (array_key_exists($key, $appConfig)) {
        return $appConfig[$key];
    }

    // Kunci bertingkat dengan pemisah titik.
    if (str_contains($key, '.')) {
        $node = $appConfig;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($node) || !array_key_exists($segment, $node)) {
                return $default;
            }

            $node = $node[$segment];
        }

        return $node;
    }

    return $default;
}

/**
 * Ambil koneksi PDO global.
 */
function db(): PDO
{
    global $pdo;

    return $pdo;
}
