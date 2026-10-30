<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { toast } from 'vue-sonner'
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import StatCard from '@/components/stat-card/StatCard.vue'
import DataSheet from '@/components/data-sheet/DataSheet.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { usePagination } from '@/composables/usePagination'
import { useAuthStore } from '@/stores/authStore'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import MataPelajaranForm from './components/MataPelajaranForm.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import {
  BookOpen,
  BookCheck,
  BookMarked,
  BookX,
  Plus,
  Send,
  Eye,
  Pencil,
  Trash2,
  Check,
  X,
  Lock,
  Unlock,
  AlertCircle,
  ToggleLeft,
  ToggleRight
} from 'lucide-vue-next'
import {
  fetchAllSubjects,
  createSubject,
  updateSubject,
  deleteSubject
} from '@/services/subjectService'
import { columns, filters } from './data/mockMataPelajaran'

const auth = useAuthStore()

// --- Role & Permissions ---
const role = computed(() => auth.user?.role || 'admin_sekolah')
const isAdminSekolah = computed(() => role.value === 'admin_sekolah')
const isKepalaSekolah = computed(() => role.value === 'kepala_sekolah')

// --- State ---
const subjects = ref([])
const perPage = ref(5)
const filterValues = ref({
  search: '',
  status: 'all'
})
const isLoading = ref(false)

async function fetchSubjectsData() {
  isLoading.value = true
  try {
    const res = await fetchAllSubjects()
    subjects.value = res.data.map(item => ({
      id: item.id,
      kode: item.code,
      nama: item.name,
      deskripsi: item.description,
      status: item.is_active ? 'approved' : 'draft',
      isActive: item.is_active,
      grades: item.grades ? item.grades.map(g => g.grade) : []
    }))
  } catch (err) {
    toast.error('Gagal mengambil data mata pelajaran')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchSubjectsData()
})

// --- Computed Stats ---
const totalCount = computed(() => subjects.value.length)
const approvedCount = computed(() => subjects.value.filter(s => s.status === 'approved').length)
const pendingCount = computed(() => 0)
const draftOrRejectedCount = computed(() => subjects.value.filter(s => s.status === 'draft' || s.status === 'rejected').length)

const stats = computed(() => [
  {
    label: 'TOTAL MAPEL',
    value: String(totalCount.value),
    icon: BookOpen,
    variant: 'primary'
  },
  {
    label: 'MAPEL DISETUJUI',
    value: String(approvedCount.value),
    icon: BookCheck,
    variant: 'emerald'
  },
  {
    label: 'MENUNGGU PERSETUJUAN',
    value: String(pendingCount.value),
    icon: BookMarked,
    variant: 'amber'
  },
  {
    label: 'DRAFT / DITOLAK',
    value: String(draftOrRejectedCount.value),
    icon: BookX,
    variant: 'violet'
  }
])

// --- Helper Functions ---
function getStatusLabel(status, item) {
  if (status === 'approved') {
    return item?.isActive !== false ? 'Aktif' : 'Nonaktif'
  }
  if (status === 'pending') return 'Menunggu Persetujuan'
  if (status === 'rejected') return 'Ditolak'
  return 'Draft'
}

function getStatusBadgeVariant(status, item) {
  if (status === 'approved') {
    return item?.isActive !== false ? 'green' : 'gray'
  }
  if (status === 'pending') return 'amber'
  if (status === 'rejected') return 'red'
  return 'gray'
}

// --- Form Sheet State & Methods (Inline Create/Edit) ---
const isFormSheetOpen = ref(false)
const isEditMode = ref(false)
const isSaveConfirmOpen = ref(false)
const formItem = ref({
  id: '',
  kode: '',
  nama: '',
  deskripsi: '',
  grades: []
})

const handleCreate = () => {
  isEditMode.value = false
  formItem.value = {
    id: '',
    kode: '',
    nama: '',
    deskripsi: '',
    grades: [],
    status: 'approved'
  }
  isFormSheetOpen.value = true
}

const handleEdit = (item) => {
  isEditMode.value = true
  formItem.value = {
    id: item.id,
    kode: item.kode,
    nama: item.nama,
    deskripsi: item.deskripsi || '',
    grades: [...(item.grades || [])],
    status: item.status
  }
  isFormSheetOpen.value = true
}

