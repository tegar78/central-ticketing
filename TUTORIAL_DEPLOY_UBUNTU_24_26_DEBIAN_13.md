<!-- cSpell:disable -->
<!-- markdownlint-disable -->

# Panduan Lengkap Deploy Central-Ticketing

## Target OS: Ubuntu 24.04 LTS, Ubuntu 26.04 LTS dan Debian 13 (Trixie)

Panduan deployment produksi mutakhir (*production-ready deployment*) untuk aplikasi **Central-Ticketing** yang disusun secara sistematis, bertahap, dan mudah dipahami oleh pemula maupun junior developer.

---

## Daftar Isi

1. [Konsep Dasar dan Gambaran Arsitektur](#1-konsep-dasar-dan-gambaran-arsitektur)
2. [Persiapan Awal Sebelum Masuk Server](#2-persiapan-awal-sebelum-masuk-server)
3. [Langkah 1: Masuk Server dan Update Sistem](#3-langkah-1-masuk-server-dan-update-sistem)
4. [Langkah 2: Amankan Server dengan Firewall UFW](#4-langkah-2-amankan-server-dengan-firewall-ufw)
5. [Langkah 3: Instal Web Server Nginx](#5-langkah-3-instal-web-server-nginx)
6. [Langkah 4: Instal PHP 8.3 dan Ekstensi Lengkap](#6-langkah-4-instal-php-83-dan-ekstensi-lengkap)
   - [Pilihan A: Untuk Ubuntu 24.04 dan 26.04 LTS](#pilihan-a-untuk-ubuntu-2404-dan-2604-lts)
   - [Pilihan B: Untuk Debian 13 Trixie](#pilihan-b-untuk-debian-13-trixie)
7. [Langkah 5: Instal Database MariaDB dan Buat Database](#7-langkah-5-instal-database-mariadb-dan-buat-database)
8. [Langkah 6: Instal Composer v2 dan Node.js LTS](#8-langkah-6-instal-composer-v2-dan-nodejs-lts)
9. [Langkah 7: Clone Source Code Proyek](#9-langkah-7-clone-source-code-proyek)
10. [Langkah 8: Setup File Konfigurasi .env](#10-langkah-8-setup-file-konfigurasi-env)
11. [Langkah 9: Install Dependensi PHP, Build Aset Frontend dan Storage Link](#11-langkah-9-install-dependensi-php-build-aset-frontend-dan-storage-link)
12. [Langkah 10: Jalankan Migrasi Database dan Sinkronisasi Master ODP](#12-langkah-10-jalankan-migrasi-database-dan-sinkronisasi-master-odp)
13. [Langkah 11: Pengaturan Hak Akses Permissions dan Ownership](#13-langkah-11-pengaturan-hak-akses-permissions-dan-ownership)
14. [Langkah 12: Konfigurasi Virtual Host Nginx](#14-langkah-12-konfigurasi-virtual-host-nginx)
15. [Langkah 13: Pasang SSL Gratis HTTPS dengan Certbot](#15-langkah-13-pasang-ssl-gratis-https-dengan-certbot)
16. [Langkah 14: Setup Cron Scheduler dan Background Worker Supervisor](#16-langkah-14-setup-cron-scheduler-dan-background-worker-supervisor)
17. [Langkah 15: Optimasi Cache Produksi](#17-langkah-15-optimasi-cache-produksi)
18. [Panduan Troubleshooting untuk Pemula](#18-panduan-troubleshooting-untuk-pemula)
19. [Prosedur Rutin: Cara Update Aplikasi di Kemudian Hari](#19-prosedur-rutin-cara-update-aplikasi-di-kemudian-hari)

---

## 1. Konsep Dasar dan Gambaran Arsitektur

Sebagai junior developer, sebelum menjalankan perintah satu per satu di terminal, pahami alur kerja aplikasi web ini di server:

```text
[ Browser Pengguna ]
       │
       ▼ (Port 80 HTTP / 443 HTTPS)
  [ Nginx Web Server ]  ──(File Statis: CSS, JS, Gambar)──> folder /public
       │
       ▼ (FastCGI Socket)
  [ PHP 8.3-FPM ]  ───────> Menjalankan kode Laravel (Controller, Model, Blade)
       │
       ├───────────────────> [ MariaDB Database ] (Menyimpan tiket, ODP, pelanggan)
       └───────────────────> [ Supervisor Worker ] (Menjalankan job antrean di background)
```

- **Nginx:** Menjadi satpam di pintu depan. Menerima request dari browser, menyajikan gambar/CSS langsung, dan meneruskan logika PHP ke PHP-FPM.
- **PHP-FPM:** Mesin yang mengeksekusi script PHP dan framework Laravel.
- **MariaDB:** Database tempat data tersimpan.
- **Supervisor:** Menjaga worker background agar tetap hidup 24/7 (misal sinkronisasi otomatis dan pengiriman notifikasi).

---

## 2. Persiapan Awal Sebelum Masuk Server

Sebelum membuka terminal, pastikan kamu sudah menyiapkan:

1. **IP Publik VPS/Server:** Contoh: `103.180.120.45`
2. **Username & Password / SSH Key Server:** Biasanya user `root` atau `ubuntu`/`debian`.
3. **Domain / Subdomain:** Contoh: `tiket.perusahaan.com`
4. **Setting DNS (A Record):**
   - Masuk ke dashboard DNS penyedia domain kamu (Cloudflare, Niagahoster, Domainesia, dsb.).
   - Buat **A Record**:
     - **Name / Host:** `tiket` (atau `@` jika domain utama)
     - **Target / Value:** Masukkan IP Publik VPS kamu (`103.180.120.45`)
     - **TTL:** Auto atau 300 detik
     - **Proxy Status:** *DNS Only* (abu-abu jika menggunakan Cloudflare saat pasang SSL awal).

---

## 3. Langkah 1: Masuk Server dan Update Sistem

Buka terminal di komputer kamu (PowerShell di Windows, Terminal di macOS/Linux), lalu ketik:

```bash
ssh root@IP_SERVER_KAMU
```

*(Ganti `IP_SERVER_KAMU` dengan IP asli VPS).*

Setelah berhasil masuk, perbarui index paket bawaan Linux agar mendapatkan versi keamanan paling baru:

```bash
sudo apt update && sudo apt upgrade -y
```

Install juga beberapa tools dasar yang akan sering kita pakai:

```bash
sudo apt install -y curl wget git unzip zip software-properties-common ca-certificates lsb-release gnupg htop
```

---

## 4. Langkah 2: Amankan Server dengan Firewall UFW

Firewall berfungsi menutup semua pintu port server kecuali yang diizinkan, agar VPS tidak mudah diserang bot internet.

Jalankan perintah berikut:

```bash
# Izinkan koneksi SSH (PENTING! Jangan sampai terputus)
sudo ufw allow OpenSSH

# Izinkan akses Web (HTTP port 80 dan HTTPS port 443)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Aktifkan firewall
sudo ufw --force enable

# Periksa status firewall
sudo ufw status
```

> **Penjelasan:** Terminal akan menampilkan status `active` dengan port 22 (SSH), 80, dan 443 berstatus `ALLOW`.

---

## 5. Langkah 3: Instal Web Server Nginx

```bash
# Install Nginx
sudo apt install -y nginx

# Pastikan Nginx otomatis menyala saat server restart
sudo systemctl enable nginx
sudo systemctl start nginx

# Cek status Nginx (harus berstatus "active (running)")
sudo systemctl status nginx --no-pager
```

> **Uji Coba Cepat:** Buka browser dan ketik alamat IP server kamu di address bar (`http://IP_SERVER_KAMU`). Kamu harus melihat halaman *"Welcome to nginx!"*.

---

## 6. Langkah 4: Instal PHP 8.3 dan Ekstensi Lengkap

Central-Ticketing membutuhkan **PHP versi 8.3** beserta modul-modul wajib untuk mengolah PDF, Excel, gambar tiang ODP, dan koneksi database.

Pilih instruksi sesuai sistem operasi server kamu:

### Pilihan A: Untuk Ubuntu 24.04 dan 26.04 LTS

Di Ubuntu, gunakan repository PPA Ondrej Surý:

```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### Pilihan B: Untuk Debian 13 Trixie

Debian 13 menggunakan standar keamanan modern (*keyring terisolasi*). Jalankan blok perintah ini:

```bash
sudo apt install -y apt-transport-https ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -sSLo /etc/apt/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
sudo sh -c 'echo "deb [signed-by=/etc/apt/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ trixie main" > /etc/apt/sources.list.d/php.list'
sudo apt update
```

### Pasang Paket PHP 8.3 dan Seluruh Ekstensi yang Dibutuhkan

Jalankan perintah ini (berlaku untuk Ubuntu maupun Debian):

```bash
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-mbstring php8.3-xml php8.3-zip \
    php8.3-curl php8.3-gd php8.3-bcmath php8.3-intl \
    php8.3-tokenizer php8.3-sqlite3
```

Verifikasi versi PHP:

```bash
php -v
```

*(Pastikan muncul tulisan PHP 8.3.x).*

Pastikan service PHP-FPM aktif:

```bash
sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm
sudo systemctl status php8.3-fpm --no-pager
```

---

## 7. Langkah 5: Instal Database MariaDB dan Buat Database

Central-Ticketing menggunakan MariaDB sebagai database utama terpusat.

```bash
# 1. Install MariaDB Server
sudo apt install -y mariadb-server

# 2. Aktifkan service MariaDB
sudo systemctl enable mariadb
sudo systemctl start mariadb
```

### Buat Database dan Akun Pengguna untuk Laravel

Masuk ke console MariaDB sebagai root:

```bash
sudo mysql
```

Ketik perintah SQL berikut di dalam prompt MariaDB (akhiri setiap baris dengan titik koma `;`):

```sql
-- 1. Buat database baru bernama central_ticket
CREATE DATABASE central_ticket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Buat user baru (Ganti 'PasswordSuperAman123!' dengan password pilihanmu!)
CREATE USER 'ticket_user'@'localhost' IDENTIFIED BY 'PasswordSuperAman123!';

-- 3. Berikan hak akses penuh atas database central_ticket ke user tersebut
GRANT ALL PRIVILEGES ON central_ticket.* TO 'ticket_user'@'localhost';

-- 4. Terapkan perubahan hak akses
FLUSH PRIVILEGES;

-- 5. Keluar dari MariaDB
EXIT;
```

> **Catatan Penting Pemula:** Catat nama database (`central_ticket`), user (`ticket_user`), dan password yang baru saja kamu buat. Tiga data ini akan kita masukkan ke file `.env` di Langkah 8.

---

## 8. Langkah 6: Instal Composer v2 dan Node.js LTS

- **Composer:** Mengunduh package PHP (Laravel, DomPDF, PHPSpreadsheet).
- **Node.js & NPM:** Mengkompilasi asset frontend (Tailwind CSS, Vite, JavaScript).

### 1. Install Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

*(Pastikan menampilkan Composer versi 2.x).*

### 2. Install Node.js v22 (LTS Terbaru) via NodeSource

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## 9. Langkah 7: Clone Source Code Proyek

Lokasi standar penyimpanan aplikasi web di Linux adalah di folder `/var/www/`.

```bash
# Berikan hak akses sementara ke user kamu untuk folder /var/www
sudo mkdir -p /var/www/central-ticketing
sudo chown -R $USER:$USER /var/www/central-ticketing

# Masuk ke direktori web
cd /var/www

# Clone repositori git kamu (gunakan link repo GitHub kamu)
git clone https://github.com/USERNAME_KAMU/NAMA_REPO.git central-ticketing

# Masuk ke dalam direktori proyek
cd /var/www/central-ticketing
```

> **Jika Repo Private di GitHub:**
> Buat Personal Access Token (PAT) di GitHub: *Settings -> Developer Settings -> Personal access tokens (classic)* dengan hak akses `repo`. Lalu clone dengan:
> `git clone https://TOKEN_KAMU@github.com/USERNAME_KAMU/NAMA_REPO.git central-ticketing`

---

## 10. Langkah 8: Setup File Konfigurasi .env

Di Laravel, semua pengaturan server, password database, dan domain disimpan di file `.env`.

Salin template file `.env.example`:

```bash
cp .env.example .env
```

Buka file `.env` menggunakan teks editor `nano`:

```bash
nano .env
```

Ubah beberapa baris kunci berikut:

```ini
APP_NAME="Central Ticketing"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://tiket.domainanda.com
APP_TIMEZONE=Asia/Jakarta

CORS_ALLOWED_ORIGINS=https://tiket.domainanda.com

# PENGATURAN DATABASE
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=central_ticket
DB_USERNAME=ticket_user
DB_PASSWORD=PasswordSuperAman123!

# DRIVER SESSION, QUEUE & CACHE
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
QUEUE_CONNECTION=database
CACHE_STORE=database
```

> **Cara Simpan di Nano:**
>
> 1. Tekan tombol `Ctrl + O` lalu tekan `Enter` untuk menyimpan.
> 2. Tekan tombol `Ctrl + X` untuk keluar dari editor.

---

## 11. Langkah 9: Install Dependensi PHP, Build Aset Frontend dan Storage Link

Pastikan posisi terminal masih berada di `/var/www/central-ticketing`:

```bash
# 1. Install vendor dependensi PHP untuk lingkungan production (tanpa dev package)
composer install --no-dev --optimize-autoloader

# 2. Buat App Encryption Key (Wajib!)
php artisan key:generate --force

# 3. Buat symlink untuk file public storage (foto ODP, bukti tiket, avatar)
php artisan storage:link

# 4. Install dependensi Node.js dan compile file CSS & JS menggunakan Vite
npm install --ignore-scripts
npm run build
```

> **Penjelasan:** `npm run build` akan menghasilkan file statis yang di-minify di folder `public/build/`. File ini yang membuat tampilan aplikasi rapi dan sangat cepat saat diakses browser.

---

## 12. Langkah 10: Jalankan Migrasi Database dan Sinkronisasi Master ODP

Sekarang kita akan mengisi struktur tabel ke database MariaDB yang tadi dibuat:

```bash
# 1. Jalankan migration struktur tabel Laravel
php artisan migrate --force

# 2. (Opsional) Jika kamu memiliki data seeder awal (admin default)
php artisan db:seed --force

# 3. Jalankan sinkronisasi master ODP canonical pertama kali
php artisan odp:sync
```

> **Hasil:** Database MariaDB kamu sekarang sudah memiliki tabel-tabel lengkap (`tickets`, `customers`, `odps`, `billing_instances`, dsb.) dan master ODP canonical tanpa duplikasi!
>
> 📖 **Panduan Mendalam:** Untuk memahami cara kerja rollback, backup otomatis, dan penanganan error migrasi, baca file [TUTORIAL_MIGRASI_DATABASE.md](file:///f:/PROJECT-TEGAR/central-ticketing/TUTORIAL_MIGRASI_DATABASE.md).

---

## 13. Langkah 11: Pengaturan Hak Akses Permissions dan Ownership

Di sistem Linux, web server berjalan dengan user bernama `www-data`. Jika folder `storage` atau `bootstrap/cache` tidak bisa ditulis oleh `www-data`, aplikasi akan melempar pesan *"Error 500: Permission Denied"*.

Jalankan perintah berikut untuk mengaturnya dengan benar:

```bash
# Berikan kepemilikan file ke user kamu dan grup web server www-data
sudo chown -R $USER:www-data /var/www/central-ticketing

# Berikan hak tulis khusus untuk folder penyimpanan file dan cache
sudo chmod -R 775 /var/www/central-ticketing/storage
sudo chmod -R 775 /var/www/central-ticketing/bootstrap/cache

# Pastikan folder upload storage dapat ditulis penuh oleh www-data
sudo chown -R www-data:www-data /var/www/central-ticketing/storage
sudo chown -R www-data:www-data /var/www/central-ticketing/bootstrap/cache
```

---

## 14. Langkah 12: Konfigurasi Virtual Host Nginx

Sekarang kita buat file konfigurasi di Nginx agar Nginx tahu domain kamu harus diarahkan ke folder `/var/www/central-ticketing/public`.

Buat file konfigurasi baru:

```bash
sudo nano /etc/nginx/sites-available/central-ticketing.conf
```

Tempelkan (*paste*) konfigurasi berikut (ganti `tiket.domainanda.com` dengan domain asli kamu):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tiket.domainanda.com;
    root /var/www/central-ticketing/public;

    # Pengaturan Keamanan Header
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php index.html;
    charset utf-8;

    # Maksimal ukuran upload file (foto ODP, lampiran tiket hingga 20MB)
    client_max_body_size 20M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # Proses Eksekusi PHP melalui PHP 8.3 FPM Socket
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Blokir akses ke file sensitif (.env, .git, .htaccess)
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Simpan file (`Ctrl + O`, lalu `Enter`, lalu `Ctrl + X`).

### Aktifkan Konfigurasi Nginx

```bash
# 1. Buat symbolic link ke sites-enabled
sudo ln -s /etc/nginx/sites-available/central-ticketing.conf /etc/nginx/sites-enabled/

# 2. Hapus default configuration bawaan Nginx (jika belum pernah dihapus)
sudo rm -f /etc/nginx/sites-enabled/default

# 3. Uji apakah sintaks Nginx valid
sudo nginx -t
```

*(Pastikan muncul pesan: `syntax is ok` dan `test is successful`).*

Muat ulang Nginx:

```bash
sudo systemctl reload nginx
```

---

## 15. Langkah 13: Pasang SSL Gratis HTTPS dengan Certbot

Sertifikat SSL menjamin koneksi terenkripsi (`https://`) dan gembok hijau di browser.

```bash
# Install Certbot dan plugin Nginx
sudo apt install -y certbot python3-certbot-nginx

# Request dan pasang sertifikat SSL secara otomatis
sudo certbot --nginx -d tiket.domainanda.com
```

> **Interaksi Certbot di Terminal:**
>
> 1. Masukkan alamat email aktif kamu (untuk notifikasi perpanjangan SSL).
> 2. Tekan `Y` untuk menyetujui Terms of Service.
> 3. Certbot akan memverifikasi domain dan langsung memodifikasi konfigurasi Nginx menjadi HTTPS otomatis!

Uji auto-renewal sertifikat SSL:

```bash
sudo certbot renew --dry-run
```

*(Certbot otomatis memperpanjang SSL setiap 3 bulan sekali).*

---

## 16. Langkah 14: Setup Cron Scheduler dan Background Worker Supervisor

Aplikasi Central-Ticketing memiliki tugas otomatis, seperti:

- Pengecekan status tiket berkala
- Sinkronisasi data pelanggan dari berbagai node billing
- Background task queue

### 1. Setup Laravel Cron Scheduler

Buka crontab sistem:

```bash
sudo crontab -e
```

*(Jika diminta memilih editor, tekan `1` untuk memilih nano).*

Tambahkan satu baris berikut di baris paling bawah:

```cron
* * * * * cd /var/www/central-ticketing && php artisan schedule:run >> /dev/null 2>&1
```

Simpan (`Ctrl + O`, `Enter`, `Ctrl + X`). Cron ini akan mengeksekusi scheduler Laravel setiap 1 menit.

### 2. Setup Background Queue Worker dengan Supervisor

Supervisor memastikan proses antrean Laravel (`queue:work`) terus berjalan di latar belakang dan otomatis menyala kembali jika terjadi crash.

Install Supervisor:

```bash
sudo apt install -y supervisor
```

Buat file konfigurasi worker baru:

```bash
sudo nano /etc/supervisor/conf.d/central-ticketing-worker.conf
```

Isi dengan konfigurasi berikut:

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

Simpan file, lalu terapkan konfigurasi ke Supervisor:

```bash
# Muat ulang konfigurasi Supervisor
sudo supervisorctl reread
sudo supervisorctl update

# Jalankan worker
sudo supervisorctl start central-ticketing-worker:*

# Cek status (harus menampilkan status RUNNING)
sudo supervisorctl status
```

---

## 17. Langkah 15: Optimasi Cache Produksi

Laravel memiliki fitur kompilasi cache untuk mempercepat waktu response hingga 5-10 kali lipat di lingkungan produksi:

```bash
cd /var/www/central-ticketing

# Bersihkan cache lama
php artisan optimize:clear

# Kompilasi cache config, routes, views, dan event
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> **PENTING UNTUK JUNIOR:** Jika kamu mengubah file `.env`, kamu **wajib** menjalankan `php artisan config:cache` kembali agar perubahannya dibaca oleh Laravel!

---

## 18. Panduan Troubleshooting untuk Pemula

Jika saat membuka website kamu mengalami kendala, berikut adalah daftar kendala yang paling sering ditemui beserta solusinya:

### Kasus 1: Error 502 Bad Gateway

- **Penyebab:** Nginx tidak dapat berkomunikasi dengan PHP-FPM socket.
- **Cara Cek:**

```bash
sudo systemctl status php8.3-fpm
```

- **Solusi:**
  - Jika PHP-FPM *inactive* (mati), nyalakan kembali:

```bash
sudo systemctl restart php8.3-fpm
```

- Periksa apakah nama socket di file Nginx (`/run/php/php8.3-fpm.sock`) cocok dengan file yang ada di server:

```bash
ls -la /run/php/
```

### Kasus 2: Error 500 Internal Server Error

- **Penyebab:** Masalah hak akses (*permission*) atau error fatal pada kodingan/database.
- **Langkah Deteksi:** Buka file log Laravel untuk membaca pesan error pastinya:

```bash
tail -n 50 /var/www/central-ticketing/storage/logs/laravel.log
```

- **Solusi Cepat Umum:**
  - Perbaiki hak akses storage:

```bash
sudo chown -R www-data:www-data /var/www/central-ticketing/storage /var/www/central-ticketing/bootstrap/cache
sudo chmod -R 775 /var/www/central-ticketing/storage /var/www/central-ticketing/bootstrap/cache
```

- Pastikan file `.env` sudah memiliki `APP_KEY`:

```bash
php artisan key:generate --force
```

### Kasus 3: Error 403 Forbidden

- **Penyebab:** Nginx membaca direktori yang salah atau tidak ada file `index.php`.
- **Solusi:** Periksa baris `root` di konfigurasi Nginx `/etc/nginx/sites-available/central-ticketing.conf`. Pastikan berakhiran `/public`:

```nginx
root /var/www/central-ticketing/public;
```

### Kasus 4: Tampilan CSS / JS Rusak

- **Penyebab:** File build Vite belum dikompilasi atau file aset tidak ditemukan.
- **Solusi:**
  - Jalankan build ulang aset di folder proyek:

```bash
cd /var/www/central-ticketing
npm run build
```

- Pastikan `APP_URL` di file `.env` sudah menggunakan domain dan protokol yang benar (`https://tiket.domainanda.com`).
- Clear cache view:

```bash
php artisan view:clear
```

### Kasus 5: Error Database Access Denied

- **Penyebab:** Password atau username database di file `.env` tidak cocok dengan yang dibuat di MariaDB.
- **Solusi:**
  - Masuk ke MariaDB dan set ulang password user:

```bash
sudo mysql -u root -e "ALTER USER 'ticket_user'@'localhost' IDENTIFIED BY 'PasswordBaru123!'; FLUSH PRIVILEGES;"
```

- Buka `.env` dan sesuaikan nilai `DB_PASSWORD=PasswordBaru123!`.
- Jalankan `php artisan config:cache`.

---

## 19. Prosedur Rutin: Cara Update Aplikasi di Kemudian Hari

Ketika kamu selesai menambahkan fitur baru atau memperbaiki bug di laptop/komputer lokal dan sudah push ke GitHub, begini cara update aplikasinya di server produksi:

```bash
# 1. Masuk ke direktori aplikasi
cd /var/www/central-ticketing

# 2. Nyalakan mode maintenance (opsional, jika update besar)
php artisan down

# 3. Tarik kode terbaru dari GitHub
git pull origin main

# 4. Install dependensi baru (jika ada tambahan library)
composer install --no-dev --optimize-autoloader
npm install --ignore-scripts
npm run build

# 5. Jalankan migrasi database jika ada tabel baru
php artisan migrate --force

# 6. Bersihkan dan kompilasi ulang cache
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Restart background queue worker agar membaca kode baru
sudo supervisorctl restart central-ticketing-worker:*

# 8. Matikan mode maintenance (website kembali online)
php artisan up
```

---

## Selesai: Aplikasi Central-Ticketing Siap Digunakan

Website Central Ticketing Anda kini telah aktif dengan standar keamanan enterprise, performa teroptimasi, SSL HTTPS terpasang, background worker aktif, dan siap melayani integrasi hingga puluhan billing instance.
