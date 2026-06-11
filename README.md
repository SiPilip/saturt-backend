# Saturt - Aplikasi Manajemen RT

Saturt adalah aplikasi untuk manajemen Rukun Tetangga (RT) yang mencakup fungsionalitas untuk mengelola penghuni, rumah, generate tagihan bulanan, pencatatan iuran, hingga laporan keuangan dan export file ke Excel.

Aplikasi ini menggunakan arsitektur **Terpisah (Decoupled)**:
- **Backend:** Pure REST API dengan Laravel 11
- **Frontend:** React + Vite + Shadcn UI

---

## 💻 System Requirements (Persyaratan Sistem)

**PENTING:** Karena aplikasi menggunakan Laravel 11 dan fitur modern, versi PHP sangat krusial untuk mencegah error (*syntax error, missing extensions, dll*).

### 1. Kebutuhan Backend (Server)
- **PHP:** Versi **8.2.12** atau **8.3.x** (Wajib). *Versi di bawah 8.2 tidak akan didukung oleh Laravel 11.*
- **Composer:** Versi 2.x
- **Database:** MySQL versi 8.0+ atau MariaDB versi 10.4+
- **Ekstensi PHP yang Wajib Aktif (di `php.ini`):**
  - `ext-pdo`
  - `ext-mbstring`
  - `ext-openssl`
  - `ext-gd` *(Untuk library intervention/image)*
  - `ext-zip` *(Untuk export Excel maatwebsite/excel)*
  - `ext-fileinfo`

### 2. Kebutuhan Frontend (Client)
- **Node.js:** Versi **18.x** atau **20.x** (LTS disarankan)
- **NPM:** Bawaan dari Node.js (atau bisa menggunakan `pnpm` / `yarn`)

---

## 🚀 Panduan Instalasi (Step-by-Step)

Ikuti langkah-langkah di bawah ini untuk menjalankan aplikasi di komputer lokal (localhost).

### Bagian A: Setup Backend (Laravel)

1. **Clone Repository & Masuk ke Folder Backend**
   Buka terminal/CMD, lalu jalankan:
   ```bash
   git clone <repository_url>
   cd saturt-backend
   ```

2. **Install Dependencies PHP**
   Pastikan Anda menggunakan PHP 8.2+ sebelum menjalankan perintah ini:
   ```bash
   composer install
   ```

3. **Buat Database MySQL**
   Buka aplikasi database Anda (seperti phpMyAdmin, DBeaver, atau MySQL CLI) dan buat database kosong dengan nama:
   `saturt_db`

4. **Konfigurasi Environment (.env)**
   Salin file `.env.example` menjadi `.env`. Di Windows jalankan:
   ```bash
   copy .env.example .env
   ```
   Lalu buka file `.env` di text editor, pastikan konfigurasi database sesuai dengan komputer Anda:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=saturt_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Generate Keys & Setup Storage**
   Jalankan tiga perintah ini secara berurutan untuk membuat App Key, JWT Secret Key, dan menautkan folder storage untuk upload gambar:
   ```bash
   php artisan key:generate
   php artisan jwt:secret
   php artisan storage:link
   ```

6. **Migrasi dan Seeding Database**
   Perintah ini akan membuat semua tabel yang dibutuhkan sekaligus memasukkan akun Admin default dan data iuran dasar:
   ```bash
   php artisan migrate --seed
   ```

7. **Jalankan Server Backend**
   ```bash
   php artisan serve
   ```
   *Backend sekarang berjalan di `http://127.0.0.1:8000`*

---

### Bagian B: Setup Frontend (React + Vite)

Buka **Terminal/CMD Baru** (jangan matikan terminal backend), lalu ikuti langkah ini:

1. **Masuk ke Folder Frontend**
   ```bash
   cd saturt-frontend
   ```

2. **Install Dependencies Node.js**
   ```bash
   npm install
   ```

3. **Konfigurasi Environment (.env)**
   Pastikan file `.env` di folder frontend sudah menunjuk ke URL backend yang benar (default-nya sudah mengarah ke port 8000):
   ```env
   VITE_API_URL=http://localhost:8000/api
   VITE_STORAGE_URL=http://localhost:8000/storage
   ```

4. **Jalankan Server Frontend**
   ```bash
   npm run dev
   ```
   *Frontend sekarang berjalan di `http://localhost:5173` (Cek link yang muncul di terminal).*

---

## 🔐 Kredensial Login Default

Setelah proses migrasi dan seeding selesai, Anda bisa login ke aplikasi melalui frontend (Halaman Admin) menggunakan akun berikut:

- **NIK:** `3271000000000001`
- **Password:** `admin123`

---
*Developed for Skill Fit Test.*
