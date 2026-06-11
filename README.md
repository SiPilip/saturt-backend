# SatuRT - Aplikasi Manajemen RT - JagoanHosting

SatuRT adalah aplikasi untuk manajemen Rukun Tetangga (RT) yang mencakup fungsionalitas untuk mengelola penghuni, rumah, generate tagihan bulanan, pencatatan iuran, hingga laporan keuangan dan export file ke Excel.
- Backend: Pure REST API dengan Laravel 11
- Frontend: React + Vite + Shadcn UI

---

## Requirements

### Kebutuhan Backend (Server)
- **PHP**: 8.3.31
- **Composer**: Versi 2.4.1
- **Database**: MySQL 8.0.30
- **Ekstensi PHP**:
  - ext-pdo
  - ext-mbstring
  - ext-openssl
  - ext-gd
  - ext-zip
  - ext-fileinfo

### Kebutuhan Frontend (Client)
- **Node.js**: 24.8.0
- **NPM**: 11.6.0

---

## Panduan Instalasi (Step-by-Step)

### Setup Backend (Laravel)

```bash
git clone https://github.com/SiPilip/saturt-backend
cd saturt-backend
composer install
```

**Buat Database MySQL**, buat database kosong dengan nama: `saturt_db`

**Konfigurasi Environment (.env)**
Salin file `.env.example` menjadi `.env`. Di Windows jalankan:
```bash
copy .env.example .env
```

Jalankan perintah setup berikut:
```bash
php artisan key:generate 
php artisan jwt:secret 
php artisan storage:link
php artisan migrate --seed
php artisan serve
```

---

### Setup Frontend (React + Vite)

*Buka terminal baru untuk frontend.*

```bash
cd saturt-frontend
npm install
npm run dev
```

---

## Kredensial Login 

- **NIK:** `3271000000000001`
- **Password:** `admin123`

---
*Developed for Skill Fit Test.*
