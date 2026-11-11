<?php
/**
 * Menampilkan pesan flash sesi (sukses/error/peringatan/info).
 */

$flashes = take_flashes();

foreach ($flashes as $flash):
    $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true)
        ? $flash['type']
        : 'info';

    $icon = match ($type) {
        'success' => 'bi-check-circle',
        'danger'  => 'bi-exclamation-octagon',
        'warning' => 'bi-exclamation-triangle',
        default   => 'bi-info-circle',
    };
    ?>
    <div class="alert-clay alert-<?= e($type) ?>" role="alert" data-auto-dismiss>
        <i class="bi <?= e($icon) ?> mt-1" aria-hidden="true"></i>
        <div><?= e($flash['message']) ?></div>
    </div>
<?php endforeach; ?>
