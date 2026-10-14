export const rawYayasanItem = {
  kode: 'YGS001',
  nama: 'Yayasan Gerbang Sekolah',
  tanggal_berdiri: '15 Januari 2005',
  status: 'Aktif',

  alamat: 'Jl. Pendidikan No. 88, Bandung, Jawa Barat',
  email: 'info@yayasan.gerbangsekolah.id',
  no_hp: '022-1234567',
  website: 'www.yayasan.gerbangsekolah.id',

  no_akta: 'AHU-0012345.AH.01.04.Tahun 2005',
  tanggal_akta: '15 Januari 2005',
  no_sk: 'AHU-0023456.AH.01.07.Tahun 2005',
  tanggal_sk: '20 Januari 2005',

  emailLogin: 'admin@yayasan.gerbangsekolah.id',
  noHpLogin: '081234567890',

  logo: '/defaults/sd/logo.png'
}

export const yayasanSheetSections = [
  {
    id: 'informasi-umum',
    title: 'Informasi Umum',
    fields: [
      { label: 'Kode Yayasan', key: 'kode' },
      { label: 'Nama Yayasan', key: 'nama' },
      { label: 'Tanggal Berdiri', key: 'tanggal_berdiri' },
      { label: 'Status', key: 'status' },
      { label: 'Alamat Lengkap', key: 'alamat', textarea: true }
    ]
  },

  {
    id: 'kontak',
    title: 'Kontak Yayasan',
    fields: [
      { label: 'Email', key: 'email' },
      { label: 'No. Telepon', key: 'no_hp' },
      { label: 'Website', key: 'website' }
    ]
  },

  {
    id: 'legalitas',
    title: 'Legalitas Yayasan',
    fields: [
      { label: 'No. Akta Pendirian', key: 'no_akta' },
      { label: 'Tanggal Akta Pendirian', key: 'tanggal_akta' },
      { label: 'No. SK Kemenkumham', key: 'no_sk' },
      { label: 'Tanggal SK Kemenkumham', key: 'tanggal_sk' }
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
