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
import { useZodForm } from '@/composables/useZodForm'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription
} from '@/components/ui/sheet'
import EkskulForm from './components/EkskulForm.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { Award, Plus, Eye, Pencil, Trash2, ToggleLeft, ToggleRight } from 'lucide-vue-next'
import { columns, filters } from './data/ekskulConfig'
import { ekskulSchema } from './data/ekskulSchema'
import {
  fetchAllEkskul,
  createEkskul,
  updateEkskul,
  deleteEkskul,
  toggleEkskulStatus
} from '@/services/ekskulService'

const auth = useAuthStore()

// --- Role & Permissions ---
const role = computed(() => auth.user?.role || 'admin_sekolah')
const canManage = computed(() => ['superadmin', 'admin_yayasan', 'admin_sekolah'].includes(role.value))

// --- State ---
const ekskuls = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const perPage = ref(10)
const filterValues = ref({
  search: '',
  status: 'all'
})

// --- Fetch Data from API ---
const loadEkskuls = async () => {
  isLoading.value = true
  try {
    const res = await fetchAllEkskul()
    ekskuls.value = res.data || []
  } catch (err) {
    console.error('Gagal memuat ekskul:', err)
    toast.error('Gagal memuat data ekstrakulikuler')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadEkskuls()
})

// --- Computed Stats ---
const totalCount = computed(() => ekskuls.value.length)
const aktifCount = computed(() => ekskuls.value.filter(s => s.status === 'aktif').length)
const nonaktifCount = computed(() => ekskuls.value.filter(s => s.status === 'nonaktif').length)

// --- Helper ---
function getStatusLabel(status) {
  return status === 'aktif' ? 'Aktif' : 'Nonaktif'
}

function getStatusBadgeVariant(status) {
  return status === 'aktif' ? 'green' : 'gray'
}

// --- Form Sheet State & Methods ---
const isFormSheetOpen = ref(false)
const isEditMode = ref(false)
const formItem = ref({
  id: '',
  nama: '',
  deskripsi: '',
  status: 'aktif'
})

const {
  errors: formErrors,
  validate: validateForm,
  clearErrors
} = useZodForm(ekskulSchema, formItem)

const handleCreate = () => {
  isEditMode.value = false
  clearErrors()
  formItem.value = {
    id: '',
    nama: '',
    deskripsi: '',
    status: 'aktif'
  }
  isFormSheetOpen.value = true
}

const handleEdit = item => {
  isEditMode.value = true
  clearErrors()
  formItem.value = {
    id: item.id,
    nama: item.nama,
    deskripsi: item.deskripsi || '',
    status: item.status || 'aktif'
  }
  isFormSheetOpen.value = true
}

const saveSummary = computed(() => [
  { label: 'Nama Ekstrakulikuler', value: formItem.value.nama },
  { label: 'Status', value: getStatusLabel(formItem.value.status) }
])

const handleSave = async () => {
  isSaving.value = true
  try {
    const payload = {
      nama: formItem.value.nama.trim(),
      deskripsi: formItem.value.deskripsi ? formItem.value.deskripsi.trim() : null,
      status: formItem.value.status
    }

    if (isEditMode.value) {
      await updateEkskul(formItem.value.id, payload)
      toast.success('Berhasil Diperbarui', {
        description: `Ekstrakulikuler "${payload.nama}" telah diperbarui.`
      })
    } else {
      await createEkskul(payload)
      toast.success('Berhasil Ditambahkan', {
        description: `Ekstrakulikuler "${payload.nama}" telah ditambahkan.`
      })
    }
    isFormSheetOpen.value = false
    await loadEkskuls()
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal menyimpan ekstrakulikuler'
    toast.error('Gagal Menyimpan', { description: msg })
  } finally {
    isSaving.value = false
  }
}

// --- Search & Filter ---
const filteredItems = computed(() => {
  return ekskuls.value.filter(item => {
    const searchVal = filterValues.value.search?.trim().toLowerCase() || ''
    const searchMatch =
      !searchVal ||
      item.nama?.toLowerCase().includes(searchVal) ||
      (item.deskripsi || '').toLowerCase().includes(searchVal)

    const statusVal = filterValues.value.status
    const statusMatch = !statusVal || statusVal === 'all' || item.status === statusVal

    return searchMatch && statusMatch
  })
})

const { currentPage, total, from, to, paginatedItems } = usePagination(filteredItems, perPage)

