export const rawSiswaItem = {
  nama: 'Muhammad Rizky',
  nama_depan: 'Muhammad',
  nama_belakang: 'Rizky',

  nik: '3276010101120001',
  nisn: '0098765432',

  tempat_lahir: 'Bandung',
  tanggal_lahir: '12 Mei 2012',
  jenis_kelamin: 'Laki-laki',
  agama: 'Islam',

  alamat: 'Jl. Ciumbuleuit No. 45, Kel. Hegarmanah, Kec. Cidadap, Kota Bandung',

  email: 'rizky@student.sch.id',
  no_hp: '081234567890',

  tahun_masuk: '2024',
  kelas: '7-A',
  status: 'Aktif',

  nama_wali: 'Budi Santoso',
  hubungan_siswa: 'Ayah',
  kelamin_wali: 'Laki-laki',
  pekerjaan_wali: 'Pegawai Negeri Sipil',
  email_wali: 'budi.santoso@gmail.com',
  no_hp_wali: '081212345678',

  emailLogin: 'budi.santoso@gmail.com',
  noHpLogin: '081212345678',

  foto: 'https://i.pravatar.cc/300?img=12'
}

export const siswaSheetSections = [
  {
    id: 'informasi-siswa',
    title: 'Informasi Siswa',
    fields: [
      { label: 'Nama Depan', key: 'nama_depan' },
      { label: 'Nama Belakang', key: 'nama_belakang' },
      { label: 'NIK', key: 'nik' },
      { label: 'NISN', key: 'nisn' },
      { label: 'Tempat Lahir', key: 'tempat_lahir' },
      { label: 'Tanggal Lahir', key: 'tanggal_lahir' },
      { label: 'Jenis Kelamin', key: 'jenis_kelamin' },
      { label: 'Agama', key: 'agama' },
      { label: 'Alamat Lengkap', key: 'alamat', textarea: true }
    ]
  },
  {
    id: 'kontak',
    title: 'Kontak Siswa',
    fields: [
      { label: 'Email', key: 'email' },
      { label: 'No. Telepon', key: 'no_hp' }
    ]
  },

  {
    id: 'akademik',
    title: 'Informasi Akademik',
    fields: [
      { label: 'Tahun Masuk', key: 'tahun_masuk' },
      { label: 'Kelas', key: 'kelas' },
      { label: 'Ekstrakurikuler', key: 'ekskul_label' },
      { label: 'Status', key: 'status' }
    ]
  },
  {
    id: 'ayah',
    title: 'Informasi Ayah',
    fields: [
      { label: 'Nama Lengkap Ayah', key: 'nama_ayah' },
      { label: 'Pekerjaan Ayah', key: 'pekerjaan_ayah' },
      { label: 'Email Ayah', key: 'email_ayah' },
      { label: 'No. HP Ayah', key: 'no_hp_ayah' },
      { label: 'Alamat Lengkap Ayah', key: 'alamat_ayah', textarea: true }
    ]
  },
  {
    id: 'ibu',
    title: 'Informasi Ibu',
    fields: [
      { label: 'Nama Lengkap Ibu', key: 'nama_ibu' },
      { label: 'Pekerjaan Ibu', key: 'pekerjaan_ibu' },
      { label: 'Email Ibu', key: 'email_ibu' },
      { label: 'No. HP Ibu', key: 'no_hp_ibu' },
      { label: 'Alamat Lengkap Ibu', key: 'alamat_ibu', textarea: true }
    ]
  },
  {
    id: 'wali',
    title: 'Informasi Wali',
    fields: [
      { label: 'Nama Lengkap Wali', key: 'nama_wali' },
      { label: 'Hubungan dengan Siswa', key: 'hubungan_siswa' },
      { label: 'Jenis Kelamin Wali', key: 'kelamin_wali' },
      { label: 'Pekerjaan Wali', key: 'pekerjaan_wali' },
      { label: 'Email Wali', key: 'email_wali' },
      { label: 'No. HP Wali', key: 'no_hp_wali' },
      { label: 'Alamat Lengkap Wali', key: 'alamat_wali', textarea: true }
    ]
  },
  {
    id: 'akun',
    title: 'Akun Pengguna Orang Tua / Wali',
    fields: [
      { label: 'Email Login', key: 'emailLogin' },
      { label: 'No. HP Login', key: 'noHpLogin' }
    ]
  }
]
