<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import BarChartCard from '@/components/chart-card/BarChartCard.vue'
import { Badge } from '@/components/ui/badge'
import { Building } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps<{
  delay?: number
  comparisonData?: Array<{
    id: number
    name: string
    fullName: string
    level: string
    accreditation: string
    siswa: number
    guru: number
    totalPtk: number
    kolektibilitas: number
  }>
  loading?: boolean
}>()

const auth = useAuthStore()
const computedDelay = computed(() => (auth.isJustLoggedIn ? 1400 : 0) + (props.delay || 0))

const defaultComparison = [
  { name: 'SD Unit', siswa: 0, guru: 0 },
  { name: 'SMP Unit', siswa: 0, guru: 0 }
]

const sourceData = computed(() => {
  if (props.comparisonData && props.comparisonData.length > 0) {
    return props.comparisonData.map(item => ({
      name: item.name || item.fullName,
      siswa: item.siswa,
      guru: item.guru
    }))
  }
  return defaultComparison
})

const activeData = ref(sourceData.value.map(d => ({ ...d, siswa: 0, guru: 0 })))

watch(sourceData, (newVal) => {
  activeData.value = newVal
}, { deep: true })

onMounted(() => {
  setTimeout(() => {
    activeData.value = sourceData.value
  }, computedDelay.value + 350)
})

const chartConfig = {
  siswa: {
    label: 'Siswa Aktif',
    color: '#0ea5e9'
  },
  guru: {
    label: 'Tenaga Pendidik',
    color: '#6366f1'
  }
}

const totalSchools = computed(() => props.comparisonData?.length || 0)
const totalSiswaInComparison = computed(() => props.comparisonData?.reduce((acc, curr) => acc + (curr.siswa || 0), 0) || 0)
</script>

<template>
  <BarChartCard
    title="Komparasi Unit Sekolah"
    description="Perbandingan jumlah siswa & tenaga pendidik per unit sekolah"
    cardClass="lg:col-span-3 flex flex-col justify-between"
    footerClass="flex-col items-start gap-1 text-xs"
    :delay="delay || 0"
    illustration="abc_board"
    :data="activeData"
    index="name"
    :categories="['siswa', 'guru']"
    :config="chartConfig"
    :height="230"
    :footerTitle="`Total ${totalSiswaInComparison} siswa di ${totalSchools} unit sekolah`"
    footerTrend="up"
    footerSubtext="Menampilkan komparasi skala kapasitas operasional sekolah"
  >
    <template #header-action>
      <Badge
        variant="secondary"
        class="font-medium text-xs gap-1 border-0 h-7 px-2.5 bg-primary/10 text-primary"
      >
        <Building class="size-3.5" />
        {{ totalSchools }} Unit Sekolah
      </Badge>
    </template>

    <template #extra>
      <div
        class="absolute -inset-x-20 -bottom-20 h-64 bg-primary/5 blur-[80px] rounded-full pointer-events-none transition-opacity group-hover:opacity-100 opacity-0"
      ></div>
    </template>
  </BarChartCard>
</template>

