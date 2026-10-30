<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Plus } from 'lucide-vue-next'

import DataTableCard from '@/components/data-table/DataTableCard.vue'
import { Badge } from '@/components/ui/badge'
import { useAuthStore } from '@/stores/authStore'

const auth = useAuthStore()

const schoolLevel = computed(() => auth.user?.school?.level)

const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  loading: {
    type: Boolean,
    default: undefined
  }
})

const emit = defineEmits(['create', 'delete'])

// DataTable state
const filterValues = ref({})
const page = ref(1)
const perPage = ref(5)

// Reset page to 1 whenever filters change
watch(
  filterValues,
  () => {
    page.value = 1
  },
  { deep: true }
)

const filters = computed(() => {
  const list = []

  list.push({
    key: 'grade',
    type: 'select',
    placeholder: 'Semua Tingkat',
    options: tingkatOptions.value
  })

  list.push({
    key: 'status',
    type: 'select',
    placeholder: 'Semua Status',
    options: [
      { label: 'Aktif', value: 'active' },
      { label: 'Penuh', value: 'full' },
      { label: 'Belum Ada Wali', value: 'no_teacher' }
    ]
  })

  list.push({
    key: 'search',
    type: 'search',
    placeholder: 'Cari Kelas (Nama, Wali Kelas, Ruangan)...'
  })

  return list
})

const actions = computed(() => {
  const list = []

  list.push({
    label: 'Tambah Kelas Baru',
    icon: Plus,
    variant: 'default',
    click: () => emit('create')
  })

  return list
})

const columns = computed(() => {
  return [
    { key: 'name', label: 'NAMA KELAS', class: 'font-bold text-foreground' },
    { key: 'grade', label: 'TINGKAT' },
    { key: 'homeroom_teacher', label: 'WALI KELAS' },
    { key: 'room', label: 'RUANGAN' },
    { key: 'capacity_info', label: 'KAPASITAS' },
    { key: 'status', label: 'STATUS' },
    { key: 'actions', label: 'AKSI' }
  ]
})

const filteredItems = computed(() => {
  return props.items.filter(item => {
    const fSearch = filterValues.value.search?.toLowerCase() || ''
    const fGrade = filterValues.value.grade || 'all'
    const fStatus = filterValues.value.status || 'all'

    const matchesSearch =
      !fSearch ||
      item.name?.toLowerCase().includes(fSearch) ||
      item.homeroom_teacher?.toLowerCase().includes(fSearch) ||
      item.room?.toLowerCase().includes(fSearch)

    const matchesGrade = fGrade === 'all' || String(item.grade) === String(fGrade)
    const matchesStatus = fStatus === 'all' || item.status === fStatus

    return matchesSearch && matchesGrade && matchesStatus
  })
})

const paginatedItems = computed(() => {
  const start = (page.value - 1) * perPage.value
  return filteredItems.value.slice(start, start + perPage.value)
})

const statusBadgeClass = status => {
  if (!status)
    return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20 shadow-xs'

  if (status === 'active') {
    return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 shadow-xs'
  } else if (status === 'full') {
    return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 shadow-xs'
  } else if (status === 'no_teacher') {
    return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 shadow-xs'
  }
  return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20 shadow-xs'
}

const statusLabel = status => {
  if (status === 'active') return 'Aktif'
  if (status === 'full') return 'Penuh'
  if (status === 'no_teacher') return 'Tanpa Wali'
  return status
}

const tingkatOptions = computed(() => {
  const jenjang = String(schoolLevel.value || '').toUpperCase()

  // TK
  if (jenjang === 'JP001' || jenjang === 'TK') {
    return [
      { label: 'TK A', value: 'TK A' },
      { label: 'TK B', value: 'TK B' }
    ]
  }

  // SD
  if (jenjang === 'JP002' || jenjang === 'SD') {
    return Array.from({ length: 6 }, (_, index) => ({
      label: `Kelas ${index + 1}`,
      value: String(index + 1)
    }))
  }

  // SMP
  if (jenjang === 'JP003' || jenjang === 'SMP') {
    return Array.from({ length: 3 }, (_, index) => ({
      label: `Kelas ${index + 7}`,
      value: String(index + 7)
    }))
  }

  // SMA
  if (jenjang === 'JP004' || jenjang === 'SMA') {
    return Array.from({ length: 3 }, (_, index) => ({
      label: `Kelas ${index + 10}`,
      value: String(index + 10)
    }))
  }

  return []
})
</script>

<template>
  <DataTableCard
    :columns="columns"
    :items="paginatedItems"
    :filters="filters"
    :actions="actions"
    :loading="loading"
    v-model:filterValues="filterValues"
    :page="page"
    :per-page="perPage"
    :total="filteredItems.length"
    :from="(page - 1) * perPage + 1"
    :to="Math.min(page * perPage, filteredItems.length)"
    @update:page="page = $event"
    @update:perPage="perPage = $event"
    @delete="(id, item) => emit('delete', id, item)"
  >
    <!-- Custom Render untuk Kolom Wali Kelas -->
    <template #cell-homeroom_teacher="{ item }">
      <span
        v-if="item.homeroom_teacher"
        class="font-medium"
        >{{ item.homeroom_teacher }}</span
      >
      <span
        v-else
        class="text-rose-500 italic text-sm font-medium"
        >Belum ada wali kelas</span
      >
    </template>

    <!-- Custom Render untuk Kolom Kapasitas -->
    <template #cell-capacity_info="{ item }">
      <div class="flex flex-col gap-1">
        <div class="flex items-center gap-2">
          <span
            class="text-sm font-medium"
            :class="item.students_count >= item.capacity ? 'text-amber-600' : 'text-emerald-600'"
          >
            {{ item.students_count }}
          </span>
          <span class="text-muted-foreground text-xs">/ {{ item.capacity }} Siswa</span>
        </div>
        <div class="w-full bg-secondary rounded-full h-1.5 overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-300"
            :class="item.students_count >= item.capacity ? 'bg-amber-500' : 'bg-emerald-500'"
            :style="{ width: `${Math.min((item.students_count / item.capacity) * 100, 100)}%` }"
          ></div>
        </div>
      </div>
    </template>

    <!-- Custom Render untuk Badge Status -->
    <template #cell-status="{ item }">
      <Badge
        :class="statusBadgeClass(item.status)"
        class="rounded-full px-2.5 py-0.5 font-bold tracking-wider text-[10px] uppercase"
      >
        {{ statusLabel(item.status) }}
      </Badge>
    </template>
  </DataTableCard>
</template>
