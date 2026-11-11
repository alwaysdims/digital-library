<?php
/**
 * Beranda publik Lentera Digital Library.
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$search   = query('q');
$category = (int) (query('kategori') ?? 0);

$categories = category_all();
$featured   = book_featured(8);

// Bila ada pencarian dari hero, arahkan pencarian ke katalog.
if ($search !== null && $search !== '') {
    redirect('katalog.php?q=' . urlencode($search));
}

render('public', 'public/home', [
    'pageTitle'  => 'Beranda',
    'categories' => $categories,
    'featured'   => $featured,
]);
