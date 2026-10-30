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
import { getEkskul, saveEkskul, columns, filters } from './data/mockEkskul'

const auth = useAuthStore()

// --- Role & Permissions ---
const role = computed(() => auth.user?.role || 'admin_sekolah')
const isAdminSekolah = computed(() => role.value === 'admin_sekolah')
const isKepalaSekolah = computed(() => role.value === 'kepala_sekolah')

// --- State ---
const ekskuls = ref([])
const perPage = ref(5)
const filterValues = ref({
  search: '',
  status: 'all'
})

onMounted(() => {
  ekskuls.value = getEkskul()
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
const formErrors = ref({})
const formItem = ref({
  id: '',
  nama: '',
  deskripsi: '',
  status: 'aktif'
})

const handleCreate = () => {
  isEditMode.value = false
  formErrors.value = {}
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
  formErrors.value = {}
  formItem.value = {
    id: item.id,
    nama: item.nama,
    deskripsi: item.deskripsi || '',
    status: item.status || 'aktif'
  }
  isFormSheetOpen.value = true
}

const validateForm = () => {
  const errors = {}
  if (!formItem.value.nama?.trim()) {
    errors.nama = 'Nama ekstrakulikuler wajib diisi.'
  }
  if (!formItem.value.deskripsi?.trim()) {
    errors.deskripsi = 'Deskripsi wajib diisi.'
  }
  if (!formItem.value.status) {
    errors.status = 'Status wajib dipilih.'
  }
  formErrors.value = errors
  if (Object.keys(errors).length > 0) {
    toast.error('Gagal Menyimpan', {
      description: 'Harap lengkapi semua field yang wajib diisi.'
    })
    return false
  }

  const nama = formItem.value.nama.trim()
  const isDuplicate = ekskuls.value.some(s => {
    const isSameName = s.nama.toLowerCase() === nama.toLowerCase()
    if (isEditMode.value) {
      return isSameName && s.id !== formItem.value.id
    }
    return isSameName
  })

  if (isDuplicate) {
    formErrors.value.nama = `Ekstrakulikuler "${nama}" sudah terdaftar.`
    toast.error('Nama Sudah Digunakan', {
      description: `Ekstrakulikuler dengan nama "${nama}" sudah ada.`
    })
    return false
  }

  return true
}

const saveSummary = computed(() => [
  { label: 'Nama Ekstrakurikuler', value: formItem.value.nama },
  { label: 'Status', value: getStatusLabel(formItem.value.status) }
])

const handleSave = () => {
  const nama = formItem.value.nama.trim()
  const deskripsi = formItem.value.deskripsi.trim()
  const status = formItem.value.status

  if (isEditMode.value) {
    const updated = ekskuls.value.map(s => {
      if (s.id === formItem.value.id) {
        return { ...s, nama, deskripsi, status }
      }
      return s
    })
    ekskuls.value = updated
    saveEkskul(updated)
    toast.success('Berhasil Diperbarui', {
      description: `Ekstrakulikuler "${nama}" telah diperbarui.`
    })
  } else {
    const newItem = {
      id: String(Date.now()),
      nama,
      deskripsi,
      status
    }
    const updated = [newItem, ...ekskuls.value]
    ekskuls.value = updated
    saveEkskul(updated)
    toast.success('Berhasil Ditambahkan', {
      description: `Ekstrakulikuler "${nama}" telah ditambahkan.`
    })
  }

  isFormSheetOpen.value = false
}

// --- Search & Filter ---
const filteredItems = computed(() => {
  return ekskuls.value.filter(item => {
    const searchVal = filterValues.value.search?.trim().toLowerCase() || ''
    const searchMatch =
      !searchVal ||
      item.nama.toLowerCase().includes(searchVal) ||
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
  if (!isAdminSekolah.value) return []
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
  const item = ekskuls.value.find(s => s.id === id)
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
const handleToggleStatus = item => {
  const nextStatus = item.status === 'aktif' ? 'nonaktif' : 'aktif'
  const updated = ekskuls.value.map(s => {
    if (s.id === item.id) {
      return { ...s, status: nextStatus }
    }
    return s
  })
  ekskuls.value = updated
  saveEkskul(updated)
  toast.success(nextStatus === 'aktif' ? 'Ekskul Diaktifkan' : 'Ekskul Dinonaktifkan', {
    description: `Ekstrakulikuler "${item.nama}" kini berstatus ${nextStatus}.`
  })
}

// --- Delete ---
const isDeleteConfirmOpen = ref(false)
const selectedItemToDelete = ref(null)

const openDeleteConfirm = item => {
  selectedItemToDelete.value = item
  isDeleteConfirmOpen.value = true
}

const confirmDelete = () => {
  if (!selectedItemToDelete.value) return

  const updated = ekskuls.value.filter(s => s.id !== selectedItemToDelete.value.id)
  ekskuls.value = updated
  saveEkskul(updated)
  isDeleteConfirmOpen.value = false

  toast.success('Berhasil Dihapus', {
    description: `Ekstrakulikuler "${selectedItemToDelete.value.nama}" telah dihapus.`
  })
  selectedItemToDelete.value = null
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
            class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-foreground transition-colors"
            title="Detail"
            @click="handleViewDetail(item.id)"
          >
            <Eye class="size-4 transition-transform group-hover/btn:scale-110" />
            <span class="text-[9px] font-semibold leading-none">Detail</span>
          </button>

          <!-- Aksi Admin Sekolah -->
          <template v-if="isAdminSekolah">
            <!-- Edit -->
            <button
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-foreground transition-colors"
              title="Edit"
              @click="handleEdit(item)"
            >
              <Pencil class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Edit</span>
            </button>

            <!-- Hapus -->
            <button
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-foreground transition-colors"
              title="Hapus"
              @click="openDeleteConfirm(item)"
            >
              <Trash2 class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Hapus</span>
            </button>

            <!-- Toggle Status -->
            <button
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none transition-colors"
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
      <SheetContent
        class="sm:max-w-[500px] flex flex-col h-full gap-2"
      >
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
          :is-edit="isEditMode"
          entity-name="Ekstrakurikuler"
          :summary-items="saveSummary"
          :before-confirm="validateForm"
          @save="handleSave"
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
  </div>
</template>
