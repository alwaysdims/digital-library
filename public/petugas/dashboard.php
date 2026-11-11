<?php
/**
 * Dashboard petugas: ringkasan pengajuan, pinjaman, keterlambatan, denda.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_staff();

$stats  = book_stats();
$recent = borrowing_recent(8);

render('dashboard', 'petugas/dashboard', [
    'pageTitle' => 'Dashboard',
    'heading'   => 'Dashboard Petugas',
    'stats'     => $stats,
    'recent'    => $recent,
]);
