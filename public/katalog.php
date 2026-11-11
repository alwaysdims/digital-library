<?php
/**
 * Katalog buku publik: pencarian, filter kategori, pagination.
 */

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

$search   = query('q', '');
$category = (int) (query('kategori') ?? 0);
$sort     = (string) (query('urut') ?? 'terbaru');
$page     = max(1, (int) (query('hal') ?? 1));

$search = is_string($search) ? trim($search) : '';

$result     = book_list($search, $category > 0 ? $category : null, $sort, $page, 12);
$categories = category_all();
$activeCat  = $category > 0 ? category_find($category) : null;

render('public', 'public/catalog', [
    'pageTitle'  => 'Katalog Buku',
    'categories' => $categories,
    'result'     => $result,
    'search'     => $search,
    'category'   => $category,
    'activeCat'  => $activeCat,
    'sort'       => $sort,
]);
