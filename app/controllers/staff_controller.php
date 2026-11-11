<?php
/**
 * Handler pengelolaan akun petugas (khusus admin).
 */

declare(strict_types=1);

/**
 * Jalankan halaman kelola petugas.
 */
function handle_staff_management(string $basePath): void
{
    if (is_post()) {
        csrf_verify();

        $action = (string) post('action', '');

        if ($action === 'create') {
            $name     = (string) post('name', '');
            $username = strtolower((string) post('username', ''));
            $email    = (string) post('email', '');
            $password = (string) ($_POST['password'] ?? '');

            $errors = validate_staff_input($name, $username, $email, $password, null);

            if ($errors === []) {
                $id = user_create($name, $username, $password, 'petugas', $email);

                flash($id !== null ? 'success' : 'danger', $id !== null
                    ? 'Akun petugas "' . $name . '" berhasil dibuat.'
                    : 'Username sudah dipakai.');
            } else {
                flash('danger', implode(' ', array_values($errors)));
            }

            redirect($basePath);
        }

        if ($action === 'update') {
            $id       = (int) post('id', '0');
            $staff    = user_find($id);
            $name     = (string) post('name', '');
            $username = strtolower((string) post('username', ''));
            $email    = (string) post('email', '');
            $password = (string) ($_POST['password'] ?? '');
            $active   = (int) post('is_active', '1') === 1 ? 1 : 0;

            if ($staff === null || $staff['role'] !== 'petugas') {
                flash('danger', 'Akun petugas tidak ditemukan.');
                redirect($basePath);
            }

            $errors = validate_staff_input($name, $username, $email, $password, $id, true);

            if ($errors === []) {
                $ok = user_update($id, $name, $username, 'petugas', $email, $password !== '' ? $password : null, $active);
                flash($ok ? 'success' : 'danger', $ok
                    ? 'Data petugas berhasil diperbarui.'
                    : 'Username sudah dipakai.');
            } else {
                flash('danger', implode(' ', array_values($errors)));
            }

            redirect($basePath);
        }

        if ($action === 'toggle') {
            $id    = (int) post('id', '0');
            $staff = user_find($id);

            if ($staff === null || $staff['role'] !== 'petugas') {
                flash('danger', 'Akun petugas tidak ditemukan.');
            } else {
                user_set_active($id, (int) $staff['is_active'] !== 1);
                flash('success', 'Status akun petugas diperbarui.');
            }

            redirect($basePath);
        }
    }

    $editId  = (int) (query('edit') ?? 0);
    $editing = $editId > 0 ? user_find($editId) : null;

    if ($editId > 0 && ($editing === null || $editing['role'] !== 'petugas')) {
        flash('warning', 'Akun petugas tidak ditemukan.');
        redirect($basePath);
    }

    render('dashboard', 'shared/staff_list', [
        'pageTitle' => 'Kelola Petugas',
        'heading'   => 'Kelola Petugas',
        'staff'     => user_list_by_role('petugas'),
        'editing'   => $editing,
        'basePath'  => $basePath,
    ]);
}

/**
 * Validasi input petugas.
 *
 * @return array<string,string>
 */
function validate_staff_input(string $name, string $username, string $email, string $password, ?int $exceptId, bool $isUpdate = false): array
{
    $errors = [];

    if ($name === '' || mb_strlen($name) > 120) {
        $errors['name'] = 'Nama lengkap wajib diisi (maksimal 120 karakter).';
    }

    if (!preg_match('/^[a-z0-9._]{3,60}$/', $username)) {
        $errors['username'] = 'Username 3–60 karakter, hanya huruf kecil, angka, titik, atau garis bawah.';
    } elseif (username_exists($username, $exceptId)) {
        $errors['username'] = 'Username sudah dipakai.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Format email tidak valid.';
    }

    if (!$isUpdate || $password !== '') {
        if (strlen($password) < 6) {
            $errors['password'] = 'Password minimal 6 karakter.';
        }
    }

    return $errors;
}
