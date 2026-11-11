<?php
/**
 * Definisi menu sidebar per role.
 * Mengembalikan array item menu.
 */

declare(strict_types=1);

$role = auth_role();

$menus = [
    'admin' => [
        ['label' => 'Dashboard',   'path' => 'admin/dashboard.php',   'icon' => 'bi-speedometer2'],
        ['label' => 'Buku',        'path' => 'admin/buku.php',        'icon' => 'bi-journal-bookmark'],
        ['label' => 'Kategori',    'path' => 'admin/kategori.php',    'icon' => 'bi-tags'],
        ['label' => 'Petugas',     'path' => 'admin/petugas.php',     'icon' => 'bi-person-badge'],
        ['label' => 'Anggota',     'path' => 'admin/anggota.php',     'icon' => 'bi-people'],
        ['label' => 'Peminjaman',  'path' => 'admin/peminjaman.php',  'icon' => 'bi-arrow-left-right'],
        ['label' => 'Denda',       'path' => 'admin/denda.php',       'icon' => 'bi-cash-coin'],
    ],
    'petugas' => [
        ['label' => 'Dashboard',     'path' => 'petugas/dashboard.php',    'icon' => 'bi-speedometer2'],
        ['label' => 'Buku',          'path' => 'petugas/buku.php',         'icon' => 'bi-journal-bookmark'],
        ['label' => 'Anggota',       'path' => 'petugas/anggota.php',      'icon' => 'bi-people'],
        ['label' => 'Peminjaman',    'path' => 'petugas/peminjaman.php',   'icon' => 'bi-clipboard-check'],
        ['label' => 'Pengembalian',  'path' => 'petugas/pengembalian.php', 'icon' => 'bi-arrow-return-left'],
        ['label' => 'Denda',         'path' => 'petugas/denda.php',        'icon' => 'bi-cash-coin'],
    ],
    'user' => [
        ['label' => 'Dashboard',      'path' => 'user/dashboard.php',  'icon' => 'bi-speedometer2'],
        ['label' => 'Katalog Buku',   'path' => 'katalog.php',         'icon' => 'bi-search'],
        ['label' => 'Pinjaman Saya',  'path' => 'user/pinjaman.php',   'icon' => 'bi-book'],
        ['label' => 'Riwayat',        'path' => 'user/riwayat.php',    'icon' => 'bi-clock-history'],
    ],
];

return $menus[$role] ?? [];
