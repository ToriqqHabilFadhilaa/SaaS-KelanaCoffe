# Kelana Coffee POS & Inventory

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

**Kelana Coffee POS** adalah aplikasi point of sale berbasis web untuk operasional kedai kopi. Sistem ini menyatukan checkout Kasir, pengelolaan produk dan resep, persediaan bahan baku, pembayaran QRIS Sandbox Midtrans, dan laporan Admin dalam satu database Laravel.

> Kata kunci: coffee shop POS, aplikasi kasir kedai kopi, inventory bahan baku, manajemen stok resep, QRIS Midtrans Sandbox, Laravel POS Indonesia.

---

## Fitur Utama

| Modul | Kemampuan |
|---|---|
| **Kasir** | Katalog produk, pencarian & filter kategori, keranjang, pembayaran Cash & QRIS, cetak nota, riwayat order |
| **Inventory** | Stok bahan baku, batas minimum, penerimaan & pengeluaran stok, supplier, audit log, pencarian, filter, pagination |
| **Admin** | Ringkasan penjualan, laporan per tanggal, detail order, export CSV, CRUD produk/resep/kategori/supplier, manajemen akun staf |
| **Pembayaran** | Midtrans Snap Sandbox — verifikasi signature, sinkronisasi status, rekonsiliasi QRIS pending otomatis |
| **Keamanan** | Pembatasan akses berbasis role, reset password via email, validasi upload gambar, pencatatan audit stok |

---

## Teknologi

- **Backend:** PHP 8.3+, Laravel 13, MySQL
- **Frontend:** Blade, Alpine.js, Tailwind CSS 4, Vite
- **Pembayaran:** Midtrans PHP SDK (Snap Sandbox)

---

## Instalasi

Pastikan PHP, Composer, Node.js/npm, dan MySQL sudah tersedia.

```powershell
# 1. Salin konfigurasi environment
Copy-Item .env.example .env

# 2. Install dependensi
composer install
npm install

# 3. Generate application key
php artisan key:generate
```

Atur koneksi database di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kelana_coffee
DB_USERNAME=root
DB_PASSWORD=
```

Kemudian jalankan migrasi, seeder, dan build aset:

```powershell
php artisan migrate --seed
php artisan storage:link
npm run build
```

---

## Menjalankan Aplikasi

Jalankan dua terminal secara bersamaan:

```powershell
# Terminal 1 — Laravel dev server
php artisan serve
```

```powershell
# Terminal 2 — Vite HMR
npm run dev
```

Buka `http://localhost:8000`.

---

## Konfigurasi Midtrans Sandbox

Isi credential dari [dashboard Midtrans](https://dashboard.sandbox.midtrans.com) ke `.env`:

```env
MIDTRANS_SERVER_KEY=SB-Mid-server-...
MIDTRANS_CLIENT_KEY=SB-Mid-client-...
MIDTRANS_IS_PRODUCTION=false
```

- Arahkan **Payment Notification URL** Midtrans ke `https://<domain>/midtrans/notification`
- Untuk localhost, gunakan tunnel HTTPS (misalnya [ngrok](https://ngrok.com) atau [Expose](https://expose.dev))
- **Jangan commit `.env` atau credential ke repository**

---

## Rekonsiliasi QRIS Otomatis

Order QRIS pending direkonsiliasi setiap 5 menit oleh Laravel Scheduler.

```powershell
# Development
php artisan schedule:work
```

Di production, tambahkan cron entry berikut:

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Roles & Akses

| Role | Akses |
|---|---|
| `admin` | Katalog, kategori, supplier, pengguna, laporan, export CSV |
| `kasir` | Checkout, pembayaran, nota, riwayat order miliknya |
| `inventory` | Stok bahan baku, audit pergerakan stok |

Akun awal dibuat oleh seeder. **Ganti password dan credential sebelum deploy ke production.**

---

## Upload Gambar Produk

Admin mengunggah gambar dari menu **Produk**. File disimpan di public storage dan ditampilkan pada katalog Kasir.

- Format yang didukung: JPG, PNG, WebP
- Ukuran maksimal: 2 MB

---

## SEO & Privasi

Kelana Coffee POS adalah workspace internal — bukan situs publik. Semua halaman yang memerlukan autentikasi menggunakan `noindex, nofollow, noarchive` agar tidak terindeks mesin pencari. Jangan publikasikan sitemap untuk halaman aplikasi.

---

## Struktur Direktori Penting

```
app/
├── Http/Controllers/   # Controller per modul (Admin, Kasir, Inventory, Auth)
├── Models/             # Eloquent models
└── Services/           # MidtransPaymentService, OrderInventoryService, CsvExportService
database/
├── migrations/         # Skema tabel
└── seeders/            # Data awal (user, kategori, produk, supplier, bahan)
resources/views/
├── admin/              # Tampilan modul Admin
├── cashier/            # Tampilan modul Kasir
├── inventory/          # Tampilan modul Inventory
└── auth/               # Login, register, reset password
```

---

## Lisensi

MIT © Kelana Coffee
