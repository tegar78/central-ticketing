<!-- cSpell:disable -->
<!-- markdownlint-disable -->

# Panduan Lengkap Instalasi & Deployment Central-Ticketing
## Target Sistem Operasi: Debian 13 (Trixie) Server — PHP 8.4 & Nginx

Panduan instalasi dan deployment produksi (*production-ready deployment*) langkah demi langkah untuk aplikasi **MANAGEMENT TICKET (Central Ticketing System)** pada sistem operasi **Debian 13 (Trixie)** menggunakan runtime terbaru **PHP 8.4-FPM**, database **MariaDB**, web server **Nginx**, antrean latar belakang **Supervisor**, dan sertifikat keamanan **SSL Let's Encrypt**.

Disusun secara sistematis, bertahap, dan dilengkapi penjelasan perintah agar mudah dipahami oleh pemula, junior developer, maupun sysadmin.

---

## 📋 Karakteristik Khusus Debian 13 (Trixie)

Debian 13 (Trixie) menerapkan standar keamanan repositori dan sistem modern:
1. **Standar Keyring Modern (deb822 & /etc/apt/keyrings):** Debian tidak lagi menggunakan perintah usang `apt-key add`. Kunci GPG disimpan terisolasi di direktori `/etc/apt/keyrings/` dengan parameter `signed-by`.
2. **Dukungan Penuh PHP 8.4:** Menggunakan repositori resmi Ondřej Surý untuk Debian (`deb.sury.org`) yang menyediakan paket PHP 8.4 terbaru dengan performa JIT compiler dan library ekstensi terkini.
3. **Database MariaDB 11:** Database performa tinggi dan kompatibel penuh dengan skema MySQL/Laravel 13.
4. **Isolasi Lingkungan Python:** Paket Certbot dipasang melalui `apt` bawaan Debian untuk mematuhi aturan Debian PEP 668.

---

## 📑 Daftar Isi

