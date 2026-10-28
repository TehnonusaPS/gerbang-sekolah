# 🔐 Analisis Autentikasi — Gerbang Sekolah

> **Fokus:** Bagian autentikasi dulu — sebelum apapun diperbaiki di modul lain,
> auth harus bersih karena semua modul bergantung padanya.

---

## 📁 File-File yang Terlibat

### Backend

| File | Fungsi |
|------|--------|
| `routes/api.php` | Mendefinisikan route `/login`, `/user`, `/logout` + semua route protected |
| `AuthController.php` | Logic login, ambil user, logout |
| `RoleMiddleware.php` | Guard role per route group |
| `User.php` | Model user dengan relasi role, foundation, school |
| `Role.php` | Model role (name, label, description) |
| `RoleSeeder.php` | Seed 9 role ke database |
| `create_users_table.php` | Skema tabel users |

### Frontend

| File | Fungsi |
|------|--------|
| `authStore.js` | State management auth (Pinia) — login, logout, token, user |
| `router/index.js` | Navigation guard — proteksi route berdasarkan token & role |
| `authService.js` | HTTP calls: login, logout, getUser |
| `api.js` | Axios instance — auto-inject Bearer token, handle 401 global |
| `auth/routes.js` | Route `/login` → `Login.vue` |
| `Login.vue` | Halaman UI login |

---

## 🔄 Flow Autentikasi Saat Ini

```
[User buka browser]
       │
       ▼
[router.beforeEach dipanggil]
  ├─ route tidak butuh auth? → lanjut ✅
  └─ route butuh auth?
       ├─ tidak ada token di localStorage? → redirect /login ❌
       └─ ada token, tapi user belum di-load? → panggil initUser()
              ├─ initUser berhasil? → cek role → lanjut ✅
              └─ initUser gagal (token invalid)? → logout + redirect /login ❌

[Login.vue - User input email & password]
       │
       ▼
[authStore.login()]
  → authService.login() → POST /api/login
  → Terima { access_token, user: { id, name, email, role, school_id, ... } }
  → Simpan token ke localStorage
  → Simpan user ke Pinia state
  → Connect WebSocket (Echo)
  → Fetch unread counts
  → Setup global listeners

[Backend - AuthController.login()]
  1. Validasi email + password
  2. Cari user by email (dengan relasi role, foundation, school)
  3. Cek Hash password
  4. Cek is_active
  5. Buat Sanctum token
  6. Return token + mapped user data

[Route protected - misal /dashboard]
  → middleware auth:sanctum (Laravel) → cek Bearer token
  → middleware role:xxx → cek role user
```

---

## ✅ Yang Sudah Benar

- **Token flow sudah benar** — Sanctum Bearer token, disimpan di localStorage, di-inject otomatis via Axios interceptor
- **Route guard sudah ada** — `router.beforeEach` memeriksa token + role sebelum masuk halaman
- **Global 401 handler** — `api.js` interceptor redirect ke `/login` kalau token expired
- **Role middleware backend** — `RoleMiddleware` memeriksa role user per route group
- **`initUser()` saat refresh** — kalau halaman di-refresh, user di-load ulang dari server (bukan dari localStorage)
- **Logout bersih** — token dihapus di server (bukan hanya di client), WebSocket disconnect, state dibersihkan
- **`is_active` di-cek saat login** — akun dinonaktifkan tidak bisa masuk

---

## ❌ Masalah yang Ditemukan

### 🔴 KRITIS

#### AUTH-K1: Role guard frontend pakai `to.meta.role` DAN `to.meta.roles` — tidak konsisten

Di `router/index.js` baris 132–138:

```js
// Versi singular
if (to.meta.role && auth.user?.role !== to.meta.role) {
  return '/login'
}

// Versi plural
if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
  return '/login'
}
```

Ada **dua format** meta yang berbeda (`role` vs `roles`). Kalau developer salah tulis `role` padahal harusnya `roles`, guard tidak berjalan sama sekali — route tidak terproteksi. **Harus distandarisasi ke satu format.**

---

#### AUTH-K2: Saat role tidak sesuai, redirect ke `/login` bukan `/403`

```js
if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
  return '/login'  // ← SALAH
}
```

User yang sudah login tapi salah role akan dikirim ke halaman login lagi — membingungkan dan terasa seperti bug. Seharusnya redirect ke halaman `/403 Forbidden` atau `/dashboard`.

---

#### AUTH-K3: Token disimpan hanya di `localStorage` — rentan XSS

