<?php
/**
 * Dashboard anggota.
 *
 * @var array $summary
 * @var array $pending
 * @var int $estimate
 */

$active = $summary['active'];
$late   = $summary['late'];
$fines  = $summary['fines'];

$totalDendaBelumLunas = 0;
foreach ($fines as $f) {
    $totalDendaBelumLunas += (int) $f['fine_amount'];
}
?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    <div class="col">
        <?php $label = 'Sedang Dipinjam'; $value = count($active); $icon = 'bi-book'; $tone = 'info';
        $hint = 'Maksimal ' . (int) app_config('max_active_loans') . ' buku'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
    <div class="col">
        <?php $label = 'Menunggu Persetujuan'; $value = count($pending); $icon = 'bi-hourglass-split'; $tone = 'warning';
        $hint = 'Diajukan ke petugas'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
    <div class="col">
        <?php $label = 'Terlambat'; $value = count($late); $icon = 'bi-exclamation-triangle'; $tone = 'danger';
        $hint = $estimate > 0 ? 'Estimasi ' . rupiah($estimate) : 'Tepat waktu'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
    <div class="col">
        <?php $label = 'Denda Belum Lunas'; $value = rupiah($totalDendaBelumLunas); $icon = 'bi-cash-coin'; $tone = 'accent';
        $hint = 'Bayar ke petugas'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
</div>

<section class="clay-panel mb-4">
    <div class="section-heading">
        <h2 class="h5 mb-0">Buku yang Sedang Dipinjam</h2>
        <a class="btn btn-ghost-clay btn-clay-sm" href="<?= e(url('user/pinjaman.php')) ?>">
            Lihat Semua <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </div>

    <?php if ($active === []): ?>
        <div class="empty-state">
            <div class="empty-icon" aria-hidden="true"><i class="bi bi-journal-check"></i></div>
            <h3 class="h5 mb-2">Belum ada buku yang dipinjam</h3>
            <p class="mb-4">Jelajahi katalog dan ajukan peminjaman buku favoritmu.</p>
            <a class="btn btn-primary-clay" href="<?= e(url('katalog.php')) ?>">
                <i class="bi bi-search" aria-hidden="true"></i> Jelajahi Katalog
            </a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table-clay">
                <thead>
                    <tr>
                        <th scope="col">Buku</th>
                        <th scope="col">Tanggal Pinjam</th>
                        <th scope="col">Jatuh Tempo</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($active, 0, 5) as $row): ?>
                        <?php $late = hari_terlambat($row['due_at']); ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($row['book_title']) ?></div>
                                <div class="text-sm text-muted-clay"><?= e($row['book_author']) ?></div>
                            </td>
                            <td><?= e(tanggal_id($row['borrowed_at'])) ?></td>
                            <td><?= e(tanggal_id($row['due_at'])) ?></td>
                            <td>
                                <?php if ($late > 0): ?>
                                    <span class="badge-clay badge-danger">
                                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                        Terlambat <?= $late ?> hari
                                    </span>
                                    <div class="text-sm text-muted-clay mt-1">
                                        Denda <?= e(rupiah(hitung_denda($late))) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="badge-clay badge-success">
                                        <i class="bi bi-check-circle" aria-hidden="true"></i> Tepat waktu
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

<?php if ($pending !== []): ?>
    <section class="clay-panel">
        <div class="section-heading">
            <h2 class="h5 mb-0">Pengajuan Menunggu Persetujuan</h2>
        </div>

        <div class="table-wrap">
            <table class="table-clay">
                <thead>
                    <tr>
                        <th scope="col">Buku</th>
                        <th scope="col">Diajukan</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending as $row): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($row['book_title']) ?></div>
                                <div class="text-sm text-muted-clay"><?= e($row['book_author']) ?></div>
                            </td>
                            <td><?= e(tanggal_id($row['requested_at'], 'd M Y H:i')) ?></td>
                            <td>
                                <span class="<?= e(badge_status((string) $row['status'])) ?>">
                                    <?= e(label_status((string) $row['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>
