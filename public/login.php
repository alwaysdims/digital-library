<?php
/**
 * Halaman login untuk seluruh role.
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

// Sudah login? Arahkan ke dashboard sesuai role.
if (auth_check()) {
    redirect(dashboard_path_for((string) auth_role()));
}

$errors = [];
$username = '';

if (is_post()) {
    csrf_verify();

    $username = (string) post('username', '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = 'Username dan password wajib diisi.';
    } else {
        $user = user_authenticate($username, $password);

        if ($user === null) {
            $errors[] = 'Username atau password salah, atau akun tidak aktif.';
        } else {
            auth_login([
                'id'       => (int) $user['id'],
                'name'     => $user['name'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ]);

            flash('success', 'Selamat datang, ' . $user['name'] . '.');

            redirect(dashboard_path_for((string) $user['role']));
        }
    }
}

view('auth/login', [
    'pageTitle' => 'Masuk',
    'errors'    => $errors,
    'username'  => $username,
]);
