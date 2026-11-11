<?php
/**
 * Layout dashboard untuk admin, petugas, dan anggota.
 * Menu sidebar dibangun sesuai role.
 *
 * Variabel:
 * @var string $content
 * @var string $pageTitle
 * @var string|null $heading
 */

$user    = auth_user();
$role    = (string) auth_role();
$current = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));

$menu = require __DIR__ . '/../partials/menu.php';

$roleLabel = match ($role) {
    'admin'   => 'Admin',
    'petugas' => 'Petugas',
    default   => 'Anggota',
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Dashboard') ?> — <?= e(app_config('name')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('images/favicon.svg')) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/clay.css')) ?>">
</head>
<body>
<a href="#dash-content" class="visually-hidden-focusable p-2">Lewati ke konten utama</a>

<div class="dash-shell">
    <aside class="dash-sidebar" data-sidebar aria-label="Menu dashboard">
        <a class="brand-mark" href="<?= e(url('index.php')) ?>">
            <span class="brand-icon" aria-hidden="true"><i class="bi bi-book-half"></i></span>
            <span><?= e(app_config('short')) ?></span>
        </a>

        <nav class="sidebar-nav">
            <?php foreach ($menu as $item): ?>
                <?php if (($item['type'] ?? 'link') === 'sep'): ?>
                    <div class="nav-sep"><?= e($item['label']) ?></div>
                <?php else: ?>
                    <a href="<?= e(url($item['path'])) ?>"
                       class="<?= $current === basename($item['path']) ? 'active' : '' ?>"
                       <?= $current === basename($item['path']) ? 'aria-current="page"' : '' ?>>
                        <i class="bi <?= e($item['icon']) ?>" aria-hidden="true"></i>
                        <span><?= e($item['label']) ?></span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="sidebar-backdrop" data-sidebar-backdrop></div>

    <div class="dash-main">
        <header class="dash-topbar">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-ghost-clay btn-clay-sm dash-menu-toggle" type="button"
                        data-sidebar-toggle aria-expanded="false" aria-label="Buka menu dashboard">
                    <i class="bi bi-list" aria-hidden="true"></i>
                </button>
                <h1><?= e($heading ?? ($pageTitle ?? 'Dashboard')) ?></h1>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="role-chip d-none d-sm-inline-flex">
                    <i class="bi bi-person-badge" aria-hidden="true"></i>
                    <?= e($user['name'] ?? '') ?> &middot; <?= e($roleLabel) ?>
                </span>
                <form method="post" action="<?= e(url('logout.php')) ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button class="btn btn-ghost-clay btn-clay-sm" type="submit">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline">Logout</span>
                    </button>
                </form>
            </div>
        </header>

        <div class="dash-content" id="dash-content">
            <?php require __DIR__ . '/../partials/flash.php'; ?>
            <?= $content ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
