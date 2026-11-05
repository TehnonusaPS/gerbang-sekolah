import { Plus, Download, School, BookCheck, BookAlert, BookX } from 'lucide-vue-next'
import { ref, computed } from 'vue'
import { toast } from 'vue-sonner'

// Data for StatsCard
export const stats = [
  {
    label: 'TOTAL SEKOLAH',
    value: '123',
    trend: '+8.4% bln ini',
    trendDirection: 'up',
    icon: School,
    illustration: 'globe',
    variant: 'primary'
  },
  {
    label: 'SEKOLAH AKTIF',
    value: '112',
    trend: '+12 Baru',
    trendDirection: 'up',
    icon: BookCheck,
    illustration: 'school_bell',
    variant: 'emerald'
  },
  {
    label: 'SEKOLAH SEDANG TRIAL',
    value: '8',
    trend: '-2 Sekolah',
    trendDirection: 'down',
    icon: BookAlert,
    illustration: 'pencil',
    variant: 'amber'
  },
  {
    label: 'SEKOLAH TIDAK AKTIF',
    value: '3',
    trend: '-1 Sekolah',
    trendDirection: 'up',
    icon: BookX,
    illustration: 'ruler',
    variant: 'violet'
  }
]

export const filters = [
  {
    type: 'search',
    key: 'search',
    placeholder: 'Cari nama sekolah disini...'
  },
  {
    type: 'select',
    key: 'status',
    label: 'Filter Status:',
    placeholder: 'Semua',
    options: [
      {
        label: 'Aktif',
        value: 'active'
      },
      {
        label: 'Nonaktif',
        value: 'inactive'
      },
      {
        label: 'Trial',
        value: 'trial'
      }
    ]
  }
]

const isExporting = ref(false)
export const actions = [
  {
    label: 'Tambah Sekolah',
    icon: Plus,
    to: '/manajemen-data/sekolah/tambah'
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
  { key: 'nama', label: 'Nama Sekolah', avatar: true, avatarKey: 'foto', sortable: true },
  {
    key: 'jenjang',
    label: 'Jenjang',
    badge: true,
    sortable: true,
    badgeVariant: {
      TK: 'purple',
      SD: 'red',
      SMP: 'blue',
      SMA: 'green',
      SMK: 'amber'
    }
  },
  { key: 'namaYayasan', label: 'Nama Yayasan' },
  { key: 'alamatSekolah', label: 'Alamat Sekolah', type: 'muted', truncate: true },
  { key: 'jmlSiswa', label: 'Jumlah Siswa', type: 'number' },
  {
    key: 'status',
    label: 'Status',
    badge: true,
    badgeVariant: {
      Aktif: 'green',
      Nonaktif: 'gray',
      Trial: 'amber'
    }
  },
  { key: 'actions', label: 'Aksi' }
]

export const allItems = computed(() => [
  {
    id: 1,
    initial: 'NPB',
    nama: 'SD Nusantara Pintar Bekasi',
    code: 'loremipsum',
    namaYayasan: 'Yayasan Nusantara Pintar',
    yayasanId: 'Y0001',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '134',
    status: 'Aktif'
  },
  {
    id: 2,
    initial: 'NPJT',
    nama: 'SD Nusantara Pintar Jakarta Timur',
    code: 'loremipsum',
    yayasanId: 'Y0001',
    namaYayasan: 'Yayasan Nusantara Pintar',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '144',
    status: 'Aktif'
  },
  {
    id: 3,
    initial: 'NPC',
    nama: 'SD Nusantara Pintar Cibubur',
    code: 'loremipsum',
    yayasanId: 'Y0001',
    namaYayasan: 'Yayasan Nusantara Pintar',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '136',
    status: 'Nonaktif'
  },
  {
    id: 4,
    initial: 'HB',
    nama: 'SD Harapan Bangsa 1',
    code: 'loremipsum',
    yayasanId: 'Y0002',
    namaYayasan: 'Yayasan Harapan Bangsa',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '166',
    status: 'Aktif'
  },
  {
    id: 5,
    initial: 'HB2',
    nama: 'SD Harapan Bangsa 2',
    code: 'loremipsum',
    yayasanId: 'Y0002',
    namaYayasan: 'Yayasan Harapan Bangsa',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '122',
    status: 'Aktif'
  },
  {
    id: 6,
    initial: 'TKHB',
    nama: 'TK Harapan Bangsa',
    code: 'loremipsum',
    yayasanId: 'Y0002',
    namaYayasan: 'Yayasan Harapan Bangsa',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '76',
    status: 'Trial'
  },
  {
    id: 7,
    initial: 'SIKL',
    nama: 'SD Sinar Ilmu Kebayoran Lama',
    code: 'loremipsum',
    yayasanId: 'Y0003',
    namaYayasan: 'Yayasan Sinar Ilmu',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '76',
    status: 'Aktif'
  },
  {
    id: 8,
    initial: 'SIT',
    nama: 'SD Sinar Ilmu Tebet',
    code: 'loremipsum',
    yayasanId: 'Y0003',
    namaYayasan: 'Yayasan Sinar Ilmu',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '76',
    status: 'Aktif'
  },
  {
    id: 9,
    initial: 'NPC',
    nama: 'SD Nusantara Pintar Cilandak',
    code: 'loremipsum',
    yayasanId: 'Y0001',
    namaYayasan: 'Yayasan Nusantara Pintar',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '122',
    status: 'Aktif'
  },
  {
    id: 10,
    initial: 'TKN',
    nama: 'TK Nusantara Pintar Bekasi',
    code: 'loremipsum',
    yayasanId: 'Y0001',
    namaYayasan: 'Yayasan Nusantara Pintar',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '76',
    status: 'Trial'
  },
  {
    id: 11,
    initial: 'TKN',
    nama: 'TK Nusantara Pintar Depok',
    code: 'loremipsum',
    yayasanId: 'Y0001',
    namaYayasan: 'Yayasan Nusantara Pintar',
    alamatSekolah:
      'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlSiswa: '76',
    status: 'Aktif'
  }
])

export const statusOptions = [
  {
    label: 'Aktif',
    value: 'Active'
  },
  {
    label: 'Tidak Aktif',
    value: 'Inactive'
  },
  {
    label: 'Trial',
    value: 'Trial'
  }
]

export const yayasanOptions = [
  {
    label: 'Yayasan Nusantara Pintar',
    value: 'Y0001'
  },
  {
    label: 'Yayasan Harapan Bangsa',
    value: 'Y0002'
  },
  {
    label: 'Yayasan Sinar Ilmu',
    value: 'Y0003'
  }
]

export const jenjangOptions = [
  {
    label: 'Taman Kanak-Kanak (TK)',
    value: 'JP001'
  },
  {
    label: 'Sekolah Dasar (SD)',
    value: 'JP002'
  },
  {
    label: 'Sekolah Menengah Pertama (SMP)',
    value: 'JP003'
  }
]

export const akreditasi = [
  {
    label: 'A',
    value: 'A'
  },
  {
    label: 'B',
    value: 'B'
  },
  {
    label: 'C',
    value: 'C'
  },
  {
    label: 'Belum Akreditasi',
    value: '0'
  }
]
