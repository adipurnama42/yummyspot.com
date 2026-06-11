<div align="center">

<img src="https://img.shields.io/badge/PHP-8.1+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
<img src="https://img.shields.io/badge/MySQL-8.0+-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
<img src="https://img.shields.io/badge/PDO-Prepared_Statements-FF6B35?style=for-the-badge" />
<img src="https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge" />

# YummySpot

**Social Media Katalog Tempat Wisata & Kuliner**

Platform berbasis web yang memadukan konsep social media dengan katalog tempat wisata dan kuliner. Pengguna dapat berbagi postingan, menemukan katalog tempat menarik, memberikan ulasan, dan saling berinteraksi — semua dalam satu platform.

[Demo Live](https://yummyspot.caro-studio.com) · [Laporan Bug](https://yummyspot.caro-studio.com/contact.php) · [Dokumentasi](#instalasi)

</div>

---

## ✨ Fitur Utama

### 👤 Pengguna (User)
- Registrasi & login dengan peran berbeda (User, Pemilik, CS, Admin)
- Feed postingan dengan like, komentar, dan follow
- Upload foto postingan dengan mention katalog
- Profil dengan grid foto gaya Instagram
- Wishlist & notifikasi real-time
- Laporan postingan/katalog bermasalah
- Form hubungi CS dengan upload screenshot

### 🏪 Pemilik (Owner)
- Dashboard analitik katalog
- Kelola katalog: tambah, edit, hapus (soft delete 30 hari)
- Trash bin — katalog dihapus permanen otomatis setelah 30 hari
- Ajukan verifikasi katalog ke CS
- Pantau ulasan dan rating katalog

### 🛡️ Customer Service (CS)
- Review & verifikasi katalog (setujui/tolak)
- Kelola laporan dari pengguna
- Riwayat verifikasi per katalog

### ⚡ Admin (Super Admin)
- Dashboard overview statistik platform
- Kelola pengguna, role, dan status akun
- Takedown/pulihkan postingan & katalog
- Manajemen kategori (tambah, edit, hapus + icon picker)
- Kelola tim CS

---

## 🛠️ Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Backend | PHP 8.1+ Native (tanpa framework) |
| Database | MySQL 8.0+ dengan PDO |
| Keamanan | Prepared Statements, CSRF Token, bcrypt |
| Frontend | HTML, CSS, JavaScript Vanilla |
| Font | Nunito + Plus Jakarta Sans (Google Fonts) |
| Icon | Font Awesome 6 Free |
| Auth | Session-based + cookie 30 hari |

---

## 📁 Struktur Proyek

```
yummyspot/
├── config/
│   ├── app.php              # Konfigurasi aplikasi & konstanta
│   └── database.php         # Koneksi PDO MySQL
├── includes/
│   ├── helpers.php          # Fungsi helper (auth, upload, notif, dll)
│   ├── header.php           # Navbar + mobile drawer
│   ├── footer.php           # Bottom nav + modal post + JS global
│   └── sidebar.php          # Sidebar navigasi
├── assets/
│   ├── css/app.css          # Stylesheet utama
│   └── js/app.js            # JavaScript utama
├── actions/
│   ├── like.php             # Toggle like (AJAX)
│   ├── follow.php           # Toggle follow (AJAX)
│   ├── wishlist.php         # Toggle wishlist (AJAX)
│   ├── post_create.php      # Buat postingan
│   ├── post_delete.php      # Hapus postingan (AJAX)
│   └── search_catalog.php   # Autocomplete katalog
├── owner/
│   ├── dashboard.php        # Dashboard pemilik
│   ├── catalogs.php         # Kelola katalog + trash bin
│   ├── catalog-create.php   # Tambah katalog
│   ├── catalog-edit.php     # Edit katalog
│   ├── reviews.php          # Ulasan katalog
│   └── analytics.php        # Analitik katalog
├── cs/
│   ├── dashboard.php        # CS panel (katalog + laporan)
│   └── catalog-detail.php   # Review verifikasi katalog
├── admin/
│   ├── dashboard.php        # Admin panel (5 tab)
│   └── manage-cs.php        # Kelola tim CS
├── uploads/                 # File upload (tidak di-commit)
│   ├── avatars/
│   ├── catalogs/
│   ├── posts/
│   └── reports/
├── index.php                # Feed utama
├── login.php                # Halaman login
├── register.php             # Halaman registrasi
├── logout.php               # Proses logout
├── explore.php              # Eksplorasi konten
├── catalog.php              # Daftar & filter katalog
├── catalog-detail.php       # Detail katalog + ulasan
├── post.php                 # Detail postingan
├── profile.php              # Profil pengguna
├── wishlist.php             # Daftar wishlist
├── notifications.php        # Notifikasi
├── report.php               # Form laporan konten
├── my-reports.php           # Riwayat laporan user
├── contact.php              # Form hubungi CS
├── install_server.sql       # SQL installer (server)
└── create-folders.php       # Setup folder upload
```

---

## 🚀 Instalasi

### Prasyarat
- PHP 8.1 atau lebih baru
- MySQL 8.0 atau lebih baru
- Apache/Nginx dengan `mod_rewrite` aktif
- XAMPP / Laragon / server hosting

### 1. Clone Repository

```bash
git clone https://github.com/username/yummyspot.git
cd yummyspot
```

### 2. Konfigurasi Database

Edit `config/database.php` sesuai environment:

```php
// Lokal (XAMPP)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'yummyspot');

// Server/Hosting
define('DB_HOST', 'localhost');
define('DB_USER', 'u880128862_yummyspot');
define('DB_PASS', 'your_password');
define('DB_NAME', 'u880128862_yummyspot');
```

Edit `config/app.php` sesuai URL:

```php
// Lokal
define('APP_URL', 'http://localhost/yummyspot');

// Server
define('APP_URL', 'https://yummyspot.caro-studio.com');
```

### 3. Import Database

**Lokal (XAMPP):**
```bash
# Buka phpMyAdmin → Buat database 'yummyspot' → Import install_server.sql
# atau via terminal:
mysql -u root -p yummyspot < install_server.sql
```

**Server/Hosting:**
1. Buka phpMyAdmin hosting
2. Pilih database
3. Tab **SQL** → paste isi `install_server.sql` → **Go**

### 4. Buat Folder Upload

Akses sekali di browser:
```
http://localhost/yummyspot/create-folders.php
```
Hapus file `create-folders.php` setelah selesai.

### 5. Akses Aplikasi

```
http://localhost/yummyspot
```


## 🔐 Keamanan

- **PDO Prepared Statements** — semua query parameterized, aman dari SQL Injection
- **CSRF Token** — setiap form dilindungi token unik per sesi
- **bcrypt** — password di-hash dengan cost factor 12
- **Session-based Auth** — tidak menggunakan JWT/cookie plaintext
- **Output Escaping** — semua output di-escape dengan `htmlspecialchars()`
- **File Upload Validation** — validasi tipe MIME dan ukuran file

---

## 🗄️ Skema Database

```
users               → akun pengguna (4 role)
categories          → kategori katalog (UNIQUE name)
catalogs            → katalog tempat (soft delete)
catalog_images      → galeri foto katalog
catalog_verifications → riwayat verifikasi CS
wishlists           → bookmark katalog per user
follows             → relasi follow antar user
posts               → postingan feed
likes               → like postingan
comments            → komentar postingan
ratings             → ulasan & rating katalog
reports             → laporan konten/bug
notifications       → notifikasi sistem
```

---

## 🎨 UI & Design

- Terinspirasi dari Instagram dengan identitas YummySpot
- Warna aksen: `#FF6B35` (oranye)
- Font: **Nunito** (heading) + **Plus Jakarta Sans** (body)
- Responsif — mobile bottom navigation bar
- Mode sidebar drawer untuk mobile

---

## 📝 Lisensi

Proyek ini dibuat untuk keperluan akademik — Tugas Ujian Akhir Semester (UAS) di **Kampus INSTIKI**.

---

<div align="center">

Dibuat dengan ☕ oleh **Adi dan Team Kelas** · INSTIKI Bali

</div>
