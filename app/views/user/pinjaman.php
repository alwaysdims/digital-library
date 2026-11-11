<?php
/**
 * Pinjaman aktif anggota.
 *
 * @var array $summary
 * @var array $pending
 * @var int $estimate
 */

$active = $summary['active'];
$fines  = $summary['fines'];

$maxLoans = (int) app_config('max_active_loans');
$used     = count($active) + count($pending);
?>
<div class="alert-clay alert-info" role="status">
    <i class="bi bi-info-circle mt-1" aria-hidden="true"></i>
    <div>
        Anda menggunakan <strong><?= $used ?></strong> dari maksimal <strong><?= $maxLoans ?></strong> slot pinjaman
        (<?= count($active) ?> dipinjam, <?= count($pending) ?> menunggu).
        Masa pinjam <?= (int) app_config('loan_days') ?> hari sejak buku diserahkan petugas.
    </div>
</div>

<section class="clay-panel mb-4">
    <div class="section-heading">
        <h2 class="h5 mb-0">Buku Sedang Dipinjam</h2>
        <a class="btn btn-primary-clay btn-clay-sm" href="<?= e(url('katalog.php')) ?>">
            <i class="bi bi-plus-lg" aria-hidden="true"></i> Pinjam Buku Lain
        </a>
    </div>

    <?php if ($active === []): ?>
        <div class="empty-state">
            <div class="empty-icon" aria-hidden="true"><i class="bi bi-journal-check"></i></div>
            <h3 class="h5 mb-2">Belum ada buku yang dipinjam</h3>
            <p class="mb-4">Ajukan peminjaman dari halaman detail buku.</p>
            <a class="btn btn-primary-clay" href="<?= e(url('katalog.php')) ?>">
                <i class="bi bi-search" aria-hidden="true"></i> Jelajahi Katalog
            </a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 g-3">
            <?php foreach ($active as $row): ?>
                <?php
                $late     = hari_terlambat($row['due_at']);
                $denda    = hitung_denda($late);
                $sisaHari = max(0, (int) ((strtotime((string) $row['due_at']) - strtotime('today')) / 86400));
                ?>
                <div class="col">
                    <article class="clay-card-sm p-3 h-100 d-flex gap-3">
                        <img src="<?= e(cover_url($row['cover_path'] ?? null)) ?>"
                             alt="Cover <?= e($row['book_title']) ?>"
                             style="width: 70px; height: 93px; object-fit: cover; border-radius: 10px; flex-shrink: 0;">

                        <div class="flex-grow-1">
                            <h3 class="h6 mb-1"><?= e($row['book_title']) ?></h3>
                            <p class="text-sm text-muted-clay mb-2"><?= e($row['book_author']) ?></p>

                            <dl class="row g-0 text-sm mb-2">
                                <dt class="col-5 text-muted-clay fw-normal">Dipinjam</dt>
                                <dd class="col-7 mb-0"><?= e(tanggal_id($row['borrowed_at'])) ?></dd>
                                <dt class="col-5 text-muted-clay fw-normal">Jatuh tempo</dt>
                                <dd class="col-7 mb-0 fw-semibold"><?= e(tanggal_id($row['due_at'])) ?></dd>
                            </dl>

                            <?php if ($late > 0): ?>
                                <span class="badge-clay badge-danger">
                                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                    Terlambat <?= $late ?> hari
                                </span>
                                <p class="text-sm text-danger mb-0 mt-2">
                                    Estimasi denda: <strong><?= e(rupiah($denda)) ?></strong>
                                </p>
                            <?php else: ?>
                                <span class="badge-clay badge-success">
                                    <i class="bi bi-check-circle" aria-hidden="true"></i>
                                    Sisa <?= $sisaHari ?> hari
                                </span>
                            <?php endif; ?>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($pending !== []): ?>
    <section class="clay-panel mb-4">
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
                                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                    <?= e(label_status((string) $row['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-sm text-muted-clay mt-3 mb-0">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            Buku akan diserahkan oleh petugas setelah pengajuan disetujui.
        </p>
    </section>
<?php endif; ?>

<?php if ($fines !== []): ?>
    <section class="clay-panel">
        <div class="section-heading">
            <h2 class="h5 mb-0">Denda Belum Lunas</h2>
        </div>

        <div class="table-wrap">
            <table class="table-clay">
                <thead>
                    <tr>
                        <th scope="col">Buku</th>
                        <th scope="col">Dikembalikan</th>
                        <th scope="col">Denda</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fines as $row): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($row['book_title']) ?></td>
                            <td><?= e(tanggal_id($row['returned_at'])) ?></td>
                            <td class="fw-semibold text-danger"><?= e(rupiah($row['fine_amount'])) ?></td>
                            <td>
                                <span class="<?= e(badge_denda((string) $row['fine_status'])) ?>">
                                    <?= e(label_denda((string) $row['fine_status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <p class="text-sm text-muted-clay mt-3 mb-0">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            Pembayaran denda dilakukan langsung ke petugas perpustakaan.
        </p>
    </section>
<?php endif; ?>