const validateForm = () => {
  const code = formItem.value.kode?.trim().toUpperCase()
  const name = formItem.value.nama?.trim()

  if (!code || !name) {
    toast.error('Gagal Menyimpan', {
      description: 'Kode dan Nama Mata Pelajaran wajib diisi.'
    })
    return false
  }

  // Check unique code
  const isDuplicate = subjects.value.some(s => {
    const isSameCode = s.kode.toUpperCase() === code
    if (isEditMode.value) {
      return isSameCode && String(s.id) !== String(formItem.value.id)
    }
    return isSameCode
  })

  if (isDuplicate) {
    toast.error('Kode Sudah Digunakan', {
      description: `Mata pelajaran dengan kode "${code}" sudah terdaftar.`
    })
    return false
  }

  return true
}

const isSaving = ref(false)
const saveSummary = computed(() => [
  { label: 'Nama Mata Pelajaran', value: formItem.value.nama },
  { label: 'Kode Mapel', value: formItem.value.kode?.toUpperCase() }
])

const executeSave = async () => {
  const code = formItem.value.kode?.trim().toUpperCase()
  const name = formItem.value.nama?.trim()
  const desc = formItem.value.deskripsi?.trim()

  const payload = {
    code: code,
    name: name,
    description: desc,
    grades: formItem.value.grades || [],
    is_active: isEditMode.value ? (formItem.value.status === 'approved' || formItem.value.status === 'draft') : true
  }

  isSaving.value = true
  try {
    if (isEditMode.value) {
      await updateSubject(formItem.value.id, payload)
      toast.success('Berhasil Diperbarui', {
        description: `Mata pelajaran "${name}" telah diperbarui.`
      })
    } else {
      await createSubject(payload)
      toast.success('Berhasil Ditambahkan', {
        description: `Mata pelajaran "${name}" telah ditambahkan.`
      })
    }
    isFormSheetOpen.value = false
    fetchSubjectsData()
  } catch (err) {
    toast.error('Gagal menyimpan mata pelajaran')
  } finally {
    isSaving.value = false
  }
}

// Search & Filter
const filteredItems = computed(() => {
  return subjects.value.filter(item => {
    const searchVal = filterValues.value.search?.trim().toLowerCase() || ''
    const searchMatch =
      !searchVal ||
      item.kode.toLowerCase().includes(searchVal) ||
      item.nama.toLowerCase().includes(searchVal)

    const statusVal = filterValues.value.status
    const statusMatch = !statusVal || statusVal === 'all' || item.status === statusVal

    return searchMatch && statusMatch
  })
})

const { currentPage, total, from, to, paginatedItems } = usePagination(filteredItems, perPage)

watch(filteredItems, () => {
  currentPage.value = 1
})

// Header Action Button (Only for Admin Sekolah)
const headerActions = computed(() => {
  if (!isAdminSekolah.value) return []
  return [
    {
      label: 'Tambah Mapel',
      icon: Plus,
      click: handleCreate
    }
  ]
})

// --- Detail Sheet State & Methods ---
const isDetailSheetOpen = ref(false)
const selectedItemForDetail = ref(null)

const handleViewDetail = (id) => {
  const item = subjects.value.find(s => s.id === id)
  if (item) {
    selectedItemForDetail.value = item
    isDetailSheetOpen.value = true
  }
}

// Dynamic fields for Detail Drawer
const rawDetailItem = computed(() => {
  if (!selectedItemForDetail.value) return {}
  return {
    nama: selectedItemForDetail.value.nama,
    kode: selectedItemForDetail.value.kode,
    status: getStatusLabel(selectedItemForDetail.value.status, selectedItemForDetail.value)
  }
})

const detailSections = computed(() => {
  if (!selectedItemForDetail.value) return []
  
  const sections = [
    {
      title: 'Informasi Umum',
      fields: [
        { label: 'Kode Mata Pelajaran', value: selectedItemForDetail.value.kode },
        { label: 'Nama Mata Pelajaran', value: selectedItemForDetail.value.nama },
        { label: 'Keterangan', value: selectedItemForDetail.value.deskripsi || '-' }
      ]
    },
    {
      title: 'Status Verifikasi',
      fields: [
        { label: 'Status Dokumen', value: getStatusLabel(selectedItemForDetail.value.status, selectedItemForDetail.value) }
      ]
    }
  ]

  if (selectedItemForDetail.value.status === 'rejected' && selectedItemForDetail.value.rejectedReason) {
    sections[1].fields.push({
      label: 'Alasan Penolakan',
      value: selectedItemForDetail.value.rejectedReason
    })
  }

  return sections
})