watch(filteredItems, () => {
  currentPage.value = 1
})

// --- Header Action Button ---
const headerActions = computed(() => {
  if (!canManage.value) return []
  return [
    {
      label: 'Tambah Ekskul',
      icon: Plus,
      click: handleCreate
    }
  ]
})

// --- Detail Sheet ---
const isDetailSheetOpen = ref(false)
const selectedItemForDetail = ref(null)

const handleViewDetail = id => {
  const item = ekskuls.value.find(s => String(s.id) === String(id))
  if (item) {
    selectedItemForDetail.value = item
    isDetailSheetOpen.value = true
  }
}

const rawDetailItem = computed(() => {
  if (!selectedItemForDetail.value) return {}
  return {
    nama: selectedItemForDetail.value.nama,
    status: getStatusLabel(selectedItemForDetail.value.status)
  }
})

const detailSections = computed(() => {
  if (!selectedItemForDetail.value) return []
  return [
    {
      id: 'info',
      title: 'Informasi Ekstrakulikuler',
      fields: [
        { label: 'Nama Ekstrakulikuler', value: selectedItemForDetail.value.nama },
        { label: 'Deskripsi', value: selectedItemForDetail.value.deskripsi || '-', textarea: true },
        { label: 'Status', value: getStatusLabel(selectedItemForDetail.value.status) }
      ]
    }
  ]
})

// --- Toggle Status ---
const handleToggleStatus = async item => {
  const nextStatus = item.status === 'aktif' ? 'nonaktif' : 'aktif'
  try {
    await toggleEkskulStatus(item.id, nextStatus)
    item.status = nextStatus
    toast.success(nextStatus === 'aktif' ? 'Ekskul Diaktifkan' : 'Ekskul Dinonaktifkan', {
      description: `Ekstrakulikuler "${item.nama}" kini berstatus ${nextStatus}.`
    })
  } catch (err) {
    toast.error('Gagal mengubah status ekstrakulikuler')
  }
}

// --- Delete ---
const isDeleteConfirmOpen = ref(false)
const selectedItemToDelete = ref(null)
const isDeleting = ref(false)

const openDeleteConfirm = item => {
  selectedItemToDelete.value = item
  isDeleteConfirmOpen.value = true
}

