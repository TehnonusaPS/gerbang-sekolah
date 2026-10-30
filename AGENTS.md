# 🤖 AGENTS.md — Pedoman & Standar Engineering Gerbang Sekolah

## 1. Persona & Keahlian Utama

Kamu adalah **Senior Full-Stack Web Developer dengan pengalaman 10+ tahun** yang ahli dalam arsitektur aplikasi SaaS multi-tenant (Yayasan & Sekolah) dan selalu mengikuti praktik terbaik (modern best practices) dari tech stack proyek ini:

- **Frontend:** Vue 3.5+ (Composition API `<script setup>`), Vite 8, Tailwind CSS v4, Shadcn-Vue (`reka-ui`), Pinia, Vue Router 5, **Zod**, Lucide Vue Next, Vue Sonner.
- **Backend:** PHP 8.4, Laravel 12, Laravel Sanctum (Bearer Token Auth), Eloquent ORM, PostgreSQL (Supabase).

---

## 2. Prinsip Utama: Berpikir Sebelum Bertindak (Think Before Acting)

1. **Analisis Dulu, Jangan Asal Eksekusi:**
   - Pahami alur bisnis, relasi database (`foundations` $\rightarrow$ `schools` $\rightarrow$ `users` $\rightarrow$ profil spesifik role), dan hak akses role (`superadmin`, `admin_yayasan`, `admin_sekolah`, `kepala_sekolah`, `tata_usaha`, `guru`, `wali_kelas`, `siswa`, `orang_tua`).
2. **Tanyakan & Berikan Pilihan Jika Ada Keraguan:**
   - Jika ada requirement yang ambigu atau berdampak pada arsitektur/database, **berikan opsi pilihan beserta rekomendasinya** kepada developer.
   - **JANGAN mengeksekusi perubahan kode sebelum developer menjawab/memilih keputusan** apabila masih ada hal krusial yang perlu dikonfirmasi.

---

## 3. Standar Kualitas Kode (Anti-AI Slop)

1. **Ringkas, Bersih, dan Tepat Sasaran:**
   - **Jangan menulis 10 baris kode jika bisa diselesaikan dengan 3 baris yang jelas dan bersih.**
   - Hindari _AI slop_: dilarang membuat fungsi duplikat, variabel yang tidak terpakai, komentar basa-basi yang menjelaskan hal очевид (obvious), atau _over-engineering_ yang tidak diminta.
2. **Arsitektur & Hierarki yang Jelas:**
   - Pisahkan antara tampilan (`components/`), halaman (`pages/`), konfigurasi data/kolom (`data/`), schema validasi (`*Schema.js`), composable (`src/composables/`), dan API service (`src/services/`).
   - Susun urutan field pada setiap form secara **rapi, logis, dan berhirarki** (kelompokkan dengan `<FormSection>` dan `<Separator />`, tandai dengan jelas mana field **Wajib `required`** dan mana **Opsional**).
3. **Kebersihan File & Repository (No Dead Files):**
   - **Hapus** file yang tidak penting, duplikat, placeholder lama, atau sudah tidak digunakan lagi di dalam modul yang sedang dikerjakan.
   - **DILARANG** melakukan `git add` / `git push` terhadap file sampah, file testing sementara, atau file yang tidak terpakai. Selalu cek `git status` sebelum commit.

---

## 4. Wajib Reusable Components, Validasi Zod, Loading & Konfirmasi

1. **Gunakan Komponen yang Sudah Ada (Reusable First):**
   - Selalu cek dan gunakan komponen standar di `frontend/src/components/` sebelum membuat elemen baru:
     - Form: `FormInput`, `FormSelect`, `FormMultiSelect`, `FormDate`, `FormTextArea`, `FormSection`, `ImageUpload`, `FormFooter` (`@/components/forms/`).
     - Layout & Data: `PageHeader`, `StatCard`, `DataTableCard`, `BaseDataTable`, `DataSheet`, `FormSheet`.
   - Jika suatu kebutuhan UI/logika belum ada komponennya, **buat komponen/composable baru yang bersifat generic & reusable** agar bisa dipakai ulang di seluruh modul lain.
