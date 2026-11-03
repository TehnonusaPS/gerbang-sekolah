import {
  Plus,
  Download,
  BookCheck,
  BookAlert,
  BookX,
  Users,
  UserCheck,
  School,
  UserRound,
  UserRoundCheck
} from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { toast } from 'vue-sonner'

export const stats = [
  {
    label: 'TOTAL SISWA',
    value: '1.245',
    sub: 'Dari semester lalu',
    trend: '+32 siswa',
    trendDirection: 'up',
    icon: Users,
    variant: 'up',
    color: 'primary'
  },
  {
    label: 'SISWA AKTIF',
    value: '1.198',
    sub: 'Dari semester lalu',
    trend: '+15 siswa',
    trendDirection: 'up',
    icon: UserCheck,
    variant: 'up',
    color: 'emerald'
  },
  {
    label: 'SISWA LAKI-LAKI',
    value: '612',
    sub: 'Komposisi Gender',
    progress: 51,
    icon: UserRound,
    variant: 'progress',
    color: 'blue'
  },
  {
    label: 'SISWA PEREMPUAN',
    value: '586',
    sub: 'Komposisi Gender',
    progress: 49,
    icon: UserRoundCheck,
    variant: 'progress',
    color: 'violet'
  }
]

// Data for StatsCard
export const statsOld = [
  {
    label: 'TOTAL SEKOLAH',
    value: '123',
    trend: '+8.4% bln ini',
    trendDirection: 'up',
    icon: School,
    variant: 'primary'
  },
  {
    label: 'SEKOLAH AKTIF',
    value: '112',
    trend: '+12 Baru',
    trendDirection: 'up',
    icon: BookCheck,
    variant: 'emerald'
  },
  {
    label: 'SEKOLAH SEDANG TRIAL',
    value: '8',
    trend: '-2 Sekolah',
    trendDirection: 'down',
    icon: BookAlert,
    variant: 'amber'
  },
  {
    label: 'SEKOLAH TIDAK AKTIF',
    value: '3',
    trend: '-1 Sekolah',
    trendDirection: 'up',
    icon: BookX,
    variant: 'violet'
  }
]

export const filters = [
  {
    type: 'search',
    key: 'search',
    placeholder: 'Cari nama siswa disini...'
  },
  {
    type: 'select',
    key: 'status',
    label: 'Filter Status:',
    placeholder: 'Semua',
    options: [
      {
        label: 'Aktif',
        value: 'Aktif'
      },
      {
        label: 'Nonaktif',
        value: 'Nonaktif'
      },
      {
        label: 'Pindah',
        value: 'Pindah'
      },
      {
        label: 'Lulus',
        value: 'Lulus'
      }
    ]
  }
]

const isExporting = ref(false)

export const actions = [
  {
    label: 'Tambah Siswa',
    icon: Plus,
    to: '/manajemen-data/siswa/tambah'
  },
  {
    label: 'Export',
    icon: Download,
    variant: 'outline',
    loading: isExporting.value,
    click: () => {
      isExporting.value = true
      setTimeout(() => {
        isExporting.value = false
        toast.success('Export berhasil', {
          description: 'Data berhasil diexport ke format Excel/PDF.'
        })
      }, 1500)
    }
  }
]

export const columns = [
  { key: 'nama', label: 'Nama Siswa', avatar: true, avatarKey: 'foto' },
  { key: 'nisn', label: 'NISN', type: 'code' },
  { key: 'kelas', label: 'Kelas' },
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
  { key: 'alamat', label: 'Alamat', type: 'muted', truncate: true },
  {
    key: 'status',
    label: 'Status',
    badge: true,
    badgeVariant: {
      Aktif: 'green',
      Nonaktif: 'gray',
      Pindah: 'amber',
      Lulus: 'purple'
    }
  },
  { key: 'namaWali', label: 'Nama Wali' },
  { key: 'actions', label: 'Aksi' }
]

