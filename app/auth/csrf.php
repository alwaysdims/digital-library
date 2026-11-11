<?php
/**
 * Proteksi CSRF berbasis token sesi.
 */

declare(strict_types=1);

/**
 * Token CSRF untuk sesi ini (dibuat sekali).
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Field hidden siap pakai untuk form.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validasi token dari request POST.
 * Menghentikan eksekusi dengan 403 (Forbidden) bila tidak valid.
 *
 * Catatan: kode 419 adalah konvensi non-standar. Pada Apache/Windows
 * (mod_php) kode tersebut diubah menjadi 500 Internal Server Error,
 * sehingga dipakai 403 yang standar dan semantiknya tepat.
 */
function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        exit('Sesi form sudah tidak valid. Silakan muat ulang halaman.');
    }
}