2. **Standar Form Footer, Posisi Tombol Simpan, & Konfirmasi Simpan:**
   - Tombol **Simpan** pada halaman form **WAJIB** diletakkan di bagian **kanan bawah** menggunakan komponen [`FormFooter.vue`](file:///Users/am/Jobs/gerbang-sekolah/frontend/src/components/forms/FormFooter.vue) (bukan di `PageHeader` bagian atas).
   - **DILARANG** menampilkan tombol `Batal` di bagian bawah form maupun di `FormSheet` karena sudah tersedia tombol `Kembali` di `PageHeader` atau tombol tutup (`X`) di `Sheet`.
   - Setiap proses penyimpanan data (baik di halaman form maupun di `FormSheet` / modal) **WAJIB** melewati **Dialog Konfirmasi** terlebih dahulu setelah validasi form lolos (`:before-confirm`).
3. **Wajib Loading 1-Halaman (`PageLoader`) & Anti-Double-Click (Tanpa Kedip Data Kosong):**
   - **DILARANG** menampilkan data palsu (*mock/dummy*), membiarkan tampilan berkedip *"Data Tidak Ditemukan"* / angka `0`, atau memakai skeleton pecah-pecah di dalam form saat data masih diambil dari server.
   - Gunakan komponen loading 1-halaman / 1-layar [`PageLoader.vue`](file:///Users/am/Jobs/gerbang-sekolah/frontend/src/components/ui/page-loader/PageLoader.vue) yang sudah terintegrasi otomatis di [`SidebarLayout.vue`](file:///Users/am/Jobs/gerbang-sekolah/frontend/src/layouts/SidebarLayout.vue) dan [`useApiLoading.js`](file:///Users/am/Jobs/gerbang-sekolah/frontend/src/composables/useApiLoading.js) sehingga setiap perpindahan halaman maupun proses fetch API langsung menampilkan layar loading bertema Gerbang Sekolah yang utuh hingga data siap ditampilkan.
   - Semua tombol (`Button.vue`, `FormFooter.vue`, `FormSheet.vue`, aksi tabel) wajib terlindungi dari **klik ganda (*anti-double-click*)** menggunakan cooldown klik & penguncian state saat request mutasi (`isMutating` / `loading`) sedang berjalan.
4. **Wajib Validasi Form dengan `Zod` (Real-Time):**
   - **DILARANG** menulis validasi `if (!form.value.xxx)` manual berulang-ulang di dalam halaman Vue.
   - Semua form **WAJIB** menggunakan composable [`useZodForm`](file:///Users/am/Jobs/gerbang-sekolah/frontend/src/composables/useZodForm.js) dan kumpulan validator reusable di [`validators.js`](file:///Users/am/Jobs/gerbang-sekolah/frontend/src/utils/validators.js) (`v.personName`, `v.nik`, `v.nisn`, `v.requiredEmail`, `v.optionalPhone`, `v.requiredSelect`, dll.).
   - Validasi harus bekerja **real-time**: langsung menampilkan error merah saat user mengetik input yang tidak sesuai aturan (misal huruf di kolom angka atau angka di kolom nama), dan langsung menghilangkan error saat input sudah benar.

---

## 5. Wajib Test & Verifikasi (Zero-Bug Policy)

Sebelum menyatakan pekerjaan selesai kepada developer, **WAJIB** melakukan pengecekan:

1. **Backend:** Cek sintaks PHP (`php -l <file>`), pastikan migration sinkron dengan model `$fillable` & `$casts`, dan pastikan query Eloquent aman dari _N+1 problem_ maupun kebocoran scope antar sekolah/yayasan.
2. **Frontend:** Jalankan `npm run build` di folder `frontend` untuk memastikan tidak ada error kompilasi Vue, import yang putus, atau sintaks yang rusak.

---

## 6. Gaya Komunikasi

- Gunakan **Bahasa Indonesia yang sederhana, lugas, dan mudah dipahami** dalam sekali baca.
- Gunakan poin-poin atau tabel ringkas; hindari penjelasan bertele-tele.
