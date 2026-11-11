<?php
/**
 * Form tambah/edit buku.
 *
 * @var array|null $book
 * @var array $categories
 * @var array $errors
 * @var string $basePath
 */

$isEdit = !empty($book['id']);

$val = static function (string $key, mixed $default = '') use ($book): string {
    $value = $book[$key] ?? $default;

    return $value === null ? '' : (string) $value;
};

$fieldError = static fn (string $key): ?string => $errors[$key] ?? null;
?>
<section class="clay-panel" style="max-width: 820px;">
    <div class="section-heading">
        <h2 class="h5 mb-0"><?= $isEdit ? 'Edit Buku' : 'Tambah Buku Baru' ?></h2>
        <a class="btn btn-ghost-clay btn-clay-sm" href="<?= e(url($basePath)) ?>">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali
        </a>
    </div>

    <form method="post" action="<?= e(url($basePath)) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="title">Judul Buku <span class="text-danger" aria-hidden="true">*</span></label>
                <input class="clay-input <?= $fieldError('title') ? 'is-invalid' : '' ?>" type="text"
                       id="title" name="title" value="<?= e($val('title')) ?>" maxlength="200" required>
                <?php if ($err = $fieldError('title')): ?><span class="field-error"><?= e($err) ?></span><?php endif; ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="author">Penulis <span class="text-danger" aria-hidden="true">*</span></label>
                <input class="clay-input <?= $fieldError('author') ? 'is-invalid' : '' ?>" type="text"
                       id="author" name="author" value="<?= e($val('author')) ?>" maxlength="150" required>
                <?php if ($err = $fieldError('author')): ?><span class="field-error"><?= e($err) ?></span><?php endif; ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="category_id">Kategori <span class="text-danger" aria-hidden="true">*</span></label>
                <select class="form-select <?= $fieldError('category_id') ? 'is-invalid' : '' ?>"
                        id="category_id" name="category_id" required>
                    <option value="">Pilih kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"
                            <?= (string) $val('category_id') === (string) $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($err = $fieldError('category_id')): ?><span class="field-error"><?= e($err) ?></span><?php endif; ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="published_year">Tahun Terbit</label>
                <input class="clay-input <?= $fieldError('published_year') ? 'is-invalid' : '' ?>" type="number"
                       id="published_year" name="published_year" value="<?= e($val('published_year')) ?>"
                       min="1000" max="<?= (int) date('Y') + 1 ?>" placeholder="Contoh: 2023">
                <span class="form-hint">Opsional.</span>
                <?php if ($err = $fieldError('published_year')): ?><span class="field-error"><?= e($err) ?></span><?php endif; ?>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="stock_total">Jumlah Stok <span class="text-danger" aria-hidden="true">*</span></label>
                <input class="clay-input <?= $fieldError('stock_total') ? 'is-invalid' : '' ?>" type="number"
                       id="stock_total" name="stock_total" value="<?= e($val('stock_total', '1')) ?>" min="0" required>
                <?php if ($isEdit): ?>
                    <span class="form-hint">
                        Saat ini <?= (int) ($book['stock_available'] ?? 0) ?> tersedia dari <?= (int) ($book['stock_total'] ?? 0) ?>.
                        Stok tidak boleh lebih kecil dari jumlah buku yang sedang dipinjam.
                    </span>
                <?php endif; ?>
                <?php if ($err = $fieldError('stock_total')): ?><span class="field-error"><?= e($err) ?></span><?php endif; ?>
            </div>

            <div class="col-12">
                <label class="form-label" for="synopsis">Sinopsis</label>
                <textarea class="clay-input" id="synopsis" name="synopsis" rows="5"
                          placeholder="Ringkasan isi buku..."><?= e($val('synopsis')) ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label" for="cover">Cover Buku</label>
                <input class="form-control <?= $fieldError('cover') ? 'is-invalid' : '' ?>" type="file"
                       id="cover" name="cover" accept="image/jpeg,image/png,image/webp">
                <span class="form-hint">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</span>
                <?php if ($err = $fieldError('cover')): ?><span class="field-error"><?= e($err) ?></span><?php endif; ?>

                <?php if ($isEdit && !empty($book['cover_path'])): ?>
                    <div class="mt-3">
                        <img src="<?= e(cover_url($book['cover_path'])) ?>" alt="Cover saat ini"
                             style="max-width: 120px; border-radius: 12px; border: 1px solid var(--border);">
                        <p class="form-hint mb-0">Cover saat ini. Unggah file baru untuk menggantinya.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary-clay" type="submit">
                <i class="bi bi-check-lg" aria-hidden="true"></i> <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Buku' ?>
            </button>
            <a class="btn btn-ghost-clay" href="<?= e(url($basePath)) ?>">Batal</a>
        </div>
    </form>
</section>
