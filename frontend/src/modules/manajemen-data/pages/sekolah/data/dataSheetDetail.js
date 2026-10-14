export const rawSekolahItem = {
  nama: 'SMA Gerbang Sekolah',
  npsn: '20212345',
  yayasan: 'Yayasan Gerbang Sekolah',
  jenjang: 'SMA',
  tanggal_berdiri: '15 Januari 2005',
  status: 'Aktif',

  alamat: 'Jl. Pendidikan No. 88, Bandung',
  email: 'info@smagerbangsekolah.sch.id',
  no_hp: '022-1234567',
  website: 'www.smagerbangsekolah.sch.id',
  instagram: '@smagerbangsekolah',
  facebook: 'SMA Gerbang Sekolah',

  no_sk: '421.3/1234/DISDIK',
  tanggal_sk: '20 Januari 2005',
  no_izin: '503/OPS/2005',
  tanggal_izin: '01 Februari 2005',
  akreditasi: 'A',
  tanggal_akreditasi: '15 Oktober 2023',
  no_akreditasi: 'BAN-SM/A/2023/001',

  emailLogin: 'admin@smagerbangsekolah.sch.id',
  noHpLogin: '081234567890',

  logo: '/defaults/sd/logo.png'
}

export const sekolahSheetSections = [
  {
    id: 'informasi-umum',
    title: 'Informasi Umum',
    fields: [
      { label: 'Nama Sekolah', key: 'nama' },
      { label: 'NPSN', key: 'npsn' },
      { label: 'Yayasan', key: 'yayasan' },
      { label: 'Jenjang Pendidikan', key: 'jenjang' },
      { label: 'Tanggal Berdiri', key: 'tanggal_berdiri' },
      { label: 'Status', key: 'status' }
    ]
  },

  {
    id: 'kontak',
    title: 'Kontak Sekolah',
    fields: [
      { label: 'Alamat Lengkap', key: 'alamat', textarea: true },
      { label: 'Email', key: 'email' },
      { label: 'No. Telepon', key: 'no_hp' },
      { label: 'Website', key: 'website' },
      { label: 'Instagram', key: 'instagram' },
      { label: 'Facebook', key: 'facebook' }
    ]
  },

  {
    id: 'legalitas',
    title: 'Legalitas & Akreditasi',
    fields: [
      { label: 'No. SK Pendirian', key: 'no_sk' },
      { label: 'Tanggal SK Pendirian', key: 'tanggal_sk' },
      { label: 'No. Izin Operasional', key: 'no_izin' },
      { label: 'Tanggal Izin Operasional', key: 'tanggal_izin' },
      { label: 'Akreditasi', key: 'akreditasi' },
      { label: 'Tanggal Akreditasi', key: 'tanggal_akreditasi' },
      { label: 'No. SK Akreditasi', key: 'no_akreditasi' }
    ]
  },

  {
    id: 'akun-admin',
    title: 'Akun Administrator',
    fields: [
      { label: 'Email Login', key: 'emailLogin' },
      { label: 'No. HP Login', key: 'noHpLogin' }
    ]
  }
]
