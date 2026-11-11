<?php
/**
 * Dashboard anggota: pinjaman aktif, jatuh tempo, denda, status pengajuan.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_user();

$userId  = (int) auth_id();
$summary = borrowing_summary_for_user($userId);
$all     = borrowing_list_for_user($userId);

$pending = array_values(array_filter($all, static fn ($r) => $r['status'] === 'menunggu'));

render('dashboard', 'user/dashboard', [
    'pageTitle' => 'Dashboard',
    'heading'   => 'Dashboard Anggota',
    'summary'   => $summary,
    'pending'   => $pending,
    'estimate'  => borrowing_estimated_fine_for_user($userId),
]);
