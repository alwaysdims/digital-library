<?php
/**
 * Data peminjaman — admin.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_admin();

require_once __DIR__ . '/../../app/controllers/borrowing_controller.php';

handle_borrowing_actions('admin/peminjaman.php');
render_borrowing_page('admin/peminjaman.php', 'all');
