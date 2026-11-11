<?php
/**
 * Service kategori buku.
 */

declare(strict_types=1);

/**
 * Seluruh kategori beserta jumlah buku.
 */
function category_all(bool $withCounts = false): array
{
    if ($withCounts) {
        return db()->query(
            'SELECT c.id, c.name, COUNT(b.id) AS book_count
             FROM categories c
             LEFT JOIN books b ON b.category_id = c.id
             GROUP BY c.id, c.name
             ORDER BY c.name ASC'
        )->fetchAll();
    }

    return db()->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
}

/**
 * Ambil kategori berdasarkan id.
 */
function category_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, name FROM categories WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $category = $stmt->fetch();

    return $category ?: null;
}

/**
 * Buat kategori baru. Mengembalikan false bila nama sudah dipakai.
 */
function category_create(string $name): bool
{
    if (category_name_exists($name)) {
        return false;
    }

    $stmt = db()->prepare('INSERT INTO categories (name) VALUES (:name)');

    return $stmt->execute([':name' => $name]);
}

/**
 * Perbarui kategori.
 */
function category_update(int $id, string $name): bool
{
    if (category_name_exists($name, $id)) {
        return false;
    }

    $stmt = db()->prepare('UPDATE categories SET name = :name WHERE id = :id');

    return $stmt->execute([':name' => $name, ':id' => $id]);
}

/**
 * Hapus kategori bila belum dipakai buku.
 */
function category_delete(int $id): bool
{
    if (category_book_count($id) > 0) {
        return false;
    }

    $stmt = db()->prepare('DELETE FROM categories WHERE id = :id');

    return $stmt->execute([':id' => $id]);
}

/**
 * Jumlah buku dalam kategori.
 */
function category_book_count(int $id): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM books WHERE category_id = :id');
    $stmt->execute([':id' => $id]);

    return (int) $stmt->fetchColumn();
}

/**
 * Cek nama kategori (opsional mengabaikan id tertentu saat update).
 */
function category_name_exists(string $name, ?int $exceptId = null): bool
{
    if ($exceptId !== null) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM categories WHERE name = :name AND id <> :id');
        $stmt->execute([':name' => $name, ':id' => $exceptId]);
    } else {
        $stmt = db()->prepare('SELECT COUNT(*) FROM categories WHERE name = :name');
        $stmt->execute([':name' => $name]);
    }

    return (int) $stmt->fetchColumn() > 0;
}
