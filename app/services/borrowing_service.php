<?php
/**
 * Service peminjaman: pengajuan, persetujuan, penolakan, pengembalian,
 * perhitungan denda, dan pelunasan.
 *
 * Semua aksi yang mengubah stok atau status memakai database transaction
 * dan row locking (SELECT ... FOR UPDATE) agar klik ganda tidak
 * menggandakan transaksi atau membuat stok negatif.
 */

declare(strict_types=1);

/**
 * Ajukan peminjaman (status menunggu). Satu pengajuan untuk satu buku.
 *
 * @return array{ok:bool,message:string,id?:int}
 */
function borrowing_request(int $userId, int $bookId): array
{
    $book = book_find($bookId);

    if ($book === null) {
        return ['ok' => false, 'message' => 'Buku tidak ditemukan.'];
    }

    $pdo = db();
    $pdo->beginTransaction();

    try {
        // Buku dikunci agar pemeriksaan tetap konsisten.
        $stmt = $pdo->prepare('SELECT id, stock_available FROM books WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $bookId]);
        $locked = $stmt->fetch();

        if (!$locked) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Buku tidak ditemukan.'];
        }

        if ((int) $locked['stock_available'] <= 0) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Stok buku sedang tidak tersedia.'];
        }

        // Cegah pengajuan ganda untuk buku yang sama.
        $dup = $pdo->prepare(
            "SELECT COUNT(*) FROM borrowings
             WHERE user_id = :user AND book_id = :book
               AND status IN ('menunggu', 'dipinjam')"
        );
        $dup->execute([':user' => $userId, ':book' => $bookId]);

        if ((int) $dup->fetchColumn() > 0) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Anda sudah memiliki pengajuan atau pinjaman aktif untuk buku ini.'];
        }

        // Batas pinjaman aktif (dipinjam + menunggu) demi kejelasan bagi anggota.
        $max = (int) app_config('max_active_loans', 3);
        $active = $pdo->prepare(
            "SELECT COUNT(*) FROM borrowings
             WHERE user_id = :user AND status IN ('menunggu', 'dipinjam')"
        );
        $active->execute([':user' => $userId]);

        if ((int) $active->fetchColumn() >= $max) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => "Batas maksimal {$max} buku aktif telah tercapai."];
        }

        $insert = $pdo->prepare(
            "INSERT INTO borrowings (user_id, book_id, status, requested_at)
             VALUES (:user, :book, 'menunggu', NOW())"
        );
        $insert->execute([':user' => $userId, ':book' => $bookId]);

        $id = (int) $pdo->lastInsertId();

        $pdo->commit();

        return ['ok' => true, 'message' => 'Pengajuan peminjaman berhasil dikirim. Menunggu persetujuan petugas.', 'id' => $id];
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('borrowing_request failed: ' . $e->getMessage());

        return ['ok' => false, 'message' => 'Pengajuan gagal diproses. Silakan coba lagi.'];
    }
}

/**
 * Setujui pengajuan: kurangi stok, catat tanggal pinjam & jatuh tempo.
 * Batas pinjaman aktif diperiksa ulang saat persetujuan.
 *
 * @return array{ok:bool,message:string}
 */
function borrowing_approve(int $borrowingId, int $staffId): array
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT * FROM borrowings WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $borrowingId]);
        $borrowing = $stmt->fetch();

        if (!$borrowing) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Data pengajuan tidak ditemukan.'];
        }

        if ($borrowing['status'] !== 'menunggu') {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Pengajuan ini sudah diproses sebelumnya.'];
        }

        $bookStmt = $pdo->prepare('SELECT * FROM books WHERE id = :id FOR UPDATE');
        $bookStmt->execute([':id' => $borrowing['book_id']]);
        $book = $bookStmt->fetch();

        if (!$book) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Buku tidak ditemukan.'];
        }

        if ((int) $book['stock_available'] <= 0) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Stok buku habis. Pengajuan sebaiknya ditolak.'];
        }

        // Periksa ulang batas pinjaman aktif saat persetujuan.
        $max = (int) app_config('max_active_loans', 3);
        $activeStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM borrowings
             WHERE user_id = :user AND status = 'dipinjam'"
        );
        $activeStmt->execute([':user' => $borrowing['user_id']]);

        if ((int) $activeStmt->fetchColumn() >= $max) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => "Anggota telah mencapai batas {$max} buku aktif. Pengajuan sebaiknya ditolak."];
        }

        $loanDays = (int) app_config('loan_days', 7);

        $update = $pdo->prepare(
            "UPDATE borrowings
             SET status = 'dipinjam',
                 borrowed_at = CURDATE(),
                 due_at = DATE_ADD(CURDATE(), INTERVAL :days DAY),
                 processed_by = :staff
             WHERE id = :id AND status = 'menunggu'"
        );
        $update->bindValue(':days', $loanDays, PDO::PARAM_INT);
        $update->bindValue(':staff', $staffId, PDO::PARAM_INT);
        $update->bindValue(':id', $borrowingId, PDO::PARAM_INT);
        $update->execute();

        if ($update->rowCount() === 0) {
            // Sudah diproses oleh request lain.
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Pengajuan ini sudah diproses sebelumnya.'];
        }

        $stock = $pdo->prepare(
            'UPDATE books SET stock_available = stock_available - 1
             WHERE id = :id AND stock_available > 0'
        );
        $stock->execute([':id' => $borrowing['book_id']]);

        if ($stock->rowCount() === 0) {
            // Stok berubah di antara pemeriksaan dan update; batalkan semua.
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Stok buku habis. Pengajuan sebaiknya ditolak.'];
        }

        $pdo->commit();

        return ['ok' => true, 'message' => 'Pengajuan disetujui. Buku tercatat sebagai dipinjam selama ' . $loanDays . ' hari.'];
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('borrowing_approve failed: ' . $e->getMessage());

        return ['ok' => false, 'message' => 'Persetujuan gagal diproses. Silakan coba lagi.'];
    }
}

