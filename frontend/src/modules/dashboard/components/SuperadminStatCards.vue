<script setup lang="ts">
import { computed } from 'vue'
import { Building2, GraduationCap, School, Users } from 'lucide-vue-next'
import StatCard from '@/components/stat-card/StatCard.vue'
import StatCardGrid from '@/components/stat-card/StatCardGrid.vue'
import { formatNumber, formatDelta } from '@/utils/formatNumber'
import { superadminStatsData as fallbackData } from '../data/superadminStats'

const props = defineProps<{
  stats?: any
  loading?: boolean
}>()

const activeStats = computed(() => props.stats || fallbackData)
</script>

<template>
  <StatCardGrid cols="4">
    <StatCard
      label="Total Yayasan"
      :value="formatNumber(activeStats.yayasan.total)"
      :icon="Building2"
      illustration="globe"
      variant="blue"
      :loading="loading"
    >
      <div class="mt-1 flex items-center gap-1.5 flex-wrap">
        <span class="inline-flex items-center gap-1 text-[10px] sm:text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded backdrop-blur-sm">
          <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          {{ activeStats.yayasan.aktif }} Aktif
        </span>
        <span class="text-muted-foreground/60 text-[10px]">•</span>
        <span class="inline-flex items-center gap-1 text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-500/10 px-1.5 py-0.5 rounded backdrop-blur-sm">
          <span class="size-1.5 rounded-full bg-slate-400"></span>
          {{ activeStats.yayasan.nonAktif }} Nonaktif
        </span>
      </div>
    </StatCard>

    <StatCard
      label="Total Sekolah"
      :value="formatNumber(activeStats.sekolah.total)"
      :icon="School"
      illustration="abc_board"
      variant="violet"
      :loading="loading"
    >
      <div class="mt-1 flex items-center gap-1.5 flex-wrap">
        <span class="inline-flex items-center gap-1 text-[10px] sm:text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded backdrop-blur-sm">
          <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          {{ activeStats.sekolah.aktif }} Aktif
        </span>
        <span class="text-muted-foreground/60 text-[10px]">•</span>
        <span class="inline-flex items-center gap-1 text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-500/10 px-1.5 py-0.5 rounded backdrop-blur-sm">
          <span class="size-1.5 rounded-full bg-slate-400"></span>
          {{ activeStats.sekolah.nonAktif }} Nonaktif
        </span>
      </div>
    </StatCard>

    <StatCard
      label="Total Guru"
      :value="formatNumber(activeStats.guru.total)"
      :sub="`${formatDelta(activeStats.guru.newThisMonth)} bulan ini`"
      :trend="`${formatDelta(activeStats.guru.growthPercent)}%`"
      :trendDirection="activeStats.guru.trendDirection"
      :icon="GraduationCap"
      illustration="open_book"
      variant="emerald"
      :loading="loading"
    />

    <StatCard
      label="Total Siswa"
      :value="formatNumber(activeStats.siswa.total)"
      :sub="`${formatDelta(activeStats.siswa.newThisMonth)} bulan ini`"
      :trend="`${formatDelta(activeStats.siswa.growthPercent)}%`"
      :trendDirection="activeStats.siswa.trendDirection"
      :icon="Users"
      illustration="graduation_cap"
      variant="amber"
      :loading="loading"
    />
  </StatCardGrid>
</template>
