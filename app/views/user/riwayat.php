<?php
/**
 * Riwayat lengkap peminjaman anggota.
 *
 * @var array $rows
 */
?>
<?php $tampil = $rows; ?>
<?php if ($tampil === []): ?>
    <section class="clay-panel">
        <div class="empty-state">
            <div class="empty-icon" aria-hidden="true"><i class="bi bi-clock-history"></i></div>
            <h2 class="h5 mb-2">Belum ada riwayat peminjaman</h2>
            <p class="mb-4">Riwayat akan muncul setelah kamu mengajukan peminjaman buku.</p>
            <a class="btn btn-primary-clay" href="<?= e(url('katalog.php')) ?>">
                <i class="bi bi-search" aria-hidden="true"></i> Jelajahi Katalog
            </a>
        </div>
    </section>
<?php else: ?>
    <section class="clay-panel">
        <div class="section-heading">
            <div>
                <h2 class="h5 mb-1">Seluruh Riwayat</h2>
                <p class="text-muted-clay mb-0 text-sm"><?= count($tampil) ?> transaksi</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-clay">
                <thead>
                    <tr>
                        <th scope="col">Buku</th>
                        <th scope="col">Diajukan</th>
                        <th scope="col">Dipinjam</th>
                        <th scope="col">Dikembalikan</th>
                        <th scope="col">Status</th>
                        <th scope="col">Denda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tampil as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($row['book_title']) ?></div>
                                <div class="text-sm text-muted-clay"><?= e($row['book_author']) ?></div>
                            </td>
                            <td class="text-sm"><?= e(tanggal_id($row['requested_at'], 'd M Y')) ?></td>
                            <td class="text-sm"><?= e(tanggal_id($row['borrowed_at'])) ?></td>
                            <td class="text-sm"><?= e(tanggal_id($row['returned_at'])) ?></td>
                            <td>
                                <span class="<?= e(badge_status((string) $row['status'])) ?>">
                                    <?= e(label_status((string) $row['status'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['fine_status'] === 'belum_lunas'): ?>
                                    <div class="fw-semibold text-danger"><?= e(rupiah($row['fine_amount'])) ?></div>
                                    <span class="<?= e(badge_denda('belum_lunas')) ?>">Belum Lunas</span>
                                <?php elseif ($row['fine_status'] === 'lunas'): ?>
                                    <div class="fw-semibold"><?= e(rupiah($row['fine_amount'])) ?></div>
                                    <span class="<?= e(badge_denda('lunas')) ?>">Lunas</span>
                                <?php else: ?>
                                    <span class="text-muted-clay">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
