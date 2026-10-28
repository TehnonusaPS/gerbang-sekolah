# 🏫 Analisis Flow Sistem — Gerbang Sekolah

> **Tujuan dokumen ini:** Memetakan flow ideal sebuah sistem manajemen sekolah, lalu membandingkan dengan apa yang sudah ada di project ini. Dicek satu per satu, bukan langsung semuanya.
>
> **Status:** Draft awal — perlu dikonfirmasi oleh tim sebelum lanjut ke perbaikan.

---

## 📋 Daftar Topik (Cek Satu per Satu)

| # | Topik | Status Dokumen |
|---|-------|---------------|
| 1 | [Struktur Hierarki & Peran (Role)](#1-struktur-hierarki--peran-role) | ✅ Ada di dokumen ini |
| 2 | [Onboarding & Setup Awal Sekolah](#2-onboarding--setup-awal-sekolah) | ✅ Ada di dokumen ini |
| 3 | [Flow Akademik (Kelas, Mapel, Jadwal)](#3-flow-akademik) | ✅ Ada di dokumen ini |
| 4 | [Flow Absensi Siswa & Guru](#4-flow-absensi) | ✅ Ada di dokumen ini |
| 5 | [Flow Keuangan SPP](#5-flow-keuangan-spp) | ✅ Ada di dokumen ini |
| 6 | [Flow Komunikasi & Persuratan](#6-flow-komunikasi--persuratan) | ✅ Ada di dokumen ini |
| 7 | [Flow Laporan](#7-flow-laporan) | ✅ Ada di dokumen ini |
| 8 | [Temuan Masalah & Relasi yang Rusak](#8-temuan-masalah--gap) | ✅ Ada di dokumen ini |

---

## 1. Struktur Hierarki & Peran (Role)

### 🟢 Seharusnya (Flow Ideal)

Sistem manajemen sekolah yang baik biasanya punya hierarki:

```
Super Admin (platform)
  └── Yayasan (Foundation)
        └── Sekolah (School)
              ├── Kepala Sekolah
              ├── Admin Sekolah / Tata Usaha
              ├── Guru (bisa merangkap Wali Kelas)
              ├── Siswa
              └── Orang Tua (terhubung ke Siswa tertentu)
```

Setiap level seharusnya hanya bisa akses data di lingkupnya sendiri. Contoh:
- **Super Admin** → bisa akses semua yayasan
- **Admin Yayasan** → hanya bisa akses sekolah di bawah yayasannya
- **Guru** → hanya bisa akses kelas yang diajarnya
- **Orang Tua** → hanya bisa lihat data anaknya sendiri

### 🔴 Kondisi di Project

**Role yang ada:**
```
superadmin → admin_yayasan → admin_sekolah
                              kepala_sekolah
                              tata_usaha
                              guru
                              wali_kelas
                              siswa
                              orang_tua
```

**Yang sudah benar:**
- ✅ Hierarki Superadmin → Yayasan → Sekolah sudah ada
- ✅ Role berbasis middleware di route sudah diterapkan
- ✅ User model punya `foundation_id` dan `school_id` untuk scope

**⚠️ Yang bermasalah:**

1. **`guru` dan `wali_kelas` adalah role terpisah** — padahal di sekolah nyata, wali kelas *adalah* guru yang punya tugas tambahan. Ini berarti kalau seorang guru jadi wali kelas, akun mereka perlu ganti role. Ini aneh dan bisa jadi masalah operasional.

2. **Tidak ada scope otomatis di query** — banyak controller yang belum konsisten memfilter berdasarkan `school_id` dari user yang login. Artinya kalau ada bug kecil, admin satu sekolah bisa lihat data sekolah lain.

3. **`tata_usaha` dan `admin_sekolah` terlalu mirip** — keduanya punya akses yang hampir sama di banyak route. Bedanya tidak jelas di route definition.

---

## 2. Onboarding & Setup Awal Sekolah

### 🟢 Seharusnya (Flow Ideal)

Urutan setup yang logis untuk sekolah baru:

```
1. Super Admin daftarkan Yayasan
2. Yayasan ditambahkan Sekolah
3. Sekolah pilih Kurikulum
4. Sekolah buat Tahun Ajaran (Academic Year) yang aktif
5. Sekolah buat Kelas (Classroom) untuk tahun ajaran tsb
6. Sekolah input Mata Pelajaran (Subject)
7. Sekolah daftarkan Guru → assign ke mata pelajaran + kelas
8. Sekolah daftarkan Siswa → assign ke kelas
9. Sekolah daftarkan Orang Tua → link ke siswa
10. Buat Time Slot (slot waktu)
11. Buat Jadwal Pelajaran
```

Jika langkah ini tidak berurutan, data jadi tidak lengkap/tidak konsisten.

### 🔴 Kondisi di Project

**Yang sudah ada:**
- ✅ Yayasan → Sekolah → Tahun Ajaran → Kelas → Mapel sudah ada sebagai model
- ✅ Ada kurikulum yang bisa di-assign ke yayasan/sekolah

**⚠️ Yang bermasalah:**

1. **Tidak ada flow onboarding yang terpandu** — sistem tidak memaksa urutan. Orang bisa buat kelas dulu sebelum ada tahun ajaran aktif.

2. **Kurikulum ada di dua level (Yayasan DAN Sekolah)** — `Foundation.curriculum_id` dan `School.curriculum_id` keduanya ada. Tidak jelas mana yang dipakai sebagai acuan kalau berbeda. Tidak ada logika "inherit dari yayasan kalau sekolah belum set".

3. **Orang Tua tidak ter-link otomatis ke Sekolah** — `User` untuk orang tua punya `school_id`, tapi `ParentProfile` tidak punya. Orang tua terhubung ke siswa lewat pivot table `parent_student`, tapi tidak ada validasi bahwa orang tua dan siswa dari sekolah yang sama.

4. **`StudentProfile` tidak punya `school_id`** — siswa hanya terhubung ke sekolah lewat `classroom_id → Classroom.school_id`. Ini membuat query "semua siswa di sekolah X" harus join dua tabel, dan mudah salah.

---

## 3. Flow Akademik

### 🟢 Seharusnya (Flow Ideal)

```
Setup:
  Kurikulum → CurriculumSubject (mapel wajib per kurikulum)
  Sekolah → Subject (mapel spesifik sekolah, bisa override)
  Subject → SubjectGrade (di kelas berapa mapel ini diajarkan)
  
Assign Guru:
  TeacherSubjectAssignment: Guru X mengajar Mapel Y di Kelas Z (Tahun Ajaran A)
  
Jadwal:
  TimeSlot (Senin jam 07:00-08:00)
  Schedule: Kelas X, Mapel Y, Guru Z, TimeSlot W, Hari Senin
  
Mengajar:
  Guru upload SubjectMaterial (materi/bahan ajar)
  Guru buat Assessment (tugas/ujian)
  Siswa dapat nilai → AssessmentScore

Kalender:
  AcademicYear punya AcademicCalendarEvent (libur, hari penting)
  Ada flow submit → approve dari Admin Sekolah → Admin Yayasan
  ExamSession (jadwal ujian tengah/akhir semester)
```

### 🔴 Kondisi di Project

**Yang sudah ada:**
- ✅ Alur Subject → TeacherSubjectAssignment → Schedule sudah ada
- ✅ SubjectMaterial dan Assessment ada dengan relasi ke subject, classroom, academic year
- ✅ ExamSession dengan ExamSessionSubject ada
- ✅ Kalender akademik dengan flow submit/approve/reject ada
- ✅ TeacherAgenda ada (agenda harian guru)

**⚠️ Yang bermasalah:**

1. **`TeacherSubjectAssignment` vs `Schedule` overlapping** — Keduanya menyimpan "Guru X mengajar Mapel Y di Kelas Z". Ini redunant. Seharusnya `Schedule` *mengacu* ke `TeacherSubjectAssignment`, bukan duplikasi data yang sama.

2. **`Subject` tidak terhubung ke `Curriculum`** — `Subject` punya `school_id` (subject milik sekolah), tapi tidak ada foreign key ke `CurriculumSubject`. Artinya, ada `CurriculumSubject` yang mendefinisikan mapel kurikulum, dan ada `Subject` yang mapel sekolah — tidak jelas mana yang dipakai untuk jadwal dan nilai.

3. **`SubjectGrade` model ada tapi tidak dipakai secara konsisten** — Model ada, tapi di banyak tempat pemfilteran mapel berdasarkan kelas dilakukan manual, tidak via `SubjectGrade`.

4. **`Assessment` tidak terhubung ke `TeacherSubjectAssignment`** — Guru bisa buat assessment untuk mapel yang bukan tugasnya (tidak ada validasi dari assignment).

5. **`AssessmentScore` sangat minimalis** — Hanya ada `assessment_id` dan nilai. Tidak ada `student_id` yang eksplisit di model (perlu cek migration). Jika tidak ada, laporan nilai per siswa tidak bisa dibuat.

---

## 4. Flow Absensi

### 🟢 Seharusnya (Flow Ideal)

**Absensi Siswa:**
```
Siswa datang → Scan wajah/RFID/fingerprint
→ Sistem cocokkan dengan data biometrik
→ Catat: hadir (H), terlambat (T), izin (I), sakit (S), alfa (A)
→ Admin/Wali Kelas bisa manual edit
→ Rekapitulasi bulanan
→ Alert jika absensi di bawah threshold
```

**Absensi Guru/Staf:**
```
Guru clock-in (GPS + foto)
→ Catat jam masuk
→ Bisa ajukan izin/cuti (LeaveRequest)
→ Admin approve/reject cuti
→ Rekap kehadiran
```

### 🔴 Kondisi di Project

**Yang sudah ada:**
- ✅ `StudentAttendance` dengan status H/T/I/S/A ada
- ✅ Biometrik wajah (`StudentBiometric`) dan RFID tersedia
- ✅ `StaffAttendance` dengan GPS + foto ada
- ✅ `LeaveRequest` untuk cuti guru ada
- ✅ Route scan wajah, scan RFID, dan register ada

**⚠️ Yang bermasalah:**

1. **`StudentAttendance` tidak punya relasi ke `AcademicYear`** — Absensi hanya punya `student_profile_id` dan `date`. Tidak ada `academic_year_id`. Ini berarti rekap per semester/tahun ajaran harus pakai filter tanggal, yang rawan error.

2. **`StudentAttendance` tidak punya `classroom_id`** — Absensi tidak dicatat per kelas. Kalau siswa pindah kelas di tengah tahun, rekap jadi campur.

3. **`StaffAttendance` tidak punya `academic_year_id`** — Sama dengan masalah siswa, rekap staf per tahun ajaran juga harus pakai filter tanggal.

4. **Tidak ada absensi *per mata pelajaran*** — Yang ada hanya absensi harian. Banyak sekolah juga perlu absensi per sesi pelajaran (siswa hadir secara fisik, tapi bolos jam matematika). Ini feature yang mungkin perlu didiskusikan apakah diperlukan.

5. **`LeaveRequest` tidak punya `approved_by`** — Tidak ada foreign key ke user yang approve/reject, jadi tidak ada audit trail.

---

## 5. Flow Keuangan SPP

### 🟢 Seharusnya (Flow Ideal)

```
Setup:
  Admin buat SppTariff (tarif SPP per kelas, misal Kelas 7: Rp 500.000/bulan)
  
Generate tagihan:
  Sistem otomatis buat StudentBill setiap bulan untuk semua siswa aktif
  
Pembayaran:
  Siswa/Orang Tua lihat tagihan → bayar (tunai atau online)
  
  [Online via Midtrans]:
    Buat StudentPayment → dapat URL pembayaran Midtrans
    Midtrans callback ke webhook → update status
    
  [Tunai]:
    Admin input StudentPayment manual → verifikasi
    
Satu payment bisa lunasi beberapa bill:
  StudentPaymentItem: payment X membayar bill Y sebesar Z
  
Laporan keuangan sekolah & yayasan
```

### 🔴 Kondisi di Project

**Yang sudah ada:**
- ✅ `SppTariff` → `StudentBill` → `StudentPayment` → `StudentPaymentItem` ada dan relasi sudah benar
- ✅ Integrasi Midtrans sudah ada (webhook route ada)
- ✅ Verifikasi manual ada

**⚠️ Yang bermasalah:**

1. **`StudentBill.student_id` → `User` bukan → `StudentProfile`** — Bill mengacu ke `User`, bukan `StudentProfile`. Ini inkonsisten. `StudentProfile` adalah data siswa yang proper (ada NISN, kelas, dll). Harusnya bill terhubung ke `StudentProfile` sehingga mudah query "tagihan siswa di kelas X".

2. **`SppTariff` tidak punya `academic_year_id`** — Tarif SPP bisa berubah tiap tahun ajaran. Saat ini tarif tidak terikat tahun ajaran, jadi tidak ada history tarif yang jelas.

3. **Tidak ada auto-generate tagihan** — Tidak ada job/scheduler yang otomatis membuat `StudentBill` setiap bulan. Ini harus manual atau harus dibuat.

4. **`StudentPayment` tidak jelas siapa yang bayar** — Apakah `created_by` itu admin yang input, atau siswa sendiri? Tidak ada field `paid_by` yang eksplisit untuk track apakah dibayar sendiri atau oleh orang tua.

5. **Keuangan Yayasan (Foundation Finance) terpisah dari keuangan sekolah** — Ada `FoundationPayment` untuk langganan SaaS platform-nya, tapi tidak ada koneksi ke laporan keuangan sekolah. Dua hal yang berbeda tapi bisa membingungkan.

---

## 6. Flow Komunikasi & Persuratan

### 🟢 Seharusnya (Flow Ideal)

```
Pengumuman:
  Admin/Guru buat Announcement → ditarget ke role tertentu / semua
  
Chat/Pesan:
  Pesan langsung antar user (Guru ↔ Orang Tua, dll)
  
Notifikasi:
  Sistem kirim Notification otomatis (tagihan jatuh tempo, absensi rendah, dll)
  
Persuratan:
  - Surat Keterangan Aktif Siswa (Active Student Certificate)
  - Surat Dispensasi (StudentDispensationCertificate)
  - Surat Peringatan (StudentWarningCertificate)
  
Berita/Kegiatan:
  Admin posting ActivityNews (berita sekolah/kegiatan)
  
Feedback:
  Orang Tua / Siswa bisa kirim Feedback
```

### 🔴 Kondisi di Project

**Yang sudah ada:**
- ✅ `Announcement`, `Chat/Message`, `Notification`, semua ada
- ✅ Tiga jenis surat tersedia
- ✅ `ActivityNews` dan `Feedback` ada

**⚠️ Yang bermasalah:**

1. **`Announcement` tidak punya target role/audience** — Pengumuman tidak bisa ditarget ke grup tertentu (misalnya hanya untuk orang tua kelas 7). Semua pengumuman tampak ke semua orang.

2. **`Notification` bersifat manual** — Tidak ada trigger otomatis (misal: kirim notifikasi ke orang tua kalau anaknya alfa 3 hari berturut-turut). Ini fitur yang sangat dibutuhkan sekolah.

3. **`ActiveStudentCertificate` duplikasi data** — Field `nama`, `nisn`, `kelas`, `alamat` di-copy manual ke tabel surat, padahal semua ini sudah ada di `StudentProfile`. Rentan data tidak sinkron. Seharusnya surat cukup punya `student_id` dan data di-render dari profil siswa.

4. **Chat tidak ada fitur grup** — Chat hanya 1-on-1 (`sender_id` dan `receiver_id`). Tidak ada grup chat (misal: grup orang tua kelas 7A).

5. **`ActivityNews` tidak ter-link ke sekolah secara eksplisit** — Perlu dicek apakah ada `school_id` di tabel. Jika tidak, berita dari satu sekolah bisa terlihat di sekolah lain.

---

## 7. Flow Laporan

### 🟢 Seharusnya (Flow Ideal)

```
Level Sekolah (Kepala Sekolah, Admin Sekolah, Wali Kelas, Guru):
  - Laporan Kehadiran (per siswa, per kelas, per periode)
  - Laporan Akademik (nilai per mapel, per kelas)
  - Laporan Keuangan SPP (yang lunas, yang menunggak)
  - Laporan Nilai (rapor mini)
  - Laporan Perkembangan Siswa
  - Laporan Staf

Level Yayasan (Admin Yayasan, Superadmin):
  - Konsolidasi semua sekolah
  - Laporan Akademik yayasan
  - Laporan SDM
  - Laporan Keuangan yayasan
  - Laporan Infrastruktur
  - Laporan Data Siswa
```

### 🔴 Kondisi di Project

**Yang sudah ada:**
- ✅ Route untuk semua laporan di atas sudah ada (school & foundation level)
- ✅ Pembagian akses per role sudah ada di route

**⚠️ Yang bermasalah:**

1. **Laporan bergantung pada data yang bermasalah** — Karena banyak relasi yang tidak lengkap (lihat section 8), laporan yang dihasilkan bisa tidak akurat atau error.

2. **Orang Tua punya akses laporan sekolah** — Di route, `orang_tua` bisa akses `/reports/school/attendance`. Ini terlalu luas. Orang tua seharusnya hanya bisa lihat laporan anaknya sendiri, bukan rekap seluruh kelas/sekolah.

3. **Tidak ada export PDF/Excel** — Laporan hanya dalam bentuk JSON API. Untuk kebutuhan sekolah nyata, biasanya butuh cetak laporan ke PDF atau Excel.

---

## 8. Temuan Masalah & Gap

> Ini adalah ringkasan dari semua masalah yang ditemukan di atas, diurutkan dari **paling kritis**.

### 🔴 KRITIS — Rusak / Berbahaya

| # | Masalah | Lokasi | Dampak |
|---|---------|--------|--------|
| K1 | `AssessmentScore` tidak ada `student_id` yang jelas | Model `AssessmentScore` | Nilai tidak bisa dikaitkan ke siswa → laporan nilai rusak |
| K2 | `StudentBill` → `User` bukan → `StudentProfile` | Model `StudentBill` | Query tagihan per kelas tidak bisa dilakukan dengan benar |
| K3 | Tidak ada scope sekolah otomatis di query | Banyak Controller | Data sekolah A bisa terekspos ke admin sekolah B |
| K4 | `guru` dan `wali_kelas` role terpisah | Route & Auth | Guru yang jadi wali kelas harus ganti role, kehilangan fungsi sebelumnya |

### 🟡 PERLU DIPERBAIKI — Inkonsisten / Rawan Bug

| # | Masalah | Lokasi | Dampak |
|---|---------|--------|--------|
| P1 | `TeacherSubjectAssignment` vs `Schedule` duplikasi data | Kedua model | Data bisa tidak sinkron, bingung mana yang jadi acuan |
| P2 | `StudentAttendance` tidak punya `academic_year_id` & `classroom_id` | Model `StudentAttendance` | Rekap absensi per semester/kelas rawan salah |
| P3 | Kurikulum ada di dua level tanpa logika inherit | `Foundation` & `School` | Tidak jelas kurikulum mana yang berlaku |
| P4 | `StudentProfile` tidak punya `school_id` langsung | Model `StudentProfile` | Setiap query siswa per sekolah harus join tambahan |
| P5 | `ActiveStudentCertificate` duplikasi data siswa | Model surat | Data bisa tidak sinkron dengan profil siswa sebenarnya |
| P6 | `SppTariff` tidak punya `academic_year_id` | Model `SppTariff` | Tidak bisa track history perubahan tarif per tahun |
| P7 | `LeaveRequest` tidak ada `approved_by` | Model `LeaveRequest` | Tidak ada audit trail siapa yang approve/reject |

### 🟠 FITUR HILANG — Diperlukan tapi Belum Ada

| # | Fitur | Keterangan |
|---|-------|------------|
| F1 | Auto-generate tagihan SPP bulanan | Perlu scheduler job |
| F2 | Notifikasi otomatis | Absensi rendah, tagihan jatuh tempo, dll |
| F3 | Target audience pengumuman | Pengumuman per kelas atau per role |
| F4 | Export laporan (PDF/Excel) | Kebutuhan dasar sekolah |
| F5 | Absensi per mata pelajaran | Jika diperlukan |
| F6 | Grup chat | Komunikasi grup orang tua/guru |

---

## ✅ Langkah Selanjutnya (Rekomendasi Urutan)

Karena ini dibaca satu per satu, berikut urutan topik yang disarankan untuk didiskusikan:

```
Sesi 1: Konfirmasi Flow Hierarki & Role (apakah guru = wali kelas atau beda?)
Sesi 2: Konfirmasi Flow Akademik (apa yang jadi acuan mapel: Subject atau CurriculumSubject?)
Sesi 3: Review dan perbaikan relasi kritis (K1 - K4)
Sesi 4: Klarifikasi Flow Keuangan (auto-billing? struktur tagihan?)
Sesi 5: Klarifikasi Fitur Hilang (mana yang prioritas?)
```

---

*Dokumen ini dibuat berdasarkan analisis kode sumber pada 28 Oktober 2026.*
*Referensi file: `/backend/app/Models/`, `/backend/routes/api.php`, `/backend/database/migrations/`*
