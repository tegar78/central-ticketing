<!-- cSpell:disable -->
<!-- markdownlint-disable -->

# Panduan Lengkap Instalasi & Deployment Central-Ticketing
## Target Sistem Operasi: Ubuntu 24.04 LTS & Ubuntu 26.04 LTS Server — PHP 8.4 & Nginx

Panduan instalasi dan deployment produksi (*production-ready deployment*) langkah demi langkah untuk aplikasi **MANAGEMENT TICKET (Central Ticketing System)** pada sistem operasi **Ubuntu 24.04 LTS (Noble Numbat)** dan **Ubuntu 26.04 LTS** menggunakan runtime **PHP 8.4-FPM**, database **MariaDB**, web server **Nginx**, background queue worker **Supervisor**, dan enkripsi **SSL Let's Encrypt**.

Panduan ini disusun secara komprehensif, terstruktur, dan ramah pemula serta junior developer.

---

## 📋 Karakteristik Khusus Ubuntu 24.04 & Ubuntu 26.04 LTS

1. **PPA Resmi Ondřej Surý:** Di Ubuntu 24.04 dan 26.04 LTS, instalasi PHP 8.4 dilakukan dengan menambahkan PPA terverifikasi `ppa:ondrej/php` melalui utilitas `software-properties-common` (`add-apt-repository`) yang secara otomatis mengonfigurasi GPG keyring sistem.
2. **PHP 8.4 Modern & JIT Teroptimasi:** Paket PHP 8.4 membawa peningkatan performa eksekusi sintaks, efisiensi memori, serta kompatibilitas penuh dengan Laravel 13 dan PhpSpreadsheet.
3. **Database Modern:** Mendukung MariaDB 10.11 / 11.x serta MySQL 8.4 LTS bawaan Canonical/Ubuntu.
4. **Isolasi Lingkungan Python (PEP 668):** Certbot diinstal langsung melalui repositori apt resmi Ubuntu (`python3-certbot-nginx`) tanpa mengganggu environment Python sistem.

---

## 📑 Daftar Isi

