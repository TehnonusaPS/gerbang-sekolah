<script setup>
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import StatCard from '@/components/stat-card/StatCard.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { Badge } from '@/components/ui/badge'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import { Calendar, Eye, Pencil, Plus, Trash2, ToggleLeft, ToggleRight, CheckCircle, XCircle } from 'lucide-vue-next'
import { glassSlide, glassFade } from '@/config/motion'
import { useZodForm } from '@/composables/useZodForm'
import { academicYearSchema } from './data/academicYearSchema'
import { createAcademicYear, updateAcademicYear } from '@/services/academicYearService'
import { useAcademicYear } from './composables/useAcademicYear'

import AcademicYearForm from './components/AcademicYearForm.vue'
import AcademicYearDetailSheet from './components/AcademicYearDetailSheet.vue'
import AcademicYearDeleteDialog from './components/AcademicYearDeleteDialog.vue'

// --- Composable ---
const {
  isLoading,
  perPage,
  filterValues,
  currentPage,
  total,
  from,
  to,
  paginatedItems,
  totalCount,
  aktifCount,
  nonaktifCount,
  columns,
  filters,
  fetchData,
  getStatusLabel,
  getStatusBadgeVariant,
  formatDate,
  handleToggleStatus,
  executeDelete
} = useAcademicYear()

onMounted(() => {
  fetchData()
})

// --- Form Sheet State & Zod ---
const isFormSheetOpen = ref(false)
const isEditMode = ref(false)
const isSaving = ref(false)

const formItem = ref({
  oddId: null,
  evenId: null,
  tahun: '',
  oddTanggalMulai: '',
  oddTanggalSelesai: '',
  evenTanggalMulai: '',
  evenTanggalSelesai: '',
  activeSemester: 'odd',
  status: 'nonaktif'
})

const {
  errors: formErrors,
  validate: validateForm,
  clearErrors
} = useZodForm(academicYearSchema, formItem)

const saveSummary = computed(() => {
  const summary = [
    { label: 'Tahun Ajaran', value: formItem.value.tahun },
    { label: 'Semester Ganjil', value: `${formItem.value.oddTanggalMulai || '-'} s/d ${formItem.value.oddTanggalSelesai || '-'}` },
    { label: 'Semester Genap', value: `${formItem.value.evenTanggalMulai || '-'} s/d ${formItem.value.evenTanggalSelesai || '-'}` }
  ]
  if (!isEditMode.value) {
    summary.push({
      label: 'Salin Kelas Lama',
      value: formItem.value.duplicateClasses ? 'Ya (Otomatis duplikasi struktur kelas)' : 'Tidak'
    })
  }
  return summary
})

const handleCreate = () => {
  isEditMode.value = false
  clearErrors()
  formItem.value = {
    oddId: null,
    evenId: null,
    tahun: '',
    oddTanggalMulai: '',
    oddTanggalSelesai: '',
    evenTanggalMulai: '',
    evenTanggalSelesai: '',
    activeSemester: 'odd',
    status: 'nonaktif',
    duplicateClasses: true
  }
  isFormSheetOpen.value = true
}

const handleEdit = (item) => {
  isEditMode.value = true
  clearErrors()
  formItem.value = {
    oddId: item.odd?.id || null,
    evenId: item.even?.id || null,
    tahun: item.tahun || '',
    oddTanggalMulai: item.odd?.tanggalMulai || '',
    oddTanggalSelesai: item.odd?.tanggalSelesai || '',
    evenTanggalMulai: item.even?.tanggalMulai || '',
    evenTanggalSelesai: item.even?.tanggalSelesai || '',
    activeSemester: 'odd',
    status: item.status || 'nonaktif'
  }
  isFormSheetOpen.value = true
}

const handleBeforeConfirm = async () => {
  const isValid = await validateForm()
  if (!isValid) {
    toast.error('Gagal Menyimpan', { description: 'Harap periksa kembali isian formulir Anda.' })
    return false
  }
  return true
}

