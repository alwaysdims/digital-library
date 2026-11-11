<?php
/**
 * Denda — petugas.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_staff();

require_once __DIR__ . '/../../app/controllers/borrowing_controller.php';

handle_borrowing_actions('petugas/denda.php');
render_fine_page('petugas/denda.php');
