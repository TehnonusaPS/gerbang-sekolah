import { computed } from 'vue'

export const filters = [
  {
    type: 'search',
    key: 'search',
    placeholder: 'Cari nama atau NISN...'
  },
  {
    type: 'select',
    key: 'keanggotaan',
    label: 'Status Penempatan:',
    placeholder: 'Semua Siswa',
    options: [
      { label: 'Semua Siswa', value: 'all' },
      { label: 'Belum Ada Kelas', value: 'unassigned' },
      { label: 'Anggota Kelas Ini', value: 'current' },
      { label: 'Dari Kelas Lain', value: 'other' }
    ]
  },
  {
    type: 'select',
    key: 'status',
    label: 'Filter Status:',
    placeholder: 'Semua',
    options: [
      { label: 'Aktif', value: 'Aktif' },
      { label: 'Nonaktif', value: 'Nonaktif' }
    ]
  }
]

export const columns = [
  { key: 'selected', label: '', type: 'checkbox' },
  { key: 'nama', label: 'Nama Siswa', avatar: true, avatarKey: 'foto' },
  { key: 'nisn', label: 'NISN', type: 'code' },
  { key: 'kelas', label: 'Kelas Saat Ini' },
  {
    key: 'jenisKelamin',
    label: 'Jenis Kelamin',
    badge: true,
    badgeVariant: {
      Perempuan: 'pink',
      'Laki-laki': 'blue'
    }
  },
  { key: 'tahunMasuk', label: 'Tahun Masuk', type: 'number' },
  {
    key: 'status',
    label: 'Status Siswa',
    badge: true,
    badgeVariant: {
      Aktif: 'green',
      Nonaktif: 'amber'
    }
  },
  { key: 'aksi', label: 'Aksi' }
]

export const allItems = computed(() => [
  {
    id: 1,
    nama: 'Ahmad Fauzi',
    foto: 'https://i.pravatar.cc/150?img=1',
    alamat: 'Jl. Sukajadi No. 100, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765432',
    kehadiran: 100,
    kelas: 'Belum Ada Kelas',
    namaWali: 'Budi Fauzi',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 2,
    nama: 'Siti Aisyah',
    foto: 'https://i.pravatar.cc/150?img=2',
    alamat: 'Jl. Sukajadi No. 101, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765433',
    kehadiran: 98,
    kelasId: 'K0001',
    kelas: 'Belum Ada Kelas',
    namaWali: 'Nurhayati',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 3,
    nama: 'Budi Santoso',
    foto: 'https://i.pravatar.cc/150?img=3',
    alamat: 'Jl. Sukajadi No. 102, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765434',
    kehadiran: 95,
    kelasId: 'K0004',
    kelas: 'Belum Ada Kelas',
    namaWali: 'Andi Santoso',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 4,
    nama: 'Dewi Lestari',
    foto: 'https://i.pravatar.cc/150?img=4',
    alamat: 'Jl. Sukajadi No. 103, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765435',
    kehadiran: 96,
    kelasId: 'K0004',
    kelas: 'Belum Ada Kelas',
    namaWali: 'Sulastri',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 5,
    nama: 'Rizky Pratama',
    foto: 'https://i.pravatar.cc/150?img=5',
    alamat: 'Jl. Sukajadi No. 104, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765436',
    kehadiran: 100,
    kelasId: 'K0005',
    kelas: 'Belum Ada Kelas',
    namaWali: 'Hendra Pratama',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 6,
    nama: 'Nabila Putri',
    foto: 'https://i.pravatar.cc/150?img=6',
    alamat: 'Jl. Sukajadi No. 105, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765437',
    kehadiran: 99,
    kelasId: 'K0005',
    kelas: '1-B',
    namaWali: 'Rina Wulandari',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 7,
    nama: 'Fajar Ramadhan',
    foto: 'https://i.pravatar.cc/150?img=7',
    alamat: 'Jl. Sukajadi No. 104, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765438',
    kehadiran: 70,
    kelasId: 'K0007',
    kelas: '1-B',
    namaWali: 'Dedi Ramadhan',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 8,
    nama: 'Aulia Maharani',
    foto: 'https://i.pravatar.cc/150?img=8',
    alamat: 'Jl. Sukajadi No. 106, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765439',
    kehadiran: 92,
    kelasId: 'K0008',
    kelas: '1-B',
    namaWali: 'Fitri Maharani',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 9,
    nama: 'Muhammad Farhan',
    foto: 'https://i.pravatar.cc/150?img=9',
    alamat: 'Jl. Sukajadi No. 107, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765440',
    kehadiran: 100,
    kelasId: 'K0009',
    kelas: '1-A',
    namaWali: 'Agus Setiawan',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 10,
    nama: 'Zahra Khairunnisa',
    foto: 'https://i.pravatar.cc/150?img=10',
    alamat: 'Jl. Sukajadi No. 108, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765441',
    kehadiran: 100,
    kelasId: 'K0010',
    kelas: '1-A',
    namaWali: 'Rahmawati',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 11,
    nama: 'Alif Maulana',
    foto: 'https://i.pravatar.cc/150?img=11',
    alamat: 'Jl. Sukajadi No. 109, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765442',
    kehadiran: 98,
    kelasId: 'K0011',
    kelas: '1-A',
    namaWali: 'Yusuf Maulana',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 12,
    nama: 'Citra Ayuningtyas',
    foto: 'https://i.pravatar.cc/150?img=12',
    alamat: 'Jl. Sukajadi No. 110, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765443',
    kehadiran: 98,
    kelasId: 'K0012',
    kelas: '1-A',
    namaWali: 'Sri Wahyuni',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2025',
    status: 'Nonaktif'
  },
  {
    id: 13,
    nama: 'Kevin Saputra',
    foto: 'https://i.pravatar.cc/150?img=13',
    alamat: 'Jl. Sukajadi No. 111, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765444',
    kehadiran: 98,
    kelasId: 'K0003',
    kelas: '1-A',
    namaWali: 'Rudi Saputra',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2025',
    status: 'Aktif'
  },
  {
    id: 14,
    nama: 'Putri Ananda',
    foto: 'https://i.pravatar.cc/150?img=14',
    alamat: 'Jl. Sukajadi No. 112, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765445',
    kehadiran: 98,
    kelasId: 'K0002',
    kelas: '1-A',
    namaWali: 'Dian Ananda',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2025',
    status: 'Nonaktif'
  },
  {
    id: 15,
    nama: 'Raka Wijaya',
    foto: 'https://i.pravatar.cc/150?img=15',
    alamat: 'Jl. Sukajadi No. 113, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765446',
    kehadiran: 96,
    kelasId: 'K0006',
    kelas: '1-A',
    namaWali: 'Eko Wijaya',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2025',
    status: 'Aktif'
  }
])

export const classes = [
  {
    label: '1-A',
    value: '1-A'
  },
  {
    label: '1-B',
    value: '1-B'
  },
  {
    label: '2-A',
    value: '2-A'
  },
  {
    label: '2-B',
    value: '2-B'
  },
  {
    label: '3-A',
    value: '3-A'
  },
  {
    label: '3-B',
    value: '3-B'
  }
]

export const guruOptions = [
  {
    label: 'Fajar Mukarami, S.Pd.',
    value: 'G0001',
    mapel: 'Bahasa Indonesia'
  },
  {
    label: 'Dewi Kartika, S.Pd.',
    value: 'G0002',
    mapel: 'Matematika'
  },
  {
    label: 'Andi Saputra, S.Pd.',
    value: 'G0003',
    mapel: 'IPA'
  }
]
