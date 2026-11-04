export const ROLE_OPTIONS = [
  { label: 'Super Admin', value: 'superadmin' },
  { label: 'Admin Yayasan', value: 'admin_yayasan' },
  { label: 'Kepala Sekolah', value: 'kepala_sekolah' },
  { label: 'Admin Sekolah', value: 'admin_sekolah' },
  { label: 'Tata Usaha', value: 'tata_usaha' },
  { label: 'Guru', value: 'guru' },
  { label: 'Siswa', value: 'siswa' },
  { label: 'Orang Tua / Wali', value: 'orang_tua' }
]

export const ROLE_LABELS = {
  superadmin: 'Super Admin',
  admin_yayasan: 'Admin Yayasan',
  kepala_sekolah: 'Kepala Sekolah',
  admin_sekolah: 'Admin Sekolah',
  tata_usaha: 'Tata Usaha',
  guru: 'Guru',
  siswa: 'Siswa',
  orang_tua: 'Orang Tua / Wali'
}

export const initialPengguna = [
  {
    id: '1',
    nama: 'Super Admin',
    email: 'superadmin@mail.com',
    role: 'superadmin',
    yayasan: '-',
    sekolah: '-',
    status: 'aktif'
  },
  {
    id: '2',
    nama: 'Admin Yayasan',
    email: 'adminyayasan@mail.com',
    role: 'admin_yayasan',
    yayasan: 'Yayasan Nusantara Pintar',
    sekolah: '-',
    status: 'aktif'
  },
  {
    id: '3',
    nama: 'Kepala Sekolah',
    email: 'kepalasekolah@mail.com',
    role: 'kepala_sekolah',
    yayasan: 'Yayasan Nusantara Pintar',
    sekolah: 'SD Nusantara Pintar Bekasi',
    status: 'aktif'
  },
  {
    id: '4',
    nama: 'Admin Sekolah',
    email: 'adminsekolah@mail.com',
    role: 'admin_sekolah',
    yayasan: 'Yayasan Nusantara Pintar',
    sekolah: 'SD Nusantara Pintar Bekasi',
    status: 'aktif'
  },
  {
    id: '5',
    nama: 'Tata Usaha',
    email: 'tatausaha@mail.com',
    role: 'tata_usaha',
    yayasan: 'Yayasan Nusantara Pintar',
    sekolah: 'SD Nusantara Pintar Bekasi',
    status: 'aktif'
  },
  {
    id: '6',
    nama: 'Guru Pengajar',
    email: 'guru@mail.com',
    role: 'guru',
    yayasan: 'Yayasan Nusantara Pintar',
    sekolah: 'SD Nusantara Pintar Bekasi',
    status: 'aktif'
  },
  {
    id: '7',
    nama: 'Wali Kelas 1-A',
    email: 'walikelas@mail.com',
    role: 'guru',
    yayasan: 'Yayasan Nusantara Pintar',
    sekolah: 'SD Nusantara Pintar Bekasi',
    status: 'aktif'
  },
  {
    id: '8',
    nama: 'Ahmad Wibowo',
    email: 'siswa@mail.com',
    role: 'siswa',
    yayasan: 'Yayasan Nusantara Pintar',
    sekolah: 'SD Nusantara Pintar Bekasi',
    status: 'aktif'
  },
  {
    id: '9',
    nama: 'Orang Tua / Wali',
    email: 'orangtua@mail.com',
    role: 'orang_tua',
    yayasan: 'Yayasan Nusantara Pintar',
    sekolah: 'SD Nusantara Pintar Bekasi',
    status: 'aktif'
  },
  {
    id: '10',
    nama: 'Budi Santoso',
    email: 'budi.santoso@mail.com',
    role: 'guru',
    yayasan: 'Yayasan Nusantara Pintar',
    sekolah: 'SD Nusantara Pintar Jakarta Timur',
    status: 'nonaktif'
  }
]

const PENGGUNA_KEY = 'pengguna_db_v2'

export function getPengguna() {
  const data = localStorage.getItem(PENGGUNA_KEY)
  if (!data) {
    localStorage.setItem(PENGGUNA_KEY, JSON.stringify(initialPengguna))
    return [...initialPengguna]
  }
  try {
    return JSON.parse(data)
  } catch (e) {
    console.error('Error parsing pengguna database from localStorage', e)
    return [...initialPengguna]
  }
}

export function savePengguna(data) {
  localStorage.setItem(PENGGUNA_KEY, JSON.stringify(data))
}

export const columns = [
  { key: 'nama', label: 'Nama Pengguna' },
  { key: 'email', label: 'Email' },
  { key: 'role', label: 'Peran' },
  { key: 'yayasan', label: 'Yayasan' },
  { key: 'sekolah', label: 'Sekolah' },
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
    placeholder: 'Cari nama / email pengguna...'
  },
  {
    type: 'select',
    key: 'role',
    label: 'Peran:',
    placeholder: 'Semua Peran',
    options: ROLE_OPTIONS
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
