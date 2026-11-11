<?php
/**
 * Beranda publik.
 *
 * @var array $categories
 * @var array $featured
 */
?>
<section class="site-container">
    <div class="hero">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <h1>Temukan Buku Favoritmu</h1>
                <p class="lead">
                    Jelajahi koleksi buku, baca sinopsis, dan temukan bacaan yang kamu cari.
                </p>

                <form class="hero-search" method="get" action="<?= e(url('katalog.php')) ?>" role="search">
                    <div class="input-group">
                        <label class="visually-hidden" for="hero-search">Cari buku berdasarkan judul atau penulis</label>
                        <input class="clay-input" type="search" id="hero-search" name="q"
                               placeholder="Cari judul atau penulis buku..." value="">
                        <button class="btn btn-primary-clay" type="submit">
                            <i class="bi bi-search" aria-hidden="true"></i> Cari Buku
                        </button>
                    </div>
                </form>

                <p class="text-sm text-muted-clay mt-3 mb-0">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    Ingin meminjam buku? Pendaftaran anggota dilakukan melalui petugas perpustakaan.
                </p>
            </div>

            <div class="col-lg-5 d-none d-lg-block">
                <div class="book-stack" role="img" aria-label="Ilustrasi tumpukan buku">
                    <span class="b1"></span>
                    <span class="b2"></span>
                    <span class="b3"></span>
                    <span class="b4"></span>
                    <span class="b5"><i class="bi bi-book" aria-hidden="true"></i></span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="site-container page-section" aria-labelledby="kategori-heading">
    <div class="section-heading">
        <h2 id="kategori-heading" class="mb-0">Kategori Buku</h2>
    </div>

    <div class="chip-row">
        <a class="chip <?= $categories === [] ? 'is-active' : '' ?>" href="<?= e(url('katalog.php')) ?>">Semua</a>
        <?php foreach ($categories as $category): ?>
            <a class="chip" href="<?= e(url('katalog.php?kategori=' . (int) $category['id'])) ?>">
                <?= e($category['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="site-container page-section pt-0" aria-labelledby="koleksi-heading">
    <div class="section-heading">
        <h2 id="koleksi-heading" class="mb-0">Koleksi Buku</h2>
        <a class="btn btn-ghost-clay btn-clay-sm" href="<?= e(url('katalog.php')) ?>">
            Lihat Semua <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </div>

    <?php if ($featured === []): ?>
        <div class="clay-panel">
            <div class="empty-state">
                <div class="empty-icon" aria-hidden="true"><i class="bi bi-journal-x"></i></div>
                <h3 class="h5 mb-2">Belum ada buku</h3>
                <p class="mb-0">Koleksi buku belum tersedia saat ini. Silakan kembali lagi nanti.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
            <?php foreach ($featured as $book): ?>
                <div class="col">
                    <?php $book = $book; require __DIR__ . '/../partials/book_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
