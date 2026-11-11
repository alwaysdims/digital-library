<?php
/**
 * Handler CRUD buku bersama untuk admin dan petugas.
 * Di-include oleh public/admin/buku.php dan public/petugas/buku.php.
 */

declare(strict_types=1);

/**
 * Jalankan halaman kelola buku.
 *
 * @param string $basePath Path relatif ke halaman ini (mis. 'admin/buku.php').
 */
function handle_book_management(string $basePath): void
{
    $action = (string) (query('aksi') ?? 'daftar');
    $id     = (int) (query('id') ?? 0);

    // --- Aksi POST -----------------------------------------------------
    if (is_post()) {
        csrf_verify();

        $postAction = (string) post('action', '');

        if ($postAction === 'save') {
            save_book($basePath);
        }

        if ($postAction === 'delete') {
            $deleteId = (int) post('id', '0');
            $book     = book_find($deleteId);

            if ($book === null) {
                flash('danger', 'Buku tidak ditemukan.');
            } elseif (book_delete($deleteId)) {
                flash('success', 'Buku "' . $book['title'] . '" berhasil dihapus.');
            } else {
                flash('warning', 'Buku tidak dapat dihapus karena sudah memiliki riwayat peminjaman.');
            }

            redirect($basePath);
        }
    }

    // --- Halaman form tambah / edit ------------------------------------
    if ($action === 'tambah' || $action === 'edit') {
        $book       = $action === 'edit' ? book_find($id) : null;
        $categories = category_all();
        $errors     = [];

        if ($action === 'edit' && $book === null) {
            flash('warning', 'Buku tidak ditemukan.');
            redirect($basePath);
        }

        if ($categories === []) {
            flash('warning', 'Tambahkan minimal satu kategori sebelum mengelola buku.');
            redirect($basePath);
        }

        render('dashboard', 'shared/book_form', [
            'pageTitle'  => $action === 'edit' ? 'Edit Buku' : 'Tambah Buku',
            'heading'    => $action === 'edit' ? 'Edit Buku' : 'Tambah Buku',
            'book'       => $book,
            'categories' => $categories,
            'errors'     => $errors,
            'basePath'   => $basePath,
        ]);

        return;
    }

    // --- Daftar buku ---------------------------------------------------
    $search   = (string) (query('q') ?? '');
    $page     = max(1, (int) (query('hal') ?? 1));
    $result   = book_list($search !== '' ? $search : null, null, 'terbaru', $page, 15);
    $stats    = [
        'total_titles' => $result['total'],
    ];

    render('dashboard', 'shared/book_list', [
        'pageTitle'  => 'Kelola Buku',
        'heading'    => 'Kelola Buku',
        'result'     => $result,
        'search'     => $search,
        'basePath'   => $basePath,
        'categories' => category_all(true),
    ]);
}

/**
 * Simpan (tambah atau edit) buku dari input form.
 */
function save_book(string $basePath): void
{
    $id         = (int) post('id', '0');
    $isEdit     = $id > 0;
    $existing   = $isEdit ? book_find($id) : null;

    if ($isEdit && $existing === null) {
        flash('danger', 'Buku tidak ditemukan.');
        redirect($basePath);
    }

    $title    = (string) post('title', '');
    $author   = (string) post('author', '');
    $category = (int) post('category_id', '0');
    $yearRaw  = (string) post('published_year', '');
    $synopsis = (string) post('synopsis', '');
    $stock    = (int) post('stock_total', '0');

    $errors = [];

    if ($title === '' || mb_strlen($title) > 200) {
        $errors['title'] = 'Judul wajib diisi (maksimal 200 karakter).';
    }

    if ($author === '' || mb_strlen($author) > 150) {
        $errors['author'] = 'Penulis wajib diisi (maksimal 150 karakter).';
    }

    if ($category <= 0 || category_find($category) === null) {
        $errors['category_id'] = 'Pilih kategori yang valid.';
    }

    $year = null;

    if ($yearRaw !== '') {
        if (!ctype_digit($yearRaw) || (int) $yearRaw < 1000 || (int) $yearRaw > (int) date('Y') + 1) {
            $errors['published_year'] = 'Tahun terbit tidak valid.';
        } else {
            $year = (int) $yearRaw;
        }
    }

    if ($stock < 0 || $stock > 100000) {
        $errors['stock_total'] = 'Jumlah stok tidak valid.';
    }

    // Cover upload.
    $coverName = $existing['cover_path'] ?? null;
    $newCoverName = null;

    if (isset($_FILES['cover']) && is_array($_FILES['cover']) && ($_FILES['cover']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = cover_upload($_FILES['cover']);

        if (!$upload['ok']) {
            $errors['cover'] = $upload['error'] ?? 'Cover gagal diunggah.';
        } else {
            $coverName = $upload['name'];
            $newCoverName = $upload['name'];
        }
    }

    if ($errors !== []) {
        if ($newCoverName !== null) {
            delete_cover_file($newCoverName);
        }

        render('dashboard', 'shared/book_form', [
            'pageTitle'  => $isEdit ? 'Edit Buku' : 'Tambah Buku',
            'heading'    => $isEdit ? 'Edit Buku' : 'Tambah Buku',
            'book'       => [
                'id'              => $id,
                'title'           => $title,
                'author'          => $author,
                'category_id'     => $category,
                'published_year'  => $year,
                'synopsis'        => $synopsis,
                'stock_total'     => $stock,
                'stock_available' => $existing['stock_available'] ?? $stock,
                'cover_path'      => $coverName,
            ],
            'categories' => category_all(),
            'errors'     => $errors,
            'basePath'   => $basePath,
        ]);

        return;
    }

    if ($isEdit) {
        $ok = book_update($id, [
            'category_id'    => $category,
            'title'          => $title,
            'author'         => $author,
            'published_year' => $year,
            'synopsis'       => $synopsis !== '' ? $synopsis : null,
            'cover_path'     => $coverName,
            'stock_total'    => $stock,
        ]);

        if ($ok) {
            if ($isEdit && !empty($existing['cover_path']) && $newCoverName !== null) {
                delete_cover_file((string) $existing['cover_path']);
            }
            flash('success', 'Buku "' . $title . '" berhasil diperbarui.');
        } else {
            if ($newCoverName !== null) {
                delete_cover_file($newCoverName);
            }
            flash('danger', 'Buku gagal diperbarui. Stok tidak boleh lebih kecil dari jumlah buku yang sedang dipinjam.');
        }
    } else {
        book_create([
            'category_id'     => $category,
            'title'           => $title,
            'author'          => $author,
            'published_year'  => $year,
            'synopsis'        => $synopsis !== '' ? $synopsis : null,
            'cover_path'      => $coverName,
            'stock_total'     => $stock,
            'stock_available' => $stock,
        ]);

        flash('success', 'Buku "' . $title . '" berhasil ditambahkan.');
    }

    redirect($basePath);
}
