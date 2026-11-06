<script setup lang="ts">
import { computed } from 'vue'
import { AlertCircle, CheckCircle2, PlusCircle, Settings } from 'lucide-vue-next'
import WidgetList from '@/components/dashboard-widget/WidgetList.vue'
import { activitiesData as fallbackData } from '../data/superadminActivityLogData'

const props = defineProps<{
  delay?: number
  activities?: Array<{ id: number; title: string; desc: string; time: string; type: string }>
}>()

const activeItems = computed(() => (props.activities && props.activities.length > 0 ? props.activities : fallbackData))

interface ActivityItem {
  title: string
  desc: string
  time: string
  type: string
}

const asActivity = (item: unknown) => item as ActivityItem

const getInitials = (title: string) => {
  return title
    .split(' ')
    .map(w => w[0])
    .join('')
    .substring(0, 2)
    .toUpperCase()
}

const getActivityConfig = (type: string) => {
  switch (type) {
    case 'add':
      return { icon: PlusCircle, color: 'text-emerald-500' }
    case 'update':
      return { icon: Settings, color: 'text-blue-500' }
    case 'success':
      return { icon: CheckCircle2, color: 'text-emerald-500' }
    case 'warning':
      return { icon: AlertCircle, color: 'text-amber-500' }
    default:
      return { icon: CheckCircle2, color: 'text-muted-foreground' }
  }
}
</script>

<template>
  <WidgetList
    title="Aktivitas Sistem"
    description="Log aktivitas terbaru dari seluruh pengguna"
    emptyText="Belum ada aktivitas sistem terbaru."
    footerText="Lihat semua log"
    :items="activeItems"
    cardClass="lg:col-span-2 flex flex-col justify-between"
    listClass="h-[230px] px-4 sm:px-6"
    :delay="delay || 0"
    illustration="paper_plane"
  >
    <template #item="{ item }">
      <!-- Avatar -->
      <div
        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/20 backdrop-blur-md border border-primary/20 text-[10px] font-bold text-primary drop-shadow-sm"
      >
        {{ getInitials(asActivity(item).title) }}
      </div>
      <!-- Content -->
      <div class="min-w-0 flex-1">
        <div class="flex items-center justify-between gap-2">
          <p class="truncate text-sm font-medium">{{ asActivity(item).title }}</p>
          <component
            :is="getActivityConfig(asActivity(item).type).icon"
            :class="[
              'size-3.5 shrink-0 drop-shadow-sm',
              getActivityConfig(asActivity(item).type).color
            ]"
          />
        </div>
        <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ asActivity(item).desc }}</p>
        <p class="mt-1 text-[10px] text-muted-foreground/70">{{ asActivity(item).time }}</p>
      </div>
    </template>
  </WidgetList>
</template>
