<?php
/**
 * Service buku: query katalog, CRUD, dan statistik stok.
 * Semua query memakai PDO prepared statements.
 */

declare(strict_types=1);

const BOOK_SORT_COLUMNS = [
    'terbaru'    => 'b.created_at DESC, b.id DESC',
    'judul'      => 'b.title ASC',
    'penulis'    => 'b.author ASC',
];

/**
 * Daftar buku dengan pencarian, filter kategori, sorting, dan pagination.
 *
 * @return array{items: array<int,array>, total: int, page: int, per_page: int, pages: int}
 */
function book_list(?string $search = null, ?int $categoryId = null, string $sort = 'terbaru', int $page = 1, int $perPage = 12): array
{
    $where  = [];
    $params = [];

    if ($search !== null && $search !== '') {
        $where[] = '(b.title LIKE :search_title OR b.author LIKE :search_author)';
        $params[':search_title']  = '%' . $search . '%';
        $params[':search_author'] = '%' . $search . '%';
    }

    if ($categoryId !== null && $categoryId > 0) {
        $where[] = 'b.category_id = :category';
        $params[':category'] = $categoryId;
    }

    $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

    $orderBy = BOOK_SORT_COLUMNS[$sort] ?? BOOK_SORT_COLUMNS['terbaru'];

    $countStmt = db()->prepare('SELECT COUNT(*) FROM books b' . $sqlWhere);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $perPage = max(1, min(48, $perPage));
    $pages   = max(1, (int) ceil($total / $perPage));
    $page    = max(1, min($pages, $page));
    $offset  = ($page - 1) * $perPage;

    $sql = 'SELECT b.*, c.name AS category_name
            FROM books b
            JOIN categories c ON c.id = b.category_id'
        . $sqlWhere
        . ' ORDER BY ' . $orderBy
        . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return [
        'items'    => $stmt->fetchAll(),
        'total'    => $total,
        'page'     => $page,
        'per_page' => $perPage,
        'pages'    => $pages,
    ];
}

/**
 * Buku unggulan/terbaru untuk beranda.
 */
function book_featured(int $limit = 8): array
{
    $limit = max(1, min(24, $limit));

    $stmt = db()->query(
        'SELECT b.*, c.name AS category_name
         FROM books b
         JOIN categories c ON c.id = b.category_id
         ORDER BY b.created_at DESC, b.id DESC
         LIMIT ' . $limit
    );

    return $stmt->fetchAll();
}

/**
 * Ambil satu buku berdasarkan id.
 */
function book_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT b.*, c.name AS category_name
         FROM books b
         JOIN categories c ON c.id = b.category_id
         WHERE b.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch();

    return $book ?: null;
}

/**
 * Buku terkait (kategori sama, selain buku ini).
 */
function book_related(int $bookId, int $categoryId, int $limit = 4): array
{
    $limit = max(1, min(12, $limit));

    $stmt = db()->prepare(
        'SELECT b.*, c.name AS category_name
         FROM books b
         JOIN categories c ON c.id = b.category_id
         WHERE b.category_id = :category AND b.id <> :id
         ORDER BY b.created_at DESC
         LIMIT ' . $limit
    );
    $stmt->execute([':category' => $categoryId, ':id' => $bookId]);

    return $stmt->fetchAll();
}

/**
 * Buat buku baru. Mengembalikan id buku.
 */
function book_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO books (category_id, title, author, published_year, synopsis, cover_path, stock_total, stock_available)
         VALUES (:category_id, :title, :author, :published_year, :synopsis, :cover_path, :stock_total, :stock_available)'
    );

    $stmt->execute([
        ':category_id'     => $data['category_id'],
        ':title'           => $data['title'],
        ':author'          => $data['author'],
        ':published_year'  => $data['published_year'],
        ':synopsis'        => $data['synopsis'],
        ':cover_path'      => $data['cover_path'],
        ':stock_total'     => $data['stock_total'],
        ':stock_available' => $data['stock_available'],
    ]);

    return (int) db()->lastInsertId();
}

