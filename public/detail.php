<?php
/**
 * Detail buku publik + pengajuan peminjaman oleh anggota.
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$bookId = (int) (query('id') ?? 0);

if ($bookId <= 0) {
    flash('warning', 'Buku yang Anda cari tidak ditemukan.');
    redirect('katalog.php');
}

$book = book_find($bookId);

if ($book === null) {
    http_response_code(404);
    flash('warning', 'Buku yang Anda cari tidak ditemukan.');
    redirect('katalog.php');
}

$error = null;

// Proses pengajuan peminjaman.
if (is_post()) {
    csrf_verify();

    if (!auth_check()) {
        flash('warning', 'Silakan masuk terlebih dahulu untuk mengajukan peminjaman.');
        redirect('login.php');
    }

    if (auth_role() !== 'user') {
        flash('warning', 'Hanya anggota yang dapat mengajukan peminjaman.');
        redirect('detail.php?id=' . $bookId);
    }

    $result = borrowing_request((int) auth_id(), $bookId);

    flash($result['ok'] ? 'success' : 'danger', $result['message']);

    redirect('detail.php?id=' . $bookId);
}

// Status pengajuan anggota untuk buku ini (bila login sebagai anggota).
$myBorrowing = null;

if (auth_role() === 'user') {
    $stmt = db()->prepare(
        "SELECT id, status, due_at FROM borrowings
         WHERE user_id = :user AND book_id = :book
           AND status IN ('menunggu', 'dipinjam')
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->execute([':user' => auth_id(), ':book' => $bookId]);
    $myBorrowing = $stmt->fetch() ?: null;
}

$related = book_related($bookId, (int) $book['category_id'], 4);

render('public', 'public/detail', [
    'pageTitle'   => $book['title'],
    'book'        => $book,
    'related'     => $related,
    'myBorrowing' => $myBorrowing,
    'error'       => $error,
]);
