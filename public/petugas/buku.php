<?php
/**
 * Kelola buku — petugas.
 */

declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

require_staff();

require_once __DIR__ . '/../../app/controllers/book_controller.php';

handle_book_management('petugas/buku.php');
