<?php
/**
 * Kartu buku untuk beranda, katalog, dan buku terkait.
 *
 * @var array $book
 */

$available = (int) ($book['stock_available'] ?? 0);
?>
<article class="book-card">
    <img class="book-cover" src="<?= e(cover_url($book['cover_path'] ?? null)) ?>"
         alt="Cover buku <?= e($book['title'] ?? '') ?>" loading="lazy">

    <div class="book-card-body">
        <span class="badge-clay <?= $available > 0 ? 'badge-success' : 'badge-danger' ?> align-self-start">
            <i class="bi <?= $available > 0 ? 'bi-check-circle' : 'bi-x-circle' ?>" aria-hidden="true"></i>
            <?= $available > 0 ? 'Tersedia' : 'Tidak Tersedia' ?>
        </span>

        <h3 class="book-title"><?= e($book['title'] ?? '') ?></h3>
        <p class="book-author">
            <i class="bi bi-person" aria-hidden="true"></i> <?= e($book['author'] ?? '') ?>
        </p>
        <p class="book-author">
            <i class="bi bi-tag" aria-hidden="true"></i> <?= e($book['category_name'] ?? '-') ?>
        </p>

        <div class="book-card-foot">
            <a class="btn btn-primary-clay btn-clay-sm btn-clay-block"
               href="<?= e(url('detail.php?id=' . (int) $book['id'])) ?>">
                Lihat Detail
            </a>
        </div>
    </div>
</article>
