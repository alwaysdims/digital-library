# Design System — Digital Library

**Versi:** 1.0  
**Tema:** Soft Clay Digital Library  
**Produk:** Lentera Digital Library (nama tampilan sementara)  
**Cakupan:** Beranda, katalog, detail buku, login, serta dashboard sederhana Admin/Petugas/User

## 1. Prinsip desain

Tampilan terasa hangat, ramah, modern, dan mudah dipakai untuk membaca informasi buku. Gunakan **claymorphism secara ringan**: sudut membulat, permukaan terang, sedikit efek timbul, dan bayangan lembut. **Keterbacaan, kejelasan status, dan kemudahan form/tabel lebih penting daripada efek 3D.**

Pedoman:
- Clean dan tidak terlalu ramai; beri ruang kosong yang cukup.
- Gunakan clay terutama untuk card, tombol, input, dan panel ringkasan.
- Data tabel tetap datar dan jelas; jangan membuat setiap sel tampak timbul.
- Ikon dekoratif seperlunya; jangan menggunakan emoji sebagai pengganti tombol aksi.
- Efek hover kecil dan konsisten; bukan animasi mencolok.
- Responsif dari laptop hingga ponsel.

## 2. Warna

| Token | Hex | Penggunaan |
|---|---|---|
| `--bg` | `#F7F4EF` | Latar utama krem |
| `--surface` | `#FFFDF9` | Card, panel, modal |
| `--primary` | `#7A9E7E` | Tombol utama, menu aktif |
| `--primary-dark` | `#4D7155` | Hover, CTA dengan teks putih |
| `--secondary` | `#D7BFAE` | Tombol sekunder, ornamen |
| `--accent` | `#E6A57E` | Hiasan kecil, sorotan |
| `--text` | `#3E3A39` | Teks utama |
| `--muted` | `#6D6765` | Metadata buku, hint |
| `--success` | `#36794E` | Tersedia, dikembalikan, lunas |
| `--warning` | `#9B621C` | Menunggu |
| `--danger` | `#B64D4D` | Terlambat, denda belum lunas |
| `--border` | `#E9E0D7` | Garis batas ringan |

**Kontras:** Gunakan teks gelap pada latar pastel. Tombol primary berteks putih sebaiknya menggunakan `--primary-dark`; hijau sage terang tidak selalu cukup kontras dengan putih.

## 3. Tipografi

- Heading: **Poppins** (fallback `Arial, sans-serif`).
- Isi: **Inter** (fallback `Arial, sans-serif`).
- H1 desktop: 34–40px; H1 mobile: 28–32px.
- H2: 24–28px; H3: 18–22px.
- Body: 16px; metadata: 14px; hindari teks inti di bawah 14px.
- Line-height isi 1.5–1.7 agar sinopsis nyaman dibaca.

Gunakan maksimal dua jenis font. Jika memakai Google Fonts, sediakan fallback saat offline.

## 4. Bentuk dan efek clay

- Radius card besar: **22px**.
- Radius tombol/input: **14px**.
- Radius badge: **999px**.
- Spacing dasar: kelipatan 8px (8, 16, 24, 32).
- Bayangan card: `8px 8px 18px rgba(111, 85, 70, .10), -6px -6px 16px rgba(255,255,255,.80)`.
- Border lembut `1px solid var(--border)`.
- Animasi 150–200ms untuk hover/focus; hormati `prefers-reduced-motion`.
- Jangan menggunakan clay shadow besar pada tabel atau seluruh blok teks.

### Contoh token CSS

```css
:root {
  --bg: #F7F4EF;
  --surface: #FFFDF9;
  --primary: #7A9E7E;
  --primary-dark: #4D7155;
  --secondary: #D7BFAE;
  --accent: #E6A57E;
  --text: #3E3A39;
  --muted: #6D6765;
  --success: #36794E;
  --warning: #9B621C;
  --danger: #B64D4D;
  --border: #E9E0D7;
  --radius-card: 22px;
  --radius-control: 14px;
  --shadow-clay: 8px 8px 18px rgba(111,85,70,.10),
                 -6px -6px 16px rgba(255,255,255,.80);
}
body { background: var(--bg); color: var(--text); }
.clay-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-card);
  box-shadow: var(--shadow-clay);
}
.btn-primary-clay {
  background: var(--primary-dark);
  color: #fff;
  border: 0;
  border-radius: var(--radius-control);
  padding: .75rem 1.25rem;
  box-shadow: 3px 5px 10px rgba(77,113,85,.16);
}
.btn-primary-clay:hover, .btn-primary-clay:focus-visible {
  background: #3D5F46;
  color: #fff;
}
:focus-visible { outline: 3px solid #E6A57E; outline-offset: 2px; }
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { transition-duration: 0.01ms !important; animation-duration: 0.01ms !important; }
}
```

