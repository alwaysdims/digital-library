<?php
/**
 * Manajemen sesi autentikasi.
 */

declare(strict_types=1);

/**
 * Mulai sesi dengan konfigurasi cookie yang aman.
 */
function app_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => $secure,
        'samesite' => 'Lax',
    ]);

    session_name('lentera_sid');
    session_start();

    global $appConfig;
    $timeout = (int) ($appConfig['session_timeout'] ?? 1800);
    if (isset($_SESSION['user'], $_SESSION['last_activity'])
        && time() - (int) $_SESSION['last_activity'] > $timeout) {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Simpan data user yang login ke sesi.
 */
function auth_login(array $user): void
{
    // Cegah session fixation.
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'       => (int) $user['id'],
        'name'     => $user['name'],
        'username' => $user['username'],
        'role'     => $user['role'],
    ];
    $_SESSION['last_activity'] = time();
}

/**
 * Hapus sesi autentikasi secara menyeluruh.
 */
function auth_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
    }

    session_destroy();
}

/**
 * Apakah ada user yang login?
 */
function auth_check(): bool
{
    return isset($_SESSION['user']['id']);
}

/**
 * Data user yang sedang login.
 */
function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Role user yang sedang login.
 */
function auth_role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

/**
 * ID user yang sedang login.
 */
function auth_id(): ?int
{
    return isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null;
}
