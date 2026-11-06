<script setup lang="ts">
import { computed } from 'vue'
import { School, GraduationCap, Users, Wallet } from 'lucide-vue-next'
import StatCard from '@/components/stat-card/StatCard.vue'
import StatCardGrid from '@/components/stat-card/StatCardGrid.vue'
import { formatNumber } from '@/utils/formatNumber'

const props = defineProps<{
  stats?: any
  loading?: boolean
}>()

const activeStats = computed(() => props.stats || {})

const formatRupiahShort = (num: number) => {
  if (!num) return 'Rp 0'
  if (num >= 1000000000) return `Rp ${(num / 1000000000).toFixed(1).replace('.0', '')} Miliar`
  if (num >= 1000000) return `Rp ${(num / 1000000).toFixed(1).replace('.0', '')} Juta`
  return `Rp ${new Intl.NumberFormat('id-ID').format(num)}`
}
</script>

<template>
  <StatCardGrid cols="4">
    <!-- 1. Total Siswa Konsolidasi -->
    <StatCard
      label="Total Siswa"
      :value="formatNumber(activeStats.siswa?.total || 0)"
      :sub="activeStats.siswa?.subText || 'di seluruh unit sekolah'"
      :trend="activeStats.siswa?.newThisMonth ? `+${activeStats.siswa.newThisMonth} bulan ini` : 'Konsolidasi'"
      :trendDirection="activeStats.siswa?.trendDirection || 'up'"
      :icon="GraduationCap"
      illustration="graduation_cap"
      variant="blue"
      :loading="loading"
    />

    <!-- 2. Unit Sekolah Binaan -->
    <StatCard
      label="Unit Sekolah"
      :value="formatNumber(activeStats.sekolah?.total || 0)"
      :icon="School"
      illustration="globe"
      variant="violet"
      :loading="loading"
    >
      <div
        class="mt-1 flex items-center gap-1.5 flex-wrap"
        :title="activeStats.sekolah?.akreditasi ? `Komposisi: ${activeStats.sekolah.akreditasi}` : undefined"
      >
        <span class="inline-flex items-center gap-1 text-[10px] sm:text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded backdrop-blur-sm">
          <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          {{ activeStats.sekolah?.aktif || 0 }} Aktif
        </span>
        <span class="text-muted-foreground/60 text-[10px]">•</span>
        <span class="inline-flex items-center gap-1 text-[10px] sm:text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-500/10 px-1.5 py-0.5 rounded backdrop-blur-sm">
          <span class="size-1.5 rounded-full bg-slate-400"></span>
          {{ activeStats.sekolah?.nonAktif || 0 }} Nonaktif
        </span>
      </div>
    </StatCard>

    <!-- 3. Pendidik & Staf (PTK) -->
    <StatCard
      label="Pendidik & Staf"
      :value="formatNumber(activeStats.ptk?.total || 0)"
      :sub="`${activeStats.ptk?.guru || 0} Guru • ${activeStats.ptk?.staff || 0} Staf`"
      :trend="activeStats.ptk?.ratio ? `Rasio ${activeStats.ptk.ratio}` : ''"
      trendDirection="up"
      :icon="Users"
      illustration="open_book"
      variant="emerald"
      :loading="loading"
    />

    <!-- 4. Kolektibilitas Keuangan SPP -->
    <StatCard
      label="Kolektibilitas SPP"
      :value="`${activeStats.keuangan?.kolektibilitasPercent ?? 0}%`"
      :sub="activeStats.keuangan?.totalTagihan > 0 ? `Target: ${formatRupiahShort(activeStats.keuangan.totalTagihan)}` : 'Belum ada tagihan'"
      :progress="activeStats.keuangan?.kolektibilitasPercent ?? 0"
      :trend="activeStats.keuangan?.totalTunggakan > 0 ? `Tunggakan ${formatRupiahShort(activeStats.keuangan.totalTunggakan)}` : '100% Lunas'"
      :trendDirection="activeStats.keuangan?.trendDirection || 'up'"
      :icon="Wallet"
      illustration="atom"
      variant="amber"
      :loading="loading"
    />
  </StatCardGrid>
</template>
