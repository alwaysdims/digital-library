<?php
/**
 * Daftar denda + aksi pelunasan.
 *
 * @var array $rows
 * @var string $search
 * @var string $status
 * @var int $totalUnpaid
 * @var int $totalPaid
 * @var string $basePath
 */
?>
<div class="row row-cols-1 row-cols-sm-2 g-3 mb-4">
    <div class="col">
        <?php $label = 'Total Denda Belum Lunas'; $value = rupiah($totalUnpaid); $icon = 'bi-exclamation-circle'; $tone = 'danger';
        $hint = 'Perlu ditagih ke anggota'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
    <div class="col">
        <?php $label = 'Total Denda Lunas'; $value = rupiah($totalPaid); $icon = 'bi-check2-circle'; $tone = 'primary';
        $hint = 'Sudah dibayar'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
</div>

<section class="clay-panel">
    <div class="section-heading">
        <div>
            <h2 class="h5 mb-1">Data Denda</h2>
            <p class="text-muted-clay mb-0 text-sm">
                Denda <?= e(rupiah(app_config('fine_per_day'))) ?> per hari per buku
            </p>
        </div>
    </div>

    <form class="filter-bar mb-4" method="get" action="<?= e(url($basePath)) ?>" role="search">
        <div class="flex-grow-1" style="min-width: 200px;">
            <label class="visually-hidden" for="fine-search">Cari denda</label>
            <input class="clay-input" type="search" id="fine-search" name="q"
                   placeholder="Cari nama anggota atau judul buku..." value="<?= e($search) ?>">
        </div>
        <div>
            <label class="visually-hidden" for="fine-status">Status denda</label>
            <select class="form-select" id="fine-status" name="status">
                <option value="belum_lunas" <?= $status === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                <option value="lunas" <?= $status === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua</option>
            </select>
        </div>
        <button class="btn btn-secondary-clay" type="submit">
            <i class="bi bi-funnel" aria-hidden="true"></i> Terapkan
        </button>
    </form>

    <?php if ($rows === []): ?>
        <div class="empty-state">
            <div class="empty-icon" aria-hidden="true"><i class="bi bi-cash-coin"></i></div>
            <h3 class="h5 mb-2">Tidak ada data denda</h3>
            <p class="mb-0">Belum ada denda pada filter yang dipilih.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table-clay">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Anggota</th>
                        <th scope="col">Buku</th>
                        <th scope="col">Dikembalikan</th>
                        <th scope="col">Denda</th>
                        <th scope="col">Status</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
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
                            <td><?= e(tanggal_id($row['returned_at'])) ?></td>
                            <td class="fw-semibold"><?= e(rupiah($row['fine_amount'])) ?></td>
                            <td>
                                <span class="<?= e(badge_denda((string) $row['fine_status'])) ?>">
                                    <?= e(label_denda((string) $row['fine_status'])) ?>
                                </span>
                                <?php if (!empty($row['fine_paid_at'])): ?>
                                    <div class="text-sm text-muted-clay mt-1">
                                        Dibayar <?= e(tanggal_id($row['fine_paid_at'], 'd M Y')) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['fine_status'] === 'belum_lunas'): ?>
                                    <form method="post" action="<?= e(url($basePath)) ?>" class="d-inline"
                                          data-confirm="Tandai denda <?= e(rupiah($row['fine_amount'])) ?> dari <?= e($row['user_name']) ?> sebagai LUNAS?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="pay_fine">
                                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                        <button class="btn btn-primary-clay btn-clay-sm" type="submit">
                                            <i class="bi bi-cash" aria-hidden="true"></i> Tandai Lunas
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge-clay badge-success">
                                        <i class="bi bi-check-circle" aria-hidden="true"></i> Selesai
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
