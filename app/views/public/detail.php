<?php
/**
 * Halaman detail buku.
 *
 * @var array $book
 * @var array $related
 * @var array|null $myBorrowing
 */

$available = (int) $book['stock_available'];
$logged    = auth_check();
$isMember  = auth_role() === 'user';
?>
<section class="site-container page-section">
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 text-sm">
            <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Beranda</a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('katalog.php')) ?>">Katalog</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($book['title']) ?></li>
        </ol>
    </nav>

    <div class="clay-panel">
        <div class="row g-4">
            <div class="col-12 col-md-4 col-lg-3">
                <div class="cover-frame">
                    <img src="<?= e(cover_url($book['cover_path'])) ?>"
                         alt="Cover buku <?= e($book['title']) ?>">
                </div>
            </div>

            <div class="col-12 col-md-8 col-lg-9">
                <span class="badge-clay <?= $available > 0 ? 'badge-success' : 'badge-danger' ?> mb-2">
                    <i class="bi <?= $available > 0 ? 'bi-check-circle' : 'bi-x-circle' ?>" aria-hidden="true"></i>
                    <?= $available > 0 ? 'Tersedia' : 'Tidak Tersedia' ?>
                </span>

                <h1 class="mb-2"><?= e($book['title']) ?></h1>

                <dl class="row g-2 mb-3 text-sm">
                    <dt class="col-sm-3 text-muted-clay fw-normal mb-0">Penulis</dt>
                    <dd class="col-sm-9 mb-0 fw-semibold"><?= e($book['author']) ?></dd>

                    <dt class="col-sm-3 text-muted-clay fw-normal mb-0">Kategori</dt>
                    <dd class="col-sm-9 mb-0">
                        <a class="chip" href="<?= e(url('katalog.php?kategori=' . (int) $book['category_id'])) ?>">
                            <?= e($book['category_name']) ?>
                        </a>
                    </dd>

                    <?php if (!empty($book['published_year'])): ?>
                        <dt class="col-sm-3 text-muted-clay fw-normal mb-0">Tahun Terbit</dt>
                        <dd class="col-sm-9 mb-0 fw-semibold"><?= (int) $book['published_year'] ?></dd>
                    <?php endif; ?>

                    <dt class="col-sm-3 text-muted-clay fw-normal mb-0">Stok tersedia</dt>
                    <dd class="col-sm-9 mb-0">
                        <strong><?= $available ?></strong> dari <?= (int) $book['stock_total'] ?> eksemplar
                    </dd>

                    <dt class="col-sm-3 text-muted-clay fw-normal mb-0">Masa pinjam</dt>
                    <dd class="col-sm-9 mb-0"><?= (int) app_config('loan_days') ?> hari (gratis)</dd>
                </dl>

                <hr class="divider-soft">

                <h2 class="h5 mb-2">Sinopsis</h2>
                <p class="synopsis mb-4">
                    <?= e($book['synopsis'] ?: 'Sinopsis belum tersedia untuk buku ini.') ?>
                </p>

                <?php if ($myBorrowing !== null): ?>
                    <div class="alert-clay alert-info" role="status">
                        <i class="bi bi-info-circle mt-1" aria-hidden="true"></i>
                        <div>
                            Anda sudah memiliki
                            <strong><?= e(label_status($myBorrowing['status'])) ?></strong>
                            untuk buku ini
                            <?php if ($myBorrowing['status'] === 'dipinjam' && $myBorrowing['due_at']): ?>
                                (jatuh tempo <?= e(tanggal_id($myBorrowing['due_at'])) ?>)
                            <?php endif; ?>.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!$logged): ?>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary-clay" href="<?= e(url('login.php')) ?>">
                            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> Masuk untuk Meminjam
                        </a>
                    </div>
                    <p class="text-sm text-muted-clay mt-3 mb-0">
                        Belum punya akun? Daftar melalui petugas perpustakaan.
                    </p>
                <?php elseif ($isMember): ?>
                    <?php if ($available <= 0): ?>
                        <button class="btn btn-primary-clay" type="button" disabled>
                            <i class="bi bi-slash-circle" aria-hidden="true"></i> Stok Habis
                        </button>
                        <p class="text-sm text-muted-clay mt-3 mb-0">
                            Semua eksemplar sedang dipinjam. Silakan coba lagi nanti.
                        </p>
                    <?php elseif ($myBorrowing !== null): ?>
                        <button class="btn btn-secondary-clay" type="button" disabled>
                            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                            <?= $myBorrowing['status'] === 'dipinjam' ? 'Sedang Dipinjam' : 'Menunggu Persetujuan' ?>
                        </button>
                    <?php else: ?>
                        <form method="post" action="<?= e(url('detail.php?id=' . (int) $book['id'])) ?>"
                              data-confirm="Ajukan peminjaman buku &quot;<?= e($book['title']) ?>&quot;?">
                            <?= csrf_field() ?>
                            <button class="btn btn-primary-clay" type="submit">
                                <i class="bi bi-book" aria-hidden="true"></i> Ajukan Peminjaman
                            </button>
                        </form>
                        <p class="text-sm text-muted-clay mt-3 mb-0">
                            Pengajuan akan diproses petugas. Buku diserahkan setelah disetujui.
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <a class="btn btn-primary-clay" href="<?= e(url(dashboard_path_for((string) auth_role()))) ?>">
                        <i class="bi bi-speedometer2" aria-hidden="true"></i> Buka Dashboard
                    </a>
                    <p class="text-sm text-muted-clay mt-3 mb-0">
                        Peminjaman hanya dapat diajukan melalui akun anggota.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($related !== []): ?>
        <section class="page-section pb-0" aria-labelledby="terkait-heading">
            <div class="section-heading">
                <h2 id="terkait-heading" class="mb-0">Buku Terkait</h2>
            </div>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                <?php foreach ($related as $book): ?>
                    <div class="col">
                        <?php require __DIR__ . '/../partials/book_card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</section>