Token disimpan di `localStorage`. Kalau ada celah XSS di mana saja di frontend, token bisa dicuri. Standar yang lebih aman adalah `httpOnly cookie`. Ini tradeoff antara kemudahan vs keamanan — perlu didiskusikan apakah perlu diubah.

---

#### AUTH-K4: `baseURL` di `api.js` hardcoded `localhost`

```js
baseURL: 'http://127.0.0.1:8000/api',  // ← hardcoded!
// baseURL: 'https://school-app-ewoy.onrender.com/api',  // ini di-comment
```

URL production di-comment manual. Ini **pasti error** kalau di-deploy — developer harus ingat ganti manual. Harus pakai environment variable dari `.env`.

---

### 🟡 PERLU DIPERBAIKI

#### AUTH-P1: `role_id` di tabel `users` tidak ada foreign key constraint yang terlihat jelas

Di migration `create_users_table.php`:
```php
$table->unsignedBigInteger('role_id');  // ← tidak ada foreignId / constrained()
```
Perlu dicek apakah sudah ditambahkan di migration terpisah `add_foreign_keys_to_users_table.php`.

---

#### AUTH-P2: Tidak ada halaman register / reset password

Saat ini hanya ada `/login`. Tidak ada:
- Halaman lupa password / reset password
- Fitur "Pertama kali login, ganti password" (untuk akun yang dibuat admin)

Untuk sekolah, biasanya akun dibuat oleh admin dan siswa/guru **wajib ganti password** saat pertama login.

---

#### AUTH-P3: Login response tidak include data profil siswa/guru

Saat login, response user adalah:
```json
{
  "id": "...",
  "name": "...",
  "role": "siswa",
  "school_id": "...",
  ...
}
```

Tidak ada `classroom_id` atau data profil spesifik. Ini berarti setiap kali siswa/guru akses fitur, backend harus query ulang profil mereka.

---

#### AUTH-P4: `api.js` hapus `localStorage.removeItem('user')` yang tidak dipakai

```js
localStorage.removeItem('token')
localStorage.removeItem('user')  // ← 'user' tidak pernah disimpan ke localStorage
```

User disimpan di Pinia state, bukan di localStorage. Sisa kode lama.

---

#### AUTH-P5: Tidak ada rate limiting di route `/login`

Route `POST /api/login` tidak ada perlindungan dari brute force. Tinggal tambahkan:

```php
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
// max 5 percobaan per menit
```

---

### 🟠 FITUR YANG HILANG

| ID | Fitur | Keterangan |
|----|-------|------------|
| AUTH-F1 | Halaman 403 Forbidden | Pesan jelas kalau role tidak diizinkan, bukan redirect ke login |
| AUTH-F2 | Reset password / lupa password | Alur email reset password |
| AUTH-F3 | Ganti password saat pertama login | Flag `must_change_password` di user |
| AUTH-F4 | Session management | Lihat & revoke sesi aktif dari perangkat lain |
| AUTH-F5 | Audit log login | Log kapan/dari mana user login/logout |

---

## 📋 Checklist Pekerjaan (Urutan Prioritas)

```
[ ] AUTH-K4  Pindahkan baseURL ke environment variable (.env)
[ ] AUTH-K1  Standarisasi meta.roles (hapus meta.role singular)
[ ] AUTH-K2  Buat halaman /403 dan redirect ke sana kalau role salah
[ ] AUTH-P5  Tambahkan throttle ke route /login (anti brute force)
[ ] AUTH-P4  Hapus localStorage.removeItem('user') yang tidak dipakai
[ ] AUTH-P1  Konfirmasi foreign key constraint role_id sudah ada
[ ] AUTH-P3  Pertimbangkan include profile data di response login
[ ] AUTH-F2  Buat fitur reset password (perlu email setup)
[ ] AUTH-F3  Buat flag must_change_password + halaman ganti password pertama
[ ] AUTH-F1  Buat halaman 403 Forbidden
```

---

## 🗂️ Relasi Auth ke Modul Lain

Semua modul bergantung pada data yang dikirim saat login:

```
authStore.user.role          → penentu menu sidebar, akses halaman
authStore.user.school_id     → scope data sekolah di setiap API call
authStore.user.foundation_id → scope data yayasan
authStore.user.id            → WebSocket channel, chat, notifikasi
```

Kalau data ini salah atau tidak lengkap saat login → semua modul bisa error.

---

*File ini dibuat: 28 Oktober 2026*
*Referensi: `backend/routes/api.php`, `AuthController.php`, `RoleMiddleware.php`, `authStore.js`, `router/index.js`*
