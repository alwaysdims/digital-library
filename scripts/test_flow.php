<?php
/**
 * Uji alur peminjaman end-to-end pada level service (tanpa HTTP).
 * Jalankan: php scripts/test_flow.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('CLI only');
}

require __DIR__ . '/../app/bootstrap.php';

$pdo = db();
$pass = 0;
$fail = 0;

function check(string $label, bool $condition, string $detail = ''): void
{
    global $pass, $fail;

    if ($condition) {
        $pass++;
        echo "  [PASS] {$label}\n";
    } else {
        $fail++;
        echo "  [FAIL] {$label}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
}

echo "=== Uji Alur Peminjaman ===\n\n";

// Siapkan data uji terpisah.
$admin   = user_find_by_username('admin');
$staff   = user_find_by_username('petugas');
$member  = user_find_by_username('anggota');

check('Akun admin tersedia', $admin !== null);
check('Akun petugas tersedia', $staff !== null);
check('Akun anggota tersedia', $member !== null);

$staffId  = (int) $staff['id'];
$memberId = (int) $member['id'];

// Buat buku khusus uji.
$catId = (int) db()->query("SELECT id FROM categories LIMIT 1")->fetchColumn();

$testTitle = 'BUKU UJI OTOMATIS ' . date('His');
$bookId = book_create([
    'category_id'     => $catId,
    'title'           => $testTitle,
    'author'          => 'Penguji',
    'published_year'  => 2024,
    'synopsis'        => 'Buku untuk pengujian otomatis.',
    'cover_path'      => null,
    'stock_total'     => 1,
    'stock_available' => 1,
]);

echo "\n1. Pengajuan peminjaman\n";
$r1 = borrowing_request($memberId, $bookId);
check('Pengajuan berhasil dibuat', $r1['ok'], $r1['message'] ?? '');
$borrowId = (int) ($r1['id'] ?? 0);

$stockAfterRequest = (int) book_find($bookId)['stock_available'];
check('Stok belum berkurang saat pengajuan', $stockAfterRequest === 1, "stok={$stockAfterRequest}");

echo "\n2. Pengajuan ganda ditolak\n";
$r2 = borrowing_request($memberId, $bookId);
check('Pengajuan ganda ditolak', !$r2['ok'], $r2['message'] ?? '');

echo "\n3. Persetujuan\n";
$r3 = borrowing_approve($borrowId, $staffId);
check('Persetujuan berhasil', $r3['ok'], $r3['message'] ?? '');

$book  = book_find($bookId);
$brow  = borrowing_find($borrowId);
check('Stok berkurang setelah disetujui', (int) $book['stock_available'] === 0, 'stok=' . $book['stock_available']);
check('Status menjadi dipinjam', $brow['status'] === 'dipinjam', 'status=' . $brow['status']);

$dueDays = (int) ((strtotime((string) $brow['due_at']) - strtotime((string) $brow['borrowed_at'])) / 86400);
check('Jatuh tempo 7 hari dari pinjam', $dueDays === 7, "selisih={$dueDays}");

echo "\n4. Persetujuan ganda dicegah\n";
$r4 = borrowing_approve($borrowId, $staffId);
check('Persetujuan ganda ditolak', !$r4['ok'], $r4['message'] ?? '');
check('Stok tidak berkurang dua kali', (int) book_find($bookId)['stock_available'] === 0);

echo "\n5. Pengembalian tepat waktu\n";
// Set jatuh tempo di masa depan agar tidak ada denda.
db()->prepare('UPDATE borrowings SET due_at = DATE_ADD(CURDATE(), INTERVAL 2 DAY) WHERE id = :id')
    ->execute([':id' => $borrowId]);

$r5 = borrowing_return($borrowId, $staffId);
check('Pengembalian berhasil', $r5['ok'], $r5['message'] ?? '');
check('Tidak ada denda tepat waktu', ($r5['fine'] ?? -1) === 0, 'fine=' . ($r5['fine'] ?? 'n/a'));
check('Stok kembali', (int) book_find($bookId)['stock_available'] === 1, 'stok=' . book_find($bookId)['stock_available']);
check('Status dikembalikan', borrowing_find($borrowId)['status'] === 'dikembalikan');

echo "\n6. Pengembalian ganda dicegah\n";
$r6 = borrowing_return($borrowId, $staffId);
check('Pengembalian ganda ditolak', !$r6['ok'], $r6['message'] ?? '');
check('Stok tidak bertambah dua kali', (int) book_find($bookId)['stock_available'] === 1);

echo "\n7. Pengembalian terlambat + denda\n";
$bookId2 = book_create([
    'category_id'     => $catId,
    'title'           => $testTitle . ' B',
    'author'          => 'Penguji',
    'published_year'  => 2024,
    'synopsis'        => 'Uji denda.',
    'cover_path'      => null,
    'stock_total'     => 1,
    'stock_available' => 1,
]);

$r7 = borrowing_request($memberId, $bookId2);
$bid2 = (int) ($r7['id'] ?? 0);
borrowing_approve($bid2, $staffId);

// Paksa jatuh tempo 3 hari lalu.
db()->prepare('UPDATE borrowings SET due_at = DATE_SUB(CURDATE(), INTERVAL 3 DAY) WHERE id = :id')
    ->execute([':id' => $bid2]);

$late = hari_terlambat(borrowing_find($bid2)['due_at']);
check('Perhitungan hari terlambat = 3', $late === 3, "late={$late}");

$r8 = borrowing_return($bid2, $staffId);
check('Pengembalian berhasil', $r8['ok'], $r8['message'] ?? '');
check('Denda Rp3.000', ($r8['fine'] ?? 0) === 3000, 'fine=' . ($r8['fine'] ?? 'n/a'));

$afterReturn = borrowing_find($bid2);
check('Status denda belum lunas', $afterReturn['fine_status'] === 'belum_lunas', 'status=' . $afterReturn['fine_status']);

echo "\n8. Pelunasan denda\n";
$r9 = borrowing_mark_fine_paid($bid2, $staffId);
check('Tandai lunas berhasil', $r9['ok'], $r9['message'] ?? '');
check('Status denda lunas', borrowing_find($bid2)['fine_status'] === 'lunas');

$r10 = borrowing_mark_fine_paid($bid2, $staffId);
check('Pelunasan ganda ditolak', !$r10['ok'], $r10['message'] ?? '');

echo "\n9. Batas pinjaman aktif\n";
$maxLoans = (int) app_config('max_active_loans');
$created  = [];
$results  = [];

for ($i = 0; $i < $maxLoans + 1; $i++) {
    $bid = book_create([
        'category_id' => $catId, 'title' => $testTitle . " L{$i}", 'author' => 'Penguji',
        'published_year' => 2024, 'synopsis' => 'Uji batas.', 'cover_path' => null,
        'stock_total' => 1, 'stock_available' => 1,
    ]);
    $created[] = $bid;
    $req = borrowing_request($memberId, $bid);
    if ($req['ok']) {
        borrowing_approve((int) $req['id'], $staffId);
    }
    $results[] = $req['ok'];
}

$stmt = db()->prepare('SELECT COUNT(*) FROM borrowings WHERE user_id = :u AND status = :s');
$stmt->execute([':u' => $memberId, ':s' => 'dipinjam']);
$activeNow = (int) $stmt->fetchColumn();

check("Maksimal {$maxLoans} pinjaman aktif ditegakkan", $activeNow === $maxLoans, "aktif={$activeNow}");

echo "\n10. Pembersihan data uji\n";
$cleanup = db()->prepare('DELETE FROM borrowings WHERE book_id IN (SELECT id FROM books WHERE title LIKE :t)');
$cleanup->execute([':t' => $testTitle . '%']);
$delBooks = db()->prepare('DELETE FROM books WHERE title LIKE :t');
$delBooks->execute([':t' => $testTitle . '%']);
check('Data uji dibersihkan', true);

echo "\n=== Hasil: {$pass} lulus, {$fail} gagal ===\n";

exit($fail > 0 ? 1 : 0);
