# SIGAP Backend (Laravel 12)

Backend API untuk aplikasi SIGAP (Sistem Informasi dan Layanan), dibangun menggunakan framework Laravel 12.

## 🚀 Persyaratan Sistem

- **PHP**: ^8.2
- **Composer**: ^2.0
- **Database**: MySQL / MariaDB (atau SQLite untuk testing)
- **Web Server**: Apache / Nginx / Laravel Artisan Serve

## 🛠️ Instalasi Awal

Ikuti langkah-langkah berikut untuk menjalankan aplikasi secara lokal:

1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd sigap-backend
   ```

2. **Instal Dependensi PHP**
   ```bash
   composer install
   ```

3. **Konfigurasi Environment**
   Salin file `.env.example` menjadi `.env` dan sesuaikan pengaturan database serta layanan lainnya:
   ```bash
   cp .env.example .env
   ```
   Lalu generate aplikasi key:
   ```bash
   php artisan key:generate
   ```

4. **Migrasi Database**
   Jalankan migrasi untuk membuat tabel-tabel yang diperlukan:
   ```bash
   php artisan migrate
   ```

5. **Jalankan Aplikasi**
   Anda bisa menggunakan script composer yang sudah disediakan:
   ```bash
   composer dev
   ```
   Atau jalankan server secara manual:
   ```bash
   php artisan serve
   ```

## ✨ Fitur Utama

- **Authentication & Authorization**:
  - Integrasi SSO (Single Sign-On) Stats NTB.
  - Autentikasi API via Laravel Sanctum.
  - Manajemen Role & Permission.
- **Manajemen Tiket**:
  - Pembuatan dan pengelolaan tiket layanan.
  - Dashboard statistik (Admin, Super Admin, Layanan).
  - Ekspor data tiket ke Excel.
  - Kalender jadwal layanan.
- **Work Orders (Perintah Kerja)**:
  - Alur kerja teknisi dari penugasan hingga penyelesaian.
  - Kartu Kendali untuk pemantauan pekerjaan.
- **Manajemen Aset BMN**:
  - Inventarisasi aset BMN.
  - Fitur Import & Export data via Excel.
- **Integrasi Zoom**:
  - Penjadwalan akun Zoom otomatis.
  - Cek ketersediaan dan konflik jadwal.
- **Audit Logs**:
  - Pencatatan otomatis setiap aktivitas penting dalam sistem.
- **Notifikasi**:
  - Notifikasi real-time untuk status tiket dan penugasan.

## 📂 Struktur File & Arsitektur

Proyek ini mengikuti arsitektur standar Laravel dengan beberapa penyesuaian:

- `app/Http/Controllers/`: Logika utama untuk menangani request API.
- `app/Models/`: Definisi skema data dan hubungan antar tabel.
- `app/Http/Services/`: (Jika ada) Logika bisnis tambahan di luar controller.
- `routes/api.php`: Definisi seluruh endpoint API.
- `database/migrations/`: Skema database.
- `database/seeders/`: Data awal untuk development.
- `.scribe/`: Dokumentasi API yang digenerate otomatis.

## 📝 Perintah Penting (Scripts)

Tersedia di `composer.json`:
- `composer setup`: Menjalankan instalasi, copy env, key generate, dan migrasi.
- `composer dev`: Menjalankan server, queue listener, dan vite secara bersamaan.
- `composer test`: Menjalankan unit & feature testing menggunakan Pest.

## 📄 Lisensi
[MIT license](https://opensource.org/licenses/MIT).
