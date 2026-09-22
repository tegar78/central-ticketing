# Panduan Instalasi Central-Ticketing (MANAGEMENT TICKET)
### Sistem Operasi Target: Ubuntu 22.04 / 24.04 LTS & Debian 11 / 12 Server

Dokumen ini berisi panduan langkah-demi-langkah (step-by-step) untuk melakukan instalasi dan konfigurasi sistem **MANAGEMENT TICKET (Central Ticket System)** pada server berbasis **Ubuntu** atau **Debian** dari awal (*fresh install*) hingga siap produksi (*production-ready*).

---

## 1. Spesifikasi Minimum & Rekomendasi Server

| Komponen | Spesifikasi Minimum | Spesifikasi Rekomendasi |
| :--- | :--- | :--- |
| **Sistem Operasi** | Ubuntu 22.04 LTS / Debian 11 | Ubuntu 24.04 LTS / Debian 12 |
| **CPU** | 1 vCPU | 2 vCPU atau lebih |
| **RAM** | 1 GB (dengan swap 1-2 GB) | 2 GB - 4 GB |
| **Penyimpanan** | 15 GB SSD | 25 GB+ SSD / NVMe |
| **Akses** | Root / User dengan hak `sudo` | User dengan hak `sudo` |

---

## 2. Langkah 1: Update Sistem & Instalasi Paket Dasar

Jalankan perintah berikut untuk memperbarui repositori dan paket server:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip zip software-properties-common ca-certificates lsb-release gnupg ufw supervisor
```

---

## 3. Langkah 2: Instalasi PHP 8.3 & Ekstensi yang Dibutuhkan

Aplikasi ini dibangun menggunakan Laravel modern yang membutuhkan **PHP 8.3** dan modul-modul pendukung manipulasi gambar (GD), dokumen Excel (PhpSpreadsheet), serta PDF (DomPDF).

### A. Untuk Ubuntu (22.04 / 24.04 LTS):
```bash
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### B. Untuk Debian (11 / 12):
```bash
sudo apt install -y apt-transport-https
sudo curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
sudo sh -c 'echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
sudo apt update
```

### C. Install PHP 8.3-FPM dan Seluruh Ekstensi:
```bash
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common \
    php8.3-mysql php8.3-mbstring php8.3-xml php8.3-zip \
    php8.3-curl php8.3-gd php8.3-bcmath php8.3-intl \
    php8.3-tokenizer php8.3-sqlite3
```

Verifikasi versi PHP:
```bash
php -v
sudo systemctl status php8.3-fpm --no-pager
```

---

## 4. Langkah 3: Instalasi Database MariaDB / MySQL

Instal MariaDB Server (atau MySQL 8.0):

```bash
sudo apt install -y mariadb-server mariadb-client
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

Jalankan skrip keamanan database:
```bash
sudo mysql_secure_installation
```
*(Ikuti petunjuk di layar: buat password root, hapus anonymous user, larang remote root login, dan hapus test database).*

### Buat Database & User Khusus:
Masuk ke terminal MariaDB:
```bash
sudo mysql -u root -p
```

Jalankan query SQL berikut (ganti `PasswordKuat123!` dengan password aman pilihan Anda):
```sql
CREATE DATABASE central_ticketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ticket_user'@'localhost' IDENTIFIED BY 'PasswordKuat123!';
GRANT ALL PRIVILEGES ON central_ticketing.* TO 'ticket_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 5. Langkah 4: Instalasi Composer & Node.js (LTS)

### A. Install Composer v2:
```bash
cd ~
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
sudo php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
composer --version
```

### B. Install Node.js 20 LTS & NPM:
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## 6. Langkah 5: Clone Proyek dari GitHub

Kita tempatkan aplikasi pada direktori standar web `/var/www/central-ticketing`:

```bash
cd /var/www
sudo git clone https://github.com/tegar78/central-ticketing.git
cd /var/www/central-ticketing
```

Berikan hak akses ke user aktif Anda untuk setup awal:
```bash
sudo chown -R $USER:$USER /var/www/central-ticketing
```

