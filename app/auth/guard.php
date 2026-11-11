<?php
/**
 * Guard akses berbasis login dan role.
 */

declare(strict_types=1);

/**
 * Wajib login; arahkan ke halaman login bila belum.
 */
function require_login(): void
{
    if (!auth_check()) {
        flash('warning', 'Silakan masuk terlebih dahulu untuk melanjutkan.');
        redirect('login.php');
    }

    $sessionUser = auth_user();
    $currentUser = user_find((int) ($sessionUser['id'] ?? 0));

    if ($currentUser === null
        || (int) $currentUser['is_active'] !== 1
        || $currentUser['role'] !== ($sessionUser['role'] ?? null)) {
        auth_logout();
        app_start_session();
        flash('warning', 'Sesi berakhir karena status akun atau hak akses berubah.');
        redirect('login.php');
    }
}

/**
 * Wajib memiliki salah satu role yang diizinkan.
 */
function require_role(string ...$roles): void
{
    require_login();

    if (!in_array(auth_role(), $roles, true)) {
        http_response_code(403);
        render('dashboard', 'errors/403', [
            'pageTitle' => 'Akses Ditolak',
            'heading'   => 'Akses Ditolak',
        ]);
        exit;
    }
}

/**
 * Wajib admin.
 */
function require_admin(): void
{
    require_role('admin');
}

/**
 * Wajib admin atau petugas.
 */
function require_staff(): void
{
    require_role('admin', 'petugas');
}

/**
 * Wajib anggota (user).
 */
function require_user(): void
{
    require_role('user');
}

/**
 * Halaman dashboard default sesuai role.
 */
function dashboard_path_for(string $role): string
{
    return match ($role) {
        'admin'   => 'admin/dashboard.php',
        'petugas' => 'petugas/dashboard.php',
        'user'    => 'user/dashboard.php',
        default   => 'login.php',
    };
}
