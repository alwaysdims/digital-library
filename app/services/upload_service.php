<?php
/**
 * Service upload cover buku.
 * Hanya menerima file gambar dengan MIME terverifikasi dan nama acak.
 */

declare(strict_types=1);

/**
 * Proses file cover dari $_FILES.
 *
 * @return array{ok:bool,error?:string,name?:string}
 */
function cover_upload(array $file): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'File cover tidak valid.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'name' => null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload cover gagal. Kode: ' . (int) $file['error']];
    }

    $maxBytes = (int) app_config('upload.max_bytes', 2 * 1024 * 1024);

    if ((int) $file['size'] > $maxBytes) {
        return ['ok' => false, 'error' => 'Ukuran cover maksimal ' . round($maxBytes / 1024 / 1024, 1) . ' MB.'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Sumber file cover tidak sah.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']) ?: '';

    $allowedMime = (array) app_config('upload.mime_types', []);
    $allowedExt  = (array) app_config('upload.extensions', []);

    if (!in_array($mime, $allowedMime, true)) {
        return ['ok' => false, 'error' => 'Format cover harus JPG, PNG, atau WEBP.'];
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        return ['ok' => false, 'error' => 'Ekstensi file cover tidak diizinkan.'];
    }

    // Pastikan benar-benar gambar yang dapat diproses.
    if (getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'error' => 'File bukan gambar yang valid.'];
    }

    $dir = (string) app_config('upload.dir');

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => 'Folder upload tidak dapat dibuat.'];
    }

    $ext = $mime === 'image/png' ? 'png' : ($mime === 'image/webp' ? 'webp' : 'jpg');
    $name = bin2hex(random_bytes(16)) . '.' . $ext;

    if (!move_uploaded_file($file['tmp_name'], $dir . DIRECTORY_SEPARATOR . $name)) {
        return ['ok' => false, 'error' => 'Cover gagal disimpan.'];
    }

    @chmod($dir . DIRECTORY_SEPARATOR . $name, 0644);

    return ['ok' => true, 'name' => $name];
}

/**
 * Hapus file cover lama.
 */
function delete_cover_file(string $name): void
{
    // Cegah path traversal.
    if ($name === '' || str_contains($name, '/') || str_contains($name, '\\')) {
        return;
    }

    $path = rtrim((string) app_config('upload.dir'), '/\\') . DIRECTORY_SEPARATOR . $name;

    if (is_file($path)) {
        @unlink($path);
    }
}
