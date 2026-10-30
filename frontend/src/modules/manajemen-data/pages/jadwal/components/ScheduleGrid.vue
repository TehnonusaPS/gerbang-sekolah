<script setup>
import { computed } from 'vue'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Plus } from 'lucide-vue-next'
import { useApiLoading } from '@/composables/useApiLoading'

const props = defineProps({
  timeSlots: Array,
  schedules: Array, // raw schedules array from backend
  includeSaturday: {
    type: Boolean,
    default: true
  },
  loading: {
    type: Boolean,
    default: undefined
  }
})

const { isFetching } = useApiLoading()
const isGridLoading = computed(() => Boolean(props.loading || isFetching.value))

const emit = defineEmits(['cell-clicked'])

const days = computed(() => {
  const list = [
    { value: 1, name: 'Senin' },
    { value: 2, name: 'Selasa' },
    { value: 3, name: 'Rabu' },
    { value: 4, name: 'Kamis' },
    { value: 5, name: 'Jumat' }
  ]
  if (props.includeSaturday) {
    list.push({ value: 6, name: 'Sabtu' })
  }
  return list
})

function getCellContent(slotId, dayValue) {
  return props.schedules.find(s => String(s.time_slot_id) === String(slotId) && Number(s.day_of_week) === Number(dayValue))
}

function handleCellClick(slot, day) {
  const existing = getCellContent(slot.id, day.value)
  emit('cell-clicked', {
    slot,
    day,
    existing
  })
}

// Generate mapel specific color badges for rich visual aesthetics
function getSubjectBadgeClass(subjectName) {
  if (!subjectName) return ''
  const name = subjectName.toLowerCase()
  if (name.includes('matematika')) return 'bg-blue-500/10 text-blue-600 hover:bg-blue-500/20 border-none'
  if (name.includes('indonesia')) return 'bg-rose-500/10 text-rose-600 hover:bg-rose-500/20 border-none'
  if (name.includes('inggris')) return 'bg-violet-500/10 text-violet-600 hover:bg-violet-500/20 border-none'
  if (name.includes('alam') || name.includes('ipa')) return 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20 border-none'
  if (name.includes('sosial') || name.includes('ips')) return 'bg-amber-500/10 text-amber-600 hover:bg-amber-500/20 border-none'
  if (name.includes('jasmani') || name.includes('olahraga') || name.includes('penjas')) return 'bg-teal-500/10 text-teal-600 hover:bg-teal-500/20 border-none'
  if (name.includes('agama')) return 'bg-indigo-500/10 text-indigo-600 hover:bg-indigo-500/20 border-none'
  if (name.includes('seni') || name.includes('budaya')) return 'bg-fuchsia-500/10 text-fuchsia-600 hover:bg-fuchsia-500/20 border-none'
  return 'bg-slate-500/10 text-slate-600 hover:bg-slate-500/20 border-none'
}

const isPublished = computed(() => {
  if (!props.schedules || props.schedules.length === 0) return false
  return props.schedules.some(s => s.status === 'published')
})
</script>

