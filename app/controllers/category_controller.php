<?php
/**
 * Handler CRUD kategori.
 */

declare(strict_types=1);

/**
 * Jalankan halaman kelola kategori.
 *
 * @param string $basePath Path relatif halaman (mis. 'admin/kategori.php').
 */
function handle_category_management(string $basePath): void
{
    // --- POST: simpan / hapus ------------------------------------------
    if (is_post()) {
        csrf_verify();

        $action = (string) post('action', '');

        if ($action === 'save') {
            $id   = (int) post('id', '0');
            $name = (string) post('name', '');

            if ($name === '' || mb_strlen($name) > 80) {
                flash('danger', 'Nama kategori wajib diisi (maksimal 80 karakter).');
                redirect($basePath);
            }

            if ($id > 0) {
                if (category_find($id) === null) {
                    flash('danger', 'Kategori tidak ditemukan.');
                } elseif (category_update($id, $name)) {
                    flash('success', 'Kategori "' . $name . '" berhasil diperbarui.');
                } else {
                    flash('warning', 'Nama kategori sudah dipakai.');
                }
            } else {
                if (category_create($name)) {
                    flash('success', 'Kategori "' . $name . '" berhasil ditambahkan.');
                } else {
                    flash('warning', 'Nama kategori sudah dipakai.');
                }
            }

            redirect($basePath);
        }

        if ($action === 'delete') {
            $id       = (int) post('id', '0');
            $category = category_find($id);

            if ($category === null) {
                flash('danger', 'Kategori tidak ditemukan.');
            } elseif (category_delete($id)) {
                flash('success', 'Kategori "' . $category['name'] . '" berhasil dihapus.');
            } else {
                flash('warning', 'Kategori tidak dapat dihapus karena masih memiliki buku.');
            }

            redirect($basePath);
        }
    }

    // --- Form edit -------------------------------------------------------
    $editId   = (int) (query('edit') ?? 0);
    $editing  = $editId > 0 ? category_find($editId) : null;

    if ($editId > 0 && $editing === null) {
        flash('warning', 'Kategori tidak ditemukan.');
        redirect($basePath);
    }

    render('dashboard', 'shared/category_list', [
        'pageTitle'  => 'Kelola Kategori',
        'heading'    => 'Kelola Kategori',
        'categories' => category_all(true),
        'editing'    => $editing,
        'basePath'   => $basePath,
    ]);
}