/**
 * Tolak pengajuan. Stok tidak berubah.
 *
 * @return array{ok:bool,message:string}
 */
function borrowing_reject(int $borrowingId, int $staffId): array
{
    $stmt = db()->prepare(
        "UPDATE borrowings
         SET status = 'ditolak', processed_by = :staff
         WHERE id = :id AND status = 'menunggu'"
    );
    $stmt->execute([':staff' => $staffId, ':id' => $borrowingId]);

    if ($stmt->rowCount() === 0) {
        return ['ok' => false, 'message' => 'Pengajuan tidak ditemukan atau sudah diproses.'];
    }

    return ['ok' => true, 'message' => 'Pengajuan telah ditolak.'];
}

/**
 * Catat pengembalian: tambah stok, hitung denda final, ubah status.
 *
 * @return array{ok:bool,message:string,fine?:int}
 */
function borrowing_return(int $borrowingId, int $staffId): array
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare('SELECT * FROM borrowings WHERE id = :id FOR UPDATE');
        $stmt->execute([':id' => $borrowingId]);
        $borrowing = $stmt->fetch();

        if (!$borrowing) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Data peminjaman tidak ditemukan.'];
        }

        if ($borrowing['status'] !== 'dipinjam') {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Transaksi ini tidak dalam status dipinjam atau sudah dikembalikan.'];
        }

        // Kunci baris buku sebelum menambah stok.
        $bookStmt = $pdo->prepare('SELECT id FROM books WHERE id = :id FOR UPDATE');
        $bookStmt->execute([':id' => $borrowing['book_id']]);

        if (!$bookStmt->fetch()) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Buku tidak ditemukan.'];
        }

        // Denda dihitung berdasarkan tanggal pengembalian sebenarnya.
        $lateDays = hari_terlambat($borrowing['due_at'], date('Y-m-d'));
        $fine     = hitung_denda($lateDays);

        $fineStatus = $fine > 0 ? 'belum_lunas' : 'tidak_ada';

        $update = $pdo->prepare(
            "UPDATE borrowings
             SET status = 'dikembalikan',
                 returned_at = CURDATE(),
                 fine_amount = :fine,
                 fine_status = :fine_status,
                 processed_by = :staff
             WHERE id = :id AND status = 'dipinjam'"
        );
        $update->execute([
            ':fine'        => $fine,
            ':fine_status' => $fineStatus,
            ':staff'       => $staffId,
            ':id'          => $borrowingId,
        ]);

        if ($update->rowCount() === 0) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Transaksi ini sudah dikembalikan sebelumnya.'];
        }

        // Stok tidak boleh melebihi total.
        $stock = $pdo->prepare(
            'UPDATE books
             SET stock_available = stock_available + 1
             WHERE id = :id AND stock_available < stock_total'
        );
        $stock->execute([':id' => $borrowing['book_id']]);

        if ($stock->rowCount() === 0) {
            $pdo->rollBack();

            return ['ok' => false, 'message' => 'Stok buku tidak konsisten. Pengembalian dibatalkan.'];
        }

        $pdo->commit();

        $message = $fine > 0
            ? 'Buku dikembalikan. Terlambat ' . $lateDays . ' hari, denda ' . rupiah($fine) . ' (belum lunas).'
            : 'Buku dikembalikan tepat waktu. Tidak ada denda.';

        return ['ok' => true, 'message' => $message, 'fine' => $fine];
    } catch (Throwable $e) {
        $pdo->rollBack();
        error_log('borrowing_return failed: ' . $e->getMessage());

        return ['ok' => false, 'message' => 'Pengembalian gagal diproses. Silakan coba lagi.'];
    }
}

/**
 * Tandai denda sebagai lunas.
 *
 * @return array{ok:bool,message:string}
 */
