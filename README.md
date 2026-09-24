# MANAGEMENT TICKET (Central Ticketing System)

Aplikasi manajemen tiket gangguan terpusat (*Central Ticketing System*) yang terintegrasi dengan berbagai instansi billing ISP / RT-RW Net (Gayuh Billing). Dirancang dengan antarmuka modern **Neumorphism**, pelacakan peta koordinat pelanggan (*Leaflet Maps*), ekspor laporan rekapitulasi gangguan, dan kontrol akses berbasis peran (*Role-Based Access Control*).

---

## 🚀 Fitur Utama

- **Integrasi Multi-Billing**: Sinkronisasi data pelanggan, ODP, dan koordinat GPS dari instansi billing secara otomatis melalui REST API.
- **Visualisasi Analytics & Chart**: Statistik distribusi volume tiket (Pending, Proses, Selesai) serta tren aktivitas mingguan.
- **Peta Interaktif Pelanggan (Leaflet GPS)**: Visualisasi persebaran lokasi gangguan dan status pelanggan langsung pada peta geografis.
- **Ekspor Dokumen Laporan**: Rekapitulasi tiket terintegrasi dengan format **CSV**, **Microsoft Excel (.xlsx)**, dan **PDF Cetak Resmi**.
- **Role-Based Access Control (RBAC)**:
  - **Admin**: Akses penuh, kelola akun pengguna, konfigurasi instansi billing, dan seluruh tiket.
  - **Operator**: Manajemen tiket gangguan, sinkronisasi data pelanggan billing, dan monitoring.
  - **Teknisi**: Portal personal khusus tiket yang ditugaskan ke teknisi bersangkutan.
- **Desain Neumorphism Modern**: Antarmuka responsif dengan skema Soft UI dan dukungan penuh Mode Terang (*Light Mode*) serta Mode Gelap (*Dark Mode*).

---

## 📋 Panduan Instalasi & Integrasi

Panduan lengkap telah disediakan pada direktori `docs/`:

1. **[Panduan Instalasi Server Ubuntu & Debian](docs/PANDUAN_INSTALASI_UBUNTU_DEBIAN.md)**  
   *Langkah-langkah lengkap instalasi dari fresh server: PHP 8.3, MariaDB/MySQL, Nginx, SSL Let's Encrypt, Supervisor Queue Worker, dan Cron Scheduler.*

2. **[Panduan Integrasi REST API CodeIgniter 3 Billing](docs/CI3_REST_API_INTEGRATION_GUIDE.md)**  
   *Panduan teknis dan helper script untuk menghubungkan instansi billing lama berbasis CodeIgniter 3 ke sistem central-ticketing.*

3. **[Panduan Koneksi & Setup Redis](docs/PANDUAN_KONEKSI_REDIS.md)**  
   *Panduan instalasi Redis server, ekstensi phpredis, konfigurasi keamanan, isolasi database, serta pengalihan cache, antrean queue, dan sesi pengguna.*

---

## ⚡ Quick Start (Pengembangan Lokal)

### Prasyarat
- PHP >= 8.3
- Composer >= 2.x
- Node.js >= 18.x & NPM
- MySQL / MariaDB / SQLite

### Langkah Menjalankan
```bash
# 1. Clone repositori
git clone https://github.com/tegar78/central-ticketing.git
cd central-ticketing

# 2. Install dependensi
composer install
npm install

# 3. Salin environment & generate key
cp .env.example .env
php artisan key:generate

# 4. Konfigurasi database di .env, lalu jalankan migrasi & seeder
php artisan migrate --seed

# 5. Build aset frontend
npm run build

# 6. Jalankan server lokal
php artisan serve
```

### Akun Bawaan (Default Seed)
- **Admin**: `admin@central.local` / `password`
- **Operator**: `operator@central.local` / `password`
- **Teknisi**: `teknisi1@central.local` / `password`

---

## 📄 Lisensi
Sistem ini berlisensi di bawah [MIT License](LICENSE).