// --- Workflow Modal States & Functions ---

// 1. AJUKAN (Admin)
const isRequestConfirmOpen = ref(false)
const selectedItemToRequest = ref(null)

const openRequestConfirm = (item) => {
  selectedItemToRequest.value = item
  isRequestConfirmOpen.value = true
}

const confirmRequest = async () => {
  if (!selectedItemToRequest.value) return
  
  try {
    await updateSubject(selectedItemToRequest.value.id, {
      code: selectedItemToRequest.value.kode,
      name: selectedItemToRequest.value.nama,
      description: selectedItemToRequest.value.deskripsi,
      is_active: true // Auto approve for now
    })
    isRequestConfirmOpen.value = false
    toast.success('Pengajuan Dikirim', {
      description: `Mata pelajaran "${selectedItemToRequest.value.nama}" telah diaktifkan.`
    })
    selectedItemToRequest.value = null
    fetchSubjectsData()
  } catch (err) {
    toast.error('Gagal mengajukan mata pelajaran')
  }
}

// 2. DELETE (Admin)
const isDeleteConfirmOpen = ref(false)
const selectedItemToDelete = ref(null)

const openDeleteConfirm = (item) => {
  selectedItemToDelete.value = item
  isDeleteConfirmOpen.value = true
}

const confirmDelete = async () => {
  if (!selectedItemToDelete.value) return
  
  try {
    await deleteSubject(selectedItemToDelete.value.id)
    isDeleteConfirmOpen.value = false
    toast.success('Berhasil Dihapus', {
      description: `Mata pelajaran "${selectedItemToDelete.value.nama}" telah dihapus.`
    })
    selectedItemToDelete.value = null
    fetchSubjectsData()
  } catch (err) {
    toast.error('Gagal menghapus mata pelajaran')
  }
}

// 3. APPROVE (Kepsek)
const isApproveConfirmOpen = ref(false)
const selectedItemToApprove = ref(null)

const openApproveConfirm = (item) => {
  selectedItemToApprove.value = item
  isApproveConfirmOpen.value = true
}

const confirmApprove = async () => {
  if (!selectedItemToApprove.value) return
  
  try {
    await updateSubject(selectedItemToApprove.value.id, {
      code: selectedItemToApprove.value.kode,
      name: selectedItemToApprove.value.nama,
      description: selectedItemToApprove.value.deskripsi,
      is_active: true
    })
    isApproveConfirmOpen.value = false
    toast.success('Mata Pelajaran Disetujui', {
      description: `Mata pelajaran "${selectedItemToApprove.value.nama}" kini berstatus Aktif.`
    })
    selectedItemToApprove.value = null
    fetchSubjectsData()
  } catch (err) {
    toast.error('Gagal menyetujui mata pelajaran')
  }
}

// 4. REJECT (Kepsek)
const isRejectDialogOpen = ref(false)
const selectedItemToReject = ref(null)
const rejectReason = ref('')
const rejectReasonError = ref('')

const openRejectDialog = (item) => {
  selectedItemToReject.value = item
  rejectReason.value = ''
  rejectReasonError.value = ''
  isRejectDialogOpen.value = true
}

const confirmReject = async () => {
  if (!rejectReason.value.trim()) {
    rejectReasonError.value = 'Alasan penolakan wajib diisi.'
    return
  }
  
  try {
    await updateSubject(selectedItemToReject.value.id, {
      code: selectedItemToReject.value.kode,
      name: selectedItemToReject.value.nama,
      description: selectedItemToReject.value.deskripsi,
      is_active: false
    })
    isRejectDialogOpen.value = false
    toast.success('Pengajuan Ditolak', {
      description: `Mata pelajaran "${selectedItemToReject.value.nama}" telah dinonaktifkan.`
    })
    selectedItemToReject.value = null
    fetchSubjectsData()
  } catch (err) {
    toast.error('Gagal menolak mata pelajaran')
  }
}

const toggleActiveState = async (item) => {
  const nextState = !item.isActive
  try {
    await updateSubject(item.id, {
      code: item.kode,
      name: item.nama,
      description: item.deskripsi,
      is_active: nextState
    })
    toast.success(nextState ? 'Mata Pelajaran Diaktifkan' : 'Mata Pelajaran Dinonaktifkan', {
      description: `Mata pelajaran "${item.nama}" telah ${nextState ? 'diaktifkan' : 'dinonaktifkan'}.`
    })
    fetchSubjectsData()
  } catch (err) {
    toast.error('Gagal mengubah status aktif')
  }
}
</script>