## 5. Komponen UI

### Header publik
- Logo kecil (ikon buku) dan nama **Lentera**.
- Menu: Beranda, Katalog, Masuk / Dashboard.
- Desktop: satu baris, sticky opsional.
- Mobile: menu hamburger Bootstrap.

### Hero beranda
- Judul: **“Temukan Buku Favoritmu”**.
- Deskripsi: “Jelajahi koleksi dan baca sinopsis buku.”
- Search bar judul/penulis/kategori; tombol **Cari Buku**.
- Ilustrasi tumpukan buku clay sederhana (ornamen, bukan navigasi).
- Hero tidak terlalu tinggi agar katalog terlihat tanpa banyak scroll.

### Card buku
- Cover dengan rasio kira-kira 3:4, `object-fit: cover`.
- Judul (maksimal 2 baris), penulis, kategori, badge ketersediaan.
- CTA **Lihat Detail**; jangan langsung meminjam dari card.
- Jika stok `0`, badge **Tidak Tersedia** / **Sedang Dipinjam**.

### Detail buku
- Desktop: cover di kiri (1/3), metadata + sinopsis + tombol di kanan (2/3).
- Mobile: cover di atas, metadata dan sinopsis di bawah.
- Informasi: judul, penulis, kategori, tahun (jika ada), stok tersedia, sinopsis.
- Jika login sebagai anggota dan stok tersedia, tombol **Ajukan Peminjaman**.
- Jika belum login, tombol **Masuk untuk Meminjam** dan teks **“Belum punya akun? Daftar melalui petugas perpustakaan.”**

### Form
- Label selalu terlihat di atas input.
- Field wajib ditandai; error tampil dekat field.
- Tombol aksi utama jelas; form singkat tanpa wizard.
- Untuk CRUD gunakan halaman form atau modal sederhana, bukan nested modal.

### Badge status
- Tersedia, Dikembalikan, Lunas: hijau lembut + teks hijau tua.
- Menunggu: krem keemasan + teks coklat.
- Dipinjam: lavender/beige netral + teks gelap.
- Terlambat, Belum Lunas, Ditolak: peach/merah lembut + teks merah tua.
- Jangan mengandalkan warna saja; selalu sertakan teks status.

### Tabel dashboard
- Head tabel background `#F4EEE7`.
- Baris putih/krem muda dengan divider halus.
- Kolom penting: identitas, judul, tanggal, status, aksi.
- Pada layar kecil, gunakan wrapper horizontal scroll atau ringkas kolom.
- Konfirmasi untuk tindakan yang mengubah data.

## 6. Struktur halaman

### A. Beranda publik
1. Navbar.
2. Hero dan pencarian.
3. Kategori (filter sederhana; tidak wajib card kategori besar).
4. Koleksi buku (maksimal 6–8 buku unggulan/terbaru).
5. Footer ringkas.

### B. Katalog
1. Judul **Katalog Buku**.
2. Search + dropdown filter kategori.
3. Grid card buku: desktop 3–4 kolom, tablet 2, ponsel 1–2 sesuai lebar.
4. Navigasi halaman (pagination) bila data banyak.

### C. Detail Buku
1. Breadcrumb kembali ke katalog.
2. Cover + identitas + stok.
3. Sinopsis yang mudah dibaca.
4. CTA sesuai status login/stok.

### D. Login
- Satu card di tengah, judul **Masuk ke Lentera**.
- Input username dan password.
- Tombol Masuk.
- Teks: **“Pendaftaran anggota dilakukan melalui petugas.”**
- Tidak ada tombol daftar mandiri.

### E. Dashboard (satu layout, menu berbeda per role)
- Sidebar kiri (desktop), offcanvas Bootstrap (mobile).
- Topbar: judul halaman, nama role, Logout.
- Ringkasan 2–4 card statistik kecil, tanpa grafik.
- Area utama berupa tabel + satu tombol aksi utama per halaman.