1. [Gambaran Arsitektur & Alur Kerja](#1-gambaran-arsitektur--alur-kerja)
2. [Persiapan Server & Konfigurasi DNS Domain](#2-persiapan-server--konfigurasi-dns-domain)
3. [Langkah 1: Akses Server SSH & Pembaruan Sistem Ubuntu](#3-langkah-1-akses-server-ssh--pembaruan-sistem-ubuntu)
4. [Langkah 2: Konfigurasi Firewall UFW](#4-langkah-2-konfigurasi-firewall-ufw)
5. [Langkah 3: Instal Web Server Nginx](#5-langkah-3-instal-web-server-nginx)
6. [Langkah 4: Tambah PPA & Instal PHP 8.4-FPM Beserta Ekstensi](#6-langkah-4-tambah-ppa--instal-php-84-fpm-beserta-ekstensi)
7. [Langkah 5: Optimasi File Konfigurasi php.ini untuk Produksi](#7-langkah-5-optimasi-file-konfigurasi-phpini-untuk-produksi)
8. [Langkah 6: Instal & Konfigurasi Database MariaDB](#8-langkah-6-instal--konfigurasi-database-mariadb)
9. [Langkah 7: Instal Composer v2 & Node.js 22 LTS](#9-langkah-7-instal-composer-v2--nodejs-22-lts)
10. [Langkah 8: Clone Source Code Central-Ticketing ke /var/www](#10-langkah-8-clone-source-code-central-ticketing-ke-varwww)
11. [Langkah 9: Konfigurasi File Environment (.env)](#11-langkah-9-konfigurasi-file-environment-env)
12. [Langkah 10: Instal Dependensi PHP, Build Aset Frontend, & Storage Link](#12-langkah-10-instal-dependensi-php-build-aset-frontend--storage-link)
13. [Langkah 11: Migrasi Database & Sinkronisasi Master ODP](#13-langkah-11-migrasi-database--sinkronisasi-master-odp)
14. [Langkah 12: Pengaturan Hak Akses Direktori (Permissions & Ownership)](#14-langkah-12-pengaturan-hak-akses-direktori-permissions--ownership)
15. [Langkah 13: Konfigurasi Virtual Host Nginx (PHP 8.4 Socket)](#15-langkah-13-konfigurasi-virtual-host-nginx-php-84-socket)
16. [Langkah 14: Pasang Sertifikat SSL HTTPS Gratis (Certbot)](#16-langkah-14-pasang-sertifikat-ssl-https-gratis-certbot)
17. [Langkah 15: Setup Background Queue Worker dengan Supervisor](#17-langkah-15-setup-background-queue-worker-dengan-supervisor)
18. [Langkah 16: Setup Laravel Cron Scheduler](#18-langkah-16-setup-laravel-cron-scheduler)
19. [Langkah 17: Kompilasi & Optimasi Cache Produksi](#19-langkah-17-kompilasi--optimasi-cache-produksi)
20. [Panduan Troubleshooting Khusus Ubuntu & PHP 8.4](#20-panduan-troubleshooting-khusus-ubuntu--php-84)
21. [Prosedur Rutin: Cara Update Aplikasi di Masa Depan](#21-prosedur-rutin-cara-update-aplikasi-di-masa-depan)

---

## 1. Gambaran Arsitektur & Alur Kerja

Di server Ubuntu 24.04 / 26.04 LTS, arsitektur aplikasi Central-Ticketing bekerja sebagai berikut:

```text
[ Browser Klien / Teknisi / Admin ]
                  │
                  ▼ (Port 80 HTTP / 443 HTTPS)
            [ Nginx Server ]
                  │
        ┌─────────┴──────────────────────────┐
        │                                    │
(Aset Statis: CSS, JS, Gambar)      (Request Dinamis PHP Laravel)
        ▼                                    ▼
[/var/www/central-ticketing/public]  [ FastCGI Unix Socket ]
                                             │
                                             ▼
                                     [ PHP 8.4-FPM Engine ]
                                             │
                       ┌─────────────────────┴────────────────────┐
                       ▼                                          ▼
             [ MariaDB Database ]                       [ Supervisor Worker ]
       (Tabel Tiket, ODP, Pelanggan)             (Antrean Sinkronisasi Billing)
```

- **Nginx:** Menerima koneksi HTTPS dari browser pengguna, melayani aset statis yang dihasilkan oleh build Vite, dan meneruskan permintaan dinamis ke PHP 8.4 melalui socket FastCGI.
- **PHP 8.4-FPM:** Menjalankan runtime Laravel 13, pemrosesan query database, kalkulasi rute ODP, serta ekspor laporan.
- **MariaDB:** Database relasional dengan skema `central_ticket` yang menyimpan riwayat tiket gangguan terpusat.
- **Supervisor:** Mengontrol antrean worker Laravel (`queue:work`) agar terus berjalan 24 jam sehari secara andal.

---

## 2. Persiapan Server & Konfigurasi DNS Domain

1. **Rekomendasi Spesifikasi Server:**
   - **OS:** Ubuntu 24.04 LTS (Noble Numbat) atau Ubuntu 26.04 LTS (64-bit x86_64 / arm64)
   - **CPU:** 2 vCPU atau lebih
   - **RAM:** 2 GB – 4 GB (Disarankan menambahkan Swap 2 GB jika RAM 2 GB)
   - **Penyimpanan:** 25 GB+ SSD / NVMe
   - **Akses:** Akses SSH ke user `root` atau user dengan hak `sudo`
2. **Domain / Subdomain:** Contoh `tiket.perusahaan.com`
3. **Konfigurasi DNS A Record:**
   - Buka DNS Manager domain Anda (Cloudflare, Niagahoster, Domainesia, dsb.).
   - Tambahkan **A Record**:
     - **Name / Host:** `tiket` (atau `@` jika domain utama)
     - **Value / Target:** Masukkan IP Publik VPS Ubuntu Anda (`103.180.120.45`)
     - **TTL:** Auto atau 300 detik
     - *Catatan:* Jika menggunakan Cloudflare, atur Proxy ke mode **DNS Only** (awan abu-abu) selama penerbitan sertifikat SSL pertama kali.

---

## 3. Langkah 1: Akses Server SSH & Pembaruan Sistem Ubuntu

Buka terminal di komputer Anda, lalu lakukan koneksi SSH ke VPS Ubuntu:

```bash
ssh root@IP_SERVER_ANDA
# Atau jika menggunakan user sudo:
# ssh ubuntu@IP_SERVER_ANDA
```

Perbarui daftar paket dan perbarui paket yang terinstal:

```bash
sudo apt update && sudo apt upgrade -y
```

Pasang kumpulan perkakas dasar yang dibutuhkan:

```bash
sudo apt install -y curl wget git unzip zip software-properties-common \
    ca-certificates lsb-release gnupg ufw supervisor htop nano
```

---

## 4. Langkah 2: Konfigurasi Firewall UFW

Ubuntu dilengkapi dengan firewall UFW bawaan. Aktifkan proteksi firewall untuk mengunci akses port selain yang diizinkan:

```bash
# Set default policy
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Izinkan port SSH (PENTING! Jangan lewatkan agar tidak terkunci keluar server)
sudo ufw allow OpenSSH

# Izinkan port HTTP (80) dan HTTPS (443)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Aktifkan firewall
sudo ufw --force enable

# Verifikasi status
sudo ufw status
```

---

## 5. Langkah 3: Instal Web Server Nginx

Pasang paket Nginx:

```bash
sudo apt install -y nginx

# Pastikan Nginx otomatis menyala saat server restart
sudo systemctl enable nginx
sudo systemctl start nginx

# Periksa status
sudo systemctl status nginx --no-pager
```

> **Uji Coba:** Akses `http://IP_SERVER_ANDA` di browser. Jika muncul halaman *"Welcome to nginx!"*, Nginx telah terpasang dengan benar.

---

## 6. Langkah 4: Tambah PPA & Instal PHP 8.4-FPM Beserta Ekstensi

Di Ubuntu, PHP 8.4 resmi didistribusikan oleh PPA Ondřej Surý.

```bash
# 1. Tambahkan repository PPA Ondrej Surý
sudo add-apt-repository ppa:ondrej/php -y

# 2. Perbarui index paket Ubuntu
sudo apt update
```

### Instal PHP 8.4-FPM dan Modul Wajib Central-Ticketing:

Aplikasi membutuhkan ekstensi PHP untuk database MySQL/MariaDB, SQLite, ZIP, manipulasi gambar GD, Excel, dokumen PDF DomPDF, dan Redis:

```bash
sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-common \
    php8.4-mysql php8.4-mbstring php8.4-xml php8.4-zip \
    php8.4-curl php8.4-gd php8.4-bcmath php8.4-intl \
    php8.4-tokenizer php8.4-sqlite3 php8.4-redis
```

Verifikasi versi PHP aktif:

```bash
php -v
```

*(Pastikan output menunjukkan `PHP 8.4.x`)*.

Pastikan service PHP 8.4-FPM aktif:

```bash
sudo systemctl enable php8.4-fpm
sudo systemctl start php8.4-fpm
sudo systemctl status php8.4-fpm --no-pager
```

Periksa socket file PHP 8.4:

```bash
ls -la /run/php/php8.4-fpm.sock
```

---

## 7. Langkah 5: Optimasi File Konfigurasi php.ini untuk Produksi

Buka file konfigurasi `php.ini` milik PHP 8.4-FPM:

```bash
sudo nano /etc/php/8.4/fpm/php.ini
```

Sesuaikan nilai parameter berikut agar pengunggahan foto tiang ODP dan file lampiran tiket berjalan tanpa hambatan:

```ini
upload_max_filesize = 25M
post_max_size = 30M
memory_limit = 256M
max_execution_time = 120
max_input_time = 120
date.timezone = Asia/Jakarta
```

Simpan file (`Ctrl + O` -> `Enter` -> `Ctrl + X`).

Muat ulang service PHP-FPM:

```bash
sudo systemctl restart php8.4-fpm
```

---

## 8. Langkah 6: Instal & Konfigurasi Database MariaDB

Pasang MariaDB Server di Ubuntu:

```bash
sudo apt install -y mariadb-server

sudo systemctl enable mariadb
sudo systemctl start mariadb
```

### Buat Database & Akun Pengguna Laravel:

Masuk ke console MariaDB:

```bash
sudo mysql
```

Eksekusi perintah SQL berikut (ganti password `PasswordSuperAman84!` dengan password kuat Anda):

```sql
-- 1. Buat database UTF8MB4
CREATE DATABASE central_ticket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Buat akun pengguna database
CREATE USER 'ticket_user'@'localhost' IDENTIFIED BY 'PasswordSuperAman84!';

-- 3. Berikan hak akses penuh atas database central_ticket
GRANT ALL PRIVILEGES ON central_ticket.* TO 'ticket_user'@'localhost';

-- 4. Terapkan perubahan hak akses
FLUSH PRIVILEGES;

-- 5. Keluar
EXIT;
```

> **Catat Data Kredensial:**
> - DB_DATABASE: `central_ticket`
> - DB_USERNAME: `ticket_user`
> - DB_PASSWORD: `PasswordSuperAman84!`

---

## 9. Langkah 7: Instal Composer v2 & Node.js 22 LTS

### 1. Instal Composer v2:

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

## 10. Langkah 8: Clone Source Code Central-Ticketing ke /var/www

```bash
# 1. Siapkan folder web dan berikan kepemilikan ke user aktif
sudo mkdir -p /var/www/central-ticketing
sudo chown -R $USER:$USER /var/www/central-ticketing

# 2. Masuk ke direktori web
cd /var/www

# 3. Clone repositori proyek
git clone https://github.com/tegar78/central-ticketing.git central-ticketing

# 4. Masuk ke dalam direktori proyek
cd /var/www/central-ticketing
```

> **Jika Repositori Private:** Gunakan GitHub Personal Access Token (PAT):
> ```bash
> git clone https://TOKEN_ANDA@github.com/tegar78/central-ticketing.git central-ticketing
> ```

---

## 11. Langkah 9: Konfigurasi File Environment (.env)

Salin template file konfigurasi:

```bash
cp .env.example .env
nano .env
```

Atur parameter utama pada file `.env`:

```ini
APP_NAME="Central Ticketing"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tiket.perusahaan.com
APP_TIMEZONE=Asia/Jakarta

CORS_ALLOWED_ORIGINS=https://tiket.perusahaan.com

# DATABASE MARIADB
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=central_ticket
DB_USERNAME=ticket_user
DB_PASSWORD=PasswordSuperAman84!

# DRIVER SESSION, QUEUE & CACHE
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Simpan file (`Ctrl + O` -> `Enter` -> `Ctrl + X`).

---

## 12. Langkah 10: Instal Dependensi PHP, Build Aset Frontend, & Storage Link

Pastikan terminal berada di `/var/www/central-ticketing`:

```bash
# 1. Pasang dependensi PHP tanpa paket development
composer install --no-dev --optimize-autoloader

# 2. Generate Application Key Laravel
php artisan key:generate --force

# 3. Buat symlink storage publik
php artisan storage:link

# 4. Pasang modul npm dan build aset frontend menggunakan Vite
npm install --ignore-scripts
npm run build
```

---

## 13. Langkah 11: Migrasi Database & Sinkronisasi Master ODP

Jalankan skrip migrasi tabel dan sinkronisasi data ODP:

```bash
# 1. Eksekusi skema migrasi tabel
php artisan migrate --force

# 2. Jalankan seeder akun bawaan & tenant billing instance
# Akun default: admin@central.local / password
php artisan db:seed --force

# 3. Pilihan Inisialisasi Data ODP:

# PILIHAN A (Direkomendasikan untuk Fresh Install):
# Langsung isi 400+ master titik ODP hasil survei riil lengkap dengan koordinat GPS:
php artisan db:seed --class=ProductionOdpSeeder --force

# PILIHAN B (Jika ingin menarik data langsung dari database billing CI3 lokal):
# Berikan akses database billing ke 'ticket_user', lalu update kredensial billing instance:
# sudo mysql -e "GRANT ALL PRIVILEGES ON \`bill3-gyh\`.* TO 'ticket_user'@'localhost'; FLUSH PRIVILEGES;"
# php artisan tinker --execute="App\Models\BillingInstance::where('tenant_code', 'BILL-001')->update(['db_username' => 'ticket_user', 'db_password' => 'PasswordSuperAman84!']);"
# php artisan odp:sync
```

---

## 14. Langkah 12: Pengaturan Hak Akses Direktori (Permissions & Ownership)

User web server di Ubuntu adalah `www-data`. Folder penyimpanan upload dan file cache wajib dapat ditulis oleh `www-data`:

```bash
# 1. Atur kepemilikan file utama
sudo chown -R $USER:www-data /var/www/central-ticketing

# 2. Set permission standar file (644) dan direktori (755)
sudo find /var/www/central-ticketing -type f -exec chmod 644 {} \;
sudo find /var/www/central-ticketing -type d -exec chmod 755 {} \;

# 3. Berikan izin tulis penuh pada folder storage dan bootstrap/cache
sudo chown -R www-data:www-data /var/www/central-ticketing/storage
sudo chown -R www-data:www-data /var/www/central-ticketing/bootstrap/cache
sudo chmod -R 775 /var/www/central-ticketing/storage
sudo chmod -R 775 /var/www/central-ticketing/bootstrap/cache
```

---

## 15. Langkah 13: Konfigurasi Virtual Host Nginx (PHP 8.4 Socket)

Buat file konfigurasi virtual host Nginx:

```bash
sudo nano /etc/nginx/sites-available/central-ticketing.conf
```

Tempelkan (*paste*) konfigurasi Nginx berikut (sesuaikan `tiket.perusahaan.com` dengan domain Anda):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tiket.perusahaan.com;
    root /var/www/central-ticketing/public;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    index index.php index.html;
    charset utf-8;

    # Maksimal ukuran upload foto ODP dan lampiran tiket
    client_max_body_size 25M;

    # Routing Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # Pemrosesan PHP via PHP 8.4-FPM Socket
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Blokir akses ke file sensitif
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Simpan file (`Ctrl + O` -> `Enter` -> `Ctrl + X`).

### Aktifkan Konfigurasi & Reload Nginx:

```bash
# 1. Buat symbolic link ke sites-enabled
sudo ln -s /etc/nginx/sites-available/central-ticketing.conf /etc/nginx/sites-enabled/

# 2. Hapus file konfigurasi default Nginx jika ada
sudo rm -f /etc/nginx/sites-enabled/default

# 3. Uji sintaks konfigurasi
sudo nginx -t
```

Jika muncul pesan `syntax is ok` dan `test is successful`:

```bash
sudo systemctl reload nginx
```

---

## 16. Langkah 14: Pasang Sertifikat SSL HTTPS Gratis (Certbot)

Pasang Certbot menggunakan paket apt Ubuntu resmi:

```bash
sudo apt install -y certbot python3-certbot-nginx
```

Minta sertifikat SSL untuk domain Anda:

```bash
sudo certbot --nginx -d tiket.perusahaan.com
```

- Masukkan email notifikasi perpanjangan Anda.
- Ketik `Y` untuk menyetujui lisensi.
- Certbot akan memvalidasi kepemilikan domain dan otomatis mengonfigurasi SSL HTTPS di Nginx.

Uji otomatisasi perpanjangan sertifikat:

```bash
sudo certbot renew --dry-run
```

---

## 17. Langkah 15: Setup Background Queue Worker dengan Supervisor

Supervisor menjaga agar worker antrean sinkronisasi pelanggan dan pengiriman data terus berjalan tanpa henti.

Buat file konfigurasi Supervisor:

```bash
sudo nano /etc/supervisor/conf.d/central-ticketing-worker.conf
```

Tambahkan isi konfigurasi berikut:

```ini
[program:central-ticketing-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/central-ticketing/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
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

Simpan file, lalu aktifkan konfigurasi:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start central-ticketing-worker:*
sudo supervisorctl status
```

*(Pastikan berstatus `RUNNING`)*.

---

## 18. Langkah 16: Setup Laravel Cron Scheduler

Buka crontab sistem Ubuntu:

```bash
sudo crontab -e
```

*(Pilih editor `1` untuk nano)*.

Tambahkan baris berikut di baris terbawah:

```cron
* * * * * cd /var/www/central-ticketing && php artisan schedule:run >> /dev/null 2>&1
```

Simpan file (`Ctrl + O` -> `Enter` -> `Ctrl + X`).

---

## 19. Langkah 17: Kompilasi & Optimasi Cache Produksi

Percepat waktu respon aplikasi dengan mengompilasi cache Laravel:

```bash
cd /var/www/central-ticketing

# Bersihkan cache lama
php artisan optimize:clear

# Kompilasi cache produksi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 20. Panduan Troubleshooting Khusus Ubuntu & PHP 8.4

### 1. Error 502 Bad Gateway
- **Penyebab:** Nginx gagal menghubungi socket PHP 8.4-FPM.
- **Pemeriksaan & Solusi:**
  ```bash
  sudo systemctl status php8.4-fpm
  sudo systemctl restart php8.4-fpm
  ls -la /run/php/php8.4-fpm.sock
  ```
  Pastikan baris `fastcgi_pass unix:/run/php/php8.4-fpm.sock;` pada konfigurasi Nginx sesuai dengan lokasi socket riil.

### 2. Error 500: Permission Denied
- **Solusi Cepat:**
  ```bash
  sudo chown -R www-data:www-data /var/www/central-ticketing/storage /var/www/central-ticketing/bootstrap/cache
  sudo chmod -R 775 /var/www/central-ticketing/storage /var/www/central-ticketing/bootstrap/cache
  ```

### 3. PPA Ondrej Gagal Ditambahkan (add-apt-repository error)
- **Solusi:**
  Pastikan paket `software-properties-common` dan sertifikat SSL terbaru terpasang:
  ```bash
  sudo apt install -y software-properties-common ca-certificates
  sudo update-ca-certificates
  sudo add-apt-repository ppa:ondrej/php -y
  sudo apt update
  ```

### 4. Tampilan CSS Tidak Beraturan / Blank Putih
- **Solusi:**
  Build ulang aset Vite:
  ```bash
  cd /var/www/central-ticketing
  npm run build
  php artisan view:clear
  ```

### 5. File Log untuk Investigasi Lanjutan di Ubuntu:
- **Nginx Error Log:** `sudo tail -n 50 /var/log/nginx/error.log`
- **PHP 8.4 FPM Log:** `sudo tail -n 50 /var/log/php8.4-fpm.log`
- **Laravel Log:** `tail -n 50 /var/www/central-ticketing/storage/logs/laravel.log`
- **Supervisor Log:** `tail -n 50 /var/www/central-ticketing/storage/logs/worker.log`

---

## 21. Prosedur Rutin: Cara Update Aplikasi di Masa Depan

Gunakan alur standar berikut setiap kali memperbarui versi aplikasi dari Git:

```bash
# 1. Masuk ke folder proyek
cd /var/www/central-ticketing

# 2. Aktifkan mode maintenance
php artisan down

# 3. Ambil kode terbaru dari GitHub
git pull origin main

# 4. Perbarui dependensi dan build aset jika ada perubahan
composer install --no-dev --optimize-autoloader
npm install --ignore-scripts
npm run build

# 5. Jalankan migrasi database baru
php artisan migrate --force

# 6. Bersihkan dan kompilasi ulang cache produksi
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart background queue worker
sudo supervisorctl restart central-ticketing-worker:*

# 8. Nonaktifkan mode maintenance
php artisan up
```

---

## ✅ Selesai!
Aplikasi **Central-Ticketing** Anda kini telah aktif di server **Ubuntu 24.04 / 26.04 LTS** dengan performa maksimal **PHP 8.4-FPM**, database **MariaDB**, proteksi firewall, enkripsi SSL HTTPS, dan manajemen antrean background worker yang stabil!
