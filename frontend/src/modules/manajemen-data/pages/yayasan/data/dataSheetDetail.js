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
      { label: 'Email Login Administrator', key: 'emailLogin' }
    ]
  }
]
