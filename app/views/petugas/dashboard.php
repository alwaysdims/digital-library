<?php
/**
 * Dashboard petugas.
 *
 * @var array $stats
 * @var array $recent
 */
?>
<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">
    <div class="col">
        <?php $label = 'Pengajuan Menunggu'; $value = $stats['pending']; $icon = 'bi-hourglass-split'; $tone = 'warning';
        $hint = 'Perlu ditinjau'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
    <div class="col">
        <?php $label = 'Buku Dipinjam'; $value = $stats['active_loans']; $icon = 'bi-book'; $tone = 'info';
        $hint = 'Transaksi aktif'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
    <div class="col">
        <?php $label = 'Buku Terlambat'; $value = $stats['late_loans']; $icon = 'bi-exclamation-triangle'; $tone = 'danger';
        $hint = 'Melewati jatuh tempo'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
    <div class="col">
        <?php $label = 'Denda Belum Lunas'; $value = rupiah($stats['unpaid_fines']); $icon = 'bi-cash-coin'; $tone = 'accent';
        $hint = 'Perlu ditagih'; require __DIR__ . '/../partials/stat_card.php'; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <a class="btn btn-primary-clay btn-clay-block" href="<?= e(url('petugas/peminjaman.php')) ?>">
            <i class="bi bi-clipboard-check" aria-hidden="true"></i> Proses Pengajuan
        </a>
    </div>
    <div class="col-12 col-md-4">
        <a class="btn btn-secondary-clay btn-clay-block" href="<?= e(url('petugas/pengembalian.php')) ?>">
            <i class="bi bi-arrow-return-left" aria-hidden="true"></i> Catat Pengembalian
        </a>
    </div>
    <div class="col-12 col-md-4">
        <a class="btn btn-ghost-clay btn-clay-block" href="<?= e(url('petugas/anggota.php')) ?>">
            <i class="bi bi-person-plus" aria-hidden="true"></i> Daftarkan Anggota
        </a>
    </div>
</div>

<section class="clay-panel">
    <div class="section-heading">
        <h2 class="h5 mb-0">Transaksi Terbaru</h2>
    </div>

    <?php $rows = $recent; $showUser = true; $showActions = false; $emptyMessage = 'Belum ada transaksi peminjaman.';
    require __DIR__ . '/../partials/borrowing_table.php'; ?>
</section>
