<?php
/**
 * Service user: autentikasi, pengelolaan petugas & anggota.
 */

declare(strict_types=1);

/**
 * Cari user berdasarkan username (tanpa memandang status aktif).
 */
function user_find_by_username(string $username): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    return $user ?: null;
}

/**
 * Ambil user berdasarkan id.
 */
function user_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    return $user ?: null;
}

/**
 * Verifikasi kredensial. Mengembalikan data user bila valid.
 */
function user_authenticate(string $username, string $password): ?array
{
    $user = user_find_by_username($username);

    if ($user === null) {
        return null;
    }

    if ((int) $user['is_active'] !== 1) {
        return null;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return null;
    }

    return $user;
}

/**
 * Daftar user berdasarkan role.
 */
function user_list_by_role(string $role, ?string $search = null): array
{
    $sql = 'SELECT * FROM users WHERE role = :role';

    if ($search !== null && $search !== '') {
        $sql .= ' AND (name LIKE :search_name OR username LIKE :search_username)';
    }

    $sql .= ' ORDER BY name ASC';

    $stmt = db()->prepare($sql);
    $params = [':role' => $role];

    if ($search !== null && $search !== '') {
        $params[':search_name']     = '%' . $search . '%';
        $params[':search_username'] = '%' . $search . '%';
    }

    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Apakah username sudah dipakai (opsional mengabaikan id tertentu).
 */
function username_exists(string $username, ?int $exceptId = null): bool
{
    if ($exceptId !== null) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE username = :username AND id <> :id');
        $stmt->execute([':username' => $username, ':id' => $exceptId]);
    } else {
        $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
        $stmt->execute([':username' => $username]);
    }

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Buat user baru (petugas atau anggota).
 */
function user_create(string $name, string $username, string $password, string $role, ?string $email = null): ?int
{
    if (username_exists($username)) {
        return null;
    }

    $stmt = db()->prepare(
        'INSERT INTO users (name, username, email, password_hash, role, is_active)
         VALUES (:name, :username, :email, :password_hash, :role, 1)'
    );

    $stmt->execute([
        ':name'          => $name,
        ':username'      => $username,
        ':email'         => $email !== '' ? $email : null,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ':role'          => $role,
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Perbarui data user. Password hanya diubah bila diisi.
 */
function user_update(int $id, string $name, string $username, string $role, ?string $email, ?string $password, ?int $isActive = null): bool
{
    if (username_exists($username, $id)) {
        return false;
    }

    $fields = [
        'name = :name',
        'username = :username',
        'email = :email',
    ];

    $params = [
        ':name'     => $name,
        ':username' => $username,
        ':email'    => $email !== '' ? $email : null,
        ':id'       => $id,
    ];

    if ($password !== null && $password !== '') {
        $fields[] = 'password_hash = :password_hash';
        $params[':password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if ($isActive !== null) {
        $fields[] = 'is_active = :is_active';
        $params[':is_active'] = $isActive;
    }

    $stmt = db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id');

    return $stmt->execute($params);
}

/**
 * Aktifkan / nonaktifkan user.
 */
function user_set_active(int $id, bool $active): bool
{
    $stmt = db()->prepare('UPDATE users SET is_active = :active WHERE id = :id');

    return $stmt->execute([':active' => $active ? 1 : 0, ':id' => $id]);
}

/**
 * Apakah user memiliki riwayat peminjaman?
 */
function user_has_history(int $id): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM borrowings WHERE user_id = :id');
    $stmt->execute([':id' => $id]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Hapus user bila belum memiliki riwayat peminjaman.
 */
function user_delete(int $id): bool
{
    if (user_has_history($id)) {
        return false;
    }

    $stmt = db()->prepare('DELETE FROM users WHERE id = :id AND role <> :admin');
    $stmt->execute([':id' => $id, ':admin' => 'admin']);

    return $stmt->rowCount() > 0;
}

/**
 * Jumlah user per role.
 */
function user_count_by_role(string $role): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
    $stmt->execute([':role' => $role]);

    return (int) $stmt->fetchColumn();
}

/**
 * Buat password awal yang aman dan mudah dibacakan petugas ke anggota.
 */
function generate_password(int $length = 8): string
{
    $chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max   = strlen($chars) - 1;
    $out   = '';

    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, $max)];
    }

    return $out;
}
