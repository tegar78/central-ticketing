# Panduan Deploy Central-Ticketing ke Debian 13 (Trixie) & Ubuntu 26.04 LTS

Panduan deployment produksi mutakhir (*next-generation server setup*) untuk **MANAGEMENT TICKET (Central Ticket System)** yang disesuaikan secara spesifik dengan arsitektur sistem operasi **Debian 13 (Trixie)** dan **Ubuntu 26.04 LTS**.

---

## 📌 Karakteristik Utama Debian 13 & Ubuntu 26
1. **Format Repositori Modern (deb822 & keyrings):** Tidak lagi menggunakan `apt-key` lama, melainkan `/etc/apt/keyrings/` yang terenkripsi dan format file `.sources` atau `.list` berbasis keyring terisolasi.
2. **PHP 8.3 / 8.4 Dukungan Penuh:** Paket bawaan dan PPA Sury / Ondrej teroptimasi dengan compiler terbaru.
3. **Database Modern:** MariaDB 11.x atau MySQL 8.4 LTS.
4. **Keamanan Ekstra:** Kebijakan isolasi modul Python (`PEP 668 EXTERNALLY-MANAGED`), OpenSSL 3.x, dan perlindungan systemd cgroup v2.

---

## 📋 Daftar Isi
1. [Spesifikasi & Persiapan Server](#1-spesifikasi--persiapan-server)
2. [Update Sistem & Pengaturan Firewall UFW](#2-update-sistem--pengaturan-firewall-ufw)
3. [Instalasi PHP 8.3-FPM di Debian 13 & Ubuntu 26](#3-instalasi-php-83-fpm-di-debian-13--ubuntu-26)
4. [Instalasi & Pengerasan Database MariaDB 11](#4-instalasi--pengerasan-database-mariadb-11)
5. [Instalasi Composer v2 & Node.js LTS](#5-instalasi-composer-v2--nodejs-lts)
6. [Clone Repositori & Setup Direktori Web](#6-clone-repositori--setup-direktori-web)
7. [Konfigurasi Environment Produksi (.env)](#7-konfigurasi-environment-produksi-env)
8. [Build Aset, Migrasi Skema & Seeding](#8-build-aset-migrasi-skema--seeding)
9. [Hak Akses Direktori (Permissions www-data)](#9-hak-akses-direktori-permissions-www-data)
10. [Konfigurasi Nginx Berkinerja Tinggi (HTTP/2 & TLS 1.3)](#10-konfigurasi-nginx-berkinerja-tinggi-http2--tls-13)
11. [Sertifikat SSL Let's Encrypt (Certbot Standar Baru)](#11-sertifikat-ssl-lets-encrypt-certbot-standar-baru)
12. [Automasi: Laravel Cron & Supervisor Queue Worker](#12-automasi-laravel-cron--supervisor-queue-worker)
13. [Kompilasi Cache Produksi](#13-kompilasi-cache-produksi)
14. [Checklist Verifikasi & Prosedur Maintenance](#14-checklist-verifikasi--prosedur-maintenance)

---

## 1. Spesifikasi & Persiapan Server

| Parameter | Rekomendasi | Keterangan |
| :--- | :--- | :--- |
| **OS Target** | Debian 13 (Trixie) atau Ubuntu 26.04 LTS | 64-bit (x86_64 / amd64 atau aarch64) |
| **CPU** | 2 Core vCPU | Minimal 1 vCPU |
| **RAM** | 2 GB – 4 GB | Ditambah Swap 2 GB jika RAM 2 GB |
| **Disk** | 25 GB+ SSD / NVMe | Cukup untuk OS, packages, database, dan log |
| **Domain** | `tiket.domainanda.com` | A Record DNS sudah diarahkan ke IP Publik server |

---

## 2. Update Sistem & Pengaturan Firewall UFW

Login ke VPS/Server melalui SSH:
```bash
ssh user@IP_SERVER_ANDA
```

Perbarui repositori dan paket dasar sistem:
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip zip software-properties-common ca-certificates lsb-release gnupg ufw supervisor htop
```

Konfigurasi Firewall UFW:
```bash
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
sudo ufw status
```

---

## 3. Instalasi PHP 8.3-FPM di Debian 13 & Ubuntu 26

### Opsi A: Untuk Ubuntu 26.04 LTS
Ubuntu 26 mendukung instalasi PHP 8.3 baik langsung dari repositori resmi maupun via PPA terpercaya:
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### Opsi B: Untuk Debian 13 (Trixie)
Debian 13 menggunakan keyring modern di `/etc/apt/keyrings`:
```bash
sudo apt install -y apt-transport-https ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -sSLo /etc/apt/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
sudo sh -c 'echo "deb [signed-by=/etc/apt/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ trixie main" > /etc/apt/sources.list.d/php.list'
sudo apt update
```

### Instalasi Paket PHP 8.3 & Ekstensi Lengkap:
```bash
sudo apt install -y php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-mbstring php8.3-xml php8.3-zip \
    php8.3-curl php8.3-gd php8.3-bcmath php8.3-intl \
    php8.3-tokenizer php8.3-sqlite3
```

Verifikasi instalasi dan status service:
```bash
php -v
sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm
sudo systemctl status php8.3-fpm --no-pager
```

---

## 4. Instalasi & Pengerasan Database MariaDB 11

Instal MariaDB Server (bawaan Debian 13 / Ubuntu 26 adalah MariaDB 10.11 / 11.x):
```bash
sudo apt install -y mariadb-server mariadb-client
sudo systemctl enable mariadb
sudo systemctl start mariadb
```

Jalankan pengerasan keamanan database:
```bash
sudo mariadb-secure-installation
```
*(Atur password root, hapus anonymous user, larang remote login root, dan hapus database test)*.

### Buat Database & User Khusus Aplikasi:
```bash
sudo mariadb -u root -p
```

Jalankan query SQL berikut (ganti `PasswordSuperAman_2026!` dengan password kuat):
```sql
CREATE DATABASE central_ticketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ticket_user'@'localhost' IDENTIFIED BY 'PasswordSuperAman_2026!';
GRANT ALL PRIVILEGES ON central_ticketing.* TO 'ticket_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 5. Instalasi Composer v2 & Node.js LTS

### A. Composer v2:
```bash
cd ~
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm /tmp/composer-setup.php
composer --version
```

### B. Node.js (NodeSource modern untuk Debian 13 & Ubuntu 26):
```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg
echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list
sudo apt update
sudo apt install -y nodejs
node -v
npm -v
```

---

## 6. Clone Repositori & Setup Direktori Web

Letakkan project pada direktori standar web server:
```bash
cd /var/www
sudo git clone https://github.com/tegar78/central-ticketing.git
cd /var/www/central-ticketing
```

Atur izin sementara ke user SSH Anda untuk proses instalasi:
```bash
sudo chown -R $USER:$USER /var/www/central-ticketing
```

---

## 7. Konfigurasi Environment Produksi (.env)

Salin template `.env.example`:
```bash
cp .env.example .env
nano .env
```

Pastikan variabel-variabel berikut telah disesuaikan:
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
DB_PASSWORD=PasswordSuperAman_2026!

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# Parameter Fallback CI3 Database Billing (jika menggunakan instance lokal)
BILLING_CI3_DB_HOST=127.0.0.1
BILLING_CI3_DB_PORT=3306
BILLING_CI3_DB_DATABASE=bill3-gyh
BILLING_CI3_DB_USERNAME=root
BILLING_CI3_DB_PASSWORD=
```
Simpan file (`Ctrl + O`, `Enter`, lalu `Ctrl + X`).

---

## 8. Build Aset, Migrasi Skema & Seeding

Jalankan perintah berikut di direktori `/var/www/central-ticketing`:

```bash
# 1. Install dependensi PHP untuk produksi
composer install --no-dev --optimize-autoloader

# 2. Generate Application Encryption Key
php artisan key:generate --force

# 3. Jalankan migrasi database dan seeder bawaan
php artisan migrate --seed --force

# 4. Install dependensi frontend dan kompilasi aset Vite
npm install
npm run build
```

---

## 9. Hak Akses Direktori (Permissions www-data)

Web server Nginx dan proses PHP-FPM di Debian/Ubuntu berjalan di bawah akun `www-data`:

```bash
# Set ownership ke www-data
sudo chown -R www-data:www-data /var/www/central-ticketing

# Set permission direktori & file standar
sudo find /var/www/central-ticketing -type f -exec chmod 644 {} \;
sudo find /var/www/central-ticketing -type d -exec chmod 755 {} \;

# Berikan hak tulis penuh untuk direktori storage & bootstrap cache
sudo chmod -R 775 /var/www/central-ticketing/storage
sudo chmod -R 775 /var/www/central-ticketing/bootstrap/cache
```

---

## 10. Konfigurasi Nginx Berkinerja Tinggi (HTTP/2 & TLS 1.3)

Instal Nginx:
```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

Buat file konfigurasi virtual host:
```bash
sudo nano /etc/nginx/sites-available/central-ticketing
```

Tempelkan konfigurasi modern berikut:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tiket.domainanda.com; # Ganti dengan domain Anda
    root /var/www/central-ticketing/public;

    # Security Headers Modern
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(self), microphone=(), camera=()" always;

    index index.php index.html;
    charset utf-8;

    # Batas ukuran upload dokumen & lampiran tiket
    client_max_body_size 30M;

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
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
    }

    # Blokir akses ke file sensitif (.env, .git, .htaccess)
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Optimasi Caching Aset Statis (CSS, JS, Fonts, Images)
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
        access_log off;
    }

    # Gzip Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/x-javascript application/xml application/json;
    gzip_disable "MSIE [1-6]\.";
}
```

Aktifkan konfigurasi dan periksa sintaks:
```bash
sudo ln -s /etc/nginx/sites-available/central-ticketing /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

---

## 11. Sertifikat SSL Let's Encrypt (Certbot Standar Baru)

Pada Debian 13 & Ubuntu 26, pasang Certbot resmi:
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d tiket.domainanda.com
```
*(Certbot otomatis menambahkan konfigurasi SSL HTTP/2, sertifikat TLS 1.3, dan redirect otomatis dari HTTP ke HTTPS)*.

Uji otomatisasi perpanjangan sertifikat:
```bash
sudo certbot renew --dry-run
```

---

## 12. Automasi: Laravel Cron & Supervisor Queue Worker

### A. Crontab (Laravel Scheduler per-menit):
```bash
sudo crontab -u www-data -e
```
Tambahkan baris berikut di baris paling bawah:
```cron
* * * * * cd /var/www/central-ticketing && php artisan schedule:run >> /dev/null 2>&1
```

### B. Supervisor Queue Worker:
Buat file service worker antrean:
```bash
sudo nano /etc/supervisor/conf.d/central-ticketing-worker.conf
```

Isi konfigurasi berikut:
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

Terapkan service Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start central-ticketing-worker:*
sudo supervisorctl status
```

---

## 13. Kompilasi Cache Produksi

Jalankan perintah cache produksi Laravel agar aplikasi merespon secara instan:

```bash
cd /var/www/central-ticketing
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

---

## 14. Checklist Verifikasi & Prosedur Maintenance

### Checklist Pasca Deploy:
- [ ] **Ganti Password Akun Admin Bawaan**:
  Akun bawaan seeder: `admin@central.local` / `password`.
  Ganti langsung via CLI:
  ```bash
  cd /var/www/central-ticketing
  sudo -u www-data php artisan tinker --execute="\$u = App\Models\User::where('email', 'admin@central.local')->first(); \$u->password = bcrypt('PasswordBaruAdminSuperAman_2026!'); \$u->save(); echo 'Password admin sukses diubah!';"
  ```
- [ ] **Uji Sinkronisasi Billing CI3**:
  ```bash
  sudo -u www-data php artisan sync:billing-customers --all
  ```
- [ ] **Uji Akses Halaman Maps**: Buka `/maps`, pastikan pin pelanggan muncul dan periksa di Developer Tools tidak ada `api_key` atau password database yang terekspos.
- [ ] **Uji Ekspor Laporan PDF**: Buka `/tickets/export/pdf`, pastikan file PDF ter-download dengan rapi (format A4 landscape).

### Prosedur Update Kode di Server:
```bash
cd /var/www/central-ticketing
sudo -u www-data php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
npm install
npm run build
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo supervisorctl restart central-ticketing-worker:*
sudo -u www-data php artisan up
```
