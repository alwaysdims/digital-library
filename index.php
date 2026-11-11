<?php
/**
 * Entry point utama Lentera Digital Library.
 *
 * DocumentRoot diarahkan ke root proyek, sehingga file ini menjadi
 * pintu masuk utama untuk http://digital-library.test/.
 *
 * Folder public/ tetap menjadi lokasi seluruh skrip yang dapat diakses
 * browser. File ini hanya bertindak sebagai launcher (bootstrap) dan
 * TIDAK menduplikasi logika aplikasi: seluruh permintaan yang bukan
 * asset/gambar dialihkan ke public/index.php melalui .htaccess.
 *
 * Bila ada permintaan yang tetap sampai ke file ini (mis. ketika mod_rewrite
 * tidak aktif), permintaan diteruskan langsung ke public/index.php dengan
 * konteks server yang tetap konsisten.
 */

declare(strict_types=1);

$target = __DIR__ . '/public/index.php';

if (!is_file($target)) {
    http_response_code(500);
    echo 'Entry point aplikasi tidak ditemukan (public/index.php).';
    exit;
}

require $target;