const executeSave = async () => {
  isSaving.value = true
  try {
    const trimmedYear = formItem.value.tahun.trim()
    if (isEditMode.value) {
      const promises = []
      if (formItem.value.oddId) {
        promises.push(updateAcademicYear(formItem.value.oddId, {
          name: trimmedYear,
          start_date: formItem.value.oddTanggalMulai,
          end_date: formItem.value.oddTanggalSelesai
        }))
      }
      if (formItem.value.evenId) {
        promises.push(updateAcademicYear(formItem.value.evenId, {
          name: trimmedYear,
          start_date: formItem.value.evenTanggalMulai,
          end_date: formItem.value.evenTanggalSelesai
        }))
      }
      await Promise.all(promises)
      toast.success('Berhasil Diperbarui', { description: `Tahun ajaran "${trimmedYear}" telah diperbarui.` })
    } else {
      const payload = {
        name: trimmedYear,
        odd_start_date: formItem.value.oddTanggalMulai,
        odd_end_date: formItem.value.oddTanggalSelesai,
        even_start_date: formItem.value.evenTanggalMulai,
        even_end_date: formItem.value.evenTanggalSelesai,
        active_semester: formItem.value.activeSemester,
        duplicate_classes: Boolean(formItem.value.duplicateClasses)
      }
      const res = await createAcademicYear(payload)
      const resMessage = res.data?.message || `Tahun ajaran "${trimmedYear}" (Ganjil & Genap) telah ditambahkan.`
      toast.success('Berhasil Ditambahkan', { description: resMessage })
    }
    isFormSheetOpen.value = false
    await fetchData()
  } catch (err) {
    toast.error('Gagal menyimpan tahun ajaran', { description: err.response?.data?.message || err.message })
  } finally {
    isSaving.value = false
  }
}

// --- Detail Sheet & Delete Modal States ---
const isDetailSheetOpen = ref(false)
const selectedDetailItem = ref(null)

const isDeleteDialogOpen = ref(false)
const selectedDeleteItem = ref(null)

function handleViewDetail(id) {
  const found = paginatedItems.value.find(t => t.id === id)
  if (found) {
    selectedDetailItem.value = found
    isDetailSheetOpen.value = true
  }
}

function openDeleteConfirm(item) {
  if (item.status === 'aktif') {
    toast.error('Tidak Dapat Dihapus', { description: 'Tahun ajaran yang berstatus aktif tidak dapat dihapus.' })
    return
  }
  selectedDeleteItem.value = item
  isDeleteDialogOpen.value = true
}

async function onConfirmDelete(item) {
  await executeDelete(item)
}

