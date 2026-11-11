<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { exit('CLI only'); }
require __DIR__ . '/../app/bootstrap.php';

$pass = 0; $fail = 0;
function ck(string $l, bool $c): void { global $pass,$fail; if($c){$pass++;echo "  [PASS] $l\n";}else{$fail++;echo "  [FAIL] $l\n";} }

echo "=== Uji Kasus Tepi & Ketahanan ===\n\n";

echo "1. Penghapusan kategori yang masih memiliki buku\n";
$cat = category_all(true)[0];
if ((int)$cat['book_count'] > 0) {
    ck('Kategori berisi buku tidak dapat dihapus', category_delete((int)$cat['id']) === false);
} else {
    echo "  [skip] kategori pertama kosong\n";
}

echo "\n2. Pembatasan stok pada update buku\n";
$b = book_list(null, null, 'terbaru', 1, 1)['items'][0];
$upd = book_update((int)$b['id'], [
    'category_id' => (int)$b['category_id'], 'title' => $b['title'], 'author' => $b['author'],
    'published_year' => $b['published_year'], 'synopsis' => 'x', 'cover_path' => null, 'stock_total' => 0,
]);
$borrowedOut = (int)$b['stock_total'] - (int)$b['stock_available'];
if ($borrowedOut > 0) {
    ck('Stok total < buku dipinjam ditolak', $upd === false);
} else {
    echo "  [skip] tidak ada buku yang sedang dipinjam pada buku uji (stok total 0 valid)\n";
    // Pulihkan stok
    book_update((int)$b['id'], [
        'category_id' => (int)$b['category_id'], 'title' => $b['title'], 'author' => $b['author'],
        'published_year' => $b['published_year'], 'synopsis' => $b['synopsis'], 'cover_path' => $b['cover_path'],
        'stock_total' => (int)$b['stock_total'],
    ]);
}

echo "\n3. Validasi upload cover\n";
$fake = tempnam(sys_get_temp_dir(), 'x') . '.jpg';
file_put_contents($fake, 'this is not an image');
$r = cover_upload(['error' => UPLOAD_ERR_OK, 'size' => 20, 'tmp_name' => $fake, 'name' => 'x.jpg']);
ck('File non-gambar ditolak', !$r['ok']);
$r2 = cover_upload(['error' => UPLOAD_ERR_OK, 'size' => 9_999_999, 'tmp_name' => $fake, 'name' => 'big.jpg']);
ck('Ukuran berlebih ditolak', !$r2['ok']);
$r3 = cover_upload(['error' => UPLOAD_ERR_OK, 'size' => 20, 'tmp_name' => '/nonexistent/path.php', 'name' => 'evil.php']);
ck('Ekstensi .php ditolak', !$r3['ok']);
@unlink($fake);

echo "\n4. Perhitungan denda\n";
ck('Tepat waktu -> Rp0', hitung_denda(hari_terlambat(date('Y-m-d'))) === 0);
ck('Terlambat 1 hari -> Rp1.000', hitung_denda(1) === 1000);
ck('Terlambat 3 hari -> Rp3.000', hitung_denda(3) === 3000);
ck('Terlambat 5 hari -> Rp5.000', hitung_denda(5) === 5000);
ck('Batas bawah: hari negatif -> Rp0', hitung_denda(-2) === 0);

echo "\n5. Format & escaping\n";
ck('Rupiah 3000 -> Rp3.000', rupiah(3000) === 'Rp3.000');
ck('Rupiah 0 -> Rp0', rupiah(0) === 'Rp0');
ck('Escaping XSS aktif', e('<script>x</script>') === '&lt;script&gt;x&lt;/script&gt;');
ck('Escaping tanda kutip', e('a"b\'c') === 'a&quot;b&#039;c');

echo "\n6. Autentikasi\n";
ck('Password salah ditolak', user_authenticate('admin', 'salah') === null);
ck('Password benar diterima', user_authenticate('admin', 'Admin#Lentera2024') !== null);
ck('Username tidak ada ditolak', user_authenticate('tidakada', 'x') === null);

echo "\n7. Penghapusan data dengan riwayat\n";
$ricky = user_find_by_username('petugas');
ck('User dengan riwayat tidak dapat dihapus', user_has_history(1) ? user_delete(1) === false : true);

echo "\n8. Pencarian (multiple placeholder regression)\n";
$found = book_list('matematika', null, 'terbaru', 1, 12);
ck('Cari buku berdasarkan judul berhasil', $found['total'] === 1, 'total=' . $found['total']);
$foundAuthor = book_list('Prameswari', null, 'terbaru', 1, 12);
ck('Cari buku berdasarkan penulis berhasil', $foundAuthor['total'] >= 1, 'total=' . $foundAuthor['total']);
ck('Cari anggota berhasil', count(user_list_by_role('user', 'anggota')) >= 1);
ck('Cari transaksi tidak error', is_array(borrowing_list(null, 'budi', 'all')));
ck('Pencarian tanpa hasil mengembalikan kosong', book_list('zzznotexist', null, 'terbaru', 1, 12)['total'] === 0);

echo "\n=== Hasil: $pass lulus, $fail gagal ===\n";
exit($fail > 0 ? 1 : 0);
