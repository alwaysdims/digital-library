<?php
/**
 * Kelola anggota — petugas.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_staff();

require_once __DIR__ . '/../../app/controllers/member_controller.php';

handle_member_management('petugas/anggota.php');
