<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Plus, FileSpreadsheet, FileText } from 'lucide-vue-next'

import DataTableCard from '@/components/data-table/DataTableCard.vue'

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
    key: 'category',
    type: 'select',
    placeholder: 'Semua Kategori',
    options: [
      { label: 'Sains', value: 'Sains' },
      { label: 'TIK', value: 'TIK' },
      { label: 'Bahasa', value: 'Bahasa' },
      { label: 'Sejarah', value: 'Sejarah' }
    ]
  })

  list.push({
    key: 'search',
    type: 'search',
    placeholder: 'Cari Buku...'
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
      label: 'Tambah Buku Baru',
      icon: Plus,
      variant: 'default',
      click: () => router.push('/lainnya/perpustakaan/tambah')
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
    { key: 'name', label: 'JUDUL BUKU', class: 'font-bold text-foreground text-xs' },
    { key: 'isbn', label: 'ISBN' },
    { key: 'kategori', label: 'KATEGORI' },
    { key: 'jumlahStok', label: 'STOK', class: 'text-center' },
    { key: 'lokasiRak', label: 'LOKASI RAK', class: 'text-center' },
    { key: 'actions', label: 'AKSI' }
  )
  return cols
})

const filteredItems = computed(() => {
  return props.items.filter(book => {
    const fSearch = filterValues.value.search?.toLowerCase() || ''
    const fCategory = filterValues.value.category || 'all'
    const fSchool = filterValues.value.school || 'all'

    const matchesSearch =
      !fSearch ||
      book.name?.toLowerCase().includes(fSearch) ||
      book.isbn?.toLowerCase().includes(fSearch)

    const matchesCategory = fCategory === 'all' || book.kategori === fCategory

    let matchesSchool = true
    if (props.isYayasan && fSchool !== 'all') {
      matchesSchool = book.school_name === fSchool
    }

    return matchesSearch && matchesCategory && matchesSchool
  })
})

const paginatedItems = computed(() => {
  const start = (page.value - 1) * perPage.value
  return filteredItems.value.slice(start, start + perPage.value)
})

import { Badge } from '@/components/ui/badge'

const kategoriBadgeClass = kategori => {
  switch (kategori) {
    case 'Sains':
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 shadow-xs'
    case 'TIK':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 shadow-xs'
    case 'Bahasa':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 shadow-xs'
    case 'Sejarah':
      return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 shadow-xs'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20 shadow-xs'
  }
}
</script>

<template>
  <DataTableCard
    :columns="columns"
    :items="paginatedItems"
    :filters="filters"
    :actions="actions"
    illustration="textbook"
    v-model:filterValues="filterValues"
    :page="page"
    :per-page="perPage"
    :total="filteredItems.length"
    :from="(page - 1) * perPage + 1"
    :to="Math.min(page * perPage, filteredItems.length)"
    @update:page="page = $event"
    @update:perPage="perPage = $event"
    :on-edit="!readonly ? (id, item) => router.push(`/lainnya/perpustakaan/edit/${typeof id === 'object' ? id?.id : (id || item?.id)}`) : undefined"
    :on-delete="!readonly ? id => emit('delete', id) : undefined"
    :on-view="id => emit('view', id)"
  >
    <template #cell-kategori="{ item }">
      <Badge
        :class="kategoriBadgeClass(item.kategori)"
        class="rounded-full px-2.5 py-0.5 font-bold uppercase tracking-wider text-[9px]"
      >
        {{ item.kategori }}
      </Badge>
    </template>

    <template #cell-jumlahStok="{ item }">
      <div class="text-center w-full">{{ item.jumlahStok }}</div>
    </template>

    <template #cell-lokasiRak="{ item }">
      <div class="text-center w-full text-muted-foreground">{{ item.lokasiRak || '-' }}</div>
    </template>
  </DataTableCard>
</template>
