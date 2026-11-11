<?php
/**
 * Halaman katalog buku.
 *
 * @var array $categories
 * @var array $result
 * @var string $search
 * @var int $category
 * @var array|null $activeCat
 * @var string $sort
 */

$buildUrl = static function (array $overrides = []) use ($search, $category, $sort): string {
    $params = array_filter([
        'q'        => $search !== '' ? $search : null,
        'kategori' => $category > 0 ? $category : null,
        'urut'     => $sort !== 'terbaru' ? $sort : null,
    ], static fn ($v) => $v !== null && $v !== '');

    $params = array_merge($params, $overrides);

    return url('katalog.php' . ($params === [] ? '' : '?' . http_build_query($params)));
};

$total = (int) $result['total'];
$pages = (int) $result['pages'];
$page  = (int) $result['page'];

$rangeStart = $total === 0 ? 0 : (($page - 1) * (int) $result['per_page']) + 1;
$rangeEnd   = min($page * (int) $result['per_page'], $total);
?>
<section class="site-container page-section">
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0 text-sm">
            <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Beranda</a></li>
            <li class="breadcrumb-item active" aria-current="page">Katalog Buku</li>
        </ol>
    </nav>

    <div class="section-heading">
        <div>
            <h1 class="mb-1">Katalog Buku</h1>
            <p class="text-muted-clay mb-0 text-sm">
                <?php if ($total > 0): ?>
                    Menampilkan <?= $rangeStart ?>–<?= $rangeEnd ?> dari <?= $total ?> buku
                    <?= $activeCat !== null ? 'dalam kategori "' . e($activeCat['name']) . '"' : '' ?>
                <?php else: ?>
                    Koleksi buku perpustakaan
                <?php endif; ?>
            </p>
        </div>
    </div>

    <form class="clay-panel mb-4" method="get" action="<?= e(url('katalog.php')) ?>" role="search">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-5">
                <label class="form-label" for="filter-q">Cari buku</label>
                <input class="clay-input" type="search" id="filter-q" name="q"
                       placeholder="Judul atau penulis..." value="<?= e($search) ?>">
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label" for="filter-kategori">Kategori</label>
                <select class="form-select" id="filter-kategori" name="kategori">
                    <option value="">Semua kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"
                            <?= $category === (int) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label" for="filter-urut">Urutkan</label>
                <select class="form-select" id="filter-urut" name="urut">
                    <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="judul" <?= $sort === 'judul' ? 'selected' : '' ?>>Judul A–Z</option>
                    <option value="penulis" <?= $sort === 'penulis' ? 'selected' : '' ?>>Penulis A–Z</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <button class="btn btn-primary-clay btn-clay-block" type="submit">
                    <i class="bi bi-funnel" aria-hidden="true"></i> Terapkan
                </button>
            </div>
        </div>

        <?php if ($search !== '' || $category > 0 || $sort !== 'terbaru'): ?>
            <div class="mt-3">
                <a class="btn-link-clay text-sm" href="<?= e(url('katalog.php')) ?>">
                    <i class="bi bi-x-circle" aria-hidden="true"></i> Reset filter
                </a>
            </div>
        <?php endif; ?>
    </form>

    <?php if ($result['items'] === []): ?>
        <div class="clay-panel">
            <div class="empty-state">
                <div class="empty-icon" aria-hidden="true"><i class="bi bi-search"></i></div>
                <h2 class="h5 mb-2">Buku tidak ditemukan</h2>
                <p class="mb-4">
                    <?php if ($search !== ''): ?>
                        Tidak ada buku yang cocok dengan kata kunci "<?= e($search) ?>".
                    <?php else: ?>
                        Belum ada buku pada kategori ini.
                    <?php endif; ?>
                </p>
                <a class="btn btn-primary-clay" href="<?= e(url('katalog.php')) ?>">
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Lihat Semua Buku
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
            <?php foreach ($result['items'] as $book): ?>
                <div class="col">
                    <?php require __DIR__ . '/../partials/book_card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($pages > 1): ?>
            <nav aria-label="Navigasi halaman katalog">
                <div class="pagination-clay">
                    <?php if ($page > 1): ?>
                        <a href="<?= e($buildUrl(['hal' => $page - 1])) ?>" aria-label="Halaman sebelumnya">
                            <i class="bi bi-chevron-left" aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <span class="is-disabled" aria-hidden="true"><i class="bi bi-chevron-left"></i></span>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($pages, $page + 2);

                    for ($i = $start; $i <= $end; $i++):
                        ?>
                        <?php if ($i === $page): ?>
                            <span class="is-active" aria-current="page"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= e($buildUrl(['hal' => $i])) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $pages): ?>
                        <a href="<?= e($buildUrl(['hal' => $page + 1])) ?>" aria-label="Halaman berikutnya">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <span class="is-disabled" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
