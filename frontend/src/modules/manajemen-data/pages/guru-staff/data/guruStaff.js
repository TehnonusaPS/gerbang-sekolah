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
      { label: 'Tetap', value: 'Tetap' },
      { label: 'Kontrak', value: 'Kontrak' },
      { label: 'Honorer', value: 'Honorer' }
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
    badgeClass: {
      Tetap: 'border-blue-200/60 bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
      Kontrak: 'border-amber-200/60 bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
      Honorer: 'border-purple-200/60 bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400',
    }
  },
  {
    key: 'status', label: 'Status', badge: true,
    badgeVariant: {
      Aktif: 'green',
      Nonaktif: 'gray',
    }
  },
  { key: 'actions', label: 'Aksi' }
]

export const agamaOptions = [
  { label: 'Islam', value: 'Islam' },
  { label: 'Kristen', value: 'Kristen' },
  { label: 'Katolik', value: 'Katolik' },
  { label: 'Buddha', value: 'Buddha' },
  { label: 'Hindu', value: 'Hindu' },
  { label: 'Konghucu', value: 'Konghucu' }
]

export const statusPernikahanOptions = [
  { label: 'Belum Menikah', value: 'Belum Menikah' },
  { label: 'Menikah', value: 'Menikah' },
  { label: 'Janda', value: 'Janda' },
  { label: 'Duda', value: 'Duda' }
]

export const kelaminOptions = [
  { label: 'Laki-laki', value: 'Laki-laki' },
  { label: 'Perempuan', value: 'Perempuan' }
]

export const pendidikanOptions = [
  { label: 'Sekolah Dasar (SD)', value: 'Sekolah Dasar (SD)' },
  { label: 'Sekolah Menengah Pertama (SMP)', value: 'Sekolah Menengah Pertama (SMP)' },
  { label: 'Sekolah Menengah Atas/Kejuruan (SMA/SMK)', value: 'Sekolah Menengah Atas/Kejuruan (SMA/SMK)' },
  { label: 'Diploma I (D1)', value: 'Diploma I (D1)' },
  { label: 'Diploma III (D3)', value: 'Diploma III (D3)' },
  { label: 'Diploma IV (D4)', value: 'Diploma IV (D4)' },
  { label: 'Sarjana (S1)', value: 'Sarjana (S1)' },
  { label: 'Magister (S2)', value: 'Magister (S2)' },
  { label: 'Doktoral (S3)', value: 'Doktoral (S3)' }
]

export const jabatanOptions = [
  { label: 'Kepala Sekolah', value: 'Kepala Sekolah' },
  { label: 'Guru', value: 'Guru' },
  { label: 'Staff Sekolah', value: 'Staff Sekolah' }
]

export const statusKepegawaianOptions = [
  { label: 'Tetap', value: 'Tetap' },
  { label: 'Kontrak', value: 'Kontrak' },
  { label: 'Honorer', value: 'Honorer' }
]

export const statusOptions = [
  { label: 'Aktif', value: 'Aktif' },
  { label: 'Nonaktif', value: 'Nonaktif' }
]