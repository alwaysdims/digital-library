<?php
/**
 * Dashboard admin.
 *
 * @var array $stats
 * @var array $recent
 * @var int $staff
 */
?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    <div class="col">
        <?php $label = 'Total Buku'; $value = $stats['total_books']; $icon = 'bi-journal-bookmark'; $tone = 'primary';
        $hint = $stats['total_titles'] . ' judul'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
    <div class="col">
        <?php $label = 'Total Anggota'; $value = $stats['total_members']; $icon = 'bi-people'; $tone = 'info';
        $hint = $staff . ' akun petugas'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
    <div class="col">
        <?php $label = 'Buku Dipinjam'; $value = $stats['active_loans']; $icon = 'bi-arrow-left-right'; $tone = 'accent';
        $hint = $stats['late_loans'] . ' terlambat'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
    <div class="col">
        <?php $label = 'Denda Belum Lunas'; $value = rupiah($stats['unpaid_fines']); $icon = 'bi-cash-coin'; $tone = 'danger';
        $hint = $stats['pending'] . ' pengajuan menunggu'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
</div>

<section class="clay-panel">
    <div class="section-heading">
        <h2 class="h5 mb-0">Transaksi Terbaru</h2>
        <a class="btn btn-ghost-clay btn-clay-sm" href="<?= e(url('admin/peminjaman.php')) ?>">
            Lihat Semua <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </div>

    <?php $rows = $recent; $showUser = true; $showActions = false; $emptyMessage = 'Belum ada transaksi peminjaman.';
    require __DIR__ . '/../partials/borrowing_table.php'; ?>
</section>
