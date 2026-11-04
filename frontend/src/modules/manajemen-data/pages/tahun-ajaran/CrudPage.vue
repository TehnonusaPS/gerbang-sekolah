<script setup>
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import StatCard from '@/components/stat-card/StatCard.vue'
import { Badge } from '@/components/ui/badge'
import { Calendar, Eye, Pencil, Plus, Trash2, ToggleLeft, ToggleRight, CheckCircle, XCircle } from 'lucide-vue-next'
import { glassSlide, glassFade } from '@/config/motion'
import { useAcademicYear } from './composables/useAcademicYear'

import AcademicYearFormSheet from './components/AcademicYearFormSheet.vue'
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

// --- Modal States ---
const isFormSheetOpen = ref(false)
const isEditMode = ref(false)
const selectedEditItem = ref(null)

const isDetailSheetOpen = ref(false)
const selectedDetailItem = ref(null)

const isDeleteDialogOpen = ref(false)
const selectedDeleteItem = ref(null)

// --- Handlers ---
function handleEdit(item) {
  isEditMode.value = true
  selectedEditItem.value = item
  isFormSheetOpen.value = true
}

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

function handleCreate() {
  isEditMode.value = false
  selectedEditItem.value = null
  isFormSheetOpen.value = true
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

    <!-- Modals & Sheets -->
    <AcademicYearFormSheet
      v-model:open="isFormSheetOpen"
      :is-edit-mode="isEditMode"
      :edit-item="selectedEditItem"
      @saved="fetchData"
    />

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