const confirmDelete = async () => {
  if (!selectedItemToDelete.value) return
  isDeleting.value = true
  try {
    await deleteEkskul(selectedItemToDelete.value.id)
    toast.success('Berhasil Dihapus', {
      description: `Ekstrakulikuler "${selectedItemToDelete.value.nama}" telah dihapus.`
    })
    isDeleteConfirmOpen.value = false
    selectedItemToDelete.value = null
    await loadEkskuls()
  } catch (err) {
    toast.error('Gagal menghapus ekstrakulikuler')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <div class="space-y-6 w-full mx-auto px-0 text-left">
    <PageHeader
      title="Ekstrakulikuler"
      description="Kelola data kegiatan ekstrakurikuler di lingkungan sekolah"
      :actions="headerActions"
    />

    <!-- Statistik Panel -->
    <div class="grid gap-4 grid-cols-1 sm:grid-cols-3">
      <StatCard
        label="TOTAL EKSKUL"
        :value="String(totalCount)"
        :icon="Award"
        variant="primary"
      />
      <StatCard
        label="AKTIF"
        :value="String(aktifCount)"
        :icon="ToggleRight"
        variant="emerald"
      />
      <StatCard
        label="NONAKTIF"
        :value="String(nonaktifCount)"
        :icon="ToggleLeft"
        variant="violet"
      />
    </div>

    <!-- Data Table Card -->
    <DataTableCard
      :columns="columns"
      :items="paginatedItems"
      :loading="isLoading"
      :filters="filters"
      v-model:filterValues="filterValues"
      v-model:perPage="perPage"
      :from="from"
      :to="to"
      :total="total"
      :page="currentPage"
      @update:page="currentPage = $event"
    >
      <!-- Custom Badge Status -->
      <template #cell-status="{ value }">
        <Badge :variant="getStatusBadgeVariant(value)">
          {{ getStatusLabel(value) }}
        </Badge>
      </template>

      <!-- Custom Actions (Role Aware) -->
      <template #cell-actions="{ item }">
        <div class="flex items-center justify-center gap-3">
          <!-- Detail (Always Available) -->
          <button
            class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
            title="Detail"
            @click="handleViewDetail(item.id)"
          >
            <Eye class="size-4 transition-transform group-hover/btn:scale-110" />
            <span class="text-[9px] font-semibold leading-none">Detail</span>
          </button>

          <!-- Aksi Pengelola (Admin Sekolah / Superadmin / Yayasan) -->
          <template v-if="canManage">
            <!-- Edit -->
            <button
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
              title="Edit"
              @click="handleEdit(item)"
            >
              <Pencil class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Edit</span>
            </button>

            <!-- Hapus -->
            <button
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-rose-600 transition-colors cursor-pointer"
              title="Hapus"
              @click="openDeleteConfirm(item)"
            >
              <Trash2 class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Hapus</span>
            </button>

            <!-- Toggle Status -->
            <button
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none transition-colors cursor-pointer"
              :class="
                item.status === 'aktif'
                  ? 'text-emerald-500 hover:text-emerald-600'
                  : 'text-muted-foreground hover:text-foreground'
              "
              :title="item.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan'"
              @click="handleToggleStatus(item)"
            >
              <component
                :is="item.status === 'aktif' ? ToggleRight : ToggleLeft"
                class="size-4 transition-transform group-hover/btn:scale-110"
              />
              <span class="text-[9px] font-semibold leading-none">
                {{ item.status === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
              </span>
            </button>
          </template>
        </div>
      </template>
    </DataTableCard>

    <!-- Detail Sheet -->
    <DataSheet
      v-model:open="isDetailSheetOpen"
      :item="rawDetailItem"
      title-key="nama"
      :badge="rawDetailItem.status"
      :badge-variant="selectedItemForDetail?.status === 'aktif' ? 'green' : 'gray'"
      :sections="detailSections"
    />

    <!-- Form Sheet (Create / Edit) -->
    <Sheet v-model:open="isFormSheetOpen">
      <SheetContent class="sm:max-w-[500px] flex flex-col h-full gap-2">
        <SheetHeader class="border-b border-border pb-3 text-left">
          <SheetTitle class="text-base font-bold text-foreground">
            {{ isEditMode ? 'Edit Ekstrakulikuler' : 'Tambah Ekstrakulikuler' }}
          </SheetTitle>
          <SheetDescription class="text-xs text-muted-foreground">
            {{
              isEditMode
                ? 'Perbarui informasi ekstrakulikuler.'
                : 'Tambahkan ekstrakulikuler baru untuk sekolah.'
            }}
          </SheetDescription>
        </SheetHeader>

        <div class="flex-1 overflow-y-auto py-6 pr-1 space-y-6 no-scrollbar">
          <EkskulForm
            :form="formItem"
            :errors="formErrors"
          />
        </div>

        <FormFooter
          :loading="isSaving"
          :is-edit="isEditMode"
          entity-name="Ekstrakurikuler"
          :summary-items="saveSummary"
          :before-confirm="validateForm"
          @submit="handleSave"
        />
      </SheetContent>
    </Sheet>

    <!-- Delete Confirm Dialog -->
    <Dialog v-model:open="isDeleteConfirmOpen">
      <DialogContent class="sm:max-w-[400px] rounded-2xl p-6">
        <DialogHeader>
          <DialogTitle class="text-sm font-bold text-foreground flex items-center gap-1.5">
            <Trash2 class="h-5 w-5 text-rose-500 animate-bounce" />
            Hapus Ekstrakulikuler
          </DialogTitle>
          <DialogDescription
            class="text-[10px] text-muted-foreground leading-relaxed mt-2 text-left"
          >
            Apakah Anda yakin ingin menghapus ekstrakulikuler
            <strong class="text-foreground">"{{ selectedItemToDelete?.nama }}"</strong>? Tindakan
            ini tidak dapat dibatalkan.
          </DialogDescription>
        </DialogHeader>

        <DialogFooter class="flex flex-row items-center justify-end gap-2 pt-4 border-t mt-4">
          <Button
            type="button"
            variant="ghost"
            class="text-xs font-bold rounded-xl cursor-pointer"
            :disabled="isDeleting"
            @click="isDeleteConfirmOpen = false"
          >
            Batal
          </Button>
          <Button
            type="button"
            class="text-xs font-bold rounded-xl cursor-pointer bg-rose-500 text-white hover:bg-rose-600 border-none shadow-none"
            :disabled="isDeleting"
            @click="confirmDelete"
          >
            {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
