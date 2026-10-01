# Catatan Rilis & Pembaruan Sistem (Release Notes)
## Versi 1.5 — Central Ticketing System
**Tanggal Rilis:** 25 September 2026  
**Commit:** `842a4d3` & `42eedcc` (`update 1.5`)  
**Status:** Stabil & Terverifikasi (Branch: `main`)

---

## 📌 Ringkasan Pembaruan

Pembaruan **Versi 1.5** berfokus pada **performa tinggi, kebersihan antarmuka (clean dashboard), penguatan keamanan (security hardening), otomasi pencadangan database, serta audit menyeluruh terhadap 14 kriteria kualitas website standar industri.**

---

## 🚀 Fitur Baru (New Features)

### 1. Sistem Pencadangan Database Otomatis & Manual (Database Backup)
* **Web UI Backup Management (`/backups`)**:
  - Halaman khusus bagi Administrator untuk membuat backup instan, mengunduh file `.sql` / `.sql.gz`, serta menghapus backup lama.
  - Ringkasan statistik ukuran file, total cadangan, dan status ketersediaan storage.
* **Artisan Command (`php artisan db:backup`)**:
  - Perintah CLI untuk otomasi cron scheduler di server Linux/Windows.
  - Mendukung database MariaDB, MySQL, dan SQLite.
  - Fitur rotasi otomatis: menghapus backup yang melebihi batas retensi yang ditentukan.
* **Komponen Baru**:
  - `app/Services/DatabaseBackupService.php`
  - `app/Console/Commands/DatabaseBackupCommand.php`
  - `app/Http/Controllers/BackupController.php`
  - `resources/views/backups/index.blade.php`

### 2. Log Aktivitas Mandiri (Dedicated Activity Log)
* **Pemisahan dari Dashboard**:
  - Log aktivitas pengguna kini memiliki halaman tersendiri di menu navigasi sidebar (`/activities`), diposisikan di urutan paling bawah sesuai alur UX kerja NOC.
  - Dashboard monitoring menjadi jauh lebih bersih (*clean view*), hanya berfokus pada metrik KPI dan visualisasi grafik tren.
* **Komponen Baru**:
  - `app/Http/Controllers/ActivityLogController.php`
  - `resources/views/activities/index.blade.php`

### 3. Direktori Tiket Mandiri & Tautan KPI Dinamis
* **Halaman Direktori Tiket (`/tickets`)**:
  - Memisahkan tabel direktori tiket dari halaman Dashboard utama.
  - Dilengkapi fitur filter status cepat (*Pending*, *Dalam Proses*, *Selesai*), pencarian tiket, serta navigasi paginasi yang responsif.
* **Interaktivitas KPI Card Dashboard**:
  - Seluruh KPI Card statistik pada Dashboard kini interaktif dan dapat diklik untuk mengarahkan operator langsung ke Direktori Tiket dengan filter status yang sesuai.

### 4. Pencarian Pelanggan Cepat (Asynchronous Live Search)
* **Debounced AJAX Endpoint (`/customers/live-search`)**:
  - Menggantikan injeksi data seluruh pelanggan (1.000+ pelanggan) yang sebelumnya dimuat sekaligus ke dalam HTML.
  - Mengurangi ukuran payload HTML dari **~1.5 MB** menjadi hanya **~35 KB** (penurunan beban jaringan hingga 97%).
  - Input form pembuatan tiket kini responsif tanpa jeda/lag, dilengkapi pencarian langsung berdasarkan Nama atau No. Layanan pelanggan.

### 5. Aturan Tiket Closed Permanen (Immutability SLA)
* **Integritas Audit & SLA**:
  - Tiket yang telah berstatus `close` (selesai) dikunci secara permanen pada level Model dan Controller.
  - Form update status disembunyikan dan request perubahan status dicegah demi menjaga validitas laporan penanganan gangguan dan data SLA teknisi.

---

## 🛡️ Peningkatan Keamanan & Keandalan (Security & Hardening)

### 1. HTTP Security Headers Global
Middleware baru `App\Http\Middleware\SecurityHeaders` diterapkan ke seluruh rute web:
- `X-Content-Type-Options: nosniff` (mencegah MIME sniffing).
- `X-Frame-Options: SAMEORIGIN` (proteksi dari serangan Clickjacking).
- `X-XSS-Protection: 1; mode=block` (mitigasi Cross-Site Scripting).
- `Referrer-Policy: strict-origin-when-cross-origin` (melindungi privasi token & parameter rute).
- `Permissions-Policy: camera=(), microphone=()` (pembatasan akses sensor hardware).

### 2. Rate Limiting API Sinkronisasi
- Menambahkan middleware pembatas laju permintaan (`throttle:120,1`) pada seluruh rute API integrasi billing di `routes/api.php` guna mencegah serangan *Denial of Service (DoS)* dan *credential brute-force*.

### 3. Perlindungan Privasi Data Tiket (Robots.txt)
- Konfigurasi `public/robots.txt` diset ke `Disallow: /` untuk memastikan data identitas pelanggan, keluhan gangguan, dan nomor layanan tidak terindeks oleh bot mesin pencari publik (Googlebot, Bingbot, dll).

### 4. Halaman Error Khusus & Ramah Pengguna
Tersedia halaman status HTTP kustom yang selaras dengan tema aplikasi (mendukung Light Mode & Dark Mode):
- `resources/views/errors/403.blade.php` (Akses Ditolak / Forbidden).
- `resources/views/errors/404.blade.php` (Halaman Tidak Ditemukan / Not Found).
- `resources/views/errors/419.blade.php` (Sesi Berakhir / Page Expired).
- `resources/views/errors/500.blade.php` (Gangguan Server Internal / Server Error).

