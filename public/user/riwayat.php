<?php
/**
 * Riwayat peminjaman — anggota.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_user();

$userId = (int) auth_id();
$all    = borrowing_list_for_user($userId);

render('dashboard', 'user/riwayat', [
    'pageTitle' => 'Riwayat Peminjaman',
    'heading'   => 'Riwayat Peminjaman',
    'rows'      => $all,
]);
