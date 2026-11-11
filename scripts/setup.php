<?php
/**
 * Seeder lokal: membuat akun admin pertama, akun petugas & anggota demo,
 * serta data buku contoh.
 *
 * HANYA dapat dijalankan dari command line (bukan lewat browser).
 * Jalankan: php scripts/setup.php
 *
 * Script ini aman dijalankan berulang; akun yang sudah ada akan dilewati.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Script ini hanya dapat dijalankan dari command line.');
}

require __DIR__ . '/../app/bootstrap.php';

$pdo = db();

echo "=== Lentera Digital Library — Seeder ===\n\n";

/**
 * Buat user bila username belum ada.
 */
function seed_user(string $name, string $username, string $password, string $role, ?string $email = null): void
{
    if (user_find_by_username($username) !== null) {
        echo "  [skip] {$role} '{$username}' sudah ada.\n";

        return;
    }

    $id = user_create($name, $username, $password, $role, $email);

    if ($id === null) {
        echo "  [gagal] {$role} '{$username}' tidak dapat dibuat.\n";

        return;
    }

    echo "  [buat] {$role} '{$username}' — password: {$password}\n";
}

// --- 1. Akun admin pertama --------------------------------------------------
echo "1. Akun admin\n";
$adminPassword = getenv('ADMIN_PASSWORD') ?: 'Admin#Lentera2024';
seed_user('Administrator Lentera', 'admin', $adminPassword, 'admin', 'admin@lentera.test');

// --- 2. Akun petugas & anggota demo ----------------------------------------
echo "\n2. Akun demo\n";
seed_user('Rani Pustakawan', 'petugas', 'Petugas#2024', 'petugas', 'petugas@lentera.test');
seed_user('Budi Anggota', 'anggota', 'Anggota#2024', 'user', 'anggota@lentera.test');

// --- 3. Kategori ------------------------------------------------------------
echo "\n3. Kategori\n";
$categories = ['Fiksi', 'Non-Fiksi', 'Pendidikan', 'Teknologi', 'Pengembangan Diri'];

foreach ($categories as $name) {
    if (!category_name_exists($name)) {
        category_create($name);
        echo "  [buat] kategori '{$name}'.\n";
    } else {
        echo "  [skip] kategori '{$name}' sudah ada.\n";
    }
}

// --- 4. Buku contoh ---------------------------------------------------------
echo "\n4. Buku contoh\n";

$existingBooks = (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn();

$demoBooks = [
    ['Fiksi', 'Lentera di Ujung Senja', 'Ayu Prameswari', 2021, 'Seorang pelajar menemukan buku tua di perpustakaan sekolah yang membawanya menelusuri kisah-kisah penduduk kampung. Cerita hangat tentang persahabatan, keberanian kecil, dan arti pulang.', 4],
    ['Fiksi', 'Hujan Bulan Desember', 'Rizal Nugraha', 2019, 'Dua sahabat yang terpisah oleh kota dan waktu berusaha menjaga janji masa kecil mereka. Novel pendek tentang kehilangan, pertumbuhan, dan harapan yang tak pernah padam.', 3],
    ['Non-Fiksi', 'Jejak Peradaban Nusantara', 'Dr. Sinta Maharani', 2020, 'Ringkasan perjalanan kebudayaan Nusantara dari masa kerajaan hingga era modern, ditulis dengan bahasa sederhana untuk pembaca pemula yang ingin memahami sejarah bangsa.', 5],
    ['Non-Fiksi', 'Membaca Cuaca dari Langit', 'Hendra Wijaya', 2018, 'Panduan ringan mengenali pola awan dan tanda-tanda perubahan cuaca sehari-hari, disertai penjelasan ilmiah yang mudah dipahami pelajar.', 2],
    ['Pendidikan', 'Matematika Itu Mudah', 'Dewi Kartika, M.Pd.', 2022, 'Buku latihan matematika dasar dengan pendekatan bertahap, contoh soal sehari-hari, dan tips mengerjakan ujian tanpa panik. Cocok untuk siswa dan guru.', 6],
    ['Pendidikan', 'Menulis Karya Ilmiah Sederhana', 'Prof. Bambang Suryono', 2021, 'Langkah-langkah menyusun karya ilmiah tingkat sekolah: menentukan topik, mencari sumber, menyusun argumentasi, hingga menyajikan hasil dengan rapi.', 4],
    ['Teknologi', 'Dasar Pemrograman untuk Pemula', 'Fajar Ramadhan', 2023, 'Pengantar logika pemrograman, variabel, percabangan, dan perulangan menggunakan bahasa yang ramah pemula. Disertai latihan kecil di setiap bab.', 5],
    ['Teknologi', 'Merawat Komputer Sendiri', 'Nadia Safira', 2020, 'Panduan praktis membersihkan, mempercepat, dan menjaga keamanan komputer pribadi tanpa perlu menjadi teknisi. Cocok untuk pengguna sehari-hari.', 3],
    ['Teknologi', 'Internet Sehat dan Aman', 'Yoga Pratama', 2022, 'Menjelaskan cara melindungi data pribadi, mengenali penipuan daring, dan memakai internet dengan bijak. Ditulis untuk pelajar dan keluarga.', 4],
    ['Pengembangan Diri', 'Kebiasaan Kecil yang Mengubah Hidup', 'Maya Lestari', 2021, 'Buku ringan tentang membangun kebiasaan baik sedikit demi sedikit: bangun pagi, membaca, dan mengatur waktu. Disertai lembar refleksi sederhana.', 5],
    ['Pengembangan Diri', 'Berbicara dengan Percaya Diri', 'Arif Setiawan', 2019, 'Teknik dasar berbicara di depan kelas atau rapat: mengatur napas, menyusun pesan, dan mengatasi gugup, dengan latihan bertahap.', 3],
    ['Fiksi', 'Petualangan di Perpustakaan Tua', 'Kak Gilang', 2023, 'Tiga anak penasaran menemukan pintu rahasia di perpustakaan kota yang membawa mereka ke dunia cerita. Petualangan penuh teka-teki dan nilai persahabatan.', 4],
];

if ($existingBooks > 0) {
    echo "  [skip] sudah ada {$existingBooks} buku pada database.\n";
} else {
    $catMap = [];
    foreach (category_all() as $cat) {
        $catMap[$cat['name']] = (int) $cat['id'];
    }

    foreach ($demoBooks as [$catName, $title, $author, $year, $synopsis, $stock]) {
        book_create([
            'category_id'     => $catMap[$catName] ?? array_values($catMap)[0],
            'title'           => $title,
            'author'          => $author,
            'published_year'  => $year,
            'synopsis'        => $synopsis,
            'cover_path'      => null,
            'stock_total'     => $stock,
            'stock_available' => $stock,
        ]);
        echo "  [buat] buku '{$title}'.\n";
    }
}

echo "\n=== Selesai ===\n";
echo "Login demo:\n";
echo "  Admin    : admin / {$adminPassword}\n";
echo "  Petugas  : petugas / Petugas#2024\n";
echo "  Anggota  : anggota / Anggota#2024\n";
echo "\nGanti password demo sebelum dipakai untuk presentasi.\n";
