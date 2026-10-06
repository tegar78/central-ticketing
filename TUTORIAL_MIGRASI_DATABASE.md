<!-- cSpell:disable -->
<!-- markdownlint-disable -->

# Panduan Lengkap Migrasi Database Laravel
### Target: Central-Ticketing (Ubuntu 24/26 & Debian 13)
**Didesain Khusus untuk Pemula & Junior Developer (Aman, Jelas, dan Anti-Data Loss)**

---

## Daftar Isi

1. [Apa Itu Migrasi Database di Laravel?](#1-apa-itu-migrasi-database-di-laravel)
2. [Aturan Emas Migrasi di Server Produksi](#2-aturan-emas-migrasi-di-server-produksi)
3. [Daftar Struktur Migrasi Proyek Central-Ticketing](#3-daftar-struktur-migrasi-proyek-central-ticketing)
4. [Skenario A: Instalasi Baru di Server Kosong (Fresh Setup)](#4-skenario-a-instalasi-baru-di-server-kosong-fresh-setup)
5. [Skenario B: Update Server Produksi yang Sedang Berjalan (Existing Server)](#5-skenario-b-update-server-produksi-yang-sedang-berjalan-existing-server)
6. [Penjelasan Khusus: Migrasi Master ODP Canonical Terbaru](#6-penjelasan-khusus-migrasi-master-odp-canonical-terbaru)
7. [Prosedur Rollback & Disaster Recovery](#7-prosedur-rollback--disaster-recovery)
8. [Troubleshooting Error Migrasi Populer](#8-troubleshooting-error-migrasi-populer)
9. [Cheat Sheet Perintah Artisan Migrate](#9-cheat-sheet-perintah-artisan-migrate)

---

## 1. Apa Itu Migrasi Database di Laravel?

Bayangkan migrasi database seperti **Git untuk struktur tabel database kamu**. 

Daripada membuat tabel secara manual lewat phpMyAdmin atau DBeaver di setiap laptop tim dan server produksi, Laravel menggunakan file PHP di folder `database/migrations/` untuk mencatat riwayat perubahan tabel:
- Membuat tabel baru (`Schema::create`)
- Menambah atau menghapus kolom (`Schema::table`)
- Mengatur relasi antar tabel (Foreign Keys & Indexes)
- Menjalankan transformasi data otomatis

Laravel mencatat file mana saja yang **sudah dijalankan** di dalam tabel khusus bernama `migrations`. Jika ada file migrasi baru yang belum pernah dijalankan, Laravel hanya akan mengeksekusi file baru tersebut tanpa menyentuh tabel lama.

---

## 2. Aturan Emas Migrasi di Server Produksi

⚠️ **CATATAN PENTING UNTUK JUNIOR DEVELOPER:**

1. **JANGAN PERNAH menjalankan `php artisan migrate:fresh` di server produksi!**
   `migrate:fresh` atau `migrate:reset` akan **MENGHAPUS SELURUH TABEL DAN DATA** (Drop All Tables) lalu membuatnya dari nol. Ini hanya untuk laptop lokal (development)!
2. **SELALU BUAT BACKUP DATABASE sebelum migrasi!**
   Jika terjadi kesalahan struktur data atau listrik padam di tengah proses, kamu punya jaring pengaman untuk mengembalikannya dalam hitungan detik.
3. **Gunakan flag `--force` di server produksi:**
   Karena `APP_ENV=production`, Laravel akan menolak perintah migrasi kecuali kamu menambahkan opsi `--force`.

---

## 3. Daftar Struktur Migrasi Proyek Central-Ticketing

Berikut urutan migrasi resmi yang ada pada proyek ini:

| File Migrasi | Fungsi & Dampak Tabel |
| :--- | :--- |
| `0001_01_01_000000_create_users_table.php` | Membuat tabel akun pengguna (`users`) & reset token |
| `0001_01_01_000001_create_cache_table.php` | Membuat tabel cache sistem |
| `0001_01_01_000002_create_jobs_table.php` | Membuat tabel antrean background queue |
| `2026_08_14_150055_create_billing_instances_table.php` | Tabel multi-tenant billing CI3 / external server |
| `2026_08_14_150056_create_tickets_table.php` | Tabel utama penanganan tiket gangguan |
| `2026_08_14_150057_create_ticket_timelines_table.php` | Log riwayat progres status tiket |
| `2026_08_25_000001_create_customers_table.php` | Tabel sinkronisasi pelanggan dari seluruh billing |
| `2026_09_29_000001_create_odps_table.php` | Tabel pendataan tiang fisik ODP |
| `2026_09_30_000001_add_port_number_to_customers_table.php` | Menambahkan kolom slot port fisik pelanggan |
| `2026_10_02_000001_update_all_odps_total_ports_to_16.php` | Normalisasi default kapasitas ODP ke 16 port |
| `2026_10_05_000001_add_ip_address_and_pppoe_to_customers_table.php` | Menambahkan IP statis & username PPPoE pelanggan |
| `2026_10_06_000001_remove_billing_node_id_from_odps_table.php` | **Master Canonical:** Menghapus `billing_node_id` & menggabungkan ODP duplikat |

---

## 4. Skenario A: Instalasi Baru di Server Kosong (Fresh Setup)

Gunakan skenario ini jika kamu baru saja menyewa VPS baru dan database MariaDB masih kosong bersih.

### Langkah 1: Masuk ke folder proyek di server

```bash
cd /var/www/central-ticketing
```

### Langkah 2: Pastikan file `.env` sudah terhubung ke database

Uji apakah koneksi database berhasil dengan perintah:

```bash
php artisan db:show
```

*(Jika muncul informasi nama database MariaDB, artinya koneksi aman).*

### Langkah 3: Jalankan seluruh migrasi dari awal

```bash
php artisan migrate --force
```

Terminal akan menampilkan status `DONE` pada setiap tabel:
```text
INFO  Running migrations.
0001_01_01_000000_create_users_table .................................... 18ms DONE
...
2026_10_06_000001_remove_billing_node_id_from_odps_table ................ 42ms DONE
```

### Langkah 4: Isi data awal (Admin default) jika diperlukan

```bash
php artisan db:seed --force
```

### Langkah 5: Sinkronisasi Master ODP pertama kali

```bash
php artisan odp:sync
```

---

## 5. Skenario B: Update Server Produksi yang Sedang Berjalan (Existing Server)

Gunakan skenario ini jika aplikasi sudah berjalan di produksi dan ada fitur atau kolom database baru yang di-push dari GitHub.

### Langkah 1: Masuk ke folder aplikasi

```bash
cd /var/www/central-ticketing
```

### Langkah 2: Aktifkan mode maintenance (Opsional)

Jika migrasi mengubah struktur tabel besar, aktifkan halaman perawatan sementara agar pengguna tidak mengirim data di tengah proses:

```bash
php artisan down --secret="bypass-admin-123"
```

*(Kamu tetap bisa mengakses web dengan menambahkan `?secret=bypass-admin-123` di URL).*

### Langkah 3: Ambil update kode terbaru dari Git

```bash
git pull origin main
```

### Langkah 4: BUAT BACKUP DATABASE (Wajib!)

Gunakan command backup bawaan Central-Ticketing:

```bash
php artisan db:backup
```

File backup `.sql` akan tersimpan aman di folder `storage/app/backups/`.

Atau gunakan `mysqldump` manual:
```bash
sudo mysqldump -u ticket_user -p central_ticket > /var/www/central-ticketing/storage/app/backups/backup_sebelum_migrate_$(date +%F_%H%M%S).sql
```

### Langkah 5: Cek migrasi apa saja yang belum dieksekusi

```bash
php artisan migrate:status
```

Perhatikan kolom status:
- `Ran`: Berarti migrasi ini sudah pernah dijalankan sebelumnya.
- `Pending`: Berarti migrasi ini **baru** dan akan dieksekusi saat kita menjalankan `migrate`.

### Langkah 6: Jalankan migrasi baru

```bash
php artisan migrate --force
```

Laravel hanya akan mengeksekusi file yang berstatus `Pending`. Data pada tabel lain **tidak akan hilang atau terganggu**.

### Langkah 7: Sinkronisasi data pasca-migrasi

Jika migrasi berhubungan dengan ODP atau Pelanggan:

```bash
# 1. Update data master fisik ODP
php artisan odp:sync

# 2. Sinkronkan data pelanggan dari billing node aktif
php artisan billing:sync-customers
```

### Langkah 8: Bersihkan & perbarui cache sistem

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Langkah 9: Matikan mode maintenance

```bash
php artisan up
```

Website kamu kini kembali online dengan struktur database terbaru!

---

## 6. Penjelasan Khusus: Migrasi Master ODP Canonical Terbaru

Migrasi `2026_10_06_000001_remove_billing_node_id_from_odps_table.php` adalah salah satu migrasi arsitektur paling penting di Central-Ticketing:

### Mengapa migrasi ini dibuat?
Sebelumnya, ODP diduplikasi untuk setiap server billing (517 ODP dikali 3 billing = 1.551 baris data). Jika ada 60 billing, jumlah ODP akan membengkak menjadi puluhan ribu baris duplikat.

### Apa yang dilakukan migrasi ini secara otomatis?
1. **Penyatuan Cerdas (Intelligent Consolidation):**
   Menggabungkan data ODP yang memiliki kode sama (misal `ODP-A1`) menjadi **1 baris master fisik tunggal**.
2. **Preservasi Koordinat & Foto:**
   Jika salah satu billing memiliki koordinat GPS atau foto tiang ODP yang lebih lengkap, data terbaik tersebut disimpan dan tidak terhapus.
3. **Penghapusan Kolom `billing_node_id`:**
   Menghapus foreign key dan kolom `billing_node_id` dari tabel `odps`.
4. **Global Unique Index:**
   Menjadikan kolom `code_odp` bersifat **Global Unique**. Tidak ada lagi ODP dengan nama sama yang dapat terduplikasi.
5. **Multi-Tenancy di Level Pelanggan:**
   Pelanggan dari billing mana pun terhubung ke ODP fisik via `customers.odp_name = odps.code_odp` dan `port_number`.

---

## 7. Prosedur Rollback & Disaster Recovery

Jika di tengah jalan migrasi kamu gagal atau terjadi kesalahan kode:

### Pilihan 1: Rollback 1 Langkah Terakhir (Laravel Rollback)

```bash
cd /var/www/central-ticketing
php artisan migrate:rollback --step=1 --force
```

Perintah ini akan menjalankan method `down()` pada migrasi terakhir untuk mengembalikan struktur ke kondisi sebelumnya.

### Pilihan 2: Restore Database Penuh dari File Backup SQL

Jika kamu perlu mengembalikan kondisi database persis seperti sebelum migrasi:

1. Buka folder backup:
   ```bash
   ls -la /var/www/central-ticketing/storage/app/backups/
   ```

2. Restore file SQL ke MariaDB (ganti nama file dengan file backup milikmu):
   ```bash
   mysql -u ticket_user -p central_ticket < /var/www/central-ticketing/storage/app/backups/NAMA_FILE_BACKUP.sql
   ```

3. Bersihkan cache:
   ```bash
   php artisan optimize:clear
   ```

---

## 8. Troubleshooting Error Migrasi Populer

Berikut error yang paling sering dialami junior developer saat menjalankan migrasi dan solusinya:

---

### Kasus 1: `SQLSTATE[42S21]: Column already exists`

- **Artinya:** Kolom yang ingin dibuat sudah ada di database (biasanya karena seseorang pernah menambahkannya secara manual di phpMyAdmin).
- **Solusi:**
  1. Jangan panik. Cek apakah kolom tersebut memang sudah ada:
     ```bash
     php artisan db:table nama_tabel
     ```
  2. Buka file migrasi terkait, dan tambahkan pengecekan `if (!Schema::hasColumn('nama_tabel', 'nama_kolom'))`.
  3. Atau tandai migrasi tersebut sebagai sudah selesai dengan mencatat namanya manual di tabel `migrations`.

---

### Kasus 2: `SQLSTATE[HY000] [1045] Access denied for user 'ticket_user'`

- **Artinya:** User database tidak memiliki hak akses yang cukup atau password di `.env` salah.
- **Solusi:**
  User MariaDB untuk Laravel membutuhkan hak akses `CREATE`, `ALTER`, `INDEX`, dan `DROP`. Berikan izin penuh:
  ```bash
  sudo mysql -u root -e "GRANT ALL PRIVILEGES ON central_ticket.* TO 'ticket_user'@'localhost'; FLUSH PRIVILEGES;"
  ```

---

### Kasus 3: `SQLSTATE[23000]: Integrity constraint violation: Cannot add foreign key constraint`

- **Artinya:** Foreign key tidak cocok dengan tipe data kolom referensinya, atau tabel yang direferensikan belum dibuat.
- **Solusi:**
  Pastikan kedua kolom memiliki tipe data identik (misal sama-sama `unsignedBigInteger()`). Pastikan tabel induk dibuat terlebih dahulu sebelum tabel anak.

---

### Kasus 4: `Lock wait timeout exceeded; try restarting transaction`

- **Artinya:** Ada proses background lain (misal cron atau queue worker) yang sedang membaca/mengunci tabel saat migrasi ingin mengubah skema.
- **Solusi:**
  1. Hentikan sementara background worker:
     ```bash
     sudo supervisorctl stop central-ticketing-worker:*
     ```
  2. Jalankan kembali migrasi:
     ```bash
     php artisan migrate --force
     ```
  3. Nyalakan kembali worker:
     ```bash
     sudo supervisorctl start central-ticketing-worker:*
     ```

---

## 9. Cheat Sheet Perintah Artisan Migrate

Simpan daftar perintah ini untuk referensi harianmu:

| Perintah | Deskripsi | Kapan Digunakan? |
| :--- | :--- | :--- |
| `php artisan migrate --force` | Menjalankan seluruh migrasi yang belum dieksekusi | Setiap kali ada pembaruan kode di server produksi |
| `php artisan migrate:status` | Menampilkan tabel status migrasi (`Ran` / `Pending`) | Sebelum deploy, untuk melihat apa yang akan diubah |
| `php artisan migrate:rollback --step=1 --force` | Membatalkan 1 file migrasi terakhir | Jika fitur baru yang di-deploy mengalami bug fatal |
| `php artisan db:backup` | Membuat salinan instan database ke folder storage | Wajib sebelum menjalankan update migrasi |
| `php artisan db:show` | Menampilkan ringkasan ukuran, tabel, dan koneksi DB | Untuk memeriksa kesehatan koneksi database |
| `php artisan db:table odps` | Menampilkan rincian struktur kolom dan index tabel | Untuk memverifikasi apakah kolom baru sudah masuk |
| `php artisan odp:sync` | Sinkronisasi master ODP dari billing external | Setelah migrasi tabel `odps` |
| `php artisan billing:sync-customers` | Sinkronisasi seluruh pelanggan & mapping port | Setelah migrasi tabel `customers` |

---

## 🏁 Selesai!
Sekarang kamu sudah memahami cara kerja migrasi database di Laravel secara profesional. Selalu utamakan backup sebelum migrasi dan ikuti prosedur langkah demi langkah! 🚀
