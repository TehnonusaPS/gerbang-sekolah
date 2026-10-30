import { Plus, Download } from 'lucide-vue-next'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

export const columns = [
  { key: 'nama', label: 'Nama Yayasan', avatar: true, avatarKey: 'logo' },
  { key: 'kode', label: 'Kode Yayasan' },
  { key: 'jmlSekolah', label: 'Jumlah Sekolah', type: 'number' },
  { key: 'alamat', label: 'Alamat Yayasan', type: 'muted', truncate: true },
  { key: 'jmlPengguna', label: 'Jumlah Pengguna', type: 'number' },
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

export const filters = [
  {
    type: 'search',
    key: 'search',
    placeholder: 'Cari nama atau kode yayasan...'
  },
  {
    type: 'select',
    key: 'status',
    label: 'Filter Status:',
    placeholder: 'Semua',
    options: [
      { label: 'Aktif', value: 'active' },
      { label: 'Nonaktif', value: 'inactive' },
      { label: 'Trial', value: 'trial' }
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

export const statusOptions = [
  { label: 'Aktif', value: 'Active' },
  { label: 'Tidak Aktif', value: 'Inactive' },
  { label: 'Trial', value: 'Trial' }
]