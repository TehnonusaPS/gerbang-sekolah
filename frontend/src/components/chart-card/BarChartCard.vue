<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue'
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
  ChartLegendContent,
  ChartCrosshair,
  componentToString
} from '@/components/ui/chart'
import { VisXYContainer, VisGroupedBar, VisAxis } from '@unovis/vue'
import WidgetCard from '@/components/dashboard-widget/WidgetCard.vue'
import { ArrowUpRight, ArrowDownRight, TrendingUp, TrendingDown } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  data: { type: Array, required: true },
  index: { type: String, required: true }, // Field name for X Axis (e.g., 'month')
  categories: { type: Array, required: true }, // Array of field names for Y values (e.g., ['data1', 'data2'])
  config: { type: Object, required: true }, // Chart configuration for labeling and colors
  delay: { type: Number, default: 0 },
  height: { type: [Number, String], default: 220 },
  icon: { type: [Object, Function], default: null },
  illustration: { type: String, default: '' },
  color: { type: String, default: 'primary' },
  cardClass: { type: String, default: '' },
  contentClass: { type: String, default: 'pb-4' },
  footerClass: { type: String, default: '' },
  trendValue: { type: String, default: '' },
  trendDirection: { type: String, default: 'up' }, // 'up' | 'down' | 'neutral'
  footerTitle: { type: String, default: '' },
  footerSubtext: { type: String, default: '' },
  footerTrend: { type: String, default: '' } // 'up' | 'down' | 'neutral'
})

// Dynamic accessors for Unovis
const yAccessors = computed(() => props.categories.map(cat => d => d[cat]))
const colors = computed(() => props.categories.map(cat => `var(--color-${cat})`))

const isMobile = ref(false)

function handleResize() {
  if (typeof window !== 'undefined') {
    isMobile.value = window.innerWidth < 640
  }
}

onMounted(() => {
  handleResize()
  window.addEventListener('resize', handleResize, { passive: true })
})

onUnmounted(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', handleResize)
  }
})

const xTickFormat = computed(() => {
  return (i) => {
    const val = props.data[i]?.[props.index]
    if (!val) return ''
    if (isMobile.value) {
      // Pada mobile, gunakan inisial huruf pertama (J, F, M, A, M, J, J, A, S, O, N, D)
      // agar tidak bertumpukan di layar sempit
      return String(val).slice(0, 1)
    }
    return val
  }
})

function getColorClasses(colorName) {
  const colors = {
    blue: { color: 'text-blue-500', bg: 'bg-blue-500/10' },
    violet: { color: 'text-violet-500', bg: 'bg-violet-500/10' },
    emerald: { color: 'text-emerald-500', bg: 'bg-emerald-500/10' },
    amber: { color: 'text-amber-500', bg: 'bg-amber-500/10' },
    primary: { color: 'text-primary', bg: 'bg-primary/10' }
  }
  return colors[colorName] || colors.primary
}
</script>

<template>
  <WidgetCard
    :title="title"
    :description="description"
    :delay="delay"
    :illustration="illustration"
    :cardClass="cardClass"
    :contentClass="contentClass"
    :footerClass="footerClass"
  >
    <!-- Header Action slot passthrough -->
    <template #header-action>
      <slot name="header-action">
        <!-- Automatic Trend Badge if trendValue is provided -->
        <Badge
          v-if="trendValue"
          variant="secondary"
          class="gap-1 text-xs bg-white/10 dark:bg-white/10 backdrop-blur-md border border-white/20 shadow-sm text-foreground"
        >
          <component
            v-if="trendDirection === 'up'"
            :is="ArrowUpRight"
            class="size-3 text-emerald-500 drop-shadow-sm"
          />
          <component
            v-else-if="trendDirection === 'down'"
            :is="ArrowDownRight"
            class="size-3 text-rose-500 drop-shadow-sm"
          />
          {{ trendValue }}
        </Badge>
        <div
          v-else-if="icon && !illustration"
          :class="[
            'stat-icon-badge rounded-xl p-2 backdrop-blur-md shadow-lg border border-white/10 transition-colors',
            getColorClasses(color).bg
          ]"
        >
          <component
            :is="icon"
            :class="['size-4 drop-shadow-md', getColorClasses(color).color]"
          />
        </div>
      </slot>
    </template>

    <slot name="extra" />

    <!-- Chart Content -->
    <div class="relative z-10">
      <ChartContainer
        :config="config"
        class="w-full bar-chart-container opacity-90 drop-shadow-sm"
        :style="{ height: typeof height === 'number' ? `${height}px` : height }"
      >
        <VisXYContainer
          :data="data"
          :xDomain="[-0.5, data.length - 0.5]"
        >
          <VisGroupedBar
            :x="(d, i) => i"
            :y="yAccessors"
            :color="colors"
            :rounded-corners="4"
          />
          <VisAxis
            type="x"
            :tickFormat="xTickFormat"
            :tickValues="data.map((_, i) => i)"
            :grid-line="false"
            :tick-line="false"
            :domain-line="false"
          />
          <VisAxis
            type="y"
            :tick-line="false"
            :domain-line="false"
            :grid-line="true"
          />
          <ChartTooltip />
          <ChartCrosshair
            :template="
              componentToString(config, ChartTooltipContent, {
                labelFormatter: x => data[Math.round(Number(x))]?.[index] || ''
              })
            "
            :color="colors"
            :hideWhenFarFromPointer="false"
            :skipRangeCheck="true"
          />
        </VisXYContainer>
        <ChartLegendContent />
      </ChartContainer>
    </div>

    <!-- Footer slot passthrough -->
    <template
      v-if="$slots.footer || footerTitle || footerSubtext"
      #footer
    >
      <slot name="footer">
        <div class="flex flex-col gap-1 w-full text-left">
          <div
            v-if="footerTitle"
            class="flex gap-2 font-medium leading-none items-center drop-shadow-sm"
          >
            {{ footerTitle }}
            <component
              v-if="footerTrend === 'up'"
              :is="TrendingUp"
              class="h-4 w-4 text-emerald-500"
            />
            <component
              v-else-if="footerTrend === 'down'"
              :is="TrendingDown"
              class="h-4 w-4 text-rose-500"
            />
          </div>
          <div
            v-if="footerSubtext"
            class="leading-none text-muted-foreground text-xs mt-1"
          >
            {{ footerSubtext }}
          </div>
        </div>
      </slot>
    </template>
  </WidgetCard>
</template>