---

## 7. Langkah 6: Konfigurasi Environment (`.env`)

Salin file template `.env.example`:
```bash
cp .env.example .env
```

Buka dan sesuaikan isi file `.env` menggunakan editor teks (misal: `nano .env`):
```bash
nano .env
```

Pastikan variabel-variabel kunci diatur sebagai berikut:
```env
APP_NAME="MANAGEMENT TICKET"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://IP_SERVER_ANDA (atau https://tiket.domainanda.com)

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=central_ticketing
DB_USERNAME=ticket_user
DB_PASSWORD=PasswordKuat123!

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Simpan file (`Ctrl + O`, `Enter`, lalu keluar dengan `Ctrl + X`).

---

## 8. Langkah 7: Install Dependensi, Generate Key & Migration

Jalankan perintah berikut di dalam direktori `/var/www/central-ticketing`:

```bash
# 1. Install dependensi PHP untuk production
composer install --no-dev --optimize-autoloader

# 2. Generate Application Encryption Key
php artisan key:generate --force

# 3. Jalankan migrasi database beserta Seeder bawaan
php artisan migrate --seed --force

# 4. Install dependensi frontend dan kompilasi aset produksi
npm install
npm run build
```

> [!NOTE]
> Perintah `php artisan migrate --seed --force` otomatis membuat akun administrator, operator, teknisi awal, serta tabel pendukung sinkronisasi billing.

---

## 9. Langkah 8: Pengaturan Permissions & Ownership Direktori

Server web Nginx berjalan di bawah user `www-data`. Berikan hak kepemilikan yang tepat:

```bash
# Atur ownership ke www-data
sudo chown -R www-data:www-data /var/www/central-ticketing

# Berikan izin direktori dan file standar
sudo find /var/www/central-ticketing -type f -exec chmod 644 {} \;
sudo find /var/www/central-ticketing -type d -exec chmod 755 {} \;

# Berikan hak tulis khusus untuk storage dan bootstrap/cache
sudo chmod -R 775 /var/www/central-ticketing/storage
sudo chmod -R 775 /var/www/central-ticketing/bootstrap/cache
```

---

## 10. Langkah 9: Konfigurasi Web Server Nginx

Instal Nginx jika belum terpasang:
```bash
sudo apt install -y nginx
sudo systemctl start nginx
sudo systemctl enable nginx
```

Buat file konfigurasi virtual host baru:
```bash
sudo nano /etc/nginx/sites-available/central-ticketing
```

Salin dan tempel konfigurasi berikut (sesuaikan `server_name` dengan domain atau IP Anda):

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name tiket.domainanda.com; # Ganti dengan domain atau IP server Anda
    root /var/www/central-ticketing/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php index.html;
    charset utf-8;

    # Batas ukuran upload file (disesuaikan jika ada lampiran tiket besar)
    client_max_body_size 20M;

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
    }

    # Blokir akses ke file tersembunyi (.env, .git, dll)
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Optimasi cache untuk file statis
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|webp|svg|woff|woff2)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

Aktifkan konfigurasi virtual host dan restart Nginx:
```bash
# Aktifkan symlink
sudo ln -s /etc/nginx/sites-available/central-ticketing /etc/nginx/sites-enabled/

# Hapus default config jika tidak diperlukan
sudo rm -f /etc/nginx/sites-enabled/default

# Uji sintaks Nginx
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

---

## 11. Langkah 10: Pasang Sertifikat SSL Gratis (HTTPS dengan Let's Encrypt)

