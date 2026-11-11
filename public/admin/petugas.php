<?php
/**
 * Kelola petugas — admin.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_admin();

require_once __DIR__ . '/../../app/controllers/staff_controller.php';

handle_staff_management('admin/petugas.php');
