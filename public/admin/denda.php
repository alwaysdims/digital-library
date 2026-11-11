<?php
/**
 * Data denda — admin.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_admin();

require_once __DIR__ . '/../../app/controllers/borrowing_controller.php';

handle_borrowing_actions('admin/denda.php');
render_fine_page('admin/denda.php');
