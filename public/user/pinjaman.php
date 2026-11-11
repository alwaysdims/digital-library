<?php
/**
 * Pinjaman saya — anggota.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_user();

$userId = (int) auth_id();

$summary = borrowing_summary_for_user($userId);
$pending = array_values(array_filter(
    borrowing_list_for_user($userId),
    static fn ($r) => $r['status'] === 'menunggu'
));

render('dashboard', 'user/pinjaman', [
    'pageTitle' => 'Pinjaman Saya',
    'heading'   => 'Pinjaman Saya',
    'summary'   => $summary,
    'pending'   => $pending,
    'estimate'  => borrowing_estimated_fine_for_user($userId),
]);
