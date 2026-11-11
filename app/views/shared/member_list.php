<?php
/**
 * Daftar anggota + form pendaftaran/edit.
 *
 * @var array $members
 * @var string $search
 * @var array|null $editing
 * @var string $basePath
 */

$isEdit = $editing !== null;
?>
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <section class="clay-panel">
            <h2 class="h5 mb-3"><?= $isEdit ? 'Edit Anggota' : 'Daftarkan Anggota' ?></h2>

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
                    <span class="form-hint">Huruf kecil, angka, titik, atau garis bawah.</span>
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
                        <?= $isEdit
                            ? 'Biarkan kosong bila tidak ingin mengganti password.'
                            : 'Password awal dibuat otomatis. Sampaikan sekali ke anggota, lalu minta segera menggantinya.' ?>
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
                        <?= $isEdit ? 'Simpan' : 'Daftarkan' ?>
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
            <div class="section-heading">
                <div>
                    <h2 class="h5 mb-1">Daftar Anggota</h2>
                    <p class="text-muted-clay mb-0 text-sm"><?= count($members) ?> anggota terdaftar</p>
                </div>
            </div>

            <form class="filter-bar mb-4" method="get" action="<?= e(url($basePath)) ?>" role="search">
                <div class="flex-grow-1" style="min-width: 200px;">
                    <label class="visually-hidden" for="member-search">Cari anggota</label>
                    <input class="clay-input" type="search" id="member-search" name="q"
                           placeholder="Cari nama atau username..." value="<?= e($search) ?>">
                </div>
                <button class="btn btn-secondary-clay" type="submit">
                    <i class="bi bi-search" aria-hidden="true"></i> Cari
                </button>
                <?php if ($search !== ''): ?>
                    <a class="btn btn-ghost-clay" href="<?= e(url($basePath)) ?>">Reset</a>
                <?php endif; ?>
            </form>

            <?php if ($members === []): ?>
                <div class="empty-state">
                    <div class="empty-icon" aria-hidden="true"><i class="bi bi-people"></i></div>
                    <h3 class="h5 mb-2">Belum ada anggota</h3>
                    <p class="mb-0">
                        <?= $search !== '' ? 'Tidak ada anggota yang cocok dengan pencarian.' : 'Daftarkan anggota pertama melalui form di samping.' ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table-clay">
                        <thead>
                            <tr>
                                <th scope="col">Nama</th>
                                <th scope="col">Username</th>
                                <th scope="col">Terdaftar</th>
                                <th scope="col">Status</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($member['name']) ?></div>
                                        <?php if (!empty($member['email'])): ?>
                                            <div class="text-sm text-muted-clay"><?= e($member['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>@<?= e($member['username']) ?></td>
                                    <td class="text-sm text-muted-clay"><?= e(tanggal_id($member['created_at'])) ?></td>
                                    <td>
                                        <span class="badge-clay <?= (int) $member['is_active'] === 1 ? 'badge-success' : 'badge-muted' ?>">
                                            <?= (int) $member['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="cell-actions">
                                            <a class="btn btn-secondary-clay btn-clay-sm"
                                               href="<?= e(url($basePath . '?edit=' . (int) $member['id'])) ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                                            </a>
                                            <form method="post" action="<?= e(url($basePath)) ?>" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                                                <button class="btn btn-ghost-clay btn-clay-sm" type="submit">
                                                    <?= (int) $member['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                </button>
                                            </form>
                                            <form method="post" action="<?= e(url($basePath)) ?>"
                                                  data-confirm="Hapus akun anggota &quot;<?= e($member['name']) ?>&quot;?"
                                                  class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
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
