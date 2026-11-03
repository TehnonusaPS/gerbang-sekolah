export const columns = [
  { key: 'nama', label: 'Nama Ekskul' },
  { key: 'jumlah_anggota', label: 'Jumlah Anggota' },
  { key: 'deskripsi', label: 'Deskripsi', truncate: true },
  {
    key: 'status',
    label: 'Status',
    badge: true,
    badgeVariant: {
      aktif: 'green',
      nonaktif: 'gray'
    }
  },
  { key: 'actions', label: 'Aksi' }
]

export const filters = [
  {
    type: 'search',
    key: 'search',
    placeholder: 'Cari ekstrakulikuler...'
  },
  {
    type: 'select',
    key: 'status',
    label: 'Status:',
    placeholder: 'Semua Status',
    options: [
      { label: 'Aktif', value: 'aktif' },
      { label: 'Nonaktif', value: 'nonaktif' }
    ]
  }
]