1. [Gambaran Arsitektur & Alur Kerja](#1-gambaran-arsitektur--alur-kerja)
2. [Persiapan Server & DNS Domain](#2-persiapan-server--dns-domain)
3. [Langkah 1: Masuk Server & Update Sistem Debian 13](#3-langkah-1-masuk-server--update-sistem-debian-13)
4. [Langkah 2: Konfigurasi Firewall UFW](#4-langkah-2-konfigurasi-firewall-ufw)
5. [Langkah 3: Instal Web Server Nginx](#5-langkah-3-instal-web-server-nginx)
6. [Langkah 4: Setup Repositori & Instal PHP 8.4-FPM](#6-langkah-4-setup-repositori--instal-php-84-fpm)
7. [Langkah 5: Optimasi Konfigurasi php.ini untuk Produksi](#7-langkah-5-optimasi-konfigurasi-phpini-untuk-produksi)
8. [Langkah 6: Instal & Amankan Database MariaDB](#8-langkah-6-instal--amankan-database-mariadb)
9. [Langkah 7: Instal Composer v2 & Node.js 22 LTS](#9-langkah-7-instal-composer-v2--nodejs-22-lts)
10. [Langkah 8: Clone Source Code Central-Ticketing](#10-langkah-8-clone-source-code-central-ticketing)
11. [Langkah 9: Konfigurasi File Environment (.env)](#11-langkah-9-konfigurasi-file-environment-env)
12. [Langkah 10: Install Dependensi, Key Generate, & Build Aset](#12-langkah-10-install-dependensi-key-generate--build-aset)
13. [Langkah 11: Migrasi Database & Inisialisasi Master ODP](#13-langkah-11-migrasi-database--inisialisasi-master-odp)
14. [Langkah 12: Pengaturan Hak Akses Direktori (Permissions & Ownership)](#14-langkah-12-pengaturan-hak-akses-direktori-permissions--ownership)
15. [Langkah 13: Konfigurasi Virtual Host Nginx (PHP 8.4 Socket)](#15-langkah-13-konfigurasi-virtual-host-nginx-php-84-socket)
16. [Langkah 14: Pasang Sertifikat SSL Gratis HTTPS (Certbot)](#16-langkah-14-pasang-sertifikat-ssl-gratis-https-certbot)
17. [Langkah 15: Konfigurasi Background Queue Worker (Supervisor)](#17-langkah-15-konfigurasi-background-queue-worker-supervisor)
18. [Langkah 16: Setup Laravel Cron Scheduler](#18-langkah-16-setup-laravel-cron-scheduler)
19. [Langkah 17: Kompilasi & Optimasi Cache Produksi](#19-langkah-17-kompilasi--optimasi-cache-produksi)
20. [Panduan Troubleshooting Khusus Debian 13 & PHP 8.4](#20-panduan-troubleshooting-khusus-debian-13--php-84)
21. [Prosedur Pembaruan / Update Aplikasi di Masa Depan](#21-prosedur-pembaruan--update-aplikasi-di-masa-depan)

---

## 1. Gambaran Arsitektur & Alur Kerja

Di server Debian 13, aplikasi **Central-Ticketing** berjalan dengan struktur berikut:

```text
[ Browser / Pengguna / Klien Billing ]
                  │
                  ▼ (Port 80 HTTP / 443 HTTPS)
            [ Nginx Server ]
                  │
        ┌─────────┴──────────────────────────┐
        │                                    │
(File Statis: CSS, JS, Gambar)      (Request Dinamis PHP)
        ▼                                    ▼
[/var/www/central-ticketing/public]  [ FastCGI Unix Socket ]
                                             │
                                             ▼
                                     [ PHP 8.4-FPM Engine ]
                                             │
                       ┌─────────────────────┴────────────────────┐
                       ▼                                          ▼
             [ MariaDB Database ]                       [ Supervisor Worker ]
       (Data Tiket, Pelanggan, ODP)              (Antrean Email, Log, Sync)
```

- **Nginx:** Menangani permintaan awal dari internet, menyajikan aset frontend statis, dan mengalihkan proses dinamis ke socket PHP 8.4.
- **PHP 8.4-FPM:** Memproses kode backend Laravel 13, logika bisnis tiket, sinkronisasi billing, dan ekspor dokumen Excel/PDF.
- **MariaDB:** Tempat penyimpanan data relasional terpusat dengan dukungan collation `utf8mb4_unicode_ci`.
- **Supervisor:** Mengawal proses *queue worker* agar berjalan 24/7 tanpa henti di latar belakang sistem.

---

## 2. Persiapan Server & DNS Domain

Sebelum memulai konfigurasi di terminal Debian:

1. **Spesifikasi Server Direkomendasikan:**
   - **OS:** Debian 13 (Trixie) 64-bit Minimal Server
   - **CPU:** 2 vCPU atau lebih
   - **RAM:** Minimal 2 GB (Disarankan 4 GB; jika RAM 1-2 GB, buat Swap 2 GB)
   - **Penyimpanan:** Minimal 25 GB SSD / NVMe
   - **Akses:** Akun `root` atau pengguna dengan hak akses `sudo`
2. **Domain / Subdomain:** Contoh `tiket.perusahaan.net`
3. **Konfigurasi DNS A Record:**
   - Masuk ke DNS Management domain Anda (Cloudflare, cPanel, Domainesia, dsb.).
   - Tambahkan **A Record**:
     - **Host / Name:** `tiket` (atau `@` untuk domain utama)
     - **Points to / Target:** `IP_PUBLIK_SERVER_DEBIAN` (Contoh: `103.180.120.45`)
     - **TTL:** Auto atau 300 detik
     - *Catatan Cloudflare:* Nonaktifkan proxy (*DNS Only / Grey Cloud*) saat proses instalasi SSL awal agar verifikasi Certbot berhasil.

---

## 3. Langkah 1: Masuk Server & Update Sistem Debian 13

Buka terminal di komputer Anda, lalu masuk ke server Debian melalui SSH:

```bash
ssh root@IP_SERVER_ANDA
# Atau jika menggunakan user sudo:
# ssh namauser@IP_SERVER_ANDA
```

Perbarui daftar paket bawaan sistem Debian 13:

```bash
sudo apt update && sudo apt upgrade -y
```

Pasang perkakas dasar administrasi server:

```bash
sudo apt install -y curl wget git unzip zip software-properties-common \
    ca-certificates lsb-release gnupg ufw supervisor htop nano
```

---

## 4. Langkah 2: Konfigurasi Firewall UFW

Amankan port server agar hanya port yang diperlukan saja yang terbuka ke internet:

```bash
# Set aturan bawaan: tolak paket masuk yang tidak terdaftar, izinkan paket keluar
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Izinkan port SSH (PENTING: Jangan lewatkan baris ini agar remote SSH tidak terputus!)
sudo ufw allow OpenSSH

# Izinkan lalu lintas web HTTP (80) dan HTTPS (443)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Aktifkan firewall
sudo ufw --force enable

# Periksa status
sudo ufw status verbose
```

---

## 5. Langkah 3: Instal Web Server Nginx

Pasang paket Nginx di Debian 13:

```bash
sudo apt install -y nginx

# Pastikan Nginx otomatis menyala setiap kali server reboot
sudo systemctl enable nginx
sudo systemctl start nginx

# Pastikan status Nginx aktif
sudo systemctl status nginx --no-pager
```

> **Verifikasi Cepat:** Buka browser dan kunjungi `http://IP_SERVER_ANDA`. Halaman selamat datang *"Welcome to nginx!"* akan tampil.

---

## 6. Langkah 4: Setup Repositori & Instal PHP 8.4-FPM

Debian 13 membutuhkan penambahan repositori PHP Ondřej Surý menggunakan mekanisme modern **GPG Keyring** (`/etc/apt/keyrings/`):

```bash
# 1. Pastikan dependensi transport HTTPS terpasang
sudo apt install -y apt-transport-https ca-certificates curl gnupg

# 2. Buat direktori keyrings jika belum ada
sudo install -m 0755 -d /etc/apt/keyrings

# 3. Unduh GPG Key resmi Ondrej Sury ke lokasi keyrings Debian
sudo curl -fsSL https://packages.sury.org/php/apt.gpg -o /etc/apt/keyrings/deb.sury.org-php.gpg
sudo chmod a+r /etc/apt/keyrings/deb.sury.org-php.gpg

# 4. Tambahkan repositori PHP untuk Debian 13 (Trixie)
echo "deb [signed-by=/etc/apt/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ trixie main" | sudo tee /etc/apt/sources.list.d/php.list

# 5. Perbarui index paket
sudo apt update
```

### Pasang PHP 8.4-FPM dan Seluruh Ekstensi yang Diperlukan:

Central-Ticketing membutuhkan ekstensi pemrosesan Excel (PhpSpreadsheet), dokumen PDF cetak (DomPDF), Redis, SQLite, dan manipulasi gambar:

```bash
sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-common \
    php8.4-mysql php8.4-mbstring php8.4-xml php8.4-zip \
    php8.4-curl php8.4-gd php8.4-bcmath php8.4-intl \
    php8.4-tokenizer php8.4-sqlite3 php8.4-redis
```

Verifikasi instalasi PHP:

```bash
php -v
```

*(Output harus menampilkan `PHP 8.4.x`)*.

Pastikan layanan `php8.4-fpm` aktif dan berjalan otomatis saat startup:

```bash
sudo systemctl enable php8.4-fpm
sudo systemctl start php8.4-fpm
sudo systemctl status php8.4-fpm --no-pager
```

Periksa socket PHP 8.4 yang tercipta di sistem:

```bash
ls -la /run/php/php8.4-fpm.sock
```

---

## 7. Langkah 5: Optimasi Konfigurasi php.ini untuk Produksi

Agar pengunggahan file foto tiang ODP, berkas lampiran tiket (hingga 20 MB), dan ekspor laporan PDF berjalan lancar, sesuaikan batas alokasi memori pada file konfigurasi PHP 8.4.

Buka file konfigurasi `php.ini` untuk FPM:

```bash
sudo nano /etc/php/8.4/fpm/php.ini
```

Cari parameter berikut (gunakan `Ctrl + W` di nano) dan sesuaikan nilainya:

```ini
upload_max_filesize = 25M
post_max_size = 30M
memory_limit = 256M
max_execution_time = 120
max_input_time = 120
date.timezone = Asia/Jakarta
```

Simpan file (`Ctrl + O`, lalu `Enter`, lalu `Ctrl + X`).

Muat ulang service PHP 8.4-FPM:

```bash
sudo systemctl restart php8.4-fpm
```

---

## 8. Langkah 6: Instal & Amankan Database MariaDB

Pasang database server MariaDB:

```bash
sudo apt install -y mariadb-server

sudo systemctl enable mariadb
sudo systemctl start mariadb
```

### Buat Database & Pengguna Khusus Central-Ticketing:

Masuk ke console MariaDB sebagai root:

```bash
sudo mysql
```

Jalankan query SQL berikut (ganti password `PasswordSuperAman84!` dengan password kuat pilihan Anda):

```sql
-- 1. Buat database UTF8MB4
CREATE DATABASE central_ticket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Buat akun pengguna khusus
CREATE USER 'ticket_user'@'localhost' IDENTIFIED BY 'PasswordSuperAman84!';

-- 3. Berikan hak akses penuh atas database central_ticket
GRANT ALL PRIVILEGES ON central_ticket.* TO 'ticket_user'@'localhost';

-- 4. Terapkan perubahan hak akses
FLUSH PRIVILEGES;

-- 5. Keluar dari MariaDB
EXIT;
```

> **Simpan Catatan Kredensial:**
> - Database Name: `central_ticket`
> - Database User: `ticket_user`
> - Database Password: `PasswordSuperAman84!`

---

## 9. Langkah 7: Instal Composer v2 & Node.js 22 LTS

### 1. Instal Composer v2 (Manajer Dependensi PHP):

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### 2. Instal Node.js 22 LTS & NPM via NodeSource:

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## 10. Langkah 8: Clone Source Code Central-Ticketing

Standar direktori aplikasi web di server Linux adalah di `/var/www/`.

```bash
# 1. Buat direktori dan berikan kepemilikan sementara ke pengguna saat ini
sudo mkdir -p /var/www/central-ticketing
sudo chown -R $USER:$USER /var/www/central-ticketing

# 2. Masuk ke direktori web
cd /var/www

# 3. Clone repositori dari GitHub (sesuaikan URL dengan repositori Anda)
git clone https://github.com/tegar78/central-ticketing.git central-ticketing

# 4. Masuk ke direktori proyek
cd /var/www/central-ticketing
```

> **Tips Repositori Private:** Jika repositori berstatus privat di GitHub, buat Personal Access Token (PAT) di akun GitHub Anda (*Settings -> Developer Settings -> Personal Access Tokens*), kemudian lakukan clone:
> ```bash
> git clone https://TOKEN_ANDA@github.com/tegar78/central-ticketing.git central-ticketing
> ```

---

## 11. Langkah 9: Konfigurasi File Environment (.env)

Salin template konfigurasi:

```bash
cp .env.example .env
nano .env
```

Sesuaikan baris-baris kunci berikut di dalam `.env`:

```ini
APP_NAME="Central Ticketing"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tiket.perusahaan.net
APP_TIMEZONE=Asia/Jakarta

CORS_ALLOWED_ORIGINS=https://tiket.perusahaan.net

# KONEKSI DATABASE MARIADB
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=central_ticket
DB_USERNAME=ticket_user
DB_PASSWORD=PasswordSuperAman84!

# DRIVER CACHE, SESSION & QUEUE
# Catatan: Jika server menggunakan Redis, ganti driver ke redis.
# Untuk setup standar database driver:
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database

# SHARED API KEY BILLING GAYUH (Git-Safe, disimpan di .env server produksi)
BILLING_SHARED_KEY=ganti_dengan_shared_key_rahasia_anda
```

Simpan file (`Ctrl + O` -> `Enter` -> `Ctrl + X`).

---

## 12. Langkah 10: Install Dependensi, Key Generate, & Build Aset

Pastikan posisi terminal masih berada di folder `/var/www/central-ticketing`:

```bash
# 1. Pasang dependensi PHP untuk lingkungan produksi (tanpa package pengujian)
composer install --no-dev --optimize-autoloader

# 2. Buat Application Encryption Key secara otomatis
php artisan key:generate --force

# 3. Buat symlink direktori storage publik (untuk foto tiang ODP, avatar, lampiran)
php artisan storage:link

# 4. Pasang paket Node.js dan lakukan build aset frontend (Tailwind CSS, JS) via Vite
npm install --ignore-scripts
npm run build
```

---

## 13. Langkah 11: Migrasi Database & Inisialisasi Master ODP

Jalankan perintah migrasi skema tabel ke MariaDB:

```bash
# 1. Jalankan migrasi seluruh tabel aplikasi
php artisan migrate --force

# 2. Jalankan seeding akun default & tenant billing instance
# Menghasilkan admin default: admin@central.local / password
php artisan db:seed --force

# 3. Pilihan Inisialisasi Data ODP:

# PILIHAN A (Direkomendasikan untuk Fresh Install):
# Langsung isi 400+ master titik ODP hasil survei riil lengkap dengan koordinat GPS:
php artisan db:seed --class=ProductionOdpSeeder --force

# PILIHAN B (Jika ingin menarik data langsung dari database billing CI3 lokal):
# Catatan: Di Debian 13, user 'root' tanpa password dilarang login via TCP.
# Berikan akses database billing ke 'ticket_user', lalu update kredensial billing instance:
# sudo mysql -e "GRANT ALL PRIVILEGES ON \`bill3-gyh\`.* TO 'ticket_user'@'localhost'; FLUSH PRIVILEGES;"
# php artisan tinker --execute="App\Models\BillingInstance::where('tenant_code', 'BILL-001')->update(['db_username' => 'ticket_user', 'db_password' => 'PasswordSuperAman84!']);"
# php artisan odp:sync

# 4. Registrasi Massal 40 Billing Instance Gayuh (Bulk Register)
# Mendaftarkan seluruh 40 domain billing Gayuh (BILL2 & BILL3) ke database secara instan
# (Membaca BILLING_SHARED_KEY dari .env secara aman):
php artisan billing:bulk-register

# Periksa status seluruh billing instance yang berhasil didaftarkan:
php artisan billing:list

# 5. Sinkronisasi Data Pelanggan & Master ODP Seluruh Billing:
# Tarik pelanggan dari seluruh 40 server billing via REST API:
php artisan sync:billing-customers --all

# Sinkronkan master titik ODP dari seluruh billing:
php artisan odp:sync
```

---

## 14. Langkah 12: Pengaturan Hak Akses Direktori (Permissions & Ownership)

Web server Nginx dan PHP-FPM di Debian berjalan dengan user dan group `www-data`. Folder penyimpanan cache dan berkas upload harus dapat ditulis oleh `www-data`:

```bash
# 1. Berikan kepemilikan umum ke user aktif dan group www-data
sudo chown -R $USER:www-data /var/www/central-ticketing

# 2. Berikan hak akses baca & eksekusi standar
sudo find /var/www/central-ticketing -type f -exec chmod 644 {} \;
sudo find /var/www/central-ticketing -type d -exec chmod 755 {} \;

# 3. Berikan hak tulis penuh kepada www-data untuk direktori storage & bootstrap/cache
sudo chown -R www-data:www-data /var/www/central-ticketing/storage
sudo chown -R www-data:www-data /var/www/central-ticketing/bootstrap/cache
sudo chmod -R 775 /var/www/central-ticketing/storage
sudo chmod -R 775 /var/www/central-ticketing/bootstrap/cache
```

---

## 15. Langkah 13: Konfigurasi Virtual Host Nginx (PHP 8.4 Socket)

Buat file konfigurasi Virtual Host baru di Nginx:

```bash
sudo nano /etc/nginx/sites-available/central-ticketing.conf
```

Tempelkan (*paste*) konfigurasi Nginx teroptimasi berikut (ganti `tiket.perusahaan.net` dengan domain Anda):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tiket.perusahaan.net;
    root /var/www/central-ticketing/public;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    index index.php index.html;
    charset utf-8;

    # Batas ukuran upload foto ODP dan dokumen lampiran
    client_max_body_size 25M;

    # Routing Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # Eksekusi script PHP via PHP 8.4-FPM Unix Socket
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Blokir akses ke file tersembunyi (.env, .git, dll)
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Simpan file (`Ctrl + O` -> `Enter` -> `Ctrl + X`).

### Aktifkan Konfigurasi & Uji Sintaks:

```bash
# 1. Buat symbolic link ke folder sites-enabled
sudo ln -s /etc/nginx/sites-available/central-ticketing.conf /etc/nginx/sites-enabled/

# 2. Hapus virtual host default Nginx (jika masih aktif)
sudo rm -f /etc/nginx/sites-enabled/default

# 3. Uji apakah file konfigurasi bebas dari kesalahan sintaks
sudo nginx -t
```

Jika output menampilkan `syntax is ok` dan `test is successful`, muat ulang Nginx:

```bash
sudo systemctl reload nginx
```

---

## 16. Langkah 14: Pasang Sertifikat SSL Gratis HTTPS (Certbot)

Pasang Certbot menggunakan paket Debian bawaan yang stabil:

```bash
sudo apt install -y certbot python3-certbot-nginx
```

Jalankan perintah pendaftaran SSL untuk domain Anda:

```bash
sudo certbot --nginx -d tiket.perusahaan.net
```

- Masukkan alamat email aktif Anda (untuk peringatan masa kedaluwarsa).
- Tekan `Y` untuk menyetujui *Terms of Service*.
- Certbot akan otomatis menguji verifikasi domain dan memodifikasi file Nginx menjadi HTTPS otomatis.

Uji mekanisme perpanjangan otomatis (*auto-renewal*):

```bash
sudo certbot renew --dry-run
```

---

## 17. Langkah 15: Konfigurasi Background Queue Worker (Supervisor)

Supervisor bertugas menjaga proses antrean (*queue worker*) Laravel tetap hidup dan otomatis memulihkannya jika terhenti.

Buat file konfigurasi worker di Supervisor:

```bash
sudo nano /etc/supervisor/conf.d/central-ticketing-worker.conf
```

Isi dengan script konfigurasi berikut:

```ini
[program:central-ticketing-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/central-ticketing/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/central-ticketing/storage/logs/worker.log
stopwaitsecs=3600
```

> **Catatan Multi-Billing:** Disarankan menyetel `numprocs=4` agar proses antrean webhook pengiriman tiket dan sinkronisasi data dari 40 server billing CI3 dapat diproses secara paralel tanpa saling menunggu.

Simpan file, kemudian terapkan ke Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start central-ticketing-worker:*
sudo supervisorctl status
```

*(Status harus menunjukkan `RUNNING` untuk keempat worker).*

---

## 18. Langkah 16: Setup Laravel Cron Scheduler

Aplikasi Central-Ticketing membutuhkan scheduler untuk pemantauan tiket otomatis dan sinkronisasi berkala data node billing.

Buka crontab sistem:

```bash
sudo crontab -e
```

*(Jika baru pertama kali dibuka, tekan `1` untuk memilih editor nano).*

Tambahkan satu baris perintah ini di baris paling akhir:

```cron
* * * * * cd /var/www/central-ticketing && php artisan schedule:run >> /dev/null 2>&1
```

Simpan (`Ctrl + O` -> `Enter` -> `Ctrl + X`).

---

## 19. Langkah 17: Kompilasi & Optimasi Cache Produksi

Kompilasi seluruh file rute, view, konfigurasi, dan event agar performa loading aplikasi menjadi instan:

```bash
cd /var/www/central-ticketing

# Bersihkan sisa cache lama
php artisan optimize:clear

# Kompilasi cache produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> ⚠️ **Catatan Penting:** Setiap kali Anda mengubah isi file `.env`, Anda **wajib** menjalankan `php artisan config:cache` kembali agar Laravel memuat konfigurasi yang baru.

---

## 20. Panduan Troubleshooting Khusus Debian 13 & PHP 8.4

### 1. Error 502 Bad Gateway
- **Penyebab:** Nginx tidak dapat tersambung ke socket PHP 8.4-FPM.
- **Pemeriksaan:**
  ```bash
  sudo systemctl status php8.4-fpm
  ls -la /run/php/php8.4-fpm.sock
  ```
- **Solusi:**
  Jika service mati, nyalakan kembali:
  ```bash
  sudo systemctl restart php8.4-fpm
  ```
  Pastikan pada file `/etc/nginx/sites-available/central-ticketing.conf` tertulis `fastcgi_pass unix:/run/php/php8.4-fpm.sock;` (bukan versi lain).

### 2. Error 500: Permission Denied
- **Penyebab:** Hak akses folder `storage` atau `bootstrap/cache` tidak mengizinkan user `www-data` menulis file log/cache.
- **Solusi:**
  ```bash
  sudo chown -R www-data:www-data /var/www/central-ticketing/storage /var/www/central-ticketing/bootstrap/cache
  sudo chmod -R 775 /var/www/central-ticketing/storage /var/www/central-ticketing/bootstrap/cache
  ```

### 3. Error 500: No Application Encryption Key Specified
- **Penyebab:** Nilai `APP_KEY` pada file `.env` masih kosong.
- **Solusi:**
  ```bash
  cd /var/www/central-ticketing
  php artisan key:generate --force
  php artisan config:cache
  ```

### 4. Tampilan CSS / Javascript Berantakan / 404 pada /build/assets
- **Penyebab:** Aset Vite belum dikompilasi atau kepemilikan folder `public/build` salah.
- **Solusi:**
  ```bash
  cd /var/www/central-ticketing
  npm run build
  sudo chown -R $USER:www-data public/build
  php artisan view:clear
  ```

### 5. Log Error Debian untuk Pengecekan Lebih Lanjut:
- **Log Nginx Error:** `sudo tail -n 50 /var/log/nginx/error.log`
- **Log PHP 8.4-FPM:** `sudo tail -n 50 /var/log/php8.4-fpm.log`
- **Log Laravel:** `tail -n 50 /var/www/central-ticketing/storage/logs/laravel.log`
- **Log Supervisor Worker:** `tail -n 50 /var/www/central-ticketing/storage/logs/worker.log`

---

## 21. Prosedur Pembaruan / Update Aplikasi di Masa Depan

Jika ada fitur baru atau perbaikan kode yang sudah dipush ke GitHub, jalankan langkah-langkah pembaruan berikut di server produksi:

```bash
# 1. Masuk ke direktori aplikasi
cd /var/www/central-ticketing

# 2. Aktifkan mode maintenance (website menampilkan halaman pemeliharaan sementara)
php artisan down

# 3. Tarik kode terbaru dari repositori GitHub
git pull origin main

# 4. Pasang library/dependensi baru jika ada
composer install --no-dev --optimize-autoloader
npm install --ignore-scripts
npm run build

# 5. Jalankan migrasi database jika terdapat tabel/kolom baru
php artisan migrate --force

# 6. Bersihkan dan perbarui cache aplikasi
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart background worker agar membaca kode baru
sudo supervisorctl restart central-ticketing-worker:*

# 8. Matikan mode maintenance (website kembali online)
php artisan up
```

---

## 22. Lampiran Khusus: Panduan Integrasi 40 Server Billing CodeIgniter 3 (Git-Safe)

Agar API Key rahasia tidak bocor atau terlihat di repositori GitHub publik pada aplikasi CodeIgniter 3:

### 1. Salin Controller Central ke Setiap Server CI3:
Salin file `docs/Central.php` dari Central-Ticketing ke direktori aplikasi CI3 masing-masing:
```text
application/controllers/Central.php
```

### 2. Buat File Konfigurasi Rahasia di CI3 (Local Only):
Buat file baru di `application/config/central.php` pada server CI3:
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['central_url']     = 'https://tiket.perusahaan.net/api/v1'; // Domain server Central Anda
$config['central_api_key'] = 'ganti_dengan_shared_key_rahasia_anda'; // Samakan dengan BILLING_SHARED_KEY di .env Central
```

### 3. Tambahkan ke .gitignore di Repositori CI3:
Buka file `.gitignore` pada proyek CI3 Anda, lalu tambahkan baris berikut di baris paling akhir:
```gitignore
application/config/central.php
```
> **Keuntungan Keamanan:** File konfigurasi yang berisi API Key rahasia ini tidak akan pernah ter-commit atau ter-push ke GitHub!

---

## ✅ Selesai!
Sistem **Central-Ticketing** Anda kini telah online di server **Debian 13 (Trixie)** dengan runtime **PHP 8.4-FPM**, database **MariaDB**, proteksi firewall, enkripsi SSL HTTPS, dan manajemen antrean background worker yang stabil!
