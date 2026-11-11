<?php
/**
 * Dashboard admin: ringkasan statistik dan transaksi terbaru.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_admin();

$stats  = book_stats();
$recent = borrowing_recent(8);
$staff  = user_count_by_role('petugas');

render('dashboard', 'admin/dashboard', [
    'pageTitle' => 'Dashboard',
    'heading'   => 'Dashboard Admin',
    'stats'     => $stats,
    'recent'    => $recent,
    'staff'     => $staff,
]);