<template>
  <div class="space-y-6 w-full mx-auto px-0 text-left">
    <PageHeader
      title="Data Mata Pelajaran"
      description="Kelola dan setujui daftar kurikulum mata pelajaran tingkat sekolah dasar"
      :actions="headerActions"
    />

    <!-- Statistik Panel -->
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        v-for="(stat, index) in stats"
        :key="index"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
        :variant="stat.variant"
      />
    </div>

    <!-- Data Table Card -->
    <DataTableCard
      :columns="columns"
      :items="paginatedItems"
      :filters="filters"
      :loading="isLoading"
      v-model:filterValues="filterValues"
      v-model:perPage="perPage"
      :from="from"
      :to="to"
      :total="total"
      :page="currentPage"
      @update:page="currentPage = $event"
    >
      <!-- Custom Grades Render -->
      <template #cell-grades="{ value }">
        <div class="flex flex-wrap gap-1 max-w-[140px]">
          <Badge
            v-for="g in (value || [])"
            :key="g"
            variant="outline"
            class="text-[10px] px-1.5 py-0 border-primary/20 bg-primary/5 text-primary font-semibold"
          >
            Kls {{ g }}
          </Badge>
          <span v-if="!value || value.length === 0" class="text-xs text-muted-foreground italic">Semua Kelas</span>
        </div>
      </template>

      <!-- Custom Badge Status Render -->
      <template #cell-status="{ value, item }">
        <div class="flex flex-col items-center gap-1">
          <Badge :variant="getStatusBadgeVariant(value, item)">
            {{ getStatusLabel(value, item) }}
          </Badge>
          <!-- Show small reason if rejected -->
          <p 
            v-if="value === 'rejected' && item.rejectedReason" 
            class="text-[9px] text-rose-500 font-bold mt-0.5 max-w-[150px] truncate"
            :title="item.rejectedReason"
          >
            Alasan: "{{ item.rejectedReason }}"
          </p>
        </div>
      </template>

      <!-- Custom Actions Render (Role & Status Aware) -->
      <template #cell-actions="{ item }">
        <div class="flex items-center justify-center gap-3">
          <!-- Show button (Always available) -->
          <button
            class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-foreground transition-colors"
            title="Detail"
            @click="handleViewDetail(item.id)"
          >
            <Eye class="size-4 transition-transform group-hover/btn:scale-110" />
            <span class="text-[9px] font-semibold leading-none">Detail</span>
          </button>

          <!-- Aksi untuk Admin Sekolah -->
          <template v-if="isAdminSekolah">
            <!-- Edit: Available for Admin Sekolah -->
            <button
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-primary transition-colors"
              title="Edit Mata Pelajaran"
              @click="handleEdit(item)"
            >
              <Pencil class="size-4 transition-transform group-hover/btn:scale-110 text-primary/80" />
              <span class="text-[9px] font-semibold leading-none">Edit</span>
            </button>

            <!-- Hapus: Draft or Rejected only -->
            <button
              v-if="item.status === 'draft' || item.status === 'rejected'"
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-rose-500 transition-colors"
              title="Hapus"
              @click="openDeleteConfirm(item)"
            >
              <Trash2 class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Hapus</span>
            </button>

            <!-- Ajukan: Draft or Rejected only -->
            <button
              v-if="item.status === 'draft' || item.status === 'rejected'"
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-foreground transition-colors"
              title="Ajukan"
              @click="openRequestConfirm(item)"
            >
              <Send class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Ajukan</span>
            </button>

            <!-- Lock: Pending only -->
            <div 
              v-if="item.status === 'pending'" 
              class="flex flex-col items-center justify-center gap-0.5 text-muted-foreground/45"
            >
              <Lock class="size-4" />
              <span class="text-[9px] font-semibold leading-none">Ditinjau</span>
            </div>

            <!-- Toggle Active State: Approved only -->
            <button
              v-if="item.status === 'approved'"
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none transition-colors"
              :class="item.isActive !== false ? 'text-emerald-500 hover:text-emerald-600' : 'text-muted-foreground hover:text-foreground'"
              :title="item.isActive !== false ? 'Nonaktifkan' : 'Aktifkan'"
              @click="toggleActiveState(item)"
            >
              <component :is="item.isActive !== false ? ToggleRight : ToggleLeft" class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">{{ item.isActive !== false ? 'Nonaktifkan' : 'Aktifkan' }}</span>
            </button>
          </template>

          <!-- Aksi untuk Kepala Sekolah -->
          <template v-if="isKepalaSekolah">
            <!-- Setujui: Pending only -->
            <button
              v-if="item.status === 'pending'"
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-emerald-500 hover:text-emerald-600 transition-colors"
              title="Setujui"
              @click="openApproveConfirm(item)"
            >
              <Check class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Setujui</span>
            </button>

            <!-- Tolak: Pending only -->
            <button
              v-if="item.status === 'pending'"
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-rose-500 hover:text-rose-600 transition-colors"
              title="Tolak"
              @click="openRejectDialog(item)"
            >
              <X class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Tolak</span>
            </button>

            <!-- Lock Status: Approved or Draft/Rejected -->
            <div 
              v-if="item.status !== 'pending'" 
              class="flex flex-col items-center justify-center gap-0.5 text-muted-foreground/45"
            >
              <Lock v-if="item.status === 'approved'" class="size-4" />
              <Unlock v-else class="size-4" />
              <span class="text-[9px] font-semibold leading-none">{{ item.status === 'approved' ? (item.isActive !== false ? 'Aktif' : 'Nonaktif') : 'Draf' }}</span>
            </div>
          </template>
        </div>
      </template>
    </DataTableCard>

    <!-- Detail Drawer Sheet -->
    <DataSheet
      v-model:open="isDetailSheetOpen"
      :item="rawDetailItem"
      title-key="nama"
      description-key="kode"
      description-prefix="Kode Mapel: "
      badge-key="status"
      :sections="detailSections"
    />

    <!-- Form Sheet (Inline Create/Edit) -->
    <Sheet v-model:open="isFormSheetOpen">
      <SheetContent class="sm:max-w-[500px] flex flex-col h-full gap-2">
        <SheetHeader class="border-b border-border pb-3 text-left">
          <SheetTitle class="text-base font-bold text-foreground">
            {{ isEditMode ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' }}
          </SheetTitle>
          <SheetDescription class="text-xs text-muted-foreground">
            {{ isEditMode ? 'Perbarui informasi dan alokasi tingkat kelas mata pelajaran.' : 'Tambahkan mata pelajaran baru untuk jenjang sekolah.' }}
          </SheetDescription>
        </SheetHeader>

        <div class="flex-1 overflow-y-auto py-6 pr-1 space-y-6 no-scrollbar">
          <MataPelajaranForm :form="formItem" :is-edit="isEditMode" />
        </div>

        <FormFooter
          :loading="isSaving"
          :is-edit="isEditMode"
          entity-name="Mata Pelajaran"
          :summary-items="saveSummary"
          :before-confirm="validateForm"
          @save="executeSave"
        />
      </SheetContent>
    </Sheet>

    <!-- Workflow Modals -->

    <!-- 1. REQUEST CONFIRM DIALOG -->
    <Dialog v-model:open="isRequestConfirmOpen">
      <DialogContent class="sm:max-w-[400px] rounded-2xl p-6">
        <DialogHeader>
          <DialogTitle class="text-sm font-bold text-foreground flex items-center gap-1.5">
            <Send class="h-5 w-5 text-emerald-500 animate-bounce" />
            Ajukan Persetujuan Mata Pelajaran
          </DialogTitle>
          <DialogDescription class="text-[10px] text-muted-foreground leading-relaxed mt-2 text-left">
            Apakah Anda yakin ingin mengajukan Mata Pelajaran <strong class="text-foreground">"{{ selectedItemToRequest?.nama }}"</strong> ke Kepala Sekolah? Data mata pelajaran akan dikunci dari perubahan selama proses peninjauan.
          </DialogDescription>
        </DialogHeader>

        <DialogFooter class="flex flex-row items-center justify-end gap-2 pt-4 border-t mt-4">
          <Button
            type="button"
            variant="ghost"
            class="text-xs font-bold rounded-xl cursor-pointer"
            @click="isRequestConfirmOpen = false"
          >
            Batal
          </Button>
          <Button
            type="button"
            class="text-xs font-bold rounded-xl cursor-pointer bg-emerald-500 text-white hover:bg-emerald-600 border-none shadow-none"
            @click="confirmRequest"
          >
            Ya, Ajukan
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- 2. DELETE CONFIRM DIALOG -->
    <Dialog v-model:open="isDeleteConfirmOpen">
      <DialogContent class="sm:max-w-[400px] rounded-2xl p-6">
        <DialogHeader>
          <DialogTitle class="text-sm font-bold text-foreground flex items-center gap-1.5">
            <Trash2 class="h-5 w-5 text-rose-500 animate-bounce" />
            Hapus Mata Pelajaran
          </DialogTitle>
          <DialogDescription class="text-[10px] text-muted-foreground leading-relaxed mt-2 text-left">
            Apakah Anda yakin ingin menghapus Mata Pelajaran <strong class="text-foreground">"{{ selectedItemToDelete?.nama }}"</strong>? Tindakan ini tidak dapat dibatalkan.
          </DialogDescription>
        </DialogHeader>

        <DialogFooter class="flex flex-row items-center justify-end gap-2 pt-4 border-t mt-4">
          <Button
            type="button"
            variant="ghost"
            class="text-xs font-bold rounded-xl cursor-pointer"
            @click="isDeleteConfirmOpen = false"
          >
            Batal
          </Button>
          <Button
            type="button"
            class="text-xs font-bold rounded-xl cursor-pointer bg-rose-500 text-white hover:bg-rose-600 border-none shadow-none"
            @click="confirmDelete"
          >
            Ya, Hapus
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- 3. APPROVE CONFIRM DIALOG -->
    <Dialog v-model:open="isApproveConfirmOpen">
      <DialogContent class="sm:max-w-[400px] rounded-2xl p-6">
        <DialogHeader>
          <DialogTitle class="text-sm font-bold text-foreground flex items-center gap-1.5">
            <Check class="h-5 w-5 text-emerald-500 animate-bounce" />
            Setujui Mata Pelajaran
          </DialogTitle>
          <DialogDescription class="text-[10px] text-muted-foreground leading-relaxed mt-2 text-left">
            Apakah Anda yakin ingin menyetujui kurikulum Mata Pelajaran <strong class="text-foreground">"{{ selectedItemToApprove?.nama }}"</strong>? Mata pelajaran ini akan menjadi Aktif dan dapat dijadwalkan di kelas.
          </DialogDescription>
        </DialogHeader>

        <DialogFooter class="flex flex-row items-center justify-end gap-2 pt-4 border-t mt-4">
          <Button
            type="button"
            variant="ghost"
            class="text-xs font-bold rounded-xl cursor-pointer"
            @click="isApproveConfirmOpen = false"
          >
            Batal
          </Button>
          <Button
            type="button"
            class="text-xs font-bold rounded-xl cursor-pointer bg-emerald-500 text-white hover:bg-emerald-600 border-none shadow-none"
            @click="confirmApprove"
          >
            Ya, Setujui
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- 4. REJECT DIALOG -->
    <Dialog v-model:open="isRejectDialogOpen">
      <DialogContent class="sm:max-w-[400px] rounded-2xl p-6">
        <DialogHeader>
          <DialogTitle class="text-sm font-bold text-foreground flex items-center gap-1.5">
            <AlertCircle class="h-5 w-5 text-rose-500" />
            Tolak Mata Pelajaran
          </DialogTitle>
          <DialogDescription class="text-[10px] text-muted-foreground leading-relaxed text-left">
            Apakah Anda yakin ingin menolak pengajuan Mata Pelajaran <strong class="text-foreground">"{{ selectedItemToReject?.nama }}"</strong>? Silakan berikan alasan di bawah ini.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-3 py-3 text-left">
          <FormTextArea
            label="Alasan Penolakan"
            placeholder="Contoh: Deskripsi kurang lengkap, sesuaikan dengan kurikulum Merdeka Belajar..."
            v-model="rejectReason"
            :error="rejectReasonError"
            required
            :rows="3"
          />
        </div>

        <DialogFooter class="flex flex-row items-center justify-end gap-2 pt-2 border-t mt-2">
          <Button
            type="button"
            variant="ghost"
            class="text-xs font-bold rounded-xl cursor-pointer"
            @click="isRejectDialogOpen = false"
          >
            Batal
          </Button>
          <Button
            type="button"
            class="text-xs font-bold rounded-xl cursor-pointer bg-rose-500 text-white hover:bg-rose-600"
            @click="confirmReject"
          >
            Tolak Mapel
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