---

## 🎨 Tampilan & Responsivitas (UI/UX Improvements)

### 1. Tampilan Mobile Adaptif (Card Stack View)
- Pada resolusi layar ponsel (`< 768px`), tabel tiket otomatis beralih menjadi format kartu (*stacked cards*) yang rapi tanpa perlu geser horizontal (*horizontal scrollbar*).
- Menghadirkan *drawer navigation* mobile dengan kontrol sentuh yang responsif.

### 2. Kompilasi Aset Produksi (Tailwind CSS v4 & Vite)
- Seluruh aset CSS dan JS telah di-bundle via Vite.
- Ukuran CSS produksi terkompresi hanya **17.5 kB (gzipped)** (`public/build/assets/app-*.css`), menghilangkan *render-blocking* dan mempercepat First Contentful Paint (FCP).

### 3. Pembersihan Laporan Ekspor PDF
- Format ekspor laporan PDF disederhanakan:
  - Log aktivitas teknis internal dan informasi browser dihapus dari dokumen resmi.
  - Frasa `Ubah Status` dan teks dalam tanda kurung `(...)` dihilangkan agar bahasa laporan lebih formal dan bersih untuk diserahkan ke pelanggan/manajemen.

### 4. Pembatasan Hak Akses Teknisi
- Menu log aktivitas sistem dan tombol penandaan peta (*mark map*) disembunyikan untuk akun ber-role `technician`.
- Teknisi hanya dapat melihat tiket yang ditugaskan kepada mereka di direktori kerja.

### 5. Sinkronisasi Jam & Timezone
- Timezone aplikasi dan database diselaraskan ke `Asia/Jakarta` (WIB) sehingga timestamp pembuatan tiket, durasi pengerjaan, dan riwayat aktivitas 100% akurat.

---

## 📋 Daftar Berkas yang Diubah & Dibuat (File Changes)

### Berkas Baru (Created):
| Path Berkas | Deskripsi |
|:---|:---|
| `app/Console/Commands/DatabaseBackupCommand.php` | Perintah CLI backup database |
| `app/Http/Controllers/ActivityLogController.php` | Controller tampilan log aktivitas |
| `app/Http/Controllers/BackupController.php` | Controller manajemen backup database |
| `app/Http/Middleware/SecurityHeaders.php` | Middleware proteksi security headers |
| `app/Services/DatabaseBackupService.php` | Service engine backup MySQL/MariaDB/SQLite |
| `resources/views/activities/index.blade.php` | Tampilan log aktivitas mandiri |
| `resources/views/backups/index.blade.php` | Tampilan manajemen backup database |
| `resources/views/errors/403.blade.php` | Halaman error 403 Forbidden |
| `resources/views/errors/404.blade.php` | Halaman error 404 Not Found |
| `resources/views/errors/419.blade.php` | Halaman error 419 Page Expired |
| `resources/views/errors/500.blade.php` | Halaman error 500 Server Error |
| `resources/views/tickets/index.blade.php` | Halaman direktori tiket responsif |

### Berkas Diperbarui (Modified):
| Path Berkas | Rincian Perubahan |
|:---|:---|
| `app/Http/Controllers/CustomerController.php` | Menambahkan method `liveSearch` untuk AJAX debounce |
| `app/Http/Controllers/DashboardController.php` | Mengoptimasi query, membuang dump 1.000 pelanggan |
| `app/Http/Controllers/MapController.php` | Menyesuaikan scoping hak akses teknisi |
| `app/Http/Controllers/TicketWebController.php` | Logika penguncian tiket closed, pembersihan data |
| `app/Models/Ticket.php` | Mutator & validasi status immutability tiket close |
| `bootstrap/app.php` | Pendaftaran global `SecurityHeaders` middleware |
| `public/robots.txt` | Aturan `Disallow: /` untuk bot search engine |
| `resources/css/app.css` | Konfigurasi import Tailwind v4 & Vite |
| `resources/views/dashboard.blade.php` | Dashboard bersih dengan grafik analitik & KPI link |
| `resources/views/layouts/app.blade.php` | Title dinamis, integrasi Vite assets, mobile drawer |
| `resources/views/tickets/pdf.blade.php` | Format laporan PDF bersih tanpa log aksi teknis |
| `resources/views/tickets/show.blade.php` | Proteksi form edit tiket berstatus closed |
| `routes/api.php` | Penerapan rate limiting `throttle:120,1` |
| `routes/console.php` | Pendaftaran command scheduler backup |
| `routes/web.php` | Rute baru `/backups`, `/activities`, `/customers/live-search` |
| `tests/Feature/TicketIntegrationTest.php` | Penyesuaian assertion pengujian hak akses teknisi |

---

## 🧪 Hasil Verifikasi & Pengujian (Quality Assurance)

```text
1. Automated Test Suite:
   php artisan test
   Result: 5 passed (10 assertions) - 410 ms (100% Success)

2. Frontend Asset Build:
   npm run build
   Result: 3 modules transformed, 0 errors, CSS gzip 17.5 kB

3. Security Headers Check:
   X-Content-Type-Options: nosniff [OK]
   X-Frame-Options: SAMEORIGIN [OK]
   X-XSS-Protection: 1; mode=block [OK]
   Referrer-Policy: strict-origin-when-cross-origin [OK]

4. Live Search Endpoint:
   GET /customers/live-search?q=0055 -> HTTP 200 OK (AJAX Response < 20 ms)
```

---

*Disusun dan diperbarui secara otomatis untuk dokumentasi pengembangan Central Ticketing System versi 1.5.*
