<?php
/**
 * Helper umum: escaping output, URL, tanggal, denda, format rupiah.
 */

declare(strict_types=1);

/**
 * Escape output HTML untuk mencegah XSS.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Buat URL absolut terhadap base aplikasi.
 */
function url(string $path = ''): string
{
    $base = rtrim(app_base_url(), '/');
    $path = '/' . ltrim($path, '/');

    return $path === '/' ? ($base === '' ? '/' : $base . '/') : $base . $path;
}

/**
 * URL asset di dalam public/assets.
 */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/**
 * URL cover buku. Mengembalikan placeholder bila cover tidak ada.
 */
function cover_url(?string $coverPath): string
{
    if ($coverPath !== null && $coverPath !== '') {
        return url('uploads/covers/' . ltrim($coverPath, '/'));
    }

    return asset('images/cover-placeholder.svg');
}

/**
 * Base URL aplikasi, dihitung dari document root Apache.
 *
 * Document root diasumsikan folder public/. Semua entry point berada di
 * dalamnya (termasuk subfolder admin/, petugas/, user/), sehingga base
 * harus dihitung dari DOCUMENT_ROOT, bukan dari direktori script.
 */
function app_base_url(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $docRoot    = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));

    // Path fisik folder public/ relatif terhadap DOCUMENT_ROOT.
    $publicDir = str_replace('\\', '/', dirname(__DIR__, 2) . '/public');

    $base = '';

    if ($docRoot !== '' && str_starts_with($publicDir, $docRoot)) {
        // Path fisik folder public/ di bawah DOCUMENT_ROOT.
        $relative = rtrim(substr($publicDir, strlen($docRoot)), '/');

        // Kasus khusus: DOCUMENT_ROOT adalah root proyek dan folder public/
        // adalah SATU-SATUNYA subfolder langsung di bawahnya. Dalam mode ini
        // .htaccess root memetakan URL bersih (tanpa /public) ke public/,
        // sehingga base URL aplikasi harus kosong (''), bukan '/public'.
        if (preg_match('#^/public$#', $relative) === 1) {
            $base = '';
        } else {
            $base = $relative;
        }
    } else {
        // Fallback: bila DOCUMENT_ROOT tidak selaras, gunakan direktori
        // entry point kecuali entry point berada pada subfolder peran.
        $scriptDir = rtrim(dirname($scriptName), '/');

        if (preg_match('#/(admin|petugas|user)$#', $scriptDir) === 1) {
            $scriptDir = rtrim(dirname($scriptDir), '/');
        }

        $base = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') ? '' : $scriptDir;
    }

    return $base;
}

/**
 * Redirect ke path internal lalu hentikan eksekusi.
 */
function redirect(string $path): never
{
    $target = preg_match('#^https?://#i', $path) === 1 ? $path : url($path);
    header('Location: ' . $target);
    exit;
}

/**
 * Ambil nilai POST yang sudah di-trim.
 */
function post(string $key, ?string $default = null): ?string
{
    $value = $_POST[$key] ?? $default;

    return is_string($value) ? trim($value) : $value;
}

/**
 * Ambil nilai GET yang sudah di-trim.
 */
function query(string $key, ?string $default = null): ?string
{
    $value = $_GET[$key] ?? $default;

    return is_string($value) ? trim($value) : $value;
}

/**
 * Simpan pesan flash untuk request berikutnya.
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Ambil dan hapus seluruh pesan flash.
 */
function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $flashes;
}

/**
 * Apakah request bertipe POST?
 */
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/**
 * Format angka menjadi rupiah tanpa desimal.
 */
function rupiah(int|float|string|null $amount): string
{
    return 'Rp' . number_format((float) $amount, 0, ',', '.');
}

/**
 * Format tanggal ISO (Y-m-d) menjadi format Indonesia.
 */
function tanggal_id(?string $date, string $format = 'd M Y'): string
{
    if ($date === null || $date === '' || $date === '0000-00-00') {
        return '-';
    }

    $ts = strtotime($date);

    return $ts === false ? '-' : date($format, $ts);
}

/**
 * Hitung jumlah hari terlambat berdasarkan tanggal jatuh tempo.
 * Mengembalikan 0 bila tepat waktu atau belum jatuh tempo.
 */
function hari_terlambat(?string $dueAt, ?string $returnAt = null): int
{
    if ($dueAt === null || $dueAt === '') {
        return 0;
    }

    // Bandingkan tanggal kalender lokal, bukan durasi jam.
    $due  = new DateTimeImmutable(substr($dueAt, 0, 10) . ' 00:00:00');
    $back = $returnAt !== null && $returnAt !== ''
        ? new DateTimeImmutable(substr($returnAt, 0, 10) . ' 00:00:00')
        : new DateTimeImmutable('today');

    if ($back <= $due) {
        return 0;
    }

    return (int) $due->diff($back)->days;
}

/**
 * Hitung nominal denda dari jumlah hari terlambat.
 */
function hitung_denda(int $hariTerlambat): int
{
    return max(0, $hariTerlambat) * (int) app_config('fine_per_day', 1000);
}

/**
 * Label status transaksi dalam bahasa Indonesia.
 */
function label_status(string $status): string
{
    return match ($status) {
        'menunggu'     => 'Menunggu',
        'ditolak'      => 'Ditolak',
        'dipinjam'     => 'Dipinjam',
        'dikembalikan' => 'Dikembalikan',
        default        => ucfirst($status),
    };
}

/**
 * Kelas badge Bootstrap untuk status transaksi.
 */
function badge_status(string $status): string
{
    return match ($status) {
        'menunggu'     => 'badge-clay badge-warning',
        'ditolak'      => 'badge-clay badge-danger',
        'dipinjam'     => 'badge-clay badge-info',
        'dikembalikan' => 'badge-clay badge-success',
        default        => 'badge-clay badge-muted',
    };
}

/**
 * Kelas badge untuk status denda.
 */
function badge_denda(string $fineStatus): string
{
    return match ($fineStatus) {
        'lunas'       => 'badge-clay badge-success',
        'belum_lunas' => 'badge-clay badge-danger',
        default       => 'badge-clay badge-muted',
    };
}

/**
 * Label status denda.
 */
function label_denda(string $fineStatus): string
{
    return match ($fineStatus) {
        'lunas'       => 'Lunas',
        'belum_lunas' => 'Belum Lunas',
        default       => 'Tidak Ada',
    };
}