const headerActions = computed(() => [
  {
    label: 'Tambah Tahun Ajaran',
    icon: Plus,
    click: handleCreate
  }
])
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 w-full mx-auto px-0 text-left"
  >
    <!-- Page Header -->
    <PageHeader
      title="Daftar Tahun Ajaran"
      description="Kelola periode tahun akademik sekolah, atur durasi semester, dan aktifkan tahun ajaran berjalan."
      :actions="headerActions"
    />

    <!-- Informative Banner -->
    <div class="p-4 rounded-2xl bg-primary/5 dark:bg-primary/10 border border-primary/20 flex items-start gap-3 text-sm shadow-2xs">
      <div class="p-2 rounded-xl bg-primary/10 text-primary shrink-0">
        <Calendar class="size-4" />
      </div>
      <div>
        <h4 class="font-extrabold text-foreground dark:text-zinc-100">Penyusunan & Pembuatan Tahun Ajaran</h4>
        <p class="text-xs text-muted-foreground dark:text-zinc-400 mt-0.5 leading-relaxed">
          Setiap Tahun Ajaran mencakup <strong>Semester Ganjil</strong> & <strong>Semester Genap</strong>. Mengaktifkan Tahun Ajaran akan otomatis mengaktifkan kedua semester untuk periode tersebut.
        </p>
      </div>
    </div>

    <!-- Stat Cards -->
    <div
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 100 } }"
      class="grid gap-4 grid-cols-1 sm:grid-cols-3"
    >
      <StatCard
        label="TOTAL TAHUN AJARAN"
        :value="String(totalCount)"
        :icon="Calendar"
        illustration="globe"
        variant="primary"
      />
      <StatCard
        label="TAHUN AJARAN AKTIF"
        :value="String(aktifCount)"
        :icon="CheckCircle"
        illustration="school_bell"
        variant="emerald"
      />
      <StatCard
        label="TAHUN AJARAN NONAKTIF"
        :value="String(nonaktifCount)"
        :icon="XCircle"
        illustration="abc_board"
        variant="violet"
      />
    </div>

    <!-- Data Table Card -->
    <div
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 200 } }"
    >
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
        <!-- Custom Date Cells -->
        <template #cell-tanggalMulai="{ value }">
          <span class="text-xs font-semibold text-foreground font-mono">
            {{ formatDate(value) }}
          </span>
        </template>
        
        <template #cell-tanggalSelesai="{ value }">
          <span class="text-xs font-semibold text-foreground font-mono">
            {{ formatDate(value) }}
          </span>
        </template>

        <!-- Custom Status Badge -->
        <template #cell-status="{ value }">
          <Badge :variant="getStatusBadgeVariant(value)" class="px-2.5 py-0.5 rounded-full text-xs font-bold">
            {{ getStatusLabel(value) }}
          </Badge>
        </template>

        <!-- Custom Actions -->
        <template #cell-actions="{ item }">
          <div class="flex items-center justify-center gap-3">
            <!-- Detail -->
            <button
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-primary transition-colors cursor-pointer"
              title="Lihat Detail Rincian Semester"
              @click="handleViewDetail(item.id)"
            >
              <Eye class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Detail</span>
            </button>

            <!-- Edit -->
            <button
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
              title="Edit Data Tahun Ajaran"
              @click="handleEdit(item)"
            >
              <Pencil class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Edit</span>
            </button>

            <!-- Toggle Status (Aktivasi) -->
            <button
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none transition-colors cursor-pointer"
              :class="item.status === 'aktif' ? 'text-emerald-500 hover:text-emerald-600' : 'text-muted-foreground hover:text-foreground'"
              :title="item.status === 'aktif' ? 'Status Aktif' : 'Klik untuk mengaktifkan'"
              @click="handleToggleStatus(item)"
            >
              <component
                :is="item.status === 'aktif' ? ToggleRight : ToggleLeft"
                class="size-4 transition-transform group-hover/btn:scale-110"
              />
              <span class="text-[9px] font-semibold leading-none">
                {{ item.status === 'aktif' ? 'Aktif' : 'Aktifkan' }}
              </span>
            </button>

            <!-- Delete (If not active) -->
            <button
              v-if="item.status !== 'aktif'"
              class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-rose-500 transition-colors cursor-pointer"
              title="Hapus Tahun Ajaran"
              @click="openDeleteConfirm(item)"
            >
              <Trash2 class="size-4 transition-transform group-hover/btn:scale-110" />
              <span class="text-[9px] font-semibold leading-none">Hapus</span>
            </button>
          </div>
        </template>
      </DataTableCard>
    </div>

    <!-- Form Sheet (Create / Edit) -->
    <Sheet v-model:open="isFormSheetOpen">
      <SheetContent class="sm:max-w-[500px] flex flex-col h-full gap-2 text-left p-6">
        <SheetHeader class="border-b border-border/60 pb-3 text-left">
          <div class="flex items-center gap-2.5">
            <div class="p-2 rounded-xl bg-primary/10 text-primary shrink-0">
              <Calendar class="size-4" />
            </div>
            <div>
              <SheetTitle class="text-base font-bold text-foreground">
                {{ isEditMode ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran' }}
              </SheetTitle>
              <SheetDescription class="text-xs text-muted-foreground mt-0.5">
                {{ isEditMode ? 'Perbarui detail data tahun ajaran sekolah.' : 'Tambahkan periode tahun ajaran baru (Ganjil & Genap) ke sistem.' }}
              </SheetDescription>
            </div>
          </div>
        </SheetHeader>

        <div class="flex-1 overflow-y-auto py-5 pr-1 space-y-4 no-scrollbar">
          <AcademicYearForm
            :form="formItem"
            :errors="formErrors"
            :is-edit="isEditMode"
          />
        </div>

        <FormFooter
          :loading="isSaving"
          :is-edit="isEditMode"
          entity-name="Tahun Ajaran"
          :summary-items="saveSummary"
          :before-confirm="handleBeforeConfirm"
          @submit="executeSave"
          @save="executeSave"
        />
      </SheetContent>
    </Sheet>

    <AcademicYearDetailSheet
      v-model:open="isDetailSheetOpen"
      :item="selectedDetailItem"
    />

    <AcademicYearDeleteDialog
      v-model:open="isDeleteDialogOpen"
      :item="selectedDeleteItem"
      @confirmed="onConfirmDelete"
    />
  </div>
</template>
