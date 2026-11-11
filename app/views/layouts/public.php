<?php
/**
 * Layout halaman publik (beranda, katalog, detail, login).
 *
 * Variabel yang dipakai:
 * @var string $content
 * @var string $pageTitle
 */

$user = auth_user();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Beranda') ?> — <?= e(app_config('name')) ?></title>
    <meta name="description" content="Lentera Digital Library — katalog dan peminjaman buku perpustakaan.">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('images/favicon.svg')) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/clay.css')) ?>">
</head>
<body>
<a href="#main-content" class="visually-hidden-focusable p-2">Lewati ke konten utama</a>

<nav class="public-nav">
    <div class="site-container">
        <div class="d-flex align-items-center justify-content-between py-2">
            <a class="brand-mark" href="<?= e(url('index.php')) ?>">
                <span class="brand-icon" aria-hidden="true"><i class="bi bi-book-half"></i></span>
                <span><?= e(app_config('short')) ?></span>
            </a>

            <button class="btn btn-ghost-clay btn-clay-sm d-lg-none" type="button"
                    data-bs-toggle="collapse" data-bs-target="#publicNav"
                    aria-controls="publicNav" aria-expanded="false" aria-label="Buka menu navigasi">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>

            <div class="collapse d-lg-block" id="publicNav">
                <ul class="nav justify-content-lg-end align-items-lg-center gap-lg-1 mt-3 mt-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= ($pageTitle ?? '') === 'Beranda' ? 'active' : '' ?>"
                           href="<?= e(url('index.php')) ?>">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= str_contains((string) ($pageTitle ?? ''), 'Katalog') ? 'active' : '' ?>"
                           href="<?= e(url('katalog.php')) ?>">Katalog Buku</a>
                    </li>
                    <li class="nav-item">
                        <?php if ($user !== null): ?>
                            <a class="btn btn-primary-clay btn-clay-sm w-100 mt-2 mt-lg-0"
                               href="<?= e(url(dashboard_path_for((string) $user['role']))) ?>">
                                <i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard
                            </a>
                        <?php else: ?>
                            <a class="btn btn-primary-clay btn-clay-sm w-100 mt-2 mt-lg-0"
                               href="<?= e(url('login.php')) ?>">
                                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<main id="main-content">
    <div class="site-container">
        <?php require __DIR__ . '/../partials/flash.php'; ?>
    </div>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="site-container">
        <div class="row g-3 align-items-center">
            <div class="col-md-8">
                <strong class="text-dark"><?= e(app_config('name')) ?></strong>
                <p class="mb-0 mt-1">
                    Perpustakaan buku fisik untuk belajar dan membaca. Katalog dapat diakses tanpa login;
                    pendaftaran anggota dilakukan melalui petugas perpustakaan.
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="text-sm">Masa pinjam <?= (int) app_config('loan_days') ?> hari &middot;
                    Denda <?= e(rupiah(app_config('fine_per_day'))) ?>/hari</span>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
