<?php
/**
 * Konfigurasi aplikasi Lentera Digital Library.
 * Jangan menaruh kredensial database di file ini; lihat config/database.php.
 */

declare(strict_types=1);

return [
    'name'  => 'Lentera Digital Library',
    'short' => 'Lentera',

    // Zona waktu untuk perhitungan tanggal & denda.
    'timezone' => 'Asia/Jakarta',
    'session_timeout' => 1800,

    // Aturan bisnis peminjaman.
    'loan_days'        => 7,      // masa pinjam (hari kalender)
    'max_active_loans' => 3,      // maksimal buku aktif per anggota
    'fine_per_day'     => 1000,   // denda keterlambatan per hari per buku (Rp)

    // Upload cover.
    'upload' => [
        'dir'         => __DIR__ . '/../public/uploads/covers',
        'max_bytes'   => 2 * 1024 * 1024, // 2 MB
        'mime_types'  => ['image/jpeg', 'image/png', 'image/webp'],
        'extensions'  => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    // Path aplikasi (untuk struktur folder dengan public/ sebagai document root).
    'base_path' => dirname(__DIR__),
];
