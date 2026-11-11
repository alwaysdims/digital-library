<?php
/**
 * Kelola anggota — admin.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_admin();

require_once __DIR__ . '/../../app/controllers/member_controller.php';

handle_member_management('admin/anggota.php');
