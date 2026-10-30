<script setup>
import { computed, ref } from 'vue'
import { FolderOpen, Loader2 } from 'lucide-vue-next'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty'
import { Spinner } from '@/components/ui/spinner'
import { Skeleton } from '@/components/ui/skeleton'
import { tableRowFade } from '@/config/motion'
import Checkbox from '@/components/ui/checkbox/Checkbox.vue'
import { useApiLoading } from '@/composables/useApiLoading'

const props = defineProps({
  columns: {
    type: Array,
    default: () => []
  },
  items: {
    type: Array,
    default: () => []
  },
  rowActions: {
    type: Array,
    default: () => []
  },
  showRowNumber: {
    type: Boolean,
    default: true
  },
  rowNumberOffset: {
    type: Number,
    default: 0
  },
  minHeight: {
    type: String,
    default: '240px'
  },
  perPage: {
    type: Number,
    default: 5
  },
  selectable: {
    type: Boolean,
    default: false
  },
  selectedRows: {
    type: Array,
    default: () => []
  },
  rowDisabled: {
    type: Function,
    default: null
  },
  fixedHeight: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: undefined
  }
})

const { isFetching, isMutating } = useApiLoading()
const isTableLoading = computed(() => Boolean(props.loading || isFetching.value))
const skeletonRowCount = computed(() => Math.max(3, Math.min(props.perPage || 5, 10)))

const rowActionCooling = ref(false)
const handleRowActionClick = (action, item) => {
  if (rowActionCooling.value || isMutating.value) return
  rowActionCooling.value = true
  setTimeout(() => {
    rowActionCooling.value = false
  }, 500)
  action?.click?.(item)
}

