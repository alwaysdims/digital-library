<?php
/**
 * Handler peminjaman: pengajuan, persetujuan/penolakan, pengembalian, denda.
 */

declare(strict_types=1);

/** Aksi yang hanya mengubah status (approve/reject). */
const BORROWING_ACTIONS_PROCESS = ['approve' => true, 'reject' => true];
/** Aksi pengembalian & pelunasan denda. */
const BORROWING_ACTIONS_RETURN  = ['return' => true, 'pay_fine' => true];

/**
 * Proses aksi POST pada halaman peminjaman (menunggu -> dipinjam/ditolak).
 */
function handle_borrowing_actions(string $basePath): void
{
    if (!is_post()) {
        return;
    }

    csrf_verify();

    $action = (string) post('action', '');
    $id     = (int) post('id', '0');

    if ($id <= 0) {
        flash('danger', 'Data transaksi tidak valid.');
        redirect($basePath);
    }

    $staffId = (int) auth_id();

    if (isset(BORROWING_ACTIONS_PROCESS[$action])) {
        $result = $action === 'approve'
            ? borrowing_approve($id, $staffId)
            : borrowing_reject($id, $staffId);

        flash($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect($basePath);
    }

    if ($action === 'return') {
        $result = borrowing_return($id, $staffId);

        flash($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect($basePath);
    }

    if ($action === 'pay_fine') {
        $result = borrowing_mark_fine_paid($id, $staffId);

        flash($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect($basePath);
    }

    flash('danger', 'Aksi tidak dikenal.');
    redirect($basePath);
}

/**
 * Render halaman daftar pengajuan & peminjaman.
 */
function render_borrowing_page(string $basePath, string $mode): void
{
    $search = (string) (query('q') ?? '');
    $rows   = borrowing_list(null, $search !== '' ? $search : null, $mode);

    $heading = match ($mode) {
        'pending'  => 'Pengajuan Peminjaman',
        'active'   => 'Peminjaman Aktif',
        default    => 'Data Peminjaman',
    };

    render('dashboard', 'shared/borrowing_list', [
        'pageTitle' => $heading,
        'heading'   => $heading,
        'rows'      => $rows,
        'search'    => $search,
        'mode'      => $mode,
        'basePath'  => $basePath,
        'stats'     => book_stats(),
    ]);
}

/**
 * Render halaman pengembalian buku.
 */
function render_return_page(string $basePath): void
{
    $search = (string) (query('q') ?? '');
    $rows   = borrowing_list(null, $search !== '' ? $search : null, 'active');

    render('dashboard', 'shared/return_list', [
        'pageTitle' => 'Pengembalian Buku',
        'heading'   => 'Pengembalian Buku',
        'rows'      => $rows,
        'search'    => $search,
        'basePath'  => $basePath,
        'stats'     => book_stats(),
    ]);
}

/**
 * Render halaman denda.
 */
function render_fine_page(string $basePath): void
{
    $search = (string) (query('q') ?? '');
    $status = (string) (query('status') ?? 'belum_lunas');

    $where  = [];
    $params = [];

    if ($status === 'lunas') {
        $where[] = "br.fine_status = 'lunas'";
    } elseif ($status === 'belum_lunas') {
        $where[] = "br.fine_status = 'belum_lunas'";
    } else {
        $where[] = "br.fine_status IN ('belum_lunas', 'lunas')";
    }

    if ($search !== '') {
        $where[] = '(u.name LIKE :search_name OR b.title LIKE :search_title)';
        $params[':search_name']  = '%' . $search . '%';
        $params[':search_title'] = '%' . $search . '%';
    }

    $sql = 'SELECT br.*, u.name AS user_name, u.username, b.title AS book_title, b.author AS book_author
            FROM borrowings br
            JOIN users u ON u.id = br.user_id
            JOIN books b ON b.id = br.book_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY br.returned_at DESC, br.id DESC
            LIMIT 300';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $totalUnpaid = db()->query(
        "SELECT COALESCE(SUM(fine_amount), 0) FROM borrowings WHERE fine_status = 'belum_lunas'"
    )->fetchColumn();

    $totalPaid = db()->query(
        "SELECT COALESCE(SUM(fine_amount), 0) FROM borrowings WHERE fine_status = 'lunas'"
    )->fetchColumn();

    render('dashboard', 'shared/fine_list', [
        'pageTitle'   => 'Denda',
        'heading'     => 'Denda Keterlambatan',
        'rows'        => $rows,
        'search'      => $search,
        'status'      => $status,
        'totalUnpaid' => (int) $totalUnpaid,
        'totalPaid'   => (int) $totalPaid,
        'basePath'    => $basePath,
    ]);
}
