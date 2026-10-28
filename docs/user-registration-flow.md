# 👤 Analisis Flow Registrasi User — Gerbang Sekolah

> **Fokus:** Siapa yang bisa buat user apa, bagaimana prosesnya, dan apa yang masih bermasalah.
> Ini adalah bagian dari auth yang paling kompleks dan paling rawan salah.

---

## 🗺️ Peta: Siapa yang Buat Siapa

```
SUPERADMIN
  ├─ Bisa buat: SEMUA role (termasuk admin_yayasan)
  ├─ Controller: UserController + TeacherController + StudentController
  └─ Scope: bebas, bisa pilih foundation_id & school_id apapun

ADMIN YAYASAN
  ├─ Bisa buat: semua role KECUALI superadmin
  ├─ Scope: hanya user di yayasannya sendiri
  └─ Bisa assign ke sekolah manapun dalam yayasannya

ADMIN SEKOLAH
  ├─ Bisa buat: semua role KECUALI superadmin & admin_yayasan
  ├─ Scope: hanya user di sekolahnya sendiri
  └─ Tidak bisa pilih foundation/school lain

KEPALA SEKOLAH / TATA USAHA / GURU / WALI KELAS
  └─ TIDAK bisa buat user apapun (forbidden 403)
```

---

## 🔄 Flow Pembuatan User (Backend)

### Endpoint yang Tersedia

| Method | Endpoint | Controller | Keterangan |
|--------|----------|------------|------------|
| POST | `/api/management/users` | `UserController@store` | Buat user + profil (semua role) |
| POST | `/api/management/teachers` | `TeacherController@store` | Khusus guru (dengan assignment mapel) |
| POST | `/api/management/students` | `StudentController@store` | Khusus siswa (dengan foto wajah dll) |

> ⚠️ **Ada tiga pintu masuk yang berbeda untuk buat user!**
> Ini adalah masalah utama yang perlu dievaluasi.

---

### Flow `UserController@store` (route `/management/users`)

```
Request masuk
  │
  ├─ Cek apakah yang request adalah superadmin / admin_yayasan / admin_sekolah
  │   └─ Selain itu → 403 Forbidden
  │
  ├─ Validasi input (name, email, password, role_id, dll)
  │
  ├─ Cek role yang akan di-assign:
  │   ├─ admin_yayasan: tidak boleh assign superadmin
  │   └─ admin_sekolah: tidak boleh assign superadmin & admin_yayasan
  │
  ├─ Set foundation_id & school_id berdasarkan siapa yang buat:
  │   ├─ superadmin → bebas dari request
  │   ├─ admin_yayasan → foundation_id auto = milik mereka, school_id dari request
  │   └─ admin_sekolah → keduanya auto = milik mereka
  │
  ├─ Buat User record
  │
  └─ (opsional) Buat profil berdasarkan kategori role:
      ├─ guru/wali_kelas/kepala_sekolah/tata_usaha/admin_sekolah → TeacherProfile
      ├─ siswa → StudentProfile
      └─ orang_tua → ParentProfile
```

---

## ✅ Yang Sudah Benar

- **Hierarki pembuatan user sudah ada** — superadmin > admin_yayasan > admin_sekolah, tidak bisa melampaui level sendiri
- **Scope sudah dikunci per level** — admin_sekolah tidak bisa buat user di sekolah lain
- **Profil otomatis dibuat bersamaan** — dalam satu transaksi DB (atomic)
- **Foreign key users sudah benar** — `role_id`, `foundation_id`, `school_id` semua ada constraint-nya
- **Role dropdown sudah di-filter per level** — `getRoles()` menyesuaikan role yang bisa dipilih
- **Self-delete dicegah** — tidak bisa hapus akun sendiri
- **Ganti kategori role di-handle** — kalau guru di-update jadi siswa, TeacherProfile lama dihapus, StudentProfile baru dibuat

---

## ❌ Masalah yang Ditemukan

### 🔴 KRITIS

#### REG-K1: Ada TIGA controller terpisah untuk buat user yang sama — tidak konsisten

Untuk membuat user, ada tiga jalur berbeda:

| Jalur | Controller | Dipakai untuk |
|-------|------------|---------------|
| `POST /management/users` | `UserController` | Buat semua jenis user |
| `POST /management/teachers` | `TeacherController` | Buat guru |
| `POST /management/students` | `StudentController` | Buat siswa |

**Masalah:**
- `TeacherController` dan `StudentController` punya logika pembuatan user sendiri yang berbeda dari `UserController`
- Validasi dan mapping field bisa berbeda antar controller
- Kalau ada bug, harus diperbaiki di **tiga tempat**
- Developer tidak tahu harus pakai yang mana — rawan duplikasi data

**Seharusnya:** Satu endpoint untuk buat user, dengan parameter `role` yang menentukan profil apa yang dibuat.

---

#### REG-K2: `kepala_sekolah` dan `tata_usaha` tidak bisa akses halaman manajemen user, padahal logic bisnis membutuhkannya

Di route:
```php
Route::middleware('role:superadmin,admin_yayasan,admin_sekolah,kepala_sekolah,tata_usaha,wali_kelas')
    ->prefix('management')
```

Kepala sekolah dan tata usaha **bisa akses route `/management/users`**, tapi di dalam `UserController@store`:
```php
if (!$isSuperAdmin && !$isAdminYayasan && !$isAdminSekolah) {
    return 403; // kepala_sekolah & tata_usaha masuk sini!
}
```

**Mereka bisa masuk halaman tapi tidak bisa buat user** → Kebingungan UX. Perlu diputuskan: apakah kepala_sekolah dan tata_usaha boleh buat user atau tidak?

---