**Admin menu:** Dashboard, Buku, Kategori, Petugas, Transaksi.  
**Petugas menu:** Dashboard, Buku, Kategori, Anggota, Pengajuan & Peminjaman, Pengembalian & Denda.  
**User menu:** Katalog, Pinjaman Saya, Riwayat.

Jangan tampilkan menu yang tidak diizinkan, tetapi keamanan wajib tetap ditegakkan oleh PHP.

## 7. Ilustrasi tata letak (wireframe tekstual)

### Beranda desktop

```text
┌───────────────────────────────────────────────────────────┐
│  [Logo Lentera]          Beranda  Katalog        [Masuk]   │
├───────────────────────────────────────────────────────────┤
│  TEMUKAN BUKU FAVORITMU       [Ilustrasi tumpukan buku]   │
│  Jelajahi katalog dan sinopsis                             │
│  [ Cari judul / penulis / kategori...      ] [Cari]         │
├───────────────────────────────────────────────────────────┤
│  Kategori Buku                                             │
│  [Semua] [Fiksi] [Nonfiksi] [Pendidikan] [Teknologi]       │
│                                                           │
│  Koleksi Buku                                              │
│  [Cover+Info] [Cover+Info] [Cover+Info]                    │
├───────────────────────────────────────────────────────────┤
│  Footer                                                   │
└───────────────────────────────────────────────────────────┘
```

### Dashboard petugas desktop

```text
┌─────────────┬─────────────────────────────────────────────┐
│ Lentera     │ Dashboard Petugas               [Logout]   │
│ Dashboard   ├─────────────────────────────────────────────┤
│ Buku        │ [Total Buku] [Anggota] [Dipinjam] [Denda]  │
│ Anggota     │                                             │
│ Peminjaman  │ Data Pengajuan / Peminjaman   [Proses Baru] │
│ Pengembalian│ ┌─────────────────────────────────────────┐ │
│ Denda       │ │ Anggota | Buku | Jatuh Tempo | Status │ │
│             │ │ ....... | .... | ........... | Aksi   │ │
│             │ └─────────────────────────────────────────┘ │
└─────────────┴─────────────────────────────────────────────┘
```

## 8. Responsivitas

- Container utama maksimum ±1200px dan padding tepi 16–24px.
- Desktop ≥992px: sidebar dashboard tetap; katalog 3–4 kolom.
- Tablet 768–991px: katalog 2 kolom; sidebar dapat dilipat.
- Mobile <768px: satu kolom untuk detail buku dan form; tabel scroll; sidebar offcanvas.
- Touch target tombol minimal 44px; jangan hanya mengandalkan hover.

## 9. Aksesibilitas dan pengalaman pengguna

- Kontras teks cukup kuat; teks utama gelap.
- Semua input punya label; semua tombol punya teks/accessible label.
- Navigasi bisa diakses dengan keyboard, focus ring terlihat.
- Pesan kosong: “Belum ada buku”, “Belum ada pengajuan”, dan seterusnya.
- Pesan sukses/error singkat dan manusiawi.
- Konfirmasi sebelum menghapus data, menolak pengajuan, atau menyelesaikan pengembalian.
- Hindari desain clay yang menyebabkan field input terlihat tidak bisa diklik.
- Tidak ada animasi wajib untuk memahami status.

## 10. Referensi desain dan implementasi

- Referensi visual utama: mockup **Lentera Digital Library** dengan katalog, detail buku, dan dashboard petugas (dibuat dalam percakapan sebelum dokumen ini).
- Implementasi frontend: Bootstrap 5 + CSS kustom; tidak perlu membeli template.
- Gaya visual: Soft Claymorphism / clay design dengan warna sage, krem, dan peach.
- Hindari menyalin foto cover buku atau ilustrasi berhak cipta secara sembarangan. Gunakan sampul contoh buatan sendiri atau konten yang memiliki izin.

## 11. Checklist selesai (UI)

- [ ] Tema warna dan komponen konsisten di semua role.
- [ ] Beranda, katalog, detail buku, dan login lengkap.
- [ ] Dashboard Admin, Petugas, User memakai satu bahasa visual.
- [ ] Semua status pinjaman dan denda dapat dibedakan lewat **teks dan warna**.
- [ ] Tidak ada tombol atau navigasi dummy.
- [ ] Seluruh form jelas, memiliki error/feedback.
- [ ] Layar mobile tidak overflow horizontal kecuali tabel yang memang diberi scroll.
- [ ] Website tetap nyaman dipakai tanpa efek dekoratif berlebihan.
