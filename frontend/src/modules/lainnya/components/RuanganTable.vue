<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, FileSpreadsheet, FileText } from 'lucide-vue-next'

import DataTableCard from '@/components/data-table/DataTableCard.vue'
import { Badge } from '@/components/ui/badge'

const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  readonly: {
    type: Boolean,
    default: false
  },
  isYayasan: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['delete', 'view', 'export-excel', 'export-pdf'])
const router = useRouter()

// DataTable state
const filterValues = ref({})
const page = ref(1)
const perPage = ref(5)

watch(
  filterValues,
  () => {
    page.value = 1
  },
  { deep: true }
)

const schoolList = computed(() => {
  const schools = [...new Set(props.items.map(i => i.school_name).filter(Boolean))]
  return schools.sort()
})

const filters = computed(() => {
  const list = []

  if (props.isYayasan) {
    list.push({
      key: 'school',
      type: 'select',
      placeholder: 'Pilih Sekolah',
      options: schoolList.value.map(s => ({ label: s, value: s }))
    })
  }

  list.push({
    key: 'type',
    type: 'select',
    placeholder: 'Pilih Tipe Ruangan',
    options: [
      { label: 'Ruang Kelas', value: 'Ruang Kelas' },
      { label: 'Laboratorium', value: 'Laboratorium' },
      { label: 'Ruang Fasilitas', value: 'Ruang Fasilitas' }
    ]
  })

  list.push({
    key: 'search',
    type: 'search',
    placeholder: 'Cari Ruangan...'
  })

  return list
})

const actions = computed(() => {
  const list = []

  list.push({
    label: 'Export Excel',
    icon: FileSpreadsheet,
    variant: 'outline',
    class: 'border-emerald-500/30 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/10',
    click: () => emit('export-excel', filteredItems.value)
  })
  list.push({
    label: 'Export PDF',
    icon: FileText,
    variant: 'outline',
    class: 'border-rose-500/30 text-rose-600 dark:text-rose-400 hover:bg-rose-500/10',
    click: () => emit('export-pdf', filteredItems.value)
  })

  if (!props.readonly && !props.isYayasan) {
    list.push({
      label: 'Tambah Ruangan Baru',
      icon: Plus,
      variant: 'default',
      click: () => router.push('/lainnya/ruangan/tambah')
    })
  }

  return list
})

const columns = computed(() => {
  const cols = []
  if (props.isYayasan) {
    cols.push({ key: 'school_name', label: 'CABANG SEKOLAH' })
  }
  cols.push(
    { key: 'code', label: 'KODE RUANGAN', class: 'font-bold text-foreground' },
    { key: 'name', label: 'NAMA RUANGAN' },
    { key: 'category', label: 'TIPE RUANGAN' },
    { key: 'facilities', label: 'FASILITAS PENDUKUNG' },
    { key: 'capacity', label: 'KAPASITAS' },
    { key: 'actions', label: 'AKSI' }
  )
  return cols
})

const filteredItems = computed(() => {
  return props.items.filter(room => {
    const fSearch = filterValues.value.search?.toLowerCase() || ''
    const fType = filterValues.value.type || 'all'
    const fSchool = filterValues.value.school || 'all'

    const matchesSearch =
      !fSearch ||
      room.name?.toLowerCase().includes(fSearch) ||
      room.code?.toLowerCase().includes(fSearch)

    const matchesType = fType === 'all' || room.category === fType

    let matchesSchool = true
    if (props.isYayasan && fSchool !== 'all') {
      matchesSchool = room.school_name === fSchool
    }

    return matchesSearch && matchesType && matchesSchool
  })
})

const paginatedItems = computed(() => {
  const start = (page.value - 1) * perPage.value
  return filteredItems.value.slice(start, start + perPage.value)
})

const typeBadgeClass = type => {
  switch (type?.toLowerCase()) {
    case 'ruang kelas':
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 shadow-xs'
    case 'laboratorium':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 shadow-xs'
    case 'ruang fasilitas':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 shadow-xs'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20 shadow-xs'
  }
}

const formatType = type => {
  if (!type) return '-'
  return type.toUpperCase()
}

const formatFacilities = facilities => {
  if (!facilities || !Array.isArray(facilities)) return '-'
  return facilities
    .map(f => {
      return f
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ')
    })
    .join(', ')
}
</script>

<template>
  <DataTableCard
    :columns="columns"
    :items="paginatedItems"
    :filters="filters"
    :actions="actions"
    illustration="abc_board"
    v-model:filterValues="filterValues"
    :page="page"
    :per-page="perPage"
    :total="filteredItems.length"
    :from="(page - 1) * perPage + 1"
    :to="Math.min(page * perPage, filteredItems.length)"
    @update:page="page = $event"
    @update:perPage="perPage = $event"
    :on-edit="!readonly ? (id, item) => router.push(`/lainnya/ruangan/edit/${typeof id === 'object' ? id?.id : (id || item?.id)}`) : undefined"
    :on-delete="!readonly ? id => emit('delete', id) : undefined"
    :on-view="id => emit('view', id)"
  >
    <template #cell-category="{ item }">
      <Badge
        :class="typeBadgeClass(item.category)"
        class="rounded-full px-2.5 py-0.5 font-bold uppercase tracking-wider text-[9px]"
      >
        {{ formatType(item.category) }}
      </Badge>
    </template>

    <template #cell-facilities="{ item }">
      <span class="text-muted-foreground">{{ formatFacilities(item.facilities) }}</span>
    </template>

    <template #cell-capacity="{ item }">
      <span class="font-medium">{{ item.capacity }} Orang</span>
    </template>
  </DataTableCard>
</template>