/**
 * Perbarui buku. stock_available disesuaikan dengan selisih stok total.
 * Mengembalikan false bila buku tidak ditemukan atau stok tidak valid.
 */
function book_update(int $id, array $data): bool
{
    $existing = book_find($id);

    if ($existing === null) {
        return false;
    }

    $borrowedOut = (int) $existing['stock_total'] - (int) $existing['stock_available'];
    $newTotal    = (int) $data['stock_total'];

    // Stok total tidak boleh lebih kecil dari jumlah buku yang sedang di luar.
    if ($newTotal < $borrowedOut) {
        return false;
    }

    $newAvailable = $newTotal - $borrowedOut;

    $stmt = db()->prepare(
        'UPDATE books
         SET category_id = :category_id,
             title = :title,
             author = :author,
             published_year = :published_year,
             synopsis = :synopsis,
             cover_path = :cover_path,
             stock_total = :stock_total,
             stock_available = :stock_available
         WHERE id = :id'
    );

    return $stmt->execute([
        ':category_id'     => $data['category_id'],
        ':title'           => $data['title'],
        ':author'          => $data['author'],
        ':published_year'  => $data['published_year'],
        ':synopsis'        => $data['synopsis'],
        ':cover_path'      => $data['cover_path'],
        ':stock_total'     => $newTotal,
        ':stock_available' => $newAvailable,
        ':id'              => $id,
    ]);
}

/**
 * Apakah buku memiliki riwayat transaksi?
 */
function book_has_history(int $id): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM borrowings WHERE book_id = :id');
    $stmt->execute([':id' => $id]);

    return (int) $stmt->fetchColumn() > 0;
}

/**
 * Hapus buku secara permanen (hanya jika belum pernah dipinjam).
 * Mengembalikan false bila masih memiliki riwayat.
 */
function book_delete(int $id): bool
{
    $book = book_find($id);

    if ($book === null) {
        return false;
    }

    if (book_has_history($id)) {
        return false;
    }

    $stmt = db()->prepare('DELETE FROM books WHERE id = :id');
    $stmt->execute([':id' => $id]);

    if ($book['cover_path'] !== null && $book['cover_path'] !== '') {
        delete_cover_file((string) $book['cover_path']);
    }

    return true;
}

/**
 * Statistik sederhana untuk dashboard.
 *
 * @return array{total_books:int,total_titles:int,total_members:int,active_loans:int,late_loans:int,pending:int,unpaid_fines:int}
 */
function book_stats(): array
{
    $row = db()->query(
        "SELECT
            COALESCE(SUM(stock_total), 0) AS total_books,
            COUNT(*) AS total_titles
         FROM books"
    )->fetch();

    $members = db()->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
    $active  = db()->query("SELECT COUNT(*) FROM borrowings WHERE status = 'dipinjam'")->fetchColumn();
    $pending = db()->query("SELECT COUNT(*) FROM borrowings WHERE status = 'menunggu'")->fetchColumn();

    $unpaid = db()->query(
        "SELECT COALESCE(SUM(fine_amount), 0) FROM borrowings WHERE fine_status = 'belum_lunas'"
    )->fetchColumn();

    $late = db()->query(
        "SELECT COUNT(*) FROM borrowings
         WHERE status = 'dipinjam' AND due_at IS NOT NULL AND due_at < CURDATE()"
    )->fetchColumn();

    return [
        'total_books'   => (int) ($row['total_books'] ?? 0),
        'total_titles'  => (int) ($row['total_titles'] ?? 0),
        'total_members' => (int) $members,
        'active_loans'  => (int) $active,
        'late_loans'    => (int) $late,
        'pending'       => (int) $pending,
        'unpaid_fines'  => (int) $unpaid,
    ];
}
