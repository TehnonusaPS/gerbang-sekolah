<script setup>
import { computed, h, render } from 'vue'
import { ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart'
import { VisSingleContainer, VisDonut } from '@unovis/vue'
import { Donut } from '@unovis/ts'
import WidgetCard from '@/components/dashboard-widget/WidgetCard.vue'
import RollingNumber from '@/components/ui/rolling-number/RollingNumber.vue'
import { Badge } from '@/components/ui/badge'
import { isClient } from '@vueuse/core'
import { useId } from 'reka-ui'

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  data: { type: Array, required: true },
  category: { type: String, required: true }, // Field name for slice value (e.g., 'nilai')
  index: { type: String, required: true }, // Field name for slice label key (e.g., 'status')
  config: { type: Object, required: true }, // Chart configuration containing labels and colors
  delay: { type: Number, default: 0 },
  height: { type: [Number, String], default: 220 },
  icon: { type: [Object, Function], default: null },
  illustration: { type: String, default: '' },
  color: { type: String, default: 'primary' },
  cardClass: { type: String, default: '' },
  showCenterLabel: { type: Boolean, default: true },
  centerLabelValue: { type: String, default: '' },
  centerLabelText: { type: String, default: '' },
  showLegend: { type: Boolean, default: true },
  contentClass: { type: String, default: 'flex-1 flex items-center justify-center pb-2' },
  footerClass: {
    type: String,
    default: 'flex-col gap-1.5 items-stretch mt-4 pt-4 border-t border-white/10'
  },
  trendValue: { type: String, default: '' },
  trendDirection: { type: String, default: 'up' }, // 'up' | 'down' | 'neutral'
  footerTitle: { type: String, default: '' },
  footerSubtext: { type: String, default: '' },
  footerTrend: { type: String, default: '' } // 'up' | 'down' | 'neutral'
})

// Color function for Donut slices mapped to the config keys
function getSliceColor(d) {
  const key = d[props.index]
  return props.config[key]?.color || 'var(--primary)'
}

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

// Auto-calculate center label percentage if not custom specified
const calculatedCenterLabelValue = computed(() => {
  if (props.centerLabelValue) return props.centerLabelValue

  // Try to find the primary/positive value to display as percentage
  // Usually, first element in data is the main/progress metric
  const firstVal = props.data[0]?.[props.category] || 0
  const total = props.data.reduce((sum, item) => sum + (item[props.category] || 0), 0)
  if (total === 0) return '0%'
  return `${Math.round((firstVal / total) * 100)}%`
})

// Cache and ID for custom tooltip rendering to keep it working for slice values
const tooltipId = useId()
const tooltipCache = new Map()

const customTooltip = computed(() => {
  if (!isClient) return undefined

  return _data => {
    const data = 'data' in _data ? _data.data : _data
    const sliceKey = data[props.index]
    const sliceConfig = props.config[sliceKey] || {}

    // Create a temporary config that maps the category key to the hovered slice config
    // This allows ChartTooltipContent to correctly match the value key and show its label/color
    const tempConfig = {
      [props.category]: {
        label: sliceConfig.label || sliceKey,
        color: sliceConfig.color || 'var(--primary)'
      }
    }

    const serializedKey = `${tooltipId}-${JSON.stringify(data)}`
    let cachedContent = tooltipCache.get(serializedKey)
    if (!cachedContent) {
      const vnode = h(ChartTooltipContent, {
        hideLabel: true,
        payload: data,
        config: tempConfig
      })
      const div = document.createElement('div')
      render(vnode, div)
      cachedContent = div.innerHTML
      tooltipCache.set(serializedKey, cachedContent)
    }
    return cachedContent
  }
})
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
    <ChartContainer
      :config="config"
      class="mx-auto aspect-square relative w-full opacity-90 drop-shadow-sm"
      :style="{ maxHeight: typeof height === 'number' ? `${height}px` : height }"
    >
      <VisSingleContainer
        :data="data"
        :margin="{ top: 20, bottom: 20 }"
      >
        <VisDonut
          :id="d => d[index]"
          :value="d => d[category]"
          :color="getSliceColor"
          :arc-width="32"
          :duration="1200"
        />
        <ChartTooltip
          :triggers="{
            [Donut.selectors.segment]: customTooltip
          }"
        />
      </VisSingleContainer>

      <!-- Central Label Overlay -->
      <div
        v-if="showCenterLabel"
        class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none mt-1"
      >
        <span class="text-3xl font-bold text-foreground">
          <RollingNumber
            :value="calculatedCenterLabelValue"
            :delay="0"
            :duration="1200"
          />
        </span>
        <span
          v-if="centerLabelText"
          class="text-xs text-muted-foreground mt-0.5"
          >{{ centerLabelText }}</span
        >
      </div>
    </ChartContainer>

    <!-- Custom Legend Footer rendered using WidgetCard footer slot -->
    <template
      v-if="showLegend || $slots.footer || footerTitle || footerSubtext"
      #footer
    >
      <slot name="footer">
        <!-- Case 1: Simple Title/Subtext Footer -->
        <div
          v-if="footerTitle || footerSubtext"
          class="flex flex-col gap-1 w-full text-left"
        >
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
        <!-- Case 2: Default Legend -->
        <div
          v-else-if="showLegend"
          class="w-full flex flex-col gap-1.5 items-stretch"
        >
          <div
            v-for="item in data"
            :key="item[index]"
            class="flex items-center justify-between text-xs"
          >
            <div class="flex items-center gap-2">
              <span
                class="inline-block size-2.5 rounded-xs"
                :style="{ backgroundColor: config[item[index]]?.color || 'var(--primary)' }"
              />
              <span class="text-muted-foreground">{{
                config[item[index]]?.label || item[index]
              }}</span>
            </div>
            <span class="font-semibold text-foreground">{{ item[category] }}</span>
          </div>
        </div>
      </slot>
    </template>
  </WidgetCard>
</template>
