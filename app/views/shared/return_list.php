<?php
/**
 * Daftar peminjaman aktif + aksi catat pengembalian.
 *
 * @var array $rows
 * @var string $search
 * @var string $basePath
 */
?>
<section class="clay-panel">
    <div class="section-heading">
        <div>
            <h2 class="h5 mb-1">Buku Sedang Dipinjam</h2>
            <p class="text-muted-clay mb-0 text-sm"><?= count($rows) ?> transaksi aktif</p>
        </div>
    </div>

    <form class="filter-bar mb-4" method="get" action="<?= e(url($basePath)) ?>" role="search">
        <div class="flex-grow-1" style="min-width: 220px;">
            <label class="visually-hidden" for="return-search">Cari transaksi</label>
            <input class="clay-input" type="search" id="return-search" name="q"
                   placeholder="Cari nama anggota atau judul buku..." value="<?= e($search) ?>">
        </div>
        <button class="btn btn-secondary-clay" type="submit">
            <i class="bi bi-search" aria-hidden="true"></i> Cari
        </button>
        <?php if ($search !== ''): ?>
            <a class="btn btn-ghost-clay" href="<?= e(url($basePath)) ?>">Reset</a>
        <?php endif; ?>
    </form>

    <?php if ($rows === []): ?>
        <div class="empty-state">
            <div class="empty-icon" aria-hidden="true"><i class="bi bi-check2-circle"></i></div>
            <h3 class="h5 mb-2">Tidak ada buku yang dipinjam</h3>
            <p class="mb-0">Semua buku sudah dikembalikan.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table-clay">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Anggota</th>
                        <th scope="col">Buku</th>
                        <th scope="col">Tanggal Pinjam</th>
                        <th scope="col">Jatuh Tempo</th>
                        <th scope="col">Estimasi Denda</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $late     = hari_terlambat($row['due_at']);
                        $estimate = hitung_denda($late);
                        ?>
                        <tr>
                            <td class="text-muted-clay">#<?= (int) $row['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= e($row['user_name']) ?></div>
                                <div class="text-sm text-muted-clay">@<?= e($row['username']) ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($row['book_title']) ?></div>
                                <div class="text-sm text-muted-clay"><?= e($row['book_author']) ?></div>
                            </td>
                            <td><?= e(tanggal_id($row['borrowed_at'])) ?></td>
                            <td>
                                <?= e(tanggal_id($row['due_at'])) ?>
                                <?php if ($late > 0): ?>
                                    <div class="mt-1">
                                        <span class="badge-clay badge-danger">
                                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Terlambat <?= $late ?> hari
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-1">
                                        <span class="badge-clay badge-success">
                                            <i class="bi bi-check-circle" aria-hidden="true"></i> Tepat waktu
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($estimate > 0): ?>
                                    <span class="fw-semibold text-danger"><?= e(rupiah($estimate)) ?></span>
                                    <div class="text-sm text-muted-clay">per hari ini</div>
                                <?php else: ?>
                                    <span class="text-muted-clay">Rp0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="<?= e(url($basePath)) ?>" class="d-inline"
                                      data-confirm="Catat pengembalian &quot;<?= e($row['book_title']) ?>&quot;?<?= $estimate > 0 ? ' Denda ' . e(rupiah($estimate)) . ' akan dicatat sebagai belum lunas.' : '' ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="return">
                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                    <button class="btn btn-primary-clay btn-clay-sm" type="submit">
                                        <i class="bi bi-arrow-return-left" aria-hidden="true"></i> Kembalikan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
