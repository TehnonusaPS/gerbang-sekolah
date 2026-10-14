import {
  Building2,
  ShieldCheck,
  ShieldAlert,
  ShieldX,
  Plus,
  Download,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { toast } from 'vue-sonner'

// Data for StatsCard
export const stats = [
  {
    label: 'TOTAL YAYASAN',
    value: '123',
    trend: '+8.4% bln ini',
    trendDirection: 'up',
    icon: Building2,
    illustration: 'globe',
    variant: 'primary'
  },
  {
    label: 'YAYASAN AKTIF',
    value: '112',
    trend: '+12 Baru',
    trendDirection: 'up',
    icon: ShieldCheck,
    illustration: 'school_bell',
    variant: 'emerald'
  },
  {
    label: 'YAYASAN SEDANG TRIAL',
    value: '8',
    trend: '-2 Yayasan',
    trendDirection: 'down',
    icon: ShieldAlert,
    illustration: 'pencil',
    variant: 'amber'
  },
  {
    label: 'YAYASAN TIDAK AKTIF',
    value: '3',
    trend: '-1 Yayasan',
    trendDirection: 'up',
    icon: ShieldX,
    illustration: 'ruler',
    variant: 'violet'
  }
]

export const columns = [
  { key: 'nama', label: 'Nama Yayasan', avatar: true, avatarKey: 'logo'  },
  { key: 'jmlSekolah', label: 'Jumlah Sekolah', type: 'number' },
  { key: 'alamat', label: 'Alamat Yayasan', type: 'muted', truncate: true  },
  { key: 'jmlPengguna', label: 'Jumlah Pengguna', type: 'number' },
  { key: 'status', label: 'Status', badge: true,
    badgeVariant: {
      Aktif: 'green',
      Nonaktif: 'gray',
      Trial: 'amber',
    } },
  { key: 'actions', label: 'Aksi' }
]

export const filters = [
  {
    type: 'search',
    key: 'search',
    placeholder: 'Cari nama yayasan disini...'
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
    label: 'Tambah Yayasan',
    icon: Plus,
    to: '/manajemen-data/yayasan/tambah'
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

export const items = computed(() => [
  {
    id: 1,
    initial: 'NP',
    nama: 'Yayasan Nusantara Pintar',
    jmlSekolah: '2',
    alamat: 'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlPengguna: '612',
    status: 'Aktif',
  },
  {
    id: 2,
    initial: 'HB',
    nama: 'Yayasan Harapan Bangsa',
    jmlSekolah: '1',
    alamat: 'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlPengguna: '248',
    status: 'Nonaktif',
  },
  {
    id: 3,
    initial: 'SI',
    nama: 'Yayasan Sinar Ilmu',
    jmlSekolah: '3',
    alamat: 'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlPengguna: '937',
    status: 'Trial',
  },
  {
    id: 4,
    nama: 'Yayasan Gerbang Sekolah',
    jmlSekolah: '3',
    alamat: 'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlPengguna: '625',
    status: 'Nonaktif',
  },
  {
    id: 5,
    nama: 'Yayasan Pendidikan Maju',
    jmlSekolah: '3',
    alamat: 'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlPengguna: '1227',
    status: 'Trial',
  },
  {
    id: 6,
    nama: 'Yayasan Cemerlang Abadi',
    jmlSekolah: '3',
    alamat: 'Jl. Lorem Ipsum Dolor Sit No. 123, Kel. Lorem, Kec. Lorem Ipsum, Lorem Ipsum 123456',
    jmlPengguna: '1025',
    status: 'Aktif',
  },
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