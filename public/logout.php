<?php
/**
 * Logout: hapus sesi lalu kembali ke beranda.
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

if (!is_post()) {
    http_response_code(405);
    header('Allow: POST');
    exit('Logout harus menggunakan POST.');
}

csrf_verify();
auth_logout();

app_start_session();
flash('success', 'Anda telah keluar. Sampai jumpa kembali!');

redirect('index.php');
