<?php
/**
 * Halaman login bersama untuk semua role.
 *
 * @var array<int,string> $errors
 * @var string $username
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — <?= e(app_config('name')) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="<?= e(asset('css/clay.css')) ?>">
</head>
<body>
<main class="auth-wrap">
    <div class="clay-card auth-card">
        <div class="text-center mb-4">
            <a class="brand-mark justify-content-center mb-3" href="<?= e(url('index.php')) ?>">
                <span class="brand-icon" aria-hidden="true"><i class="bi bi-book-half"></i></span>
                <span><?= e(app_config('short')) ?></span>
            </a>
            <h1 class="h3 mb-1">Masuk ke <?= e(app_config('short')) ?></h1>
            <p class="text-muted-clay mb-0 text-sm">
                Gunakan akun yang diberikan oleh petugas perpustakaan.
            </p>
        </div>

        <?php if ($errors !== []): ?>
            <div class="alert-clay alert-danger" role="alert">
                <i class="bi bi-exclamation-octagon mt-1" aria-hidden="true"></i>
                <div>
                    <?php foreach ($errors as $error): ?>
                        <div><?= e($error) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('login.php')) ?>" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label" for="username">Username</label>
                <input class="clay-input" type="text" id="username" name="username"
                       value="<?= e($username) ?>" autocomplete="username" required autofocus>
            </div>

            <div class="mb-4">
                <label class="form-label" for="password">Password</label>
                <input class="clay-input" type="password" id="password" name="password"
                       autocomplete="current-password" required>
            </div>

            <button class="btn btn-primary-clay btn-clay-block" type="submit">
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Masuk
            </button>
        </form>

        <hr class="divider-soft">

        <p class="text-center text-sm text-muted-clay mb-3">
            Pendaftaran anggota dilakukan melalui petugas perpustakaan.
        </p>

        <div class="text-center">
            <a class="btn btn-ghost-clay btn-clay-sm" href="<?= e(url('index.php')) ?>">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