#### REG-K3: Orang tua tidak ter-link ke sekolah saat registrasi

Saat buat user `orang_tua`:
- `User.school_id` di-set ke sekolah yang membuat
- Tapi `ParentProfile` tidak punya `school_id`
- Link antara orang tua dan siswa (`parent_student` pivot) **tidak dibuat saat registrasi** — harus dilakukan manual terpisah

**Ini berarti:** Orang tua yang baru terdaftar **tidak terhubung ke anaknya** sampai ada proses linking manual. Tidak ada UI/endpoint yang jelas untuk ini di `UserController`.

---

### 🟡 PERLU DIPERBAIKI

#### REG-P1: `wali_kelas` masuk route middleware management tapi tidak bisa buat user

Di route definition:
```php
Route::middleware('role:superadmin,admin_yayasan,admin_sekolah,kepala_sekolah,tata_usaha,wali_kelas')
```

Tapi `wali_kelas` tidak ada di kondisi if `UserController`. Artinya wali_kelas bisa akses endpoint tapi selalu dapat 403. **Kenapa wali_kelas ada di middleware tapi tidak ada di controller?**

---

#### REG-P2: Validasi `classroom_id` siswa tidak dicek apakah classroom milik sekolah yang sama

Saat membuat siswa:
```php
'profile.classroom_id' => 'nullable|exists:classrooms,id',
```

Validasi hanya cek apakah classroom ada, **tidak cek apakah classroom itu milik sekolah yang sama**. Admin sekolah A bisa assign siswa ke kelas sekolah B.

---

#### REG-P3: Password bisa terlalu sederhana — minimum hanya 6 karakter

```php
'password' => 'required|string|min:6',
```

Tidak ada validasi kompleksitas password. Untuk aplikasi sekolah yang menyimpan data anak-anak, sebaiknya minimal ada aturan kombinasi huruf + angka.

---

#### REG-P4: `TeacherController` punya helper `resolveTenantId()` yang parsing format "S001/Y001" — tidak konsisten dengan `UserController`

`TeacherController` menerima `unit_kerja` dalam format `"S0001"` (sekolah) atau `"Y0001"` (yayasan), lalu di-parse manual. Ini format custom yang tidak ada di controller lain. Rawan salah dan tidak terdokumentasi.

---

#### REG-P5: Tidak ada notifikasi/email ke user baru setelah akun dibuat

Saat admin buat akun guru/siswa, user tidak mendapat pemberitahuan bahwa akun mereka sudah dibuat. User perlu diberitahu secara manual tentang email dan password. Seharusnya ada email otomatis yang dikirim.

---

### 🟠 FITUR YANG HILANG

| ID | Fitur | Keterangan |
|----|-------|------------|
| REG-F1 | Link orang tua ke siswa saat registrasi | Saat buat akun orang_tua, langsung bisa pilih siswa mana anaknya |
| REG-F2 | Import bulk siswa/guru via CSV/Excel | Untuk sekolah yang punya banyak siswa, input satu-satu tidak praktis |
| REG-F3 | Email notifikasi ke user baru | Kirim email berisi username + password sementara |
| REG-F4 | Flag `must_change_password` | Paksa ganti password saat pertama login |
| REG-F5 | Nonaktifkan vs Hapus user | Saat ini hanya ada hard delete. Seharusnya bisa dinonaktifkan dulu (soft delete / is_active = false) tanpa hapus histori |

---

## 📊 Ringkasan Siapa Buat Siapa (Matrix)

| Yang Membuat ↓ \ Role yang Dibuat → | superadmin | admin_yayasan | kepala_sekolah | admin_sekolah | tata_usaha | guru | wali_kelas | siswa | orang_tua |
|--------------------------------------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **superadmin** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **admin_yayasan** | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **admin_sekolah** | ❌ | ❌ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| **kepala_sekolah** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **tata_usaha** | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

> ⚠️ **Pertanyaan untuk tim:** Apakah tata_usaha seharusnya bisa daftarkan siswa & orang tua?
> Di banyak sekolah, tata usaha adalah yang paling sering entry data siswa baru.

---

## 📋 Checklist Pekerjaan

```
[ ] REG-K1  Konsolidasi: 3 controller (User/Teacher/Student) → idealnya 1 endpoint unified
[ ] REG-K2  Tentukan: kepala_sekolah & tata_usaha boleh buat user atau tidak? → update middleware & controller
[ ] REG-K3  Buat endpoint/flow untuk link orang_tua ↔ siswa saat/setelah registrasi
[ ] REG-P2  Validasi classroom_id harus dari sekolah yang sama
[ ] REG-P4  Hapus format "S0001/Y0001" di TeacherController, seragamkan dengan integer ID
[ ] REG-F2  Pertimbangkan fitur import bulk siswa via CSV
[ ] REG-F3  Kirim email notifikasi ke user baru
[ ] REG-F4  Tambah flag must_change_password
```

---

## ❓ Pertanyaan yang Perlu Dijawab Tim

1. **Apakah `tata_usaha` boleh daftarkan siswa baru?** — Ini sangat umum di sekolah nyata.
2. **Siapa yang menghubungkan orang tua ke siswa?** — Admin sekolah? Atau orang tua sendiri saat pertama login?
3. **Apakah perlu fitur daftar mandiri?** — Atau semua akun selalu dibuat oleh admin?
4. **Apakah `kepala_sekolah` perlu bisa lihat tapi tidak bisa buat user?** — Sekarang bisa akses halaman tapi selalu 403.

---

*File ini dibuat: 28 Oktober 2026*
*Referensi: `UserController.php`, `TeacherController.php`, `StudentController.php`, `RoleSeeder.php`, `routes/api.php`*
