<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import WidgetCard from '@/components/dashboard-widget/WidgetCard.vue'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import { ScrollArea } from '@/components/ui/scroll-area'
import { CheckCircle2, ChevronRight, School, Users, Wallet } from 'lucide-vue-next'

const props = defineProps<{
  delay?: number
  schools?: Array<{
    id: number
    name: string
    fullName: string
    level: string
    accreditation: string
    status: string
    siswa: number
    guru: number
    staff: number
    totalPtk: number
    tagihan: number
    terbayar: number
    tunggakan: number
    kolektibilitas: number
  }>
  loading?: boolean
}>()

const router = useRouter()

const activeSchools = computed(() => props.schools || [])

const getAccreditationVariant = (akred: string) => {
  if (akred === 'A') return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20'
  if (akred === 'B') return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'
  return 'bg-muted text-muted-foreground border-border'
}

const getLevelColor = (level: string) => {
  const l = (level || '').toUpperCase()
  if (l === 'SD') return 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20'
  if (l === 'SMP') return 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20'
  if (l === 'SMA') return 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20'
  if (l === 'SMK') return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20'
  return 'bg-primary/10 text-primary border-primary/20'
}

const goToSchools = () => {
  router.push('/manajemen-data/sekolah')
}
</script>

<template>
  <WidgetCard
    title="Ringkasan Unit Sekolah"
    description="Status operasional & kepatuhan SPP per unit"
    cardClass="lg:col-span-2 flex flex-col justify-between"
    contentClass="p-0"
    :delay="delay || 0"
    illustration="closed_book"
  >
    <ScrollArea class="h-[230px] px-4 sm:px-6">
      <div
        v-if="activeSchools && activeSchools.length > 0"
        class="space-y-2.5 py-2.5"
      >
        <div
          v-for="school in activeSchools"
          :key="school.id"
          class="group p-3 rounded-xl border border-white/10 bg-white/5 dark:bg-white/[0.02] hover:bg-white/10 transition-all"
        >
          <!-- Baris 1: Nama Sekolah & Badge -->
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-1.5 min-w-0">
              <span
                class="px-1.5 py-0.5 rounded text-[10px] font-bold border shrink-0"
                :class="getLevelColor(school.level)"
              >
                {{ school.level }}
              </span>
              <h4 class="text-xs sm:text-sm font-semibold truncate text-foreground group-hover:text-primary transition-colors">
                {{ school.fullName || school.name }}
              </h4>
            </div>
            <span
              class="px-1.5 py-0.5 rounded text-[10px] font-semibold border shrink-0"
              :class="getAccreditationVariant(school.accreditation)"
            >
              Akred. {{ school.accreditation }}
            </span>
          </div>

          <!-- Baris 2: Metrik Singkat -->
          <div class="mt-2 flex items-center justify-between text-[11px] text-muted-foreground">
            <span class="flex items-center gap-1">
              <Users class="size-3 text-muted-foreground/80" />
              <strong class="text-foreground font-medium">{{ school.siswa }}</strong> Siswa
              <span class="text-muted-foreground/40">•</span>
              <strong class="text-foreground font-medium">{{ school.guru }}</strong> Guru
            </span>
            <span class="font-medium text-foreground">
              SPP: <strong :class="school.kolektibilitas >= 80 ? 'text-emerald-500' : 'text-amber-500'">{{ school.kolektibilitas }}%</strong>
            </span>
          </div>

          <!-- Baris 3: Progress Kolektibilitas SPP Unit -->
          <div class="mt-1.5">
            <Progress
              :model-value="school.kolektibilitas"
              class="h-1.5 bg-muted/60"
            />
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div
        v-else
        class="flex flex-col items-center justify-center h-[200px] text-muted-foreground text-xs gap-1.5"
      >
        <School class="size-8 opacity-40" />
        <p>Belum ada unit sekolah yang terdaftar.</p>
      </div>
    </ScrollArea>

    <template #footer>
      <button
        type="button"
        @click="goToSchools"
        class="w-full flex items-center justify-center gap-1 text-xs text-muted-foreground hover:text-foreground py-1 font-medium transition-colors"
      >
        Lihat detail sekolah di Manajemen Data
        <ChevronRight class="size-3.5" />
      </button>
    </template>
  </WidgetCard>
</template>

