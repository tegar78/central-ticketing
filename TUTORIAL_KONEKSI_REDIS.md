# Panduan Lengkap Instalasi, Konfigurasi, dan Koneksi Redis (Central-Ticketing)

Panduan komprehensif implementasi dan integrasi **Redis** sebagai sistem *In-Memory Data Store* untuk **Cache**, **Queue (Antrean Pekerjaan)**, dan **Session** pada aplikasi **MANAGEMENT TICKET (Central Ticket System)** berbasis Laravel dan server Linux (Ubuntu 24.04 / 26.04 LTS & Debian 12 / 13 Trixie) serta lingkungan pengembangan lokal.

---

## 📋 Daftar Isi
1. [Mengapa Redis Dibutuhkan?](#1-mengapa-redis-dibutuhkan)
2. [Instalasi Redis Server di Linux (Debian/Ubuntu)](#2-instalasi-redis-server-di-linux-debianubuntu)
3. [Konfigurasi Keamanan & Optimalisasi Redis (`redis.conf`)](#3-konfigurasi-keamanan--optimalisasi-redis-redisconf)
4. [Instalasi Driver Ekstensi PHP (`phpredis`)](#4-instalasi-driver-ekstensi-php-phpredis)
5. [Konfigurasi Environment Laravel (`.env` & `config/`)](#5-konfigurasi-environment-laravel-env--config)
6. [Pengujian & Verifikasi Koneksi](#6-pengujian--verifikasi-koneksi)
7. [Implementasi di Central-Ticketing (Cache, Queue, Session)](#7-implementasi-di-central-ticketing-cache-queue-session)
8. [Setup Redis di Lingkungan Pengembangan Lokal (Windows / Docker)](#8-setup-redis-di-lingkungan-pengembangan-lokal-windows--docker)
9. [Troubleshooting & Pemecahan Masalah Umum](#9-troubleshooting--pemecahan-masalah-umum)

---

## 1. Mengapa Redis Dibutuhkan?

Pada konfigurasi bawaan (`.env.example`), aplikasi menggunakan database MySQL/SQLite untuk mengelola cache, session, dan queue:
* `CACHE_STORE=database`
* `QUEUE_CONNECTION=database`
* `SESSION_DRIVER=database`

Kelemahan menggunakan database relational (RDBMS) untuk task di atas:
1. **Beban I/O Tinggi:** Setiap request HTTP membaca dan menulis session ke tabel disk database.
2. **Locking & Latency:** Queue polling (`queue:work`) melakukan query berkala (`SELECT ... FOR UPDATE`) yang membebani CPU database.
3. **Kecepatan:** Redis beroperasi sepenuhnya di dalam memori (RAM), menghasilkan response time sub-milidetik (10x - 50x lebih cepat dari RDBMS).

---

## 2. Instalasi Redis Server di Linux (Debian/Ubuntu)

Login ke server produksi via SSH:
```bash
ssh user@IP_SERVER_ANDA
```

### A. Update Repositori & Pasang Redis
```bash
sudo apt update
sudo apt install -y redis-server
```

*(Catatan: Pada repositori Debian 13 / Ubuntu 26 terbaru, paket juga tersedia dengan nama `redis`)*.

### B. Aktifkan & Jalankan Service Redis
```bash
sudo systemctl enable redis-server
sudo systemctl start redis-server
sudo systemctl status redis-server
```

### C. Tes Respon CLI Dasar
```bash
redis-cli ping
```
Jika berhasil, terminal akan membalas:
```text
PONG
```

---

## 3. Konfigurasi Keamanan & Optimalisasi Redis (`redis.conf`)

Buka file konfigurasi utama Redis:
```bash
sudo nano /etc/redis/redis.conf
```

Lakukan penyesuaian pada baris-baris berikut:

### A. Pengaturan Binding IP (Keamanan Jaringan)
Pastikan Redis hanya dapat diakses secara lokal oleh server internal:
```text
bind 127.0.0.1 ::1
protected-mode yes
```
*(Jangan pernah mengarahkan bind ke `0.0.0.0` kecuali server Redis berada di private network terisolasi).*

### B. Pasang Password Autentikasi (`requirepass`)
Cari parameter `# requirepass foobared`, hilangkan tanda `#` lalu ganti dengan kata sandi yang kuat dan aman:
```text
requirepass RahasiaSuperKuatRedisTiket2026!
```

### C. Batasan Memori & Eviction Policy (Pencegahan RAM Penuh)
Agar Redis tidak memakan seluruh RAM server hingga menyebabkan OOM (Out Of Memory) killer:
```text
maxmemory 256mb
maxmemory-policy allkeys-lru
```
*Catatan:*
* `maxmemory 256mb`: Sesuaikan kapasitas RAM yang dialokasikan (misal: 256mb untuk VPS 2GB, 512mb - 1GB untuk VPS 4GB+).
* `allkeys-lru`: Otomatis menghapus cache lama yang jarang dipakai ketika kuota memori tercapai (Least Recently Used).

### D. Mode Supervisi Systemd
Cari baris `supervised no`, ubah menjadi:
```text
supervised systemd
```

### E. Simpan dan Restart Service Redis
Tekan `Ctrl + O` lalu `Enter` untuk menyimpan, kemudian `Ctrl + X` untuk keluar dari nano.

Muat ulang service Redis:
```bash
sudo systemctl restart redis-server
```

### F. Uji Koneksi dengan Password
```bash
redis-cli -a 'RahasiaSuperKuatRedisTiket2026!' ping
```
Output:
```text
PONG
```

---

## 4. Instalasi Driver Ekstensi PHP (`phpredis`)

Laravel mendukung dua jenis client Redis:
1. **`phpredis` (Sangat Direkomendasikan):** Ekstensi native C untuk PHP. Kinerja jauh lebih tinggi dan hemat alokasi memory.
2. **`predis`:** Paket murni PHP melalui composer (`predis/predis`). Digunakan jika server tidak mengizinkan kompilasi modul native.

### Langkah Pasang `phpredis` (PHP 8.3):
```bash
sudo apt install -y php8.3-redis
```
*(Sesuaikan versi PHP jika menggunakan PHP 8.2 atau 8.4, contoh: `php8.4-redis`)*.

Restart PHP-FPM dan Nginx agar ekstensi dimuat:
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx
```

Verifikasi bahwa ekstensi `redis` sudah aktif di PHP:
```bash
php -m | grep -i redis
```
Output:
```text
redis
```

---

## 5. Konfigurasi Environment Laravel (`.env` & `config/`)

Masuk ke direktori project Central-Ticketing:
```bash
cd /var/www/central-ticketing
sudo nano .env
```

### A. Sesuaikan Blok Konfigurasi Redis
Sesuaikan variabel environment Redis dengan password yang telah dibuat pada langkah ke-3:

```dotenv
# ==========================================
# REDIS CONFIGURATION
# ==========================================
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=RahasiaSuperKuatRedisTiket2026!
REDIS_PORT=6379

# Isolasi Database Index
REDIS_DB=0
REDIS_CACHE_DB=1
```

> **Tips Isolasi Database Redis:**
> Redis menyediakan 16 virtual database (index 0 sampai 15).
> * **DB 0**: Digunakan untuk koneksi default & Queue Jobs.
> * **DB 1**: Digunakan khusus untuk Cache.
> *Dengan memisahkan DB Cache dan Queue, perintah pembersihan cache (`php artisan cache:clear`) tidak akan menghapus antrean pekerjaan yang sedang berjalan.*

### B. Alihkan Driver Cache, Queue, dan Session ke Redis
Pada file `.env`, ubah driver berikut:

```dotenv
# Driver Cache
CACHE_STORE=redis

# Driver Antrean (Queue)
QUEUE_CONNECTION=redis

# Driver Sesi Pengguna
SESSION_DRIVER=redis
```

Simpan file `.env` (`Ctrl + O`, `Enter`, `Ctrl + X`).

---

## 6. Pengujian & Verifikasi Koneksi

Setelah konfigurasi diubah, bersihkan cache konfigurasi Laravel:
```bash
php artisan config:clear
```

### A. Uji Otomatis via Artisan Command (Sangat Praktis)
Telah disediakan perintah bawaan untuk memverifikasi ekstensi, latency koneksi, operasi write/read, serta integrasi Cache secara instan:
```bash
php artisan redis:test
```
Perintah ini akan menampilkan tabel parameter konfigurasi, status modul PHP, kecepatan respon (latency dalam milidetik), dan pengujian operasi baca/tulis data ke Redis.

### B. Uji Koneksi Menggunakan Laravel Tinker (Manual)
Jalankan tinker jika ingin melakukan pengetesan interaktif:
```bash
php artisan tinker
```

Jalankan perintah pengujian berikut di dalam tinker:

```php
// 1. Tes Ping ke Redis Server
Illuminate\Support\Facades\Redis::ping();
// Output yang diharapkan: "+PONG" atau true

// 2. Tes Menulis Data Langsung ke Redis
Illuminate\Support\Facades\Redis::set('tiket_check', 'Koneksi Berhasil!');

// 3. Tes Membaca Data dari Redis
Illuminate\Support\Facades\Redis::get('tiket_check');
// Output yang diharapkan: "Koneksi Berhasil!"

// 4. Tes Facade Cache Berbasis Redis
Illuminate\Support\Facades\Cache::put('tes_cache_redis', 'Cache Super Cepat!', 60);
Illuminate\Support\Facades\Cache::get('tes_cache_redis');
// Output: "Cache Super Cepat!"

exit;
```

### C. Uji Melalui Redis CLI Monitor (Realtime Inspection)
Buka jendela terminal terpisah dan jalankan pemantauan realtime:
```bash
redis-cli -a 'RahasiaSuperKuatRedisTiket2026!' monitor
```
Lalu lakukan refresh atau akses web Central-Ticketing di browser. Anda akan melihat log interaksi Redis yang diproses secara instan (GET, SET, EXPIRE).

---

## 7. Implementasi di Central-Ticketing (Cache, Queue, Session)

### A. Jalankan Queue Worker Berbasis Redis
Ketika `QUEUE_CONNECTION=redis`, antrean pekerjaan (seperti sync tiket REST API, pengiriman email notifikasi, ekspor spreadsheet) akan dikirim ke antrean Redis.

Jalankan worker antrean untuk menguji:
```bash
php artisan queue:work redis --verbose
```

### B. Integrasi dengan Supervisor (Layanan Latar Belakang Produksi)
Perbarui konfigurasi Supervisor `/etc/supervisor/conf.d/central-ticketing-worker.conf`:

```ini
[program:central-ticketing-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/central-ticketing/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/central-ticketing/storage/logs/worker.log
stopwaitsecs=3600
```

Terapkan pembaruan Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart central-ticketing-worker:*
sudo supervisorctl status
```

### C. Kompilasi Cache Produksi
Setelah semua pengujian berhasil:
```bash
cd /var/www/central-ticketing
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

---

## 8. Setup Redis di Lingkungan Pengembangan Lokal (Windows / Docker)

Jika Anda mengembangkan aplikasi ini di sistem operasi Windows lokal:

### Cara 1: Menggunakan Docker Desktop (Paling Praktis)
Jalankan container Redis dengan satu perintah:
```bash
docker run -d --name redis-local -p 6379:6379 redis:alpine
```
Pada file `.env` lokal Anda:
```dotenv
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```
*(Catatan: Pasang `composer require predis/predis` jika PHP Windows Anda belum memiliki ekstensi `php_redis.dll`)*.

### Cara 2: Menggunakan WSL2 (Windows Subsystem for Linux)
1. Buka terminal Ubuntu WSL:
   ```bash
   sudo apt install redis-server
   sudo service redis-server start
   ```
2. Redis akan otomatis dapat diakses oleh Windows melalui `127.0.0.1:6379`.

---

## 9. Troubleshooting & Pemecahan Masalah Umum

| Gejala Error | Kemungkinan Penyebab | Solusi |
| :--- | :--- | :--- |
| **`Connection refused [tcp://127.0.0.1:6379]`** | Service Redis belum berjalan atau port terblokir. | Jalankan `sudo systemctl start redis-server` dan cek status dengan `sudo systemctl status redis-server`. |
| **`NOAUTH Authentication required.`** | Redis memiliki sandi (`requirepass`), tetapi `REDIS_PASSWORD` di `.env` masih `null`. | Isi `REDIS_PASSWORD=password_anda` di `.env`, lalu jalankan `php artisan config:clear`. |
| **`ERR invalid password`** | Sandi di `.env` tidak cocok dengan yang ada di `/etc/redis/redis.conf`. | Samakan nilai password di kedua file tersebut. Jika password mengandung simbol, bungkus dengan tanda kutip dua: `REDIS_PASSWORD="P@ssw0rd!"`. |
| **`Class "Redis" not found`** | Ekstensi PHP `phpredis` belum terpasang atau PHP-FPM belum dimuat ulang. | Pasang modul: `sudo apt install php8.3-redis` lalu restart: `sudo systemctl restart php8.3-fpm`. Alternatifnya, gunakan client `predis` via composer. |
| **`OOM command not allowed when used memory > 'maxmemory'`** | RAM Redis penuh dan policy eviction belum diatur. | Buka `/etc/redis/redis.conf`, atur `maxmemory-policy allkeys-lru`, kemudian restart service Redis. |
| **Perubahan `.env` tidak berefek** | Konfigurasi lama masih tersimpan dalam cache Laravel. | Jalankan `php artisan config:clear` disusul `php artisan config:cache`. |

---

## 📌 Rangkuman Perintah Cepat

```bash
# Periksa status Redis service
sudo systemctl status redis-server

# Masuk ke Redis CLI dengan autentikasi
redis-cli -a 'PASSWORD_ANDA'

# Monitor query Redis secara live
redis-cli -a 'PASSWORD_ANDA' monitor

# Periksa jumlah memori terpakai
redis-cli -a 'PASSWORD_ANDA' info memory

# Bersihkan cache Laravel
php artisan cache:clear
php artisan config:clear
```
