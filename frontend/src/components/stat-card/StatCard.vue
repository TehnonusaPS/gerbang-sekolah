<script setup>
import { TrendingUp, TrendingDown, Minus } from 'lucide-vue-next'
import { Card, CardContent, CardDescription, CardHeader } from '@/components/ui/card'
import { Progress } from '@/components/ui/progress'
import RollingNumber from '@/components/ui/rolling-number/RollingNumber.vue'
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useApiLoading } from '@/composables/useApiLoading'

const props = defineProps({
  label: { type: String, required: true },
  value: { type: [String, Number], required: true },
  valueClass: { type: String, default: '' },
  sub: { type: String, default: '' },
  trend: { type: [String, Number], default: '' },
  trendDirection: {
    type: String,
    default: 'up',
    validator: v => ['up', 'down', 'neutral'].includes(v)
  },
  icon: { type: [Object, Function], required: false },
  illustration: { type: String, default: '' },
  progress: { type: Number, required: false },
  variant: {
    type: String,
    default: ''
  },
  color: {
    type: String,
    default: ''
  },
  delay: { type: Number, default: 0 },
  loading: { type: Boolean, default: undefined }
})

const { isFetching } = useApiLoading()
const isCardLoading = computed(() => Boolean(props.loading || isFetching.value))

const auth = useAuthStore()
const computedDelay = computed(() => (auth.isJustLoggedIn ? 1400 : 0) + props.delay)

// Semantic variants: up, down, neutral, progress, default
const typeList = ['up', 'down', 'neutral', 'progress', 'default']

const resolvedType = computed(() => {
  if (typeList.includes(props.variant)) {
    return props.variant
  }
  if (props.progress !== undefined) {
    return 'progress'
  }
  if (props.trend) {
    return props.trendDirection || 'up'
  }
  return 'default'
})

const resolvedColor = computed(() => {
  if (typeList.includes(props.variant)) {
    return props.color || 'primary'
  }
  return props.variant || 'primary'
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

function getTrendConfig(direction) {
  switch (direction) {
    case 'down':
      return { icon: TrendingDown, colorClass: 'text-red-500', bgClass: 'bg-red-500/10' }
    case 'neutral':
      return { icon: Minus, colorClass: 'text-slate-500', bgClass: 'bg-slate-500/10' }
    case 'up':
    default:
      return { icon: TrendingUp, colorClass: 'text-emerald-500', bgClass: 'bg-emerald-500/10' }
  }
}

const illustrationUrl = computed(() => {
  if (!props.illustration) return ''
  return new URL(`../../assets/images/illustrations/${props.illustration}.png`, import.meta.url)
    .href
})
</script>

<template>
  <Card
    :style="{ '--base-delay': `${computedDelay}ms` }"
    class="animate-card-enter relative overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-white/40 group glass-ui py-0 sm:py-4 gap-0 sm:gap-4 !rounded-2xl sm:!rounded-[32px]"
  >
    <!-- Watermark Doodle Illustration Background -->
    <div
      v-if="illustration"
      :class="[
        'absolute -right-2 -bottom-2 size-10 sm:size-18 opacity-[0.18] dark:opacity-[0.25] rotate-12 transition-all duration-300 group-hover:scale-110 group-hover:rotate-6 watermark-illustration',
        getColorClasses(resolvedColor).color
      ]"
      :style="{
        backgroundColor: 'currentColor',
        maskImage: `url(${illustrationUrl})`,
        webkitMaskImage: `url(${illustrationUrl})`,
        maskSize: 'contain',
        webkitMaskSize: 'contain',
        maskRepeat: 'no-repeat',
        webkitMaskRepeat: 'no-repeat',
        maskPosition: 'center',
        webkitMaskPosition: 'center'
      }"
    />
    <!-- Fallback Watermark Icon Background -->
    <component
      v-if="icon"
      :is="icon"
      :class="[
        'absolute -right-4 -bottom-4 size-12 sm:size-24 opacity-[0.04] rotate-12 transition-transform duration-300 group-hover:scale-110 watermark-icon-fallback',
        getColorClasses(resolvedColor).color
      ]"
    />

    <CardHeader class="!px-3.5 pt-2 pb-0 sm:!px-4 sm:pt-0 sm:pb-2 relative z-10">
      <div class="flex items-center justify-between">
        <CardDescription class="text-[10px] sm:text-sm font-medium truncate max-w-[80%]">
          {{ label }}
        </CardDescription>
        <div
          :class="[
            'stat-icon-badge rounded-md sm:rounded-xl p-1 sm:p-2.5 backdrop-blur-md shadow-lg border border-white/10 transition-colors',
            getColorClasses(resolvedColor).bg
          ]"
        >
          <component
            v-if="icon"
            :is="icon"
            :class="['size-3 sm:size-4 drop-shadow-md', getColorClasses(resolvedColor).color]"
          />
        </div>
      </div>
    </CardHeader>

    <CardContent class="!px-3.5 pt-0 pb-2 sm:!px-4 sm:pb-0 relative z-10 mt-[-3px] sm:mt-0">
      <div :class="[valueClass || 'text-lg sm:text-3xl', 'font-bold tracking-tight drop-shadow-sm']">
        <RollingNumber
          :value="value"
          :delay="computedDelay"
        />
      </div>

      <!-- Variant Progress -->
      <div
        v-if="resolvedType === 'progress' && progress !== undefined"
        class="space-y-0.5 sm:space-y-1.5 mt-1 sm:mt-3 w-full"
      >
        <div class="flex items-center justify-between text-[9px] sm:text-xs">
          <span class="text-muted-foreground truncate max-w-[70%]">{{ sub || 'Realisasi' }}</span>
          <span :class="['font-semibold', getColorClasses(resolvedColor).color]"
            >{{ progress }}%</span
          >
        </div>
        <Progress
          :model-value="progress"
          :delay="computedDelay + 250"
          class="h-1 sm:h-2"
        />
      </div>

      <!-- Variant Trend Default -->
      <div
        v-else-if="
          resolvedType === 'up' ||
          resolvedType === 'down' ||
          resolvedType === 'neutral' ||
          trend ||
          sub
        "
        class="mt-0.5 sm:mt-1 flex flex-col items-start gap-0 sm:flex-row sm:items-center sm:gap-1.5"
      >
        <span
          v-if="trend"
          :class="[
            'flex items-center gap-0.5 text-[9px] sm:text-xs font-bold px-1 py-0.2 rounded backdrop-blur-sm whitespace-nowrap',
            getTrendConfig(resolvedType).colorClass,
            getTrendConfig(resolvedType).bgClass
          ]"
        >
          <component
            :is="getTrendConfig(resolvedType).icon"
            class="size-2 sm:size-3"
          />
          {{ trend }}
        </span>
        <span
          v-if="sub"
          class="text-[9px] sm:text-xs text-muted-foreground whitespace-nowrap truncate max-w-full"
          >{{ sub }}</span
        >
      </div>
    </CardContent>
  </Card>
</template>

<style scoped>
@keyframes card-enter {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.animate-card-enter {
  opacity: 0;
  animation: card-enter 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  animation-delay: calc(
    var(--base-delay, 0ms) + var(--delay-index, 0) * var(--stagger-delay, 100ms)
  );
}
</style>
