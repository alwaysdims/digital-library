<?php
/**
 * Tabel daftar transaksi peminjaman (dipakai admin & petugas).
 *
 * @var array $rows
 * @var bool $showUser      Tampilkan kolom anggota.
 * @var bool $showActions   Tampilkan kolom aksi (diisi oleh pemanggil lewat $rowActions).
 * @var string|null $emptyMessage
 */

$showUser    = $showUser ?? true;
$showActions = $showActions ?? false;
$emptyMessage = $emptyMessage ?? 'Belum ada data peminjaman.';
?>
<?php if ($rows === []): ?>
    <div class="empty-state">
        <div class="empty-icon" aria-hidden="true"><i class="bi bi-inbox"></i></div>
        <p class="mb-0"><?= e($emptyMessage) ?></p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table class="table-clay">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <?php if ($showUser): ?><th scope="col">Anggota</th><?php endif; ?>
                    <th scope="col">Buku</th>
                    <th scope="col">Jatuh Tempo</th>
                    <th scope="col">Keterlambatan</th>
                    <th scope="col">Status</th>
                    <th scope="col">Denda</th>
                    <?php if ($showActions): ?><th scope="col">Aksi</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $isBorrowed = $row['status'] === 'dipinjam';
                    $late       = $isBorrowed ? hari_terlambat($row['due_at']) : 0;
                    $estimate   = $isBorrowed ? hitung_denda($late) : (int) $row['fine_amount'];
                    ?>
                    <tr>
                        <td class="text-muted-clay">#<?= (int) $row['id'] ?></td>
                        <?php if ($showUser): ?>
                            <td>
                                <div class="fw-semibold"><?= e($row['user_name'] ?? '-') ?></div>
                                <?php if (!empty($row['username'])): ?>
                                    <div class="text-sm text-muted-clay">@<?= e($row['username']) ?></div>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td>
                            <div class="fw-semibold"><?= e($row['book_title'] ?? '-') ?></div>
                            <?php if (!empty($row['book_author'])): ?>
                                <div class="text-sm text-muted-clay"><?= e($row['book_author']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e(tanggal_id($row['due_at'])) ?></td>
                        <td>
                            <?php if ($isBorrowed && $late > 0): ?>
                                <span class="badge-clay badge-danger">
                                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                    <?= $late ?> hari
                                </span>
                            <?php elseif ($isBorrowed): ?>
                                <span class="badge-clay badge-success">
                                    <i class="bi bi-check-circle" aria-hidden="true"></i> Tepat waktu
                                </span>
                            <?php else: ?>
                                <span class="text-muted-clay">—</span>
                            <?php endif; ?>
                        </td>
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
                            <?php elseif ($isBorrowed && $estimate > 0): ?>
                                <span class="text-muted-clay text-sm">
                                    Estimasi <?= e(rupiah($estimate)) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted-clay">—</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($showActions): ?>
                            <td>
                                <div class="cell-actions">
                                    <?php if (is_callable($rowActions ?? null)): ?>
                                        <?= $rowActions($row) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
