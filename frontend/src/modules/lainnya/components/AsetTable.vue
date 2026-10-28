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

// Reset page to 1 whenever filters change
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
      placeholder: 'Semua Sekolah',
      options: [...schoolList.value.map(s => ({ label: s, value: s }))]
    })
  }

  list.push({
    key: 'category',
    type: 'select',
    placeholder: 'Semua Kategori',
    options: [
      { label: 'Elektronik', value: 'Elektronik' },
      { label: 'Furniture', value: 'Furniture' },
      { label: 'Peralatan Pembelajaran', value: 'Peralatan Pembelajaran' },
      { label: 'Alat Kebersihan', value: 'Alat Kebersihan' },
      { label: 'Peralatan Kantor', value: 'Peralatan Kantor' },
      { label: 'Peralatan Olahraga', value: 'Peralatan Olahraga' },
      { label: 'Peralatan Laboratorium', value: 'Peralatan Laboratorium' }
    ]
  })

  list.push({
    key: 'status',
    type: 'select',
    placeholder: 'Status Kondisi',
    options: [
      { label: 'Baik', value: 'Baik' },
      { label: 'Rusak Ringan', value: 'Rusak Ringan' },
      { label: 'Rusak Berat', value: 'Rusak Berat' }
    ]
  })

  list.push({
    key: 'search',
    type: 'search',
    placeholder: 'Cari Aset...'
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
      label: 'Tambah Aset Baru',
      icon: Plus,
      variant: 'default',
      click: () => router.push('/lainnya/aset/tambah')
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
    { key: 'code', label: 'KODE ASET', class: 'font-bold text-foreground' },
    { key: 'name', label: 'NAMA ASET' },
    { key: 'category', label: 'KATEGORI' },
    { key: 'location', label: 'LOKASI' },
    { key: 'condition', label: 'STATUS' },
    { key: 'actions', label: 'AKSI' }
  )
  return cols
})

const filteredItems = computed(() => {
  return props.items.filter(aset => {
    const fSearch = filterValues.value.search?.toLowerCase() || ''
    const fCategory = filterValues.value.category || 'all'
    const fStatus = filterValues.value.status || 'all'
    const fSchool = filterValues.value.school || 'all'

    const matchesSearch =
      !fSearch ||
      aset.name?.toLowerCase().includes(fSearch) ||
      aset.code?.toLowerCase().includes(fSearch)

    const matchesCategory = fCategory === 'all' || aset.category === fCategory
    const matchesStatus = fStatus === 'all' || aset.condition === fStatus

    let matchesSchool = true
    if (props.isYayasan && fSchool !== 'all') {
      matchesSchool = aset.school_name === fSchool
    }

    return matchesSearch && matchesCategory && matchesStatus && matchesSchool
  })
})

const paginatedItems = computed(() => {
  const start = (page.value - 1) * perPage.value
  return filteredItems.value.slice(start, start + perPage.value)
})

const categoryBadgeClass = category => {
  switch (category) {
    case 'Elektronik':
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 shadow-xs'
    case 'Furniture':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 shadow-xs'
    case 'Peralatan Pembelajaran':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 shadow-xs'
    case 'Alat Kebersihan':
      return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 shadow-xs'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20 shadow-xs'
  }
}

const conditionBadgeClass = condition => {
  switch (condition) {
    case 'Baik':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 shadow-xs'
    case 'Rusak Ringan':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 shadow-xs'
    case 'Rusak Berat':
      return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 shadow-xs'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20 shadow-xs'
  }
}

// Handlers for Row Actions
const handleEdit = (id, item) => {
  const targetId = typeof id === 'object' ? id?.id : (id || item?.id)
  router.push(`/lainnya/aset/edit/${targetId}`)
}

const handleDelete = id => {
  emit('delete', id)
}

const handleView = id => {
  emit('view', id)
}
</script>

<template>
  <DataTableCard
    :columns="columns"
    :items="paginatedItems"
    :filters="filters"
    :actions="actions"
    illustration="ruler"
    v-model:filterValues="filterValues"
    :page="page"
    :per-page="perPage"
    :total="filteredItems.length"
    :from="(page - 1) * perPage + 1"
    :to="Math.min(page * perPage, filteredItems.length)"
    @update:page="page = $event"
    @update:perPage="perPage = $event"
    :on-edit="!readonly ? handleEdit : undefined"
    :on-delete="!readonly ? handleDelete : undefined"
    :on-view="handleView"
  >
    <!-- Custom Render untuk Kolom Lokasi -->
    <template #cell-location="{ item }">
      <span class="text-muted-foreground">{{ item.room || item.building || '-' }}</span>
    </template>

    <!-- Custom Render untuk Kolom Kategori -->
    <template #cell-category="{ item }">
      <Badge
        :class="categoryBadgeClass(item.category)"
        class="rounded-full px-2.5 py-0.5 font-bold uppercase tracking-wider text-[10px]"
      >
        {{ item.category }}
      </Badge>
    </template>

    <!-- Custom Render untuk Badge Status -->
    <template #cell-condition="{ item }">
      <Badge
        :class="conditionBadgeClass(item.condition)"
        class="rounded-full px-2.5 py-0.5 font-bold uppercase tracking-wider text-[10px]"
      >
        {{ item.condition }}
      </Badge>
    </template>
  </DataTableCard>
</template>
