# Panduan Lengkap Deploy Central-Ticketing ke Ubuntu 24.04 LTS & Debian 12

Panduan teknis langkah demi langkah (*step-by-step production deployment guide*) untuk aplikasi **MANAGEMENT TICKET (Central Ticket System)** pada server berbasis **Ubuntu 24.04 LTS (Noble Numbat)** atau **Debian 12 (Bookworm)**.

---

## 📋 Daftar Isi
1. [Pra-Syarat & Spesifikasi Server](#1-pra-syarat--spesifikasi-server)
2. [Langkah 0: Komit & Push Kode dari Komputer Lokal](#2-langkah-0-komit--push-kode-dari-komputer-lokal)
3. [Langkah 1: Update Server & Instalasi Paket Dasar](#3-langkah-1-update-server--instalasi-paket-dasar)
4. [Langkah 2: Instalasi PHP 8.3-FPM & Ekstensi](#4-langkah-2-instalasi-php-83-fpm--ekstensi)
5. [Langkah 3: Instalasi & Konfigurasi Database MariaDB](#5-langkah-3-instalasi--konfigurasi-database-mariadb)
6. [Langkah 4: Instalasi Composer v2 & Node.js 20 LTS](#6-langkah-4-instalasi-composer-v2--nodejs-20-lts)
7. [Langkah 5: Clone Repositori ke /var/www](#7-langkah-5-clone-repositori-ke-varwww)
8. [Langkah 6: Konfigurasi Environment Produksi (.env)](#8-langkah-6-konfigurasi-environment-produksi-env)
9. [Langkah 7: Install Dependensi, Generate Key, Migration & Build](#9-langkah-7-install-dependensi-generate-key-migration--build)
10. [Langkah 8: Pengaturan Hak Akses (Permissions & Ownership)](#10-langkah-8-pengaturan-hak-akses-permissions--ownership)
11. [Langkah 9: Konfigurasi Web Server Nginx](#11-langkah-9-konfigurasi-web-server-nginx)
12. [Langkah 10: Pasang SSL Gratis (HTTPS Let's Encrypt)](#12-langkah-10-pasang-ssl-gratis-https-lets-encrypt)
13. [Langkah 11: Konfigurasi Otomatisasi (Cron Scheduler & Supervisor Queue)](#13-langkah-11-konfigurasi-otomatisasi-cron-scheduler--supervisor-queue)
14. [Langkah 12: Kompilasi & Optimasi Cache Laravel](#14-langkah-12-kompilasi--optimasi-cache-laravel)
15. [Langkah 13: Verifikasi Sinkronisasi Billing CI3](#15-langkah-13-verifikasi-sinkronisasi-billing-ci3)
16. [Langkah 14: Checklist Keamanan Pasca Deploy](#16-langkah-14-checklist-keamanan-pasca-deploy)
17. [Langkah 15: Prosedur Pembaruan / Update Aplikasi](#17-langkah-15-prosedur-pembaruan--update-aplikasi)

---

## 1. Pra-Syarat & Spesifikasi Server

| Komponen | Spesifikasi Rekomendasi | Keterangan |
| :--- | :--- | :--- |
| **Sistem Operasi** | Ubuntu 24.04 LTS atau Debian 12 | 64-bit Architecture |
| **CPU** | 2 vCPU atau lebih | Untuk menangani antrean tiket & query agregasi |
| **RAM** | 2 GB - 4 GB (dengan Swap 2 GB) | Minimal 1 GB jika trafik rendah |
| **Penyimpanan** | 25 GB+ SSD / NVMe | Cukup untuk database dan log |
| **Akses Server** | User dengan hak `sudo` | Disarankan non-root login via SSH Key |
| **Domain** | `tiket.domainanda.com` | DNS A Record sudah mengarah ke IP Server |

---

## 2. Langkah 0: Komit & Push Kode dari Komputer Lokal

Sebelum melakukan clone di server, pastikan seluruh file aset baru (`public/css/`, `public/js/`, dan model) sudah dikomit dan dipush ke repositori GitHub:

```powershell
# Jalankan di terminal komputer lokal proyek central-ticketing:
git status
git add .
git commit -m "feat: complete production readiness, modular CSS, and security hardening"
git push origin main
```

---

## 3. Langkah 1: Update Server & Instalasi Paket Dasar

Login ke server via SSH:
```bash
ssh user@IP_SERVER_ANDA
```

Jalankan update sistem dan install utilitas dasar:
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip zip software-properties-common ca-certificates lsb-release gnupg ufw supervisor htop
```

Aktifkan firewall dasar UFW:
```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
sudo ufw status
```

---

## 4. Langkah 2: Instalasi PHP 8.3-FPM & Ekstensi

Laravel pada sistem ini membutuhkan **PHP 8.3**.

### A. Untuk Ubuntu 24.04 LTS:
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### B. Untuk Debian 12 (Bookworm):
```bash
sudo apt install -y apt-transport-https
sudo curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
sudo sh -c 'echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
sudo apt update
```

### C. Install PHP 8.3-FPM dan Seluruh Ekstensi yang Dibutuhkan:
```bash
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-mbstring php8.3-xml php8.3-zip \
    php8.3-curl php8.3-gd php8.3-bcmath php8.3-intl \
    php8.3-tokenizer php8.3-sqlite3
```

Verifikasi versi dan status PHP-FPM:
```bash
php -v
sudo systemctl status php8.3-fpm --no-pager
```

---

## 5. Langkah 3: Instalasi & Konfigurasi Database MariaDB

Instal database server MariaDB:
```bash
sudo apt install -y mariadb-server mariadb-client
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

Amankan instalasi database:
```bash
sudo mysql_secure_installation
```
*(Atur password root MariaDB, hapus anonymous users, disable remote root login, dan hapus test database)*.

### Buat Database & User Khusus Aplikasi:
Masuk ke MariaDB console:
```bash
sudo mysql -u root -p
```

Eksekusi perintah SQL berikut (ganti `PasswordKuatAnda_123!` dengan password yang aman):
```sql
CREATE DATABASE central_ticketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ticket_user'@'localhost' IDENTIFIED BY 'PasswordKuatAnda_123!';
GRANT ALL PRIVILEGES ON central_ticketing.* TO 'ticket_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 6. Langkah 4: Instalasi Composer v2 & Node.js 20 LTS

### A. Composer v2:
```bash
cd ~
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
```

### B. Node.js 20 LTS (NodeSource):
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## 7. Langkah 5: Clone Repositori ke /var/www

Tempatkan proyek pada direktori standar web server:
```bash
cd /var/www
sudo git clone https://github.com/tegar78/central-ticketing.git
cd /var/www/central-ticketing
```

Berikan hak akses ke user aktif Anda sementara waktu:
```bash
sudo chown -R $USER:$USER /var/www/central-ticketing
```

---

## 8. Langkah 6: Konfigurasi Environment Produksi (.env)

Salin template konfigurasi:
```bash
cp .env.example .env
nano .env
```

Sesuaikan parameter-parameter kunci berikut pada `.env`:
```env
APP_NAME="MANAGEMENT TICKET"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tiket.domainanda.com

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=central_ticketing
DB_USERNAME=ticket_user
DB_PASSWORD=PasswordKuatAnda_123!

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Fallback CI3 Billing DB (jika instance menggunakan koneksi lokal)
BILLING_CI3_DB_HOST=127.0.0.1
BILLING_CI3_DB_PORT=3306
BILLING_CI3_DB_DATABASE=bill3-gyh
BILLING_CI3_DB_USERNAME=root
BILLING_CI3_DB_PASSWORD=
```
Simpan file (`Ctrl + O`, `Enter`, lalu `Ctrl + X`).

---

## 9. Langkah 7: Install Dependensi, Generate Key, Migration & Build

Jalankan rangkaian perintah berikut di dalam direktori `/var/www/central-ticketing`:

```bash
# 1. Install dependensi PHP produksi (tanpa dev package)
composer install --no-dev --optimize-autoloader

# 2. Generate Application Key enkripsi
php artisan key:generate --force

# 3. Jalankan migrasi database beserta data awal
php artisan migrate --seed --force

# 4. Install dependensi frontend & kompilasi aset produksi Vite
npm install
npm run build
```

---

## 10. Langkah 8: Pengaturan Hak Akses (Permissions & Ownership)

Nginx dan PHP-FPM berjalan di bawah user `www-data`. Terapkan hak kepemilikan dan permission direktori:

```bash
# Set ownership ke www-data
sudo chown -R www-data:www-data /var/www/central-ticketing

# Izin standar direktori dan file
sudo find /var/www/central-ticketing -type f -exec chmod 644 {} \;
sudo find /var/www/central-ticketing -type d -exec chmod 755 {} \;

# Berikan hak tulis khusus untuk storage dan cache
sudo chmod -R 775 /var/www/central-ticketing/storage
sudo chmod -R 775 /var/www/central-ticketing/bootstrap/cache
```

---

## 11. Langkah 9: Konfigurasi Web Server Nginx

Instal Nginx:
```bash
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

Buat konfigurasi virtual host:
```bash
sudo nano /etc/nginx/sites-available/central-ticketing
```

Salin konfigurasi Nginx berkinerja tinggi berikut:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tiket.domainanda.com; # Ganti dengan domain Anda
    root /var/www/central-ticketing/public;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    index index.php index.html;
    charset utf-8;

    # Batas ukuran upload dokumen lampiran
    client_max_body_size 25M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_read_timeout 300;
    }

    # Blokir akses ke file sensitif (.env, .git, .htaccess)
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Caching aset statis (CSS, JS, Fonts, Gambar)
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/x-javascript application/xml application/json;
    gzip_disable "MSIE [1-6]\.";
}
```

Aktifkan konfigurasi:
```bash
sudo ln -s /etc/nginx/sites-available/central-ticketing /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## 12. Langkah 10: Pasang SSL Gratis (HTTPS Let's Encrypt)

Gunakan **Certbot** untuk otomatisasi sertifikat SSL:
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d tiket.domainanda.com
```
*(Pilih opsi untuk otomatis me-redirect seluruh trafik HTTP ke HTTPS)*.

---

## 13. Langkah 11: Konfigurasi Otomatisasi (Cron Scheduler & Supervisor Queue)

### A. Crontab (Laravel Scheduler):
```bash
sudo crontab -u www-data -e
```
Tambahkan baris berikut di baris paling bawah:
```cron
* * * * * cd /var/www/central-ticketing && php artisan schedule:run >> /dev/null 2>&1
```

### B. Supervisor Queue Worker:
Buat file konfigurasi worker:
```bash
sudo nano /etc/supervisor/conf.d/central-ticketing-worker.conf
```
Isi konfigurasi:
```ini
[program:central-ticketing-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/central-ticketing/artisan queue:work --sleep=3 --tries=3 --max-time=3600
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

Aktifkan Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start central-ticketing-worker:*
```

---

## 14. Langkah 12: Kompilasi & Optimasi Cache Laravel

Kompilasi route, konfigurasi, dan view Blade ke dalam cache produksi untuk performa maksimal:

```bash
cd /var/www/central-ticketing
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

---

## 15. Langkah 13: Verifikasi Sinkronisasi Billing CI3

Uji sinkronisasi pelanggan dari server billing Gayuh (CodeIgniter 3):

```bash
cd /var/www/central-ticketing
# Sinkronisasi instance default (BILL-001)
sudo -u www-data php artisan sync:billing-customers BILL-001

# Atau sinkronisasi seluruh 60 instance aktif sekaligus
sudo -u www-data php artisan sync:billing-customers --all
```

---

## 16. Langkah 14: Checklist Keamanan Pasca Deploy

- [ ] **Ganti Password Default Akun Admin Segera!**
  Akun bawaan seeder: `admin@central.local` / `password`.
  Ganti langsung melalui CLI:
  ```bash
  cd /var/www/central-ticketing
  sudo -u www-data php artisan tinker --execute="\$u = App\Models\User::where('email', 'admin@central.local')->first(); \$u->password = bcrypt('PasswordBaruAdminYangSangatKuat_2026!'); \$u->save(); echo 'Password Admin Sukses Diperbarui!';"
  ```
- [ ] Pastikan `APP_DEBUG=false` pada file `.env`.
- [ ] Pastikan `APP_ENV=production` pada file `.env`.
- [ ] Uji akses ke halaman `/maps` dan pastikan tidak ada `api_key` atau password DB yang bocor di DOM HTML.
- [ ] Uji export laporan PDF tiket di `/tickets/export/pdf`.

---

## 17. Langkah 15: Prosedur Pembaruan / Update Aplikasi

Setiap kali melakukan update kode di GitHub, jalankan langkah berikut di server:

```bash
cd /var/www/central-ticketing

# 1. Masuk mode maintenance
sudo -u www-data php artisan down

# 2. Tarik kode terbaru
git pull origin main

# 3. Update dependensi
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 4. Migrasi skema database baru
sudo -u www-data php artisan migrate --force

# 5. Segarkan cache
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 6. Restart antrean worker
sudo supervisorctl restart central-ticketing-worker:*

# 7. Aktifkan kembali aplikasi
sudo -u www-data php artisan up
```
