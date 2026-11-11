<?php
/**
 * Daftar peminjaman dengan aksi setuju/tolak.
 *
 * @var array $rows
 * @var string $search
 * @var string $mode
 * @var string $basePath
 */

$isPending = $mode === 'pending';

$tabs = [
    'all'     => 'Semua',
    'pending' => 'Menunggu',
    'active'  => 'Dipinjam',
];
?>
<section class="clay-panel">
    <div class="section-heading">
        <div>
            <h2 class="h5 mb-1"><?= $isPending ? 'Pengajuan Menunggu Persetujuan' : 'Data Peminjaman' ?></h2>
            <p class="text-muted-clay mb-0 text-sm">
                <?= count($rows) ?> transaksi<?= $isPending ? ' menunggu diproses' : '' ?>
            </p>
        </div>
    </div>

    <div class="chip-row mb-4" role="tablist" aria-label="Filter status peminjaman">
        <?php foreach ($tabs as $key => $label): ?>
            <a class="chip <?= $mode === $key ? 'is-active' : '' ?>"
               role="tab"
               aria-selected="<?= $mode === $key ? 'true' : 'false' ?>"
               href="<?= e(url($basePath . ($key === 'all' ? '' : '?mode=' . $key))) ?>">
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <form class="filter-bar mb-4" method="get" action="<?= e(url($basePath)) ?>" role="search">
        <?php if ($mode !== 'all'): ?>
            <input type="hidden" name="mode" value="<?= e($mode) ?>">
        <?php endif; ?>
        <div class="flex-grow-1" style="min-width: 220px;">
            <label class="visually-hidden" for="borrow-search">Cari transaksi</label>
            <input class="clay-input" type="search" id="borrow-search" name="q"
                   placeholder="Cari nama anggota atau judul buku..." value="<?= e($search) ?>">
        </div>
        <button class="btn btn-secondary-clay" type="submit">
            <i class="bi bi-search" aria-hidden="true"></i> Cari
        </button>
        <?php if ($search !== ''): ?>
            <a class="btn btn-ghost-clay" href="<?= e(url($basePath . ($mode === 'all' ? '' : '?mode=' . $mode))) ?>">Reset</a>
        <?php endif; ?>
    </form>

    <?php if ($rows === []): ?>
        <div class="empty-state">
            <div class="empty-icon" aria-hidden="true"><i class="bi bi-inbox"></i></div>
            <h3 class="h5 mb-2"><?= $isPending ? 'Tidak ada pengajuan' : 'Belum ada data peminjaman' ?></h3>
            <p class="mb-0">
                <?= $isPending
                    ? 'Semua pengajuan sudah diproses. Kerja bagus!'
                    : 'Belum ada transaksi peminjaman yang tercatat.' ?>
            </p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table-clay">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Anggota</th>
                        <th scope="col">Buku</th>
                        <th scope="col">Diajukan</th>
                        <th scope="col">Jatuh Tempo</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                        $isBorrowed = $row['status'] === 'dipinjam';
                        $late       = $isBorrowed ? hari_terlambat($row['due_at']) : 0;
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
                            <td class="text-sm"><?= e(tanggal_id($row['requested_at'], 'd M Y H:i')) ?></td>
                            <td>
                                <?= e(tanggal_id($row['due_at'])) ?>
                                <?php if ($late > 0): ?>
                                    <div class="mt-1">
                                        <span class="badge-clay badge-danger">
                                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> <?= $late ?> hari
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?= e(badge_status((string) $row['status'])) ?>">
                                    <?= e(label_status((string) $row['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'menunggu'): ?>
                                    <div class="cell-actions">
                                        <form method="post" action="<?= e(url($basePath)) ?>" class="d-inline"
                                              data-confirm="Setujui peminjaman &quot;<?= e($row['book_title']) ?>&quot; untuk <?= e($row['user_name']) ?>? Stok akan berkurang 1.">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                            <button class="btn btn-primary-clay btn-clay-sm" type="submit">
                                                <i class="bi bi-check-lg" aria-hidden="true"></i> Setujui
                                            </button>
                                        </form>
                                        <form method="post" action="<?= e(url($basePath)) ?>" class="d-inline"
                                              data-confirm="Tolak pengajuan &quot;<?= e($row['book_title']) ?>&quot;?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                            <button class="btn btn-danger-clay btn-clay-sm" type="submit">
                                                <i class="bi bi-x-lg" aria-hidden="true"></i> Tolak
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted-clay text-sm">Tidak ada aksi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
