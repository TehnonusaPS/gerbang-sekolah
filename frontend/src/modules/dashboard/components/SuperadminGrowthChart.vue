<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { Calendar, ArrowUpRight, ArrowDownRight } from 'lucide-vue-next'
import BarChartCard from '@/components/chart-card/BarChartCard.vue'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { useAuthStore } from '@/stores/authStore'
import { growthChartData as fallbackData } from '../data/superadminGrowthChartData'

const props = defineProps<{
  delay?: number
  chartData?: Array<{ bulan: string; yayasan: number; sekolah: number }>
  availableYears?: number[]
  selectedYear?: number | string
  loading?: boolean
  summary?: {
    year?: string
    trendValue?: string
    trendDirection?: string
    footerTitle?: string
    footerTrend?: string
    footerSubtext?: string
  }
}>()

const emit = defineEmits<{
  (e: 'year-change', year: number): void
}>()

const auth = useAuthStore()
const computedDelay = computed(() => (auth.isJustLoggedIn ? 1400 : 0) + (props.delay || 0))

const sourceData = computed(() => (props.chartData && props.chartData.length > 0 ? props.chartData : fallbackData))
const activeData = ref(sourceData.value.map(d => ({ ...d, yayasan: 0, sekolah: 0 })))

const chartYear = computed(() => String(props.selectedYear || props.summary?.year || new Date().getFullYear()))

const yearOptions = computed(() => {
  if (props.availableYears && props.availableYears.length > 0) {
    return props.availableYears
  }
  const curr = new Date().getFullYear()
  return [curr, curr - 1]
})

const handleYearSelect = (val: string) => {
  const numericYear = parseInt(val, 10)
  if (!isNaN(numericYear) && numericYear !== Number(chartYear.value)) {
    emit('year-change', numericYear)
  }
}

watch(sourceData, (newVal) => {
  activeData.value = newVal
}, { deep: true })

onMounted(() => {
  setTimeout(() => {
    activeData.value = sourceData.value
  }, computedDelay.value + 350)
})

const chartConfig = {
  yayasan: {
    label: 'Yayasan Baru',
    color: '#6366f1'
  },
  sekolah: {
    label: 'Sekolah Baru',
    color: '#0ea5e9'
  }
}
</script>

<template>
  <BarChartCard
    title="Tren Pertumbuhan Yayasan & Sekolah Baru"
    :description="`Jumlah yayasan dan sekolah terdaftar per bulan — ${chartYear}`"
    cardClass="lg:col-span-3 flex flex-col justify-between"
    footerClass="flex-col items-start gap-1 text-xs"
    :delay="delay || 0"
    illustration="planet"
    :data="activeData"
    index="bulan"
    :categories="['yayasan', 'sekolah']"
    :config="chartConfig"
    :height="230"
    :footerTitle="summary?.footerTitle || `Pertumbuhan tahun ${chartYear}`"
    :footerTrend="summary?.footerTrend || 'up'"
    :footerSubtext="summary?.footerSubtext || `Menampilkan total yayasan dan sekolah baru selama tahun ${chartYear}`"
  >
    <template #header-action>
      <div class="flex items-center gap-2">
        <!-- Filter Tahun Dinamis -->
        <Select
          :model-value="chartYear"
          @update:model-value="handleYearSelect"
        >
          <SelectTrigger
            size="sm"
            class="h-7.5 sm:h-7 min-w-[108px] sm:min-w-[112px] text-xs px-2.5 rounded-lg border-border/70 bg-background/60 backdrop-blur-sm focus:ring-1 focus:ring-primary/40 font-medium cursor-pointer"
          >
            <div class="flex items-center gap-1.5 shrink-0">
              <Calendar class="size-3.5 sm:size-3 text-muted-foreground" />
              <SelectValue :placeholder="chartYear">
                {{ chartYear }}
              </SelectValue>
            </div>
          </SelectTrigger>
          <SelectContent align="start" class="min-w-[110px]">
            <SelectItem
              v-for="yr in yearOptions"
              :key="yr"
              :value="String(yr)"
              class="text-xs cursor-pointer"
            >
              {{ yr }}
            </SelectItem>
          </SelectContent>
        </Select>

        <!-- Trend Badge -->
        <Badge
          v-if="summary?.trendValue"
          variant="secondary"
          class="font-medium text-xs gap-1 border-0 h-7.5 sm:h-7 px-2.5 shrink-0"
          :class="[
            (summary?.trendDirection || 'up') === 'up' && 'text-emerald-500 bg-emerald-500/10',
            (summary?.trendDirection || 'down') === 'down' && 'text-red-500 bg-red-500/10',
            (summary?.trendDirection || 'up') === 'neutral' && 'text-muted-foreground bg-muted'
          ]"
        >
          <component
            :is="(summary?.trendDirection || 'up') === 'up' ? ArrowUpRight : ArrowDownRight"
            class="size-3.5"
          />
          {{ summary.trendValue }}
        </Badge>
      </div>
    </template>

    <template #extra>
      <div
        class="absolute -inset-x-20 -bottom-20 h-64 bg-primary/5 blur-[80px] rounded-full pointer-events-none transition-opacity group-hover:opacity-100 opacity-0"
      ></div>
    </template>
  </BarChartCard>
</template>
