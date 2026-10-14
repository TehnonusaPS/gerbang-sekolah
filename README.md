# 🏫 Gerbang Sekolah — Sistem Informasi Manajemen Sekolah

> Platform terpadu untuk pengelolaan administrasi, akademik, keuangan, dan komunikasi sekolah.

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-8.x-646CFF?style=for-the-badge&logo=vite&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-4.x-06B6D4?style=for-the-badge&logo=tailwind-css&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)

---

## 📋 Daftar Isi

- [Arsitektur Proyek](#️-arsitektur-proyek)
- [Stack Teknologi](#-stack-teknologi)
- [Prasyarat](#-prasyarat)
- [Struktur Proyek](#-struktur-proyek)
- [Cara Setup (Clone dari Git)](#-cara-setup-clone-dari-git)
- [Menjalankan Aplikasi](#️-menjalankan-aplikasi)
- [Modul Aplikasi](#-modul-aplikasi)
- [Konvensi & Struktur Folder Frontend](#️-konvensi--struktur-folder-frontend)
- [Perintah yang Sering Dipakai](#️-perintah-yang-sering-dipakai)
- [Troubleshooting](#-troubleshooting)

---

## 🏗️ Arsitektur Proyek

Proyek ini menggunakan arsitektur **Decoupled (Terpisah)** — frontend dan backend berjalan sebagai dua aplikasi independen yang berkomunikasi via REST API.

```
Browser (Vue 3 SPA)
     │
     │  HTTP Request (Axios)
     ▼
frontend/  ─── Vue 3 + Vite + TailwindCSS
     │
     │  REST API (JSON)
     ▼
backend/  ──── Laravel 12 + Sanctum (API Auth)
     │
     ▼
Database (SQLite / MySQL)
```

> **Intinya:** Backend hanya bertugas menyediakan data via API JSON. Frontend bertugas merender tampilan dan mengelola state. Keduanya berjalan di port yang berbeda.

---

## 🔧 Stack Teknologi

### Frontend (`frontend/`)

| Layer | Teknologi | Versi |
|---|---|---|
| Framework UI | Vue.js | 3.x |
| Build Tool | Vite | 8.x |
| Styling | Tailwind CSS | 4.x |
| UI Component | shadcn-vue / Reka UI | Latest |
| State Management | Pinia | 3.x |
| Routing | Vue Router | 5.x |
| HTTP Client | Axios | 1.x |
| Form Validation | VeeValidate + Zod | 4.x |
| Tabel | TanStack Vue Table | 8.x |
| Animasi | @vueuse/motion | 3.x |

### Backend (`backend/`)

| Layer | Teknologi | Versi |
|---|---|---|
| Framework | Laravel | 12.x |
| Auth API | Laravel Sanctum | 4.x |
| PHP | PHP | 8.2+ |
| Database | SQLite (dev) / MySQL (prod) | — |
| Package Manager | Composer + npm | Latest |

---

## ✅ Prasyarat

Pastikan sudah terinstal di komputer Anda:

| Software | Versi Minimum | Cara Cek |
|---|---|---|
| PHP | **8.2+** | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 20.x+ | `node -v` |
| npm | 10.x+ | `npm -v` |
| Git | Latest | `git --version` |

> SQLite sudah tersedia secara bawaan di PHP, tidak perlu instalasi terpisah untuk development.

---

## 📁 Struktur Proyek

```
school-app/
├── frontend/                           ← Aplikasi Vue 3 (SPA)
│   ├── src/
│   │   ├── App.vue                     ← Root component
│   │   ├── main.js                     ← Entry point Vue + Pinia + Router
│   │   ├── style.css                   ← Global CSS (Tailwind)
│   │   ├── components/                 ← Komponen UI reusable
│   │   ├── composables/                ← Vue composables (logika reusable)
│   │   ├── layouts/                    ← Layout wrapper (MainLayout, dll)
│   │   ├── modules/                    ← ⭐ Fitur-fitur utama aplikasi
│   │   │   ├── auth/
│   │   │   ├── dashboard/
│   │   │   ├── akademik/
│   │   │   ├── absensi/
│   │   │   ├── keuangan/
│   │   │   ├── komunikasi/
│   │   │   ├── laporan/
│   │   │   ├── manajemen-data/
│   │   │   └── ...
│   │   ├── router/                     ← Konfigurasi Vue Router
│   │   ├── services/                   ← API service calls (Axios)
│   │   ├── stores/                     ← Pinia stores (global state)
│   │   └── utils/                      ← Fungsi helper
│   ├── public/                         ← Aset statis (favicon, dll)
│   ├── index.html                      ← Root HTML
│   ├── vite.config.js                  ← Konfigurasi Vite
│   └── package.json
│
├── backend/                            ← Aplikasi Laravel (REST API)
│   ├── app/
│   │   └── Http/
│   │       └── Controllers/            ← API Controllers
│   ├── database/
│   │   ├── migrations/                 ← Migrasi database
│   │   └── seeders/                    ← Seed data
│   ├── routes/
│   │   └── api.php                     ← ⭐ Semua endpoint API di sini
│   ├── .env.example                    ← Template konfigurasi environment
│   └── composer.json
│
└── README.md                           ← File ini
```

---

## 🚀 Cara Setup (Clone dari Git)

Ikuti langkah-langkah berikut **secara berurutan** setelah melakukan `git clone`:

### Langkah 1 — Clone Repository

```bash
git clone https://github.com/<username>/school-app.git
cd school-app
```

---

### 🔵 Setup Backend

### Langkah 2 — Install PHP Dependencies

```bash
cd backend
composer install
```

### Langkah 3 — Buat File Environment

```bash
cp .env.example .env
php artisan key:generate
```

### Langkah 4 — Konfigurasi Database

Secara default, proyek menggunakan **SQLite** (tidak perlu setup tambahan). Pastikan `.env` berisi:

```dotenv
DB_CONNECTION=sqlite
```

Jika ingin menggunakan **MySQL**, ubah konfigurasi database di `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_app
DB_USERNAME=root
DB_PASSWORD=
```

> ⚠️ **Jika menggunakan MySQL:** Buat database `school_app` terlebih dahulu sebelum menjalankan migrasi.

### Langkah 5 — Jalankan Migrasi & Seeder

```bash
php artisan migrate
php artisan db:seed
```

---

### 🟢 Setup Frontend

### Langkah 6 — Masuk ke Direktori Frontend

```bash
cd ../frontend
```

### Langkah 7 — Install Node.js Dependencies

```bash
npm install
```

---

## ▶️ Menjalankan Aplikasi

Buka **dua terminal** secara bersamaan:

**Terminal 1 — Backend Laravel (dari folder `backend/`):**
```bash
php artisan serve
```
Backend API berjalan di: **http://127.0.0.1:8000**

**Terminal 2 — Frontend Vue (dari folder `frontend/`):**
```bash
npm run dev
```
Aplikasi dapat diakses di: **http://localhost:5173**

> 💡 **Catatan penting:**
> - Akses aplikasi melalui **http://localhost:5173** (frontend Vue)
> - URL `http://127.0.0.1:8000` adalah backend API — jangan diakses langsung dari browser
> - Kedua terminal **harus berjalan bersamaan** saat development

---

## 📦 Modul Aplikasi

Fitur-fitur aplikasi diorganisir dalam folder `src/modules/`:

| Modul | Deskripsi |
|---|---|
| `auth/` | Login, register, dan manajemen sesi |
| `dashboard/` | Halaman beranda & ringkasan data |
| `akademik/` | Data mata pelajaran, kelas, jadwal |
| `absensi/` | Pencatatan kehadiran siswa & guru |
| `keuangan/` | Manajemen pembayaran & laporan keuangan |
| `komunikasi/` | Pengumuman dan pesan internal |
| `laporan/` | Generasi laporan akademik & administrasi |
| `manajemen-data/` | CRUD data siswa, guru, dan staf |
| `akun-setting/` | Pengaturan profil dan akun pengguna |
| `landing/` | Halaman publik / beranda sekolah |

---

## 🗂️ Konvensi & Struktur Folder Frontend

### Penamaan File

| Jenis | Lokasi | Contoh |
|---|---|---|
| Halaman / View | `modules/<modul>/pages/` | `modules/akademik/pages/Index.vue` |
| Komponen modul | `modules/<modul>/components/` | `modules/akademik/components/MataPelajaranCard.vue` |
| Komponen reusable | `components/ui/` | `components/ui/Button.vue` |
| Layout | `layouts/` | `layouts/MainLayout.vue` |
| State (Pinia) | `stores/` | `stores/auth.js` |
| API calls | `services/` | `services/akademik.js` |

### Pola Pemanggilan API

```js
// services/akademik.js
import axios from '@/lib/axios';

export const getMataPelajaran = () => axios.get('/api/mata-pelajaran');
export const createMataPelajaran = (data) => axios.post('/api/mata-pelajaran', data);
```

```vue
<!-- Menggunakan di komponen -->
<script setup>
import { getMataPelajaran } from '@/services/akademik';
import { ref, onMounted } from 'vue';

const mataPelajaran = ref([]);

onMounted(async () => {
  const { data } = await getMataPelajaran();
  mataPelajaran.value = data;
});
</script>
```

---

## 🛠️ Perintah yang Sering Dipakai

### Backend (dari folder `backend/`)

```bash
# Jalankan server development
php artisan serve

# Buat migration baru
php artisan make:migration create_nama_table

# Jalankan migrasi
php artisan migrate

# Rollback migrasi terakhir
php artisan migrate:rollback

# Buat model + migration + controller sekaligus
php artisan make:model NamaModel -mc

# Buat controller resource (API)
php artisan make:controller NamaController --api

# Lihat semua route
php artisan route:list

# Jalankan seeder
php artisan db:seed

# Clear semua cache (jalankan jika ada perilaku aneh)
php artisan optimize:clear
```

### Frontend (dari folder `frontend/`)

```bash
# Jalankan development server
npm run dev

# Build untuk production
npm run build

# Preview hasil build
npm run preview

# Format kode dengan Prettier
npm run format
```

---

## 🔍 Troubleshooting

### ❌ Error: `php artisan serve` — port sudah dipakai

```bash
# Gunakan port lain
php artisan serve --port=8001
```

### ❌ Error: `CORS` saat frontend memanggil API

Pastikan konfigurasi CORS di backend (`config/cors.php`) sudah mengizinkan origin `http://localhost:5173`:

```php
'allowed_origins' => ['http://localhost:5173'],
```

### ❌ Error: `Unauthenticated` saat memanggil API yang dilindungi

Pastikan token Sanctum disertakan di setiap request dari frontend, dan konfigurasi Sanctum sudah benar di `.env`.

### ❌ Error: `npm run build` gagal setelah pull terbaru

```bash
npm install        # install dependensi yang mungkin bertambah
npm run build
```

### ❌ Perubahan `.vue` tidak ter-refresh otomatis

Pastikan `npm run dev` sedang berjalan di terminal terpisah dan halaman diakses via `http://localhost:5173`.

### ❌ Error migrasi: tabel sudah ada

```bash
php artisan migrate:fresh --seed   # ⚠️ Akan menghapus semua data!
```

---

## 🤝 Alur Kerja Tim (Git Workflow)

```bash
# Sebelum mulai bekerja, selalu pull terbaru
git pull origin main

# Buat branch baru untuk fitur/perbaikan
git checkout -b feat/nama-fitur

# Setelah selesai coding
git add .
git commit -m "feat: deskripsi perubahan"
git push origin feat/nama-fitur

# Buat Pull Request ke main di GitHub
```

> ⚠️ **Jangan pernah commit file `.env`!** File `.env` sudah masuk `.gitignore`.
> Setiap anggota tim harus membuat file `.env` sendiri berdasarkan `.env.example`.

---

## 📚 Referensi

| Dokumentasi | Link |
|---|---|
| Laravel 12 | https://laravel.com/docs/12.x |
| Vue.js 3 | https://vuejs.org |
| Vue Router | https://router.vuejs.org |
| Pinia | https://pinia.vuejs.org |
| Vite | https://vitejs.dev |
| Tailwind CSS v4 | https://tailwindcss.com |
| shadcn-vue | https://www.shadcn-vue.com |
| Laravel Sanctum | https://laravel.com/docs/12.x/sanctum |

---

<p align="center">
  Dibuat dengan ❤️ untuk kemajuan pendidikan Indonesia
</p>
