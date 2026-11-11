<?php
/**
 * Daftar buku untuk admin & petugas.
 *
 * @var array $result
 * @var string $search
 * @var string $basePath
 * @var array $categories
 */

$page  = (int) $result['page'];
$pages = (int) $result['pages'];
?>
<section class="clay-panel">
    <div class="section-heading">
        <div>
            <h2 class="h5 mb-1">Daftar Buku</h2>
            <p class="text-muted-clay mb-0 text-sm"><?= (int) $result['total'] ?> judul terdaftar</p>
        </div>
        <a class="btn btn-primary-clay" href="<?= e(url($basePath . '?aksi=tambah')) ?>">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah Buku
        </a>
    </div>

    <form class="filter-bar mb-4" method="get" action="<?= e(url($basePath)) ?>" role="search">
        <div class="flex-grow-1" style="min-width: 220px;">
            <label class="visually-hidden" for="book-search">Cari buku</label>
            <input class="clay-input" type="search" id="book-search" name="q"
                   placeholder="Cari judul atau penulis..." value="<?= e($search) ?>">
        </div>
        <button class="btn btn-secondary-clay" type="submit">
            <i class="bi bi-search" aria-hidden="true"></i> Cari
        </button>
        <?php if ($search !== ''): ?>
            <a class="btn btn-ghost-clay" href="<?= e(url($basePath)) ?>">Reset</a>
        <?php endif; ?>
    </form>

    <?php if ($result['items'] === []): ?>
        <div class="empty-state">
            <div class="empty-icon" aria-hidden="true"><i class="bi bi-journal-x"></i></div>
            <h3 class="h5 mb-2">Belum ada buku</h3>
            <p class="mb-4">
                <?= $search !== '' ? 'Tidak ada buku yang cocok dengan pencarian.' : 'Mulai dengan menambahkan buku pertama.' ?>
            </p>
            <a class="btn btn-primary-clay" href="<?= e(url($basePath . '?aksi=tambah')) ?>">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah Buku
            </a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table-clay">
                <thead>
                    <tr>
                        <th scope="col">Judul</th>
                        <th scope="col">Penulis</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Tahun</th>
                        <th scope="col">Stok</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['items'] as $book): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($book['title']) ?></td>
                            <td><?= e($book['author']) ?></td>
                            <td><span class="badge-clay badge-muted"><?= e($book['category_name']) ?></span></td>
                            <td><?= $book['published_year'] !== null ? (int) $book['published_year'] : '—' ?></td>
                            <td>
                                <span class="badge-clay <?= (int) $book['stock_available'] > 0 ? 'badge-success' : 'badge-danger' ?>">
                                    <?= (int) $book['stock_available'] ?> / <?= (int) $book['stock_total'] ?>
                                </span>
                            </td>
                            <td>
                                <div class="cell-actions">
                                    <a class="btn btn-ghost-clay btn-clay-sm"
                                       href="<?= e(url('detail.php?id=' . (int) $book['id'])) ?>"
                                       aria-label="Lihat detail buku <?= e($book['title']) ?>">
                                        <i class="bi bi-eye" aria-hidden="true"></i>
                                    </a>
                                    <a class="btn btn-secondary-clay btn-clay-sm"
                                       href="<?= e(url($basePath . '?aksi=edit&id=' . (int) $book['id'])) ?>"
                                       aria-label="Edit buku <?= e($book['title']) ?>">
                                        <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                                    </a>
                                    <form method="post" action="<?= e(url($basePath)) ?>"
                                          data-confirm="Hapus buku &quot;<?= e($book['title']) ?>&quot;? Tindakan ini tidak dapat dibatalkan."
                                          class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                                        <button class="btn btn-danger-clay btn-clay-sm" type="submit"
                                                aria-label="Hapus buku <?= e($book['title']) ?>">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <nav aria-label="Navigasi halaman buku">
                <div class="pagination-clay">
                    <?php for ($i = 1; $i <= $pages; $i++):
                        $params = ['hal' => $i];
                        if ($search !== '') {
                            $params['q'] = $search;
                        }
                        ?>
                        <?php if ($i === $page): ?>
                            <span class="is-active" aria-current="page"><?= $i ?></span>
                        <?php else: ?>
                            <a href="<?= e(url($basePath . '?' . http_build_query($params))) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>
