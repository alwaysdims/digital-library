<?php
/**
 * Handler pengelolaan akun: petugas (admin) dan anggota (petugas/admin).
 */

declare(strict_types=1);

/**
 * Jalankan halaman kelola anggota.
 *
 * @param string $basePath
 */
function handle_member_management(string $basePath): void
{
    if (is_post()) {
        csrf_verify();

        $action = (string) post('action', '');

        if ($action === 'create') {
            $name     = (string) post('name', '');
            $username = strtolower((string) post('username', ''));
            $email    = (string) post('email', '');
            $password = (string) ($_POST['password'] ?? '');

            $errors = validate_member_input($name, $username, $email, $password, null);

            if ($errors === []) {
                $id = user_create($name, $username, $password, 'user', $email);

                if ($id === null) {
                    flash('danger', 'Username sudah dipakai.');
                } else {
                    flash('success', 'Anggota "' . $name . '" berhasil didaftarkan. Sampaikan password awal kepada anggota dan minta segera menggantinya.');
                }
            } else {
                flash('danger', implode(' ', array_values($errors)));
            }

            redirect($basePath);
        }

        if ($action === 'update') {
            $id       = (int) post('id', '0');
            $member   = user_find($id);
            $name     = (string) post('name', '');
            $username = strtolower((string) post('username', ''));
            $email    = (string) post('email', '');
            $password = (string) ($_POST['password'] ?? '');
            $active   = (int) post('is_active', '1') === 1 ? 1 : 0;

            if ($member === null || $member['role'] !== 'user') {
                flash('danger', 'Anggota tidak ditemukan.');
                redirect($basePath);
            }

            $errors = validate_member_input($name, $username, $email, $password, $id, true);

            if ($errors === []) {
                $ok = user_update($id, $name, $username, 'user', $email, $password !== '' ? $password : null, $active);
                flash($ok ? 'success' : 'danger', $ok
                    ? 'Data anggota berhasil diperbarui.'
                    : 'Username sudah dipakai.');
            } else {
                flash('danger', implode(' ', array_values($errors)));
            }

            redirect($basePath);
        }

        if ($action === 'toggle') {
            $id     = (int) post('id', '0');
            $member = user_find($id);

            if ($member === null || $member['role'] !== 'user') {
                flash('danger', 'Anggota tidak ditemukan.');
            } else {
                user_set_active($id, (int) $member['is_active'] !== 1);
                flash('success', 'Status akun anggota diperbarui.');
            }

            redirect($basePath);
        }

        if ($action === 'delete') {
            $id     = (int) post('id', '0');
            $member = user_find($id);

            if ($member === null || $member['role'] !== 'user') {
                flash('danger', 'Anggota tidak ditemukan.');
            } elseif (user_delete($id)) {
                flash('success', 'Akun anggota berhasil dihapus.');
            } else {
                flash('warning', 'Anggota tidak dapat dihapus karena memiliki riwayat peminjaman. Nonaktifkan akun saja.');
            }

            redirect($basePath);
        }
    }

    $search = (string) (query('q') ?? '');
    $editId = (int) (query('edit') ?? 0);
    $editing = $editId > 0 ? user_find($editId) : null;

    if ($editId > 0 && ($editing === null || $editing['role'] !== 'user')) {
        flash('warning', 'Anggota tidak ditemukan.');
        redirect($basePath);
    }

    render('dashboard', 'shared/member_list', [
        'pageTitle' => 'Kelola Anggota',
        'heading'   => 'Kelola Anggota',
        'members'   => user_list_by_role('user', $search !== '' ? $search : null),
        'search'    => $search,
        'editing'   => $editing,
        'basePath'  => $basePath,
        'stats'     => book_stats(),
    ]);
}

/**
 * Validasi input anggota.
 *
 * @return array<string,string>
 */
function validate_member_input(string $name, string $username, string $email, string $password, ?int $exceptId, bool $isUpdate = false): array
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

/**
 * Simpan nomor telepon anggota bila kolom tersedia.
 * Skema MVP tidak menyediakan kolom phone; nilai diabaikan dengan aman.
 */
function store_member_phone(int $userId, string $phone): void
{
    // Tempat ekspansi di masa depan. Skema utama belum memuat kolom phone.
    unset($userId, $phone);
}