export const allItems = computed(() => [
  {
    id: 1,
    nama: 'Ahmad Fauzi',
    alamat: 'Jl. Sukajadi No. 100, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765432',
    sekolahId: 'S0001',
    kelasId: 'K0001',
    kelas: '1-A',
    namaWali: 'Budi Fauzi',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2024',
    status: 'Aktif'
  },
  {
    id: 2,
    nama: 'Siti Aisyah',
    alamat: 'Jl. Sukajadi No. 101, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765433',
    sekolahId: 'S0001',
    kelasId: 'K0001',
    kelas: '1-A',
    namaWali: 'Nurhayati',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2024',
    status: 'Aktif'
  },
  {
    id: 3,
    nama: 'Budi Santoso',
    alamat: 'Jl. Sukajadi No. 102, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765434',
    sekolahId: 'S0002',
    kelasId: 'K0004',
    kelas: '2-B',
    namaWali: 'Andi Santoso',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2023',
    status: 'Aktif'
  },
  {
    id: 4,
    nama: 'Dewi Lestari',
    alamat: 'Jl. Sukajadi No. 103, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765435',
    sekolahId: 'S0002',
    kelasId: 'K0004',
    kelas: '2-B',
    namaWali: 'Sulastri',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2023',
    status: 'Aktif'
  },
  {
    id: 5,
    nama: 'Rizky Pratama',
    alamat: 'Jl. Sukajadi No. 104, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765436',
    sekolahId: 'S0004',
    kelasId: 'K0005',
    kelas: '3-A',
    namaWali: 'Hendra Pratama',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2022',
    status: 'Aktif'
  },
  {
    id: 6,
    nama: 'Nabila Putri',
    alamat: 'Jl. Sukajadi No. 105, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765437',
    sekolahId: 'S0004',
    kelasId: 'K0005',
    kelas: '3-A',
    namaWali: 'Rina Wulandari',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2022',
    status: 'Aktif'
  },
  {
    id: 7,
    nama: 'Fajar Ramadhan',
    alamat: 'Jl. Sukajadi No. 104, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765438',
    sekolahId: 'S0005',
    kelasId: 'K0007',
    kelas: '4-A',
    namaWali: 'Dedi Ramadhan',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2021',
    status: 'Nonaktif'
  },
  {
    id: 8,
    nama: 'Aulia Maharani',
    alamat: 'Jl. Sukajadi No. 106, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765439',
    sekolahId: 'S0005',
    kelasId: 'K0008',
    kelas: '4-B',
    namaWali: 'Fitri Maharani',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2021',
    status: 'Aktif'
  },
  {
    id: 9,
    nama: 'Muhammad Farhan',
    alamat: 'Jl. Sukajadi No. 107, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765440',
    sekolahId: 'S0006',
    kelasId: 'K0009',
    kelas: '5-A',
    namaWali: 'Agus Setiawan',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2020',
    status: 'Pindah'
  },
  {
    id: 10,
    nama: 'Zahra Khairunnisa',
    alamat: 'Jl. Sukajadi No. 108, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765441',
    sekolahId: 'S0006',
    kelasId: 'K0010',
    kelas: '5-B',
    namaWali: 'Rahmawati',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2020',
    status: 'Aktif'
  },
  {
    id: 11,
    nama: 'Alif Maulana',
    alamat: 'Jl. Sukajadi No. 109, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765442',
    sekolahId: 'S0001',
    kelasId: 'K0011',
    kelas: '6-A',
    namaWali: 'Yusuf Maulana',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2019',
    status: 'Lulus'
  },
  {
    id: 12,
    nama: 'Citra Ayuningtyas',
    alamat: 'Jl. Sukajadi No. 110, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765443',
    sekolahId: 'S0002',
    kelasId: 'K0012',
    kelas: '6-B',
    namaWali: 'Sri Wahyuni',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2019',
    status: 'Lulus'
  },
  {
    id: 13,
    nama: 'Kevin Saputra',
    alamat: 'Jl. Sukajadi No. 111, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765444',
    sekolahId: 'S0004',
    kelasId: 'K0003',
    kelas: '2-A',
    namaWali: 'Rudi Saputra',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2024',
    status: 'Aktif'
  },
  {
    id: 14,
    nama: 'Putri Ananda',
    alamat: 'Jl. Sukajadi No. 112, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765445',
    sekolahId: 'S0005',
    kelasId: 'K0002',
    kelas: '1-B',
    namaWali: 'Dian Ananda',
    jenisKelamin: 'Perempuan',
    tahunMasuk: '2025',
    status: 'Pindah'
  },
  {
    id: 15,
    nama: 'Raka Wijaya',
    alamat: 'Jl. Sukajadi No. 113, Kel. Sukagalih, Kec. Sukajadi, Kota Bandung',
    nisn: '0098765446',
    sekolahId: 'S0001',
    kelasId: 'K0006',
    kelas: '3-B',
    namaWali: 'Eko Wijaya',
    jenisKelamin: 'Laki-laki',
    tahunMasuk: '2023',
    status: 'Aktif'
  }
])

export const statusOptions = [
  { label: 'Aktif', value: 'Aktif' },
  { label: 'Nonaktif', value: 'Nonaktif' },
  { label: 'Pindah', value: 'Pindah' },
  { label: 'Lulus', value: 'Lulus' }
]

export const agamaOptions = [
  { label: 'Islam', value: 'Islam' },
  { label: 'Kristen', value: 'Kristen' },
  { label: 'Katolik', value: 'Katolik' },
  { label: 'Buddha', value: 'Buddha' },
  { label: 'Hindu', value: 'Hindu' },
  { label: 'Konghucu', value: 'Konghucu' }
]

export const kelaminOptions = [
  { label: 'Laki-laki', value: 'Laki-laki' },
  { label: 'Perempuan', value: 'Perempuan' }
]

export const pekerjaanOptions = [
  { label: 'PNS', value: 'PNS' },
  { label: 'BUMN', value: 'BUMN' },
  { label: 'Wiraswasta', value: 'Wiraswasta' },
  { label: 'Pegawai Swasta', value: 'Pegawai Swasta' },
  { label: 'Ibu Rumah Tangga', value: 'Ibu Rumah Tangga' },
  { label: 'Lainnya', value: 'Lainnya' }
]

export const hubunganOptions = [
  { label: 'Kakek', value: 'Kakek' },
  { label: 'Nenek', value: 'Nenek' },
  { label: 'Paman', value: 'Paman' },
  { label: 'Bibi', value: 'Bibi' },
  { label: 'Kakak', value: 'Kakak' },
  { label: 'Saudara lainnya', value: 'Saudara lainnya' },
  { label: 'Orang lain', value: 'Orang lain' }
]

export const kelasOptions = [
  {
    label: '1-A',
    value: 'K0001'
  },
  {
    label: '1-B',
    value: 'K0002'
  },
  {
    label: '2-A',
    value: 'K0003'
  },
  {
    label: '2-B',
    value: 'K0004'
  },
  {
    label: '3-A',
    value: 'K0005'
  },
  {
    label: '3-B',
    value: 'K0006'
  }
]
