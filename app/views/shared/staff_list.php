<?php
/**
 * Daftar petugas + form buat/edit.
 *
 * @var array $staff
 * @var array|null $editing
 * @var string $basePath
 */

$isEdit = $editing !== null;
?>
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <section class="clay-panel">
            <h2 class="h5 mb-3"><?= $isEdit ? 'Edit Petugas' : 'Buat Akun Petugas' ?></h2>

            <form method="post" action="<?= e(url($basePath)) ?>" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= (int) $editing['id'] ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label" for="name">Nama Lengkap <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="clay-input" type="text" id="name" name="name"
                           value="<?= e($editing['name'] ?? '') ?>" maxlength="120" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="username">Username <span class="text-danger" aria-hidden="true">*</span></label>
                    <input class="clay-input" type="text" id="username" name="username"
                           value="<?= e($editing['username'] ?? '') ?>" maxlength="60" required
                           pattern="[a-z0-9._]{3,60}">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="clay-input" type="email" id="email" name="email"
                           value="<?= e($editing['email'] ?? '') ?>" maxlength="150">
                    <span class="form-hint">Opsional.</span>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">
                        Password <?= $isEdit ? '' : '<span class="text-danger" aria-hidden="true">*</span>' ?>
                    </label>
                    <input class="clay-input" type="text" id="password" name="password"
                           value="<?= $isEdit ? '' : e(generate_password()) ?>"
                           minlength="6" <?= $isEdit ? '' : 'required' ?> autocomplete="off">
                    <span class="form-hint">
                        <?= $isEdit ? 'Biarkan kosong bila tidak ingin mengganti password.' : 'Catat dan sampaikan password ini kepada petugas.' ?>
                    </span>
                </div>

                <?php if ($isEdit): ?>
                    <div class="mb-3">
                        <label class="form-label" for="is_active">Status Akun</label>
                        <select class="form-select" id="is_active" name="is_active">
                            <option value="1" <?= (int) $editing['is_active'] === 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= (int) $editing['is_active'] === 0 ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary-clay" type="submit">
                        <i class="bi <?= $isEdit ? 'bi-check-lg' : 'bi-person-plus' ?>" aria-hidden="true"></i>
                        <?= $isEdit ? 'Simpan' : 'Buat Akun' ?>
                    </button>
                    <?php if ($isEdit): ?>
                        <a class="btn btn-ghost-clay" href="<?= e(url($basePath)) ?>">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </div>

    <div class="col-12 col-xl-8">
        <section class="clay-panel">
            <h2 class="h5 mb-3">Daftar Petugas</h2>

            <?php if ($staff === []): ?>
                <div class="empty-state">
                    <div class="empty-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></div>
                    <p class="mb-0">Belum ada akun petugas.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table-clay">
                        <thead>
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col">Username</th>
                                <th scope="col">Status</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $person): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($person['name']) ?></div>
                                        <?php if (!empty($person['email'])): ?>
                                            <div class="text-sm text-muted-clay"><?= e($person['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>@<?= e($person['username']) ?></td>
                                    <td>
                                        <span class="badge-clay <?= (int) $person['is_active'] === 1 ? 'badge-success' : 'badge-muted' ?>">
                                            <?= (int) $person['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="cell-actions">
                                            <a class="btn btn-secondary-clay btn-clay-sm"
                                               href="<?= e(url($basePath . '?edit=' . (int) $person['id'])) ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                                            </a>
                                            <form method="post" action="<?= e(url($basePath)) ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= (int) $person['id'] ?>">
                                                <button class="btn btn-ghost-clay btn-clay-sm" type="submit">
                                                    <?= (int) $person['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
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
