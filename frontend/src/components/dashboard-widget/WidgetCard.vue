<script setup>
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle
} from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ChevronRight } from 'lucide-vue-next'
import { glassSlide } from '@/config/motion'

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  icon: { type: [Object, Function], required: false },
  footerText: { type: String, default: '' },
  contentClass: { type: String, default: '' },
  footerClass: { type: String, default: '' },
  cardClass: { type: String, default: '' },
  delay: { type: Number, default: 0 },
  illustration: { type: String, default: '' },
  loading: { type: Boolean, default: undefined }
})

import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useApiLoading } from '@/composables/useApiLoading'
const auth = useAuthStore()
const { isFetching } = useApiLoading()
const isWidgetLoading = computed(() => Boolean(props.loading || isFetching.value))
const computedDelay = computed(() => (auth.isJustLoggedIn ? 1400 : 0) + props.delay)

const illustrationUrl = computed(() => {
  if (!props.illustration) return ''
  return new URL(`../../assets/images/illustrations/${props.illustration}.png`, import.meta.url)
    .href
})
</script>

<template>
  <Card
    v-motion
    :initial="glassSlide.initial"
    :visible-once="{
      ...glassSlide.visible,
      transition: { ...glassSlide.visible.transition, delay: computedDelay }
    }"
    class="flex flex-col relative overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1 hover:border-white/40 group glass-ui"
    :class="cardClass"
  >
    <!-- Background Watermark Illustration (Top-Right) -->
    <div
      v-if="illustration"
      class="absolute top-[-15px] right-[-18px] size-30 rotate-[-15deg] opacity-[0.15] dark:opacity-[0.22] pointer-events-none select-none bg-primary z-0 transition-transform duration-300 group-hover:scale-105 group-hover:rotate-[-10deg] watermark-illustration"
      style="
        mask-size: contain;
        -webkit-mask-size: contain;
        mask-repeat: no-repeat;
        -webkit-mask-repeat: no-repeat;
        mask-position: center;
        -webkit-mask-position: center;
      "
      :style="{
        maskImage: `url(${illustrationUrl})`,
        webkitMaskImage: `url(${illustrationUrl})`
      }"
    />

    <CardHeader class="pb-2 relative z-10">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <component
            v-if="icon"
            :is="icon"
            class="size-4 text-primary"
          />
          <CardTitle class="text-base font-semibold">{{ title }}</CardTitle>
        </div>
        <slot name="header-action" />
      </div>
      <CardDescription
        v-if="description"
        class="text-xs mt-0.5"
      >
        {{ description }}
      </CardDescription>
    </CardHeader>

    <CardContent
      class="flex-1 relative z-10"
      :class="contentClass"
    >
      <slot />
    </CardContent>

    <CardFooter
      v-if="$slots.footer || footerText"
      class="border-t border-white/10 pt-4 relative z-10"
      :class="footerClass"
    >
      <slot name="footer">
        <Button
          v-if="footerText"
          variant="ghost"
          class="w-full gap-1.5 text-xs h-8 text-muted-foreground hover:text-foreground"
        >
          {{ footerText }}
          <ChevronRight class="size-3.5" />
        </Button>
      </slot>
    </CardFooter>
  </Card>
</template>