Jika Anda menggunakan nama domain aktif yang mengarah ke IP server, pasang SSL gratis dengan **Certbot**:

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d tiket.domainanda.com
```

Pilih opsi untuk otomatis redirect HTTP ke HTTPS. Certbot akan otomatis memperbarui sertifikat saat mendekati masa kedaluwarsa.

---

## 12. Langkah 11: Konfigurasi Otomatisasi (Cron Scheduler & Supervisor Queue)

### A. Menambahkan Laravel Scheduler ke Crontab:
Laravel membutuhkan cron job per-menit untuk menjalankan background task sinkronisasi billing dan pembersihan tiket:

```bash
sudo crontab -u www-data -e
```
Tambahkan baris berikut di bagian paling bawah:
```cron
* * * * * cd /var/www/central-ticketing && php artisan schedule:run >> /dev/null 2>&1
```

### B. Konfigurasi Supervisor untuk Queue Worker:
Queue worker memastikan proses pengiriman notifikasi atau sinkronisasi data billing berjalan mulus di background:

```bash
sudo nano /etc/supervisor/conf.d/central-ticketing-worker.conf
```

Isi dengan konfigurasi berikut:
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

Terapkan konfigurasi Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start central-ticketing-worker:*
```

---

## 13. Langkah 12: Optimasi Cache Laravel untuk Lingkungan Produksi

Jalankan perintah optimasi untuk mempercepat respon aplikasi secara signifikan:

```bash
cd /var/www/central-ticketing
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

---

## 14. Informasi Akun Default & Login Pertama Kali

Setelah proses migrasi dan seeding selesai, sistem menyediakan akun bawaan berikut:

| Peran (Role) | Alamat Email / Telepon | Password Default | Akses Menu |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@central.local` / `081234567890` | `password` | Akses Penuh (Kelola User, Tiket, Pelanggan, Peta, Billing) |
| **Operator** | `operator@central.local` / `081234567891` | `password` | Manajemen Tiket & Pelanggan |
| **Teknisi** | `teknisi1@central.local` / `081234567892` | `password` | Portal Khusus Tiket yang Ditugaskan |

> [!CAUTION]
> **PENTING: Segera ganti password default admin setelah login pertama kali!**
> 
> Anda dapat mengganti password via menu **Kelola Pengguna** di dashboard atau langsung via CLI menggunakan perintah:
> ```bash
> cd /var/www/central-ticketing
> php artisan tinker --execute="\$u = App\Models\User::where('email', 'admin@central.local')->first(); \$u->password = bcrypt('PasswordBaruSuperAman123!'); \$u->save(); echo 'Password berhasil diubah!';"
> ```

---

## 15. Prosedur Pembaruan Aplikasi (Update / Maintenance)

Setiap kali ada pembaruan kode di repositori GitHub, jalankan langkah pembaruan aman ini di server:

```bash
cd /var/www/central-ticketing

# 1. Aktifkan mode maintenance (opsional jika perubahan besar)
sudo -u www-data php artisan down

# 2. Ambil update kode terbaru
git pull origin main

# 3. Update dependensi jika ada perubahan
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 4. Jalankan migrasi database jika ada skema baru
sudo -u www-data php artisan migrate --force

# 5. Segarkan cache aplikasi
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 6. Restart Queue Worker
sudo supervisorctl restart central-ticketing-worker:*

# 7. Nonaktifkan mode maintenance
sudo -u www-data php artisan up
```

---

## 16. Troubleshooting Masalah Umum

### 1. Error `500 Server Error`
Periksa log error Laravel:
```bash
tail -n 100 /var/www/central-ticketing/storage/logs/laravel.log
```
Biasanya disebabkan oleh:
- Permission direktori storage: jalankan `sudo chmod -R 775 /var/www/central-ticketing/storage`.
- Belum menjalankan `php artisan key:generate`.
- Kredensial database pada `.env` belum sesuai.

### 2. Error Nginx `502 Bad Gateway`
Periksa apakah socket PHP-FPM aktif:
```bash
sudo systemctl status php8.3-fpm
```
Pastikan file socket berada di `/var/run/php/php8.3-fpm.sock` sesuai dengan baris `fastcgi_pass` di konfigurasi Nginx.

### 3. Tampilan Halaman Rusak / CSS Tidak Muncul
Pastikan perintah build frontend telah selesai dijalankan:
```bash
cd /var/www/central-ticketing
npm run build
sudo -u www-data php artisan view:clear
```
Serta pastikan direktori `public/build` dapat dibaca oleh Nginx (`chmod -R 755 public`).
