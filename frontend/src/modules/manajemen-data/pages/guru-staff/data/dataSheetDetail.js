export const rawGuruStaffItem = {
  nama: 'Ahmad Fauzi',
  nama_depan: 'Ahmad',
  nama_belakang: 'Fauzi',
  nik: '3276010101900001',
  nip_nuptk: '198901012020121001',

  tempat_lahir: 'Bandung',
  tanggal_lahir: '01 Januari 1989',
  jenis_kelamin: 'Laki-laki',
  agama: 'Islam',
  status_pernikahan: 'Menikah',
  pendidikan_terakhir: 'S2 Pendidikan',

  gelar_depan: 'Dr.',
  gelar_belakang: 'M.Pd.',

  email: 'ahmad.fauzi@sekolah.sch.id',
  no_hp: '081234567890',
  alamat: 'Jl. Sukajadi No. 100, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',

  jabatan: 'Guru Matematika',
  status_kepegawaian: 'Guru Tetap',
  unit_kerja: 'SMA',
  status_aktif: 'Aktif',

  emailLogin: 'ahmad.fauzi@sekolah.sch.id',
  noHpLogin: '081234567890',

  foto: 'https://i.pravatar.cc/300?img=60'
}

export const guruStaffSheetSections = [
  {
    id: 'informasi-pribadi',
    title: 'Informasi Pribadi',
    fields: [
      { label: 'Nama Depan', key: 'nama_depan' },
      { label: 'Nama Belakang', key: 'nama_belakang' },
      { label: 'Gelar Depan', key: 'gelar_depan' },
      { label: 'Gelar Belakang', key: 'gelar_belakang' },
      { label: 'NIK', key: 'nik' },
      { label: 'Jenis Kelamin', key: 'jenis_kelamin' },
      { label: 'Tempat Lahir', key: 'tempat_lahir' },
      { label: 'Tanggal Lahir', key: 'tanggal_lahir' },
      { label: 'Agama', key: 'agama' },
      { label: 'Status Pernikahan', key: 'status_pernikahan' },
      { label: 'Pendidikan Terakhir', key: 'pendidikan_terakhir' },
      { label: 'Alamat Lengkap', key: 'alamat', textarea: true }
    ]
  },

  {
    id: 'kepegawaian',
    title: 'Informasi Kepegawaian',
    fields: [
      { label: 'Unit Kerja', key: 'unit_kerja' },
      { label: 'Jabatan', key: 'jabatan' },
      { label: 'Status Kepegawaian', key: 'status_kepegawaian' },
      { label: 'Status Aktif', key: 'status_aktif' },
      { label: 'Tanggal Bergabung', key: 'join_date' },
      { label: 'NIP / NUPTK', key: 'nip_nuptk' }
    ]
  },

  {
    id: 'akun-admin',
    title: 'Akun Pengguna',
    fields: [
      { label: 'Email Login', key: 'emailLogin' },
      { label: 'No. HP / WhatsApp', key: 'noHpLogin' }
    ]
  }
]
