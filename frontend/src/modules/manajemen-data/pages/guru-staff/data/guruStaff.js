import {
  Plus,
  Download,
  GraduationCap,
  BookCheck,
  School,
  BookX
} from 'lucide-vue-next'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

// Data for StatsCard
export const stats = [
  {
    label: 'TOTAL PEGAWAI',
    value: '187',
    trend: '+8.4% bln ini',
    trendDirection: 'up',
    icon: School,
    illustration: 'globe',
    variant: 'primary'
  },
  {
    label: 'PEGAWAI AKTIF',
    value: '180',
    trend: '+12 Baru',
    trendDirection: 'up',
    icon: BookCheck,
    illustration: 'school_bell',
    variant: 'emerald'
  },
  {
    label: 'TOTAL GURU',
    value: '150',
    sub: 'Kehadiran',
    progress: 98,
    icon: GraduationCap,
    illustration: 'open_book',
    variant: 'amber'
  },
  {
    label: 'TOTAL STAFF',
    value: '30',
    sub: 'Kehadiran',
    progress: 90,
    icon: BookX,
    illustration: 'abc_board',
    variant: 'violet'
  }
]

export const filters = [
  {
    type: 'search',
    key: 'search',
    placeholder: 'Cari nama guru/staff disini...'
  },
  {
    type: 'select',
    key: 'status',
    label: 'Filter Status:',
    placeholder: 'Semua',
    options: [
      {
        label: 'Tetap',
        value: 'tetap'
      },
      {
        label: 'Kontrak',
        value: 'kontrak'
      },
      {
        label: 'Honorer',
        value: 'Honorer'
      }
    ]
  }
]

const isExporting = ref(false)
export const actions = [
  {
    label: 'Tambah Guru/Staff',
    icon: Plus,
    to: '/manajemen-data/guru-staff/tambah'
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
  { key: 'nama', label: 'Nama Lengkap', avatar: true, avatarKey: 'foto' },
  { key: 'nip', label: 'NIP' },
  { key: 'unitKerja', label: 'Unit Kerja' },
  { key: 'jabatan', label: 'Jabatan' },
  { key: 'masaKerja', label: 'Masa Kerja' },
  {
    key: 'statusKepegawaian', label: 'Status Kepegawaian', badge: true,
    badgeVariant: {
      Tetap: 'green',
      Honorer: 'gray',
      Kontrak: 'amber',
    }
  },
  { key: 'actions', label: 'Aksi' }
]

export const agamaOptions = [
  {
    label: 'Islam',
    value: 'A01'
  },
  {
    label: 'Kristen',
    value: 'A02'
  },
  {
    label: 'Katolik',
    value: 'A03'
  },
  {
    label: 'Buddha',
    value: 'A04'
  },
  {
    label: 'Hindu',
    value: 'A05'
  },
  {
    label: 'Konghucu',
    value: 'A06'
  },
]

export const statusPernikahanOptions = [
  {
    label: 'Belum Menikah',
    value: 'SP01'
  },
  {
    label: 'Menikah',
    value: 'SP02'
  },
  {
    label: 'Janda',
    value: 'SP03'
  },
  {
    label: 'Duda',
    value: 'SP04'
  }
]

export const kelaminOptions = [
  {
    label: 'Laki-laki',
    value: 'JK01'
  },
  {
    label: 'Perempuan',
    value: 'JK02'
  },
]

export const pendidikanOptions = [
  {
    label: 'Sekolah Dasar (SD)',
    value: 'P01'
  },
  {
    label: 'Sekolah Menengah Pertama (SMP)',
    value: 'P02'
  },
  {
    label: 'Sekolah Menengah Awal/Kejuruan (SMA/SMK)',
    value: 'P03'
  },
  {
    label: 'Diploma I (D1)',
    value: 'P04'
  },
  {
    label: 'Diploma III (D3)',
    value: 'P05'
  },
  {
    label: 'Diploma IV (D4)',
    value: 'P06'
  },
  {
    label: 'Sarjana (S1)',
    value: 'P07'
  },
  {
    label: 'Magister (S2)',
    value: 'P08'
  },
  {
    label: 'Doktoral (S3)',
    value: 'P09'
  },
]

export const jabatanOptions = [
  {
    label: 'Kepala Yayasan',
    value: 'J001'
  },
  {
    label: 'Staff Yayasan',
    value: 'J002'
  },
  {
    label: 'Kepala Sekolah',
    value: 'J003'
  },
  {
    label: 'Guru',
    value: 'J004'
  },
  {
    label: 'Staff Sekolah',
    value: 'J005'
  },
  {
    label: 'Admin Sekolah',
    value: 'J006'
  }
]

export const statusKepegawaianOptions = [
  {
    label: 'Tetap',
    value: 'SK01'
  },
  {
    label: 'Kontrak',
    value: 'SK02'
  },
  {
    label: 'Honorer',
    value: 'SK03'
  },
]

export const statusOptions = [
  {
    label: 'Aktif',
    value: 'Aktif'
  },
  {
    label: 'Nonaktif',
    value: 'Nonaktif'
  },
]