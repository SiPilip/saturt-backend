# Saturt - REST API Backend

Saturt adalah aplikasi backend murni (Pure REST API) untuk manajemen Rukun Tetangga (RT). API ini mencakup fungsionalitas untuk mengelola penghuni, rumah, generate tagihan bulanan, pencatatan iuran publik, hingga laporan keuangan dan export file ke Excel.

API ini didesain sebagai **Pure Backend** dengan otentikasi berbasis **JWT (JSON Web Tokens) via HttpOnly Cookie**.

## 🛠️ Stack Teknologi

- **Framework:** Laravel 11 (PHP 8.3+)
- **Database:** MySQL
- **Auth:** `php-open-source-saver/jwt-auth` (JWT Token di HttpOnly Cookie)
- **Image Processing:** `intervention/image` (v4)
- **Excel Export:** `maatwebsite/excel` (v3.1)
- **Dokumentasi API:** Swagger / OpenAPI 3.0

---

## 🚀 Panduan Instalasi (Untuk Reviewer)

Aplikasi ini **TIDAK** menggunakan *Node.js/NPM*, sehingga Anda tidak perlu menjalankan `npm install`. Proses setup murni menggunakan ekosistem PHP.

### 1. Clone & Install Dependencies
```bash
git clone <repository_url>
cd saturt-backend
composer install
```

### 2. Konfigurasi Environment
Salin file environment dan atur kredensial database Anda (secara default diset ke `saturt_db` dengan username `root`):
```bash
cp .env.example .env
```
*(Pastikan Anda telah membuat database kosong bernama `saturt_db` di MySQL Anda sebelum lanjut ke langkah berikutnya).*

### 3. Generate Keys & Storage Link
Jalankan perintah berikut secara berurutan untuk menyiapkan kunci enkripsi, JWT secret, dan symbolic link untuk penyimpanan foto KTP:
```bash
php artisan key:generate
php artisan jwt:secret
php artisan storage:link
```

### 4. Migrasi & Seeding Database
Aplikasi dilengkapi dengan seeder untuk akun Admin dan beberapa data iuran default.
```bash
php artisan migrate --seed
```
Setelah proses ini, akun default berikut akan tersedia:
- **NIK (Login):** `3271000000000001`
- **Password:** `admin123`

### 5. Jalankan Server Lokal
```bash
php artisan serve
```

---

## 📖 Dokumentasi API (Swagger)

Untuk memudahkan pengetesan seluruh endpoint, dokumentasi interaktif **Swagger UI** telah di-bundle langsung di dalam aplikasi.

Saat server berjalan, Anda dapat membuka browser dan langsung menuju:
👉 **[http://localhost:8000](http://localhost:8000)**

Halaman utama akan langsung mengarahkan Anda ke dokumentasi API (Swagger UI). Anda bisa melihat seluruh *request payload*, *response structure*, dan deskripsi dari masing-masing endpoint di sana.

### 💡 Tips Testing via Postman
Jika Anda lebih nyaman menggunakan Postman, Anda tidak perlu memasukkan URL satu per satu secara manual. Cukup:
1. Buka Postman.
2. Klik tombol **Import**.
3. Pilih file `public/swagger.yaml` dari folder proyek ini.
4. Postman akan otomatis membuat *Collection* lengkap beserta URL dan contoh Body Payload untuk API Saturt.

---

## 🔐 Catatan Tentang Autentikasi (JWT + HttpOnly Cookie)

API ini tidak menggunakan header `Authorization: Bearer <token>` konvensional. Sebagai gantinya, token JWT disimpan di dalam **HttpOnly Cookie** demi keamanan yang lebih tinggi terhadap serangan XSS.

**Alur Testing:**
1. Hit endpoint `POST /api/auth/login` dengan kredensial admin.
2. Jika berhasil, server akan merespons dengan header `Set-Cookie: token=...`.
3. Pada request selanjutnya ke endpoint *protected* (misal: `POST /api/tagihan/generate`), browser atau Postman akan otomatis mengirimkan cookie tersebut tanpa Anda perlu mengaturnya secara manual.

---
*Developed for Skill Fit Test.*
