<script setup>
import { computed } from 'vue'
import { ProgressIndicator, ProgressRoot } from 'reka-ui'
import { Card, CardContent } from '@/components/ui/card'
import { useApiLoading } from '@/composables/useApiLoading'
import { useTenantBrand } from '@/composables/useTenantBrand'

const props = defineProps({
  loading: {
    type: Boolean,
    default: undefined
  },
  progress: {
    type: Number,
    default: undefined
  },
  fullscreen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Memuat Halaman'
  },
  description: {
    type: String,
    default: 'Menyiapkan data terbaru untuk Anda...'
  }
})

const { isPageLoading, pageProgress } = useApiLoading()
const { tenantName, tenantLogo, onLogoError } = useTenantBrand()

const isVisible = computed(() =>
  props.loading !== undefined ? props.loading : isPageLoading.value
)

const displayProgress = computed(() => {
  const val = props.progress !== undefined ? props.progress : pageProgress.value
  return Math.max(0, Math.min(100, Math.round(val || 0)))
})
</script>

<template>
  <div
    v-if="isVisible"
    role="status"
    aria-live="polite"
    :class="[
      fullscreen
        ? 'fixed inset-0 z-[100] min-h-screen bg-background/85 backdrop-blur-md p-6'
        : 'w-full flex-1 min-h-[calc(100vh-9.5rem)] py-10 px-4',
      'flex flex-col items-center justify-center select-none'
    ]"
  >
    <Card
      class="relative w-full max-w-sm overflow-hidden border-primary/20 bg-card/90 dark:bg-card/80 shadow-xl backdrop-blur-xl glass-ui"
    >
      <!-- Pendar Cahaya Tema (Ambient Glow) -->
      <div
        class="pointer-events-none absolute -top-14 left-1/2 -translate-x-1/2 h-32 w-32 rounded-full bg-primary/15 blur-2xl"
      />
      <div
        class="pointer-events-none absolute -bottom-14 right-0 h-28 w-28 rounded-full bg-primary/10 blur-2xl"
      />

      <CardContent class="relative z-10 flex flex-col items-center text-center px-8 py-8">
        <!-- Emblem Logo & Cincin Orbital Adaptif -->
        <div class="relative flex items-center justify-center h-32 w-32 mb-4">
          <!-- Gelombang Pulse Luar -->
          <span
            class="absolute inset-0 rounded-full bg-primary/10 animate-ping"
            style="animation-duration: 2.2s"
          />
          <!-- Lingkaran Statis Luar -->
          <span class="absolute inset-1 rounded-full border border-primary/15" />

          <!-- Cincin Orbital Utama (--primary) -->
          <span
            class="absolute inset-1 rounded-full border-[3px] border-transparent border-t-primary border-r-primary/60 animate-spin"
            style="animation-duration: 1s"
          />

          <!-- Cincin Orbital Dalam (Berlawanan Arah) -->
          <span
            class="absolute inset-3 rounded-full border-2 border-transparent border-b-primary/40 border-l-primary/20 animate-spin"
            style="animation-direction: reverse; animation-duration: 1.6s"
          />

          <!-- Safe Zone Panggung Logo (Mendukung Logo Bulat, Kotak, Perisai & Transparan) -->
          <div
            class="relative z-10 flex h-20 w-20 items-center justify-center rounded-2xl bg-background/80 dark:bg-background/60 backdrop-blur-sm border border-primary/15 p-2 shadow-sm"
          >
            <img
              :src="tenantLogo"
              :alt="tenantName"
              class="max-h-full max-w-full object-contain drop-shadow-xs transition-transform duration-200"
              @error="onLogoError"
            />
          </div>
        </div>

        <!-- Nama Sekolah / Yayasan / Gerbang Sekolah -->
        <p class="mb-3 max-w-full truncate text-sm font-extrabold uppercase tracking-wider text-primary">
          {{ tenantName }}
        </p>

        <!-- Judul & Deskripsi Halaman -->
        <h3 class="text-base font-bold tracking-tight text-foreground">
          {{ title }}
        </h3>
        <p class="mt-1 text-xs text-muted-foreground leading-relaxed max-w-[240px]">
          {{ description }}
        </p>

        <!-- Progress Bar Fungsional (0% - 100%) -->
        <div class="mt-6 w-52 space-y-1.5">
          <ProgressRoot
            :model-value="displayProgress"
            class="bg-primary/15 h-1 rounded-full relative flex w-full items-center overflow-hidden"
          >
            <ProgressIndicator
              class="bg-primary size-full flex-1 transition-transform duration-150 ease-out rounded-full"
              :style="`transform: translateX(-${100 - displayProgress}%);`"
            />
          </ProgressRoot>
          <div class="flex items-center justify-between text-[11px] font-medium text-muted-foreground px-0.5">
            <span>Memuat data</span>
            <span class="font-bold text-primary tabular-nums">{{ displayProgress }}%</span>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
