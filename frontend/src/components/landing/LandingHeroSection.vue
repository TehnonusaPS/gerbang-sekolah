<script setup>
import { ArrowRight, Building2, MessageCircle, School, Sparkles, Download } from 'lucide-vue-next'
import heroBg from '@/assets/images/landing-hero.png'
import { usePwa } from '@/composables/usePwa'

const { triggerInstall } = usePwa()

const stats = [
  { label: 'Total Siswa', value: '4.218', color: 'bg-primary' },
  { label: 'Sekolah Aktif', value: '12', color: 'bg-accent' },
  { label: 'Pemasukan Bulan Ini', value: 'Rp 1.8M', color: 'bg-secondary' },
  { label: 'Kehadiran Hari Ini', value: '96.4%', color: 'bg-lavender' }
]

const yayasan = [
  'Yayasan Cahaya Bangsa',
  'Al-Hikmah Foundation',
  'Tunas Mulia',
  'Pelita Harapan Group',
  'Yayasan Nurul Iman',
  'Bina Insan Mandiri',
  'Yayasan Bakti Pertiwi',
  'Cendekia Muda'
]

const marqueeItems = [...yayasan, ...yayasan]
</script>

<template>
  <section class="relative overflow-hidden pb-24 pt-36">
    <!-- Background Image with premium blend overlays using standard CSS variable gradients -->
    <div class="absolute inset-0 z-0 overflow-hidden">
      <img
        :src="heroBg"
        alt="Hero Background"
        class="w-full h-full object-cover opacity-25 dark:opacity-30 pointer-events-none select-none scale-105 transition-opacity duration-700"
      />
      <!-- Fading vertical and radial overlays using standard CSS variables to ensure flawless compilation -->
      <div
        class="absolute inset-0"
        style="background: linear-gradient(to bottom, transparent 20%, var(--background) 95%)"
      ></div>
      <div
        class="absolute inset-0"
        style="
          background: radial-gradient(circle at center, transparent 20%, var(--background) 90%);
        "
      ></div>
    </div>

    <!-- Soft Ambient Glows -->
    <div class="blob -left-20 -top-20 h-105 w-105 bg-primary/30 dark:bg-primary/15" />
    <div class="blob right-0 top-40 h-95 w-95 bg-accent/30 dark:bg-accent/15" />
    <div class="blob bottom-0 left-1/3 h-80 w-80 bg-secondary/20" />

    <div class="relative mx-auto max-w-6xl px-4 sm:px-6 text-center">
      <div
        class="mx-auto inline-flex items-center gap-2 rounded-full bg-secondary/15 px-4 py-1.5 text-xs font-medium text-secondary-foreground ring-1 ring-secondary/20"
      >
        <Sparkles class="h-3.5 w-3.5 text-primary" />
        Platform ERP #1 untuk Yayasan Pendidikan
      </div>
      <h1
        class="mx-auto mt-6 max-w-4xl text-3xl font-bold leading-[1.2] sm:text-4xl tracking-tight text-foreground md:text-5xl md:leading-[1.1]"
      >
        Kelola seluruh sekolah yayasan Anda dari
        <span class="relative inline-block">
          <span class="relative z-10">satu dashboard</span>
          <span class="absolute inset-x-0 bottom-2 z-0 h-3 rounded-full bg-secondary/70" /></span
        >.
      </h1>
      <p class="mx-auto mt-6 max-w-2xl text-base sm:text-lg text-muted-foreground md:text-xl leading-relaxed">
        Gerbang Sekolah menyatukan akademik, keuangan, kesiswaan, dan komunikasi orang tua untuk semua
        unit sekolah di bawah yayasan Anda - modern, aman, dan mudah dipakai guru.
      </p>
 
      <!-- Premium Responsive Action Buttons -->
      <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3.5">
        <a
          href="#kontak"
          class="inline-flex items-center gap-2 rounded-full bg-primary px-6 py-3.5 text-sm font-semibold text-primary-foreground transition hover:opacity-90 shadow-lg shadow-primary/25 cursor-pointer w-full max-w-xs sm:max-w-none sm:w-auto justify-center"
        >
          Coba Demo Gratis
          <ArrowRight class="h-4 w-4" />
        </a>
 
        <button
          @click="triggerInstall"
          class="inline-flex items-center justify-center gap-2 rounded-full border border-border bg-background px-6 py-3.5 text-sm font-semibold text-foreground transition hover:bg-muted/50 cursor-pointer w-full max-w-xs sm:max-w-none sm:w-auto"
        >
          <Download class="h-4 w-4 text-primary animate-bounce" />
          Install Apps
        </button>
      </div>
    </div>
 
    <div class="mx-auto max-w-6xl px-4 sm:px-6 mt-12 md:mt-20 space-y-4">
        <p class="text-center text-xs uppercase tracking-[0.25em] text-muted-foreground">
          Dipercaya oleh yayasan di Indonesia
        </p>
        <div class="marquee-mask relative mt-6 overflow-hidden">
          <div class="animate-marquee flex w-max gap-10 whitespace-nowrap">
            <div
              v-for="(name, index) in marqueeItems"
              :key="`r-${index}`"
              class="flex shrink-0 items-center gap-2.5 text-foreground/70"
            >
              <span
                class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
              >
                <Building2 class="h-4 w-4" />
              </span>
              <span class="text-sm font-semibold tracking-tight">{{ name }}</span>
            </div>
          </div>
        </div>
        <div class="marquee-mask relative mt-3 overflow-hidden">
          <div class="animate-marquee-reverse flex w-max gap-10 whitespace-nowrap">
            <div
              v-for="(name, index) in marqueeItems"
              :key="`l-${index}`"
              class="flex shrink-0 items-center gap-2.5 text-foreground/70"
            >
              <span
                class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
              >
                <Building2 class="h-4 w-4" />
              </span>
              <span class="text-sm font-semibold tracking-tight">{{ name }}</span>
            </div>
          </div>
      </div>
    </div>
  </section>
</template>
