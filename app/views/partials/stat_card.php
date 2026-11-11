<?php
/**
 * Kartu statistik dashboard.
 *
 * @var string $label
 * @var string|int $value
 * @var string $icon   Kelas ikon Bootstrap.
 * @var string $tone   primary|warning|danger|info|accent
 * @var string|null $hint
 */

$tone = $tone ?? 'primary';
?>
<div class="stat-card">
    <div class="stat-icon stat-icon-<?= e($tone) ?>" aria-hidden="true">
        <i class="bi <?= e($icon) ?>"></i>
    </div>
    <div class="stat-value"><?= e((string) $value) ?></div>
    <div class="stat-label"><?= e($label) ?></div>
    <?php if (!empty($hint)): ?>
        <div class="stat-label mt-1 text-sm"><?= e($hint) ?></div>
    <?php endif; ?>
</div>