<template>
  <Card class="border border-border bg-card/60 backdrop-blur-md rounded-2xl shadow-sm overflow-hidden flex-1">
    <CardContent class="p-0">
      <div class="overflow-x-auto">
        <table class="w-full border-collapse">
          <thead>
            <tr class="bg-muted/40 border-b border-border">
              <!-- Left spacer for Time Column -->
              <th class="p-4 w-[160px] text-xs font-bold text-foreground text-left border-r border-border/60">
                <div class="flex items-center justify-between gap-1">
                  <span>WAKTU / HARI</span>
                  <span
                    v-if="schedules && schedules.length > 0"
                    class="text-[9px] px-1.5 py-0.5 rounded-md font-bold uppercase tracking-wider"
                    :class="isPublished ? 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-300' : 'bg-amber-500/20 text-amber-600 dark:text-amber-300'"
                  >
                    {{ isPublished ? 'Published' : 'Draft' }}
                  </span>
                </div>
              </th>
              <th
                v-for="day in days"
                :key="day.value"
                class="p-4 text-center text-xs font-bold text-foreground uppercase border-r border-border/60 last:border-r-0"
              >
                {{ day.name }}
              </th>
            </tr>
          </thead>
          
          <tbody class="divide-y divide-border">
            <template v-if="isGridLoading && (!timeSlots || timeSlots.length === 0)">
              <tr v-for="r in 5" :key="'skel-' + r">
                <td class="p-3 border-r border-border/60">
                  <div class="h-3.5 w-20 rounded bg-muted animate-pulse mb-1.5" />
                  <div class="h-2.5 w-24 rounded bg-muted/70 animate-pulse" />
                </td>
                <td v-for="day in days" :key="day.value" class="p-3 border-r border-border/60 last:border-r-0">
                  <div class="h-10 w-full rounded-xl bg-muted/50 animate-pulse" />
                </td>
              </tr>
            </template>

            <tr
              v-else
              v-for="slot in timeSlots"
              :key="slot.id"
              class="hover:bg-muted/10 transition-colors"
            >
              <!-- Row header slot time range -->
              <td class="p-3 border-r border-border/60 text-left" :class="slot.is_break ? 'bg-amber-500/5 dark:bg-amber-950/20' : ''">
                <div class="font-bold text-xs flex items-center gap-1.5" :class="slot.is_break ? 'text-amber-700 dark:text-amber-300' : 'text-foreground'">
                  <span>{{ slot.label || `Jam ${slot.slot_number}` }}</span>
                </div>
                <div class="text-[10px] font-mono mt-0.5" :class="slot.is_break ? 'text-amber-600/80 dark:text-amber-400/80 font-bold' : 'text-muted-foreground'">
                  {{ slot.start_time.substring(0, 5) }} - {{ slot.end_time.substring(0, 5) }}
                </div>
              </td>

              <!-- If this slot is marked as break, render a spanned row -->
              <template v-if="slot.is_break">
                <td
                  :colspan="days.length"
                  class="p-3 bg-amber-500/10 dark:bg-amber-950/30 text-center font-bold text-xs tracking-widest text-amber-700 dark:text-amber-300 uppercase border-r last:border-r-0 border-y border-amber-500/20"
                >
                  ☕ {{ slot.label || 'ISTIRAHAT' }} ({{ slot.start_time.substring(0, 5) }} - {{ slot.end_time.substring(0, 5) }})
                </td>
              </template>

              <!-- Otherwise, render individual cells -->
              <template v-else>
                <td
                  v-for="day in days"
                  :key="day.value"
                  class="p-2 border-r border-border/60 last:border-r-0 text-center relative group min-h-[75px]"
                >
                  <div v-if="getCellContent(slot.id, day.value)" class="space-y-1.5 py-1 text-center">
                    <Badge
                      :class="getSubjectBadgeClass(getCellContent(slot.id, day.value).subject?.name)"
                      class="font-bold text-[10px] px-2 py-0.5 rounded-full"
                    >
                      {{ getCellContent(slot.id, day.value).subject?.name }}
                    </Badge>
                    
                    <p class="text-[10px] font-bold text-foreground leading-tight">
                      {{ getCellContent(slot.id, day.value).teacher?.name }}
                    </p>

                    <!-- Edit trigger mask -->
                    <button
                      type="button"
                      class="absolute inset-0 w-full h-full opacity-0 hover:opacity-100 bg-background/80 flex items-center justify-center text-[10px] font-bold text-primary transition-opacity rounded-lg cursor-pointer"
                      @click="handleCellClick(slot, day)"
                    >
                      Ubah Jadwal
                    </button>
                  </div>

                  <div v-else class="h-full flex items-center justify-center min-h-[50px] py-1">
                    <button
                      type="button"
                      class="opacity-0 group-hover:opacity-100 flex items-center gap-1 text-[10px] font-bold text-primary hover:text-primary/95 transition-opacity px-2 py-1 rounded-lg border border-dashed border-primary bg-primary/5 cursor-pointer"
                      @click="handleCellClick(slot, day)"
                    >
                      <Plus class="size-3" />
                      Set
                    </button>
                  </div>
                </td>
              </template>
            </tr>

            <tr v-if="!isGridLoading && timeSlots.length === 0">
              <td :colspan="days.length + 1" class="h-32 text-center text-muted-foreground font-semibold">
                Belum ada jam pelajaran dikonfigurasi. Klik "Atur Jam Pelajaran" untuk mengatur.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardContent>
  </Card>
</template>
