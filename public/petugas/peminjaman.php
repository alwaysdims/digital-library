<?php
/**
 * Pengajuan & peminjaman — petugas.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_staff();

require_once __DIR__ . '/../../app/controllers/borrowing_controller.php';

$mode = (string) (query('mode') ?? 'all');
$mode = in_array($mode, ['all', 'pending', 'active'], true) ? $mode : 'all';

handle_borrowing_actions('petugas/peminjaman.php');
render_borrowing_page('petugas/peminjaman.php', $mode);