// ── Inisial dari nama ──────────────────────────────────────────────────────────
const getInitials = name => {
  if (!name) return '?'
  const parts = String(name).trim().split(/\s+/)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

// ── Resolve badge variant ─────────────────────────────────────────────────────
const resolveBadgeVariant = (column, value) => {
  if (!column.badgeVariant) return 'default'
  if (typeof column.badgeVariant === 'function') return column.badgeVariant(value)
  if (typeof column.badgeVariant === 'object') return column.badgeVariant[value] || 'default'
  return column.badgeVariant
}

/**
 * Resolve class untuk <TableHead>.
 * Alignment diputuskan otomatis berdasarkan tipe/fitur kolom:
 *   - actions          → selalu center
 *   - badge            → selalu center
 *   - type: 'number'   → selalu center
 */
const resolveHeaderClass = column => {
  const classes = ['font-semibold text-xs uppercase text-muted-foreground']
  const isCentered = column.key === 'actions' || column.badge || column.type === 'number'
  if (isCentered) classes.push('text-center')
  if (column.class) classes.push(column.class)
  return classes
}

/**
 * Resolve class untuk <TableCell>.
 * Semua keputusan layout diambil otomatis dari props semantik:
 *
 *   type: 'number' → tabular-nums font-semibold, center
 *   type: 'date'   → text-xs text-muted-foreground whitespace-nowrap
 *   type: 'code'   → font-mono text-xs
 *   type: 'muted'  → text-xs text-muted-foreground
 *   badge: true    → center
 *   key=actions    → center
 *   truncate: true → truncate dengan max-width standar
 */
const resolveCellClass = column => {
  const classes = []

  // Alignment otomatis
  if (column.key === 'actions' || column.badge || column.type === 'number') {
    classes.push('text-center')
  }

  // Styling per tipe semantik
  switch (column.type) {
    case 'number':
      classes.push('tabular-nums font-semibold')
      break
    case 'date':
      classes.push('text-xs text-muted-foreground whitespace-nowrap')
      break
    case 'code':
      classes.push('font-mono text-xs')
      break
    case 'muted':
      classes.push('text-xs text-muted-foreground')
      break
  }

  // Truncate — max-w-xs sebagai batas standar yang wajar untuk kolom terpotong
  if (column.truncate) classes.push('truncate max-w-xs')

  if (column.class) classes.push(column.class)

  return classes
}

// ── Filler Rows & Empty State ──────────────────────────────────────────────────
const emptyRowsCount = computed(() => {
  if (isTableLoading.value || !props.fixedHeight || !props.perPage || props.items.length === 0) return 0
  return Math.max(0, props.perPage - props.items.length)
})

// ── Checkbox / Combobox ───────────────────────────────────

const emit = defineEmits(['update:selectedRows'])

const isSelected = item => {
  return props.selectedRows.some(row => row.id === item.id)
}

const toggleRow = item => {
  if (isDisabled(item)) return

  const exists = isSelected(item)

  if (exists) {
    emit(
      'update:selectedRows',
      props.selectedRows.filter(row => row.id !== item.id)
    )
  } else {
    emit('update:selectedRows', [...props.selectedRows, item])
  }
}

const isDisabled = item => {
  return props.rowDisabled?.(item) ?? false
}

const selectableItems = computed(() => {
  return props.items.filter(item => !props.rowDisabled?.(item))
})

const allSelected = computed(() => {
  if (!selectableItems.value.length) return false

  return selectableItems.value.every(item => props.selectedRows.some(row => row.id === item.id))
})

const toggleAll = checked => {
  if (checked) {
    emit('update:selectedRows', [...selectableItems.value])
  } else {
    emit('update:selectedRows', [])
  }
}
</script>

<template>
  <div
    class="relative w-full min-w-0"
    :style="items.length === 0 && !perPage ? { minHeight } : {}"
  >
    <Table :loading="false">
      <TableHeader class="bg-muted/50">
        <TableRow>
          <TableHead
            v-if="selectable"
            class="w-12 text-center"
          >
            <Checkbox
              :model-value="allSelected"
              @update:model-value="toggleAll"
            />
          </TableHead>
          <!-- Kolom Nomor Urut -->
          <TableHead
            v-if="showRowNumber && !selectable"
            class="font-semibold text-xs uppercase text-muted-foreground w-12 text-center"
          >
            No.
          </TableHead>
          <TableHead
            v-for="column in columns"
            :key="column.key"
            :class="resolveHeaderClass(column)"
          >
            {{ column.label }}
          </TableHead>
        </TableRow>
      </TableHeader>

      <TableBody>
        <!-- Skeleton Rows menggunakan komponen Skeleton bawaan saat filter/search dalam tabel -->
        <template v-if="isTableLoading && items.length === 0">
          <TableRow
            v-for="sRow in skeletonRowCount"
            :key="`skeleton-${sRow}`"
            class="h-[53px] pointer-events-none select-none"
          >
            <TableCell v-if="selectable" class="w-12 text-center">
              <Skeleton class="mx-auto size-4 rounded" />
            </TableCell>
            <TableCell v-if="showRowNumber && !selectable" class="w-12 text-center">
              <Skeleton class="mx-auto h-3.5 w-5" />
            </TableCell>
            <TableCell
              v-for="column in columns"
              :key="`sk-col-${column.key}`"
              :class="resolveCellClass(column)"
            >
              <div v-if="column.avatar" class="flex items-center gap-2.5">
                <Skeleton class="size-7 rounded-full shrink-0" />
                <Skeleton class="h-3.5 w-32" />
              </div>
              <div v-else-if="column.badge" class="flex justify-center">
                <Skeleton class="h-5 w-16 rounded-full" />
              </div>
              <div v-else-if="column.key === 'actions'" class="flex items-center justify-center gap-2">
                <Skeleton class="size-6 rounded" />
                <Skeleton class="size-6 rounded" />
              </div>
              <div v-else class="flex" :class="column.type === 'number' ? 'justify-center' : 'justify-start'">
                <Skeleton class="h-3.5 w-24 max-w-full" />
              </div>
            </TableCell>
          </TableRow>
        </template>

        <!-- Data Rows -->
        <TableRow
          v-else
          v-for="(item, rowIndex) in items"
          :key="item.id || rowIndex"
          class="h-[53px]"
          v-motion
          :initial="tableRowFade.initial"
          :visible-once="{
            ...tableRowFade.visible,
            transition: {
              ...tableRowFade.visible.transition,
              delay: 80 + rowIndex * 70
            }
          }"
        >
          <!-- Sel Checkbox -->
          <TableCell
            v-if="selectable"
            class="w-12 text-center"
          >
            <Checkbox
              :disabled="isDisabled(item)"
              :model-value="isSelected(item)"
              @update:model-value="() => toggleRow(item)"
            />
          </TableCell>
          <!-- Sel Nomor Urut -->
          <TableCell
            v-if="showRowNumber && !selectable"
            class="w-12 text-center text-sm text-muted-foreground tabular-nums"
          >
            {{ rowNumberOffset + rowIndex + 1 }}
          </TableCell>

          <TableCell
            v-for="column in columns"
            :key="column.key"
            :class="resolveCellClass(column)"
          >
            <slot
              :name="`cell-${column.key}`"
              :item="item"
              :value="item[column.key]"
              :index="rowIndex"
            >
              <!-- AVATAR + TEKS -->
              <template v-if="column.avatar">
                <div class="flex items-center gap-2.5 min-w-0">
                  <Avatar
                    data-size="sm"
                    class="shrink-0"
                  >
                    <!-- Tampilkan foto dari backend jika tersedia -->
                    <AvatarImage
                      v-if="column.avatarKey && item[column.avatarKey]"
                      :src="item[column.avatarKey]"
                      :alt="item[column.key]"
                    />
                    <!-- Fallback ke inisial jika tidak ada foto -->
                    <AvatarFallback class="text-[11px] font-bold">
                      {{ getInitials(item[column.key]) }}
                    </AvatarFallback>
                  </Avatar>
                  <span class="truncate text-sm font-medium">{{ item[column.key] }}</span>
                </div>
              </template>

              <!-- BADGE -->
              <template v-else-if="column.badge">
                <Badge :variant="resolveBadgeVariant(column, item[column.key])">
                  {{ item[column.key] }}
                </Badge>
              </template>

              <!-- AKSI BARIS -->
              <template v-else-if="column.key === 'actions' && rowActions && rowActions.length">
                <div class="flex items-center justify-center gap-3">
                  <button
                    v-for="action in rowActions"
                    :key="action.label"
                    type="button"
                    :disabled="rowActionCooling || isMutating"
                    class="flex flex-col items-center justify-center gap-0.5 group/btn focus:outline-none text-muted-foreground hover:text-foreground transition-colors disabled:opacity-50 disabled:pointer-events-none"
                    :title="action.label"
                    @click="handleRowActionClick(action, item)"
                  >
                    <component
                      :is="action.icon"
                      class="size-4 transition-transform group-hover/btn:scale-110"
                    />
                    <span class="text-[9px] font-semibold leading-none">{{ action.label }}</span>
                  </button>
                </div>
              </template>

              <!-- TEKS BIASA -->
              <template v-else>
                <div :class="{ 'truncate max-w-xs': column.truncate }">
                  {{ item[column.key] }}
                </div>
              </template>
            </slot>
          </TableCell>
        </TableRow>

        <!-- Empty State / No Data Found -->
        <TableRow
          v-if="!isTableLoading && items.length === 0"
          class="border-none hover:bg-transparent!"
        >
          <TableCell
            :colspan="columns.length + (showRowNumber ? 1 : 0) + (selectable ? 1 : 0)"
            class="p-0 border-none select-none pointer-events-none"
          >
            <div
              class="flex flex-col items-center justify-center text-center p-6 w-full"
              :style="{ height: `${(perPage || 5) * 53}px` }"
            >
              <Empty class="border-none bg-transparent">
                <EmptyHeader>
                  <EmptyMedia variant="icon">
                    <FolderOpen class="w-10 h-10 text-muted-foreground/30" />
                  </EmptyMedia>
                  <EmptyTitle class="text-sm font-semibold text-foreground/80 mt-2"
                    >Tidak Ada Data</EmptyTitle
                  >
                  <EmptyDescription class="text-xs text-muted-foreground/50 max-w-xs mt-1">
                    Tidak ada data yang ditemukan atau belum ditambahkan ke tabel ini.
                  </EmptyDescription>
                </EmptyHeader>
              </Empty>
            </div>
          </TableCell>
        </TableRow>

        <!-- Filler Rows -->
        <TableRow
          v-for="i in emptyRowsCount"
          :key="`empty-${i}`"
          class="h-[53px] hover:bg-transparent! border-b border-transparent pointer-events-none"
        >
          <TableCell v-if="selectable"> &nbsp; </TableCell>
          <!-- Sel Nomor Urut Kosong -->
          <TableCell
            v-if="showRowNumber"
            class="w-12 text-center"
          >
            &nbsp;
          </TableCell>
          <TableCell
            v-for="column in columns"
            :key="column.key"
            :class="resolveCellClass(column)"
          >
            &nbsp;
          </TableCell>
        </TableRow>
      </TableBody>
    </Table>

    <!-- Indikator Spinner bawaan project saat filter/search/pindah halaman tabel -->
    <div
      v-if="isTableLoading && items.length > 0"
      class="absolute inset-0 z-20 flex items-center justify-center bg-background/50 backdrop-blur-[1px] transition-opacity"
    >
      <div class="flex items-center gap-2 rounded-xl bg-card px-3.5 py-2 text-xs font-medium text-foreground shadow-sm border border-border">
        <Spinner class="size-4 text-primary" />
        <span>Memuat data...</span>
      </div>
    </div>
  </div>
</template>