function borrowing_mark_fine_paid(int $borrowingId, int $staffId): array
{
    $stmt = db()->prepare(
        "UPDATE borrowings
         SET fine_status = 'lunas', fine_paid_at = NOW(), processed_by = :staff
         WHERE id = :id AND status = 'dikembalikan' AND fine_status = 'belum_lunas'"
    );
    $stmt->execute([':staff' => $staffId, ':id' => $borrowingId]);

    if ($stmt->rowCount() === 0) {
        return ['ok' => false, 'message' => 'Denda tidak ditemukan atau sudah lunas.'];
    }

    return ['ok' => true, 'message' => 'Denda ditandai lunas.'];
}

/**
 * Ambil transaksi berdasarkan id beserta data anggota dan buku.
 */
function borrowing_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT br.*, u.name AS user_name, u.username,
                b.title AS book_title, b.author AS book_author, b.cover_path
         FROM borrowings br
         JOIN users u ON u.id = br.user_id
         JOIN books b ON b.id = br.book_id
         WHERE br.id = :id'
    );
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

/**
 * Daftar transaksi dengan filter status (untuk admin/petugas).
 */
function borrowing_list(?string $status = null, ?string $search = null, string $mode = 'all', int $limit = 200): array
{
    $where  = [];
    $params = [];

    if ($status !== null && $status !== '' && $status !== 'all') {
        $where[] = 'br.status = :status';
        $params[':status'] = $status;
    }

    if ($mode === 'pending') {
        $where[] = "br.status = 'menunggu'";
    } elseif ($mode === 'active') {
        $where[] = "br.status = 'dipinjam'";
    } elseif ($mode === 'returned') {
        $where[] = "br.status = 'dikembalikan'";
    } elseif ($mode === 'fines') {
        $where[] = "br.fine_status = 'belum_lunas'";
    } elseif ($mode === 'late') {
        $where[] = "br.status = 'dipinjam' AND br.due_at < CURDATE()";
    }

    if ($search !== null && $search !== '') {
        $where[] = '(u.name LIKE :search_name OR u.username LIKE :search_username OR b.title LIKE :search_title)';
        $params[':search_name']     = '%' . $search . '%';
        $params[':search_username'] = '%' . $search . '%';
        $params[':search_title']    = '%' . $search . '%';
    }

    $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);

    $limit = max(1, min(500, $limit));

    $sql = 'SELECT br.*, u.name AS user_name, u.username,
                   b.title AS book_title, b.author AS book_author
            FROM borrowings br
            JOIN users u ON u.id = br.user_id
            JOIN books b ON b.id = br.book_id'
        . $sqlWhere
        . ' ORDER BY br.requested_at DESC, br.id DESC'
        . ' LIMIT ' . $limit;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Riwayat peminjaman milik satu anggota.
 */
function borrowing_list_for_user(int $userId, ?string $status = null): array
{
    $sql = 'SELECT br.*, b.title AS book_title, b.author AS book_author, b.cover_path
            FROM borrowings br
            JOIN books b ON b.id = br.book_id
            WHERE br.user_id = :user';

    $params = [':user' => $userId];

    if ($status !== null && $status !== '') {
        $sql .= ' AND br.status = :status';
        $params[':status'] = $status;
    }

    $sql .= ' ORDER BY br.requested_at DESC, br.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Ringkasan pinjaman milik anggota untuk dashboard.
 *
 * @return array{active:array,late:array,fines:array}
 */
function borrowing_summary_for_user(int $userId): array
{
    $all = borrowing_list_for_user($userId);

    $active = array_values(array_filter($all, static fn ($r) => $r['status'] === 'dipinjam'));
    $late   = array_values(array_filter(
        $active,
        static fn ($r) => hari_terlambat($r['due_at']) > 0
    ));
    $fines  = array_values(array_filter(
        $all,
        static fn ($r) => $r['fine_status'] === 'belum_lunas' && (float) $r['fine_amount'] > 0
    ));

    return ['active' => $active, 'late' => $late, 'fines' => $fines];
}

/**
 * Estimasi total denda berjalan untuk anggota (belum final).
 */
function borrowing_estimated_fine_for_user(int $userId): int
{
    $summary = borrowing_summary_for_user($userId);

    $total = 0;

    foreach ($summary['active'] as $row) {
        $total += hitung_denda(hari_terlambat($row['due_at']));
    }

    return $total;
}

/**
 * Transaksi terbaru untuk dashboard.
 */
function borrowing_recent(int $limit = 8): array
{
    $limit = max(1, min(50, $limit));

    $stmt = db()->query(
        'SELECT br.*, u.name AS user_name, u.username,
                b.title AS book_title, b.author AS book_author
         FROM borrowings br
         JOIN users u ON u.id = br.user_id
         JOIN books b ON b.id = br.book_id
         ORDER BY br.requested_at DESC, br.id DESC
         LIMIT ' . $limit
    );

    return $stmt->fetchAll();
}
