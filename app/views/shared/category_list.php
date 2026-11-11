<?php
/**
 * Daftar kategori + form tambah/edit.
 *
 * @var array $categories
 * @var array|null $editing
 * @var string $basePath
 */

$isEdit = $editing !== null;
?>
<div class="row g-4">
    <div class="col-12 col-lg-5">
        <section class="clay-panel">
            <h2 class="h5 mb-3"><?= $isEdit ? 'Edit Kategori' : 'Tambah Kategori' ?></h2>

            <form method="post" action="<?= e(url($basePath)) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label" for="name">Nama Kategori <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="clay-input" type="text" id="name" name="name"
                           value="<?= e($editing['name'] ?? '') ?>" maxlength="80" required>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary-clay" type="submit">
                        <i class="bi <?= $isEdit ? 'bi-check-lg' : 'bi-plus-lg' ?>" aria-hidden="true"></i>
                        <?= $isEdit ? 'Simpan' : 'Tambah' ?>
                    </button>
                    <?php if ($isEdit): ?>
                        <a class="btn btn-ghost-clay" href="<?= e(url($basePath)) ?>">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </div>

    <div class="col-12 col-lg-7">
        <section class="clay-panel">
            <h2 class="h5 mb-3">Daftar Kategori</h2>

            <?php if ($categories === []): ?>
                <div class="empty-state">
                    <div class="empty-icon" aria-hidden="true"><i class="bi bi-tags"></i></div>
                    <p class="mb-0">Belum ada kategori.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table-clay">
                        <thead>
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col">Jumlah Buku</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($cat['name']) ?></td>
                                    <td><span class="badge-clay badge-muted"><?= (int) $cat['book_count'] ?> buku</span></td>
                                    <td>
                                        <div class="cell-actions">
                                            <a class="btn btn-secondary-clay btn-clay-sm"
                                               href="<?= e(url($basePath . '?edit=' . (int) $cat['id'])) ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                                            </a>
                                            <form method="post" action="<?= e(url($basePath)) ?>"
                                                  data-confirm="Hapus kategori &quot;<?= e($cat['name']) ?>&quot;?"
                                                  class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                                                <button class="btn btn-danger-clay btn-clay-sm" type="submit">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
