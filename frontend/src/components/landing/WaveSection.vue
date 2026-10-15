<script setup>
import s1 from '@/assets/images/students-1.jpg'
import s2 from '@/assets/images/students-2.jpg'
import s3 from '@/assets/images/students-3.jpg'
import s4 from '@/assets/images/students-4.jpg'
import s5 from '@/assets/images/students-5.jpg'
import { Sparkles } from 'lucide-vue-next'

const slides = [
  { src: s1, label: 'Belajar Bersama' },
  { src: s2, label: 'Literasi Aktif' },
  { src: s3, label: 'Kolaborasi Digital' },
  { src: s4, label: 'Tumbuh Ceria' },
  { src: s5, label: 'Antusias Berkarya' }
]

const items = [...slides, ...slides]

const yellowList = [
  'PPDB online tanpa antre',
  'Rapor digital instan',
  'SPP otomatis & transparan',
  'Notifikasi orang tua real-time',
  'Dashboard yayasan multi-sekolah'
]

const waveClip =
  'polygon(0% 15%, 4% 5%, 8% 15%, 12% 5%, 16% 15%, 20% 5%, 24% 15%, 28% 5%, 32% 15%, 36% 5%, 40% 15%, 44% 5%, 48% 15%, 52% 5%, 56% 15%, 60% 5%, 64% 15%, 68% 5%, 72% 15%, 76% 5%, 80% 15%, 84% 5%, 88% 15%, 92% 5%, 96% 15%, 100% 5%, 100% 85%, 96% 95%, 92% 85%, 88% 95%, 84% 85%, 80% 95%, 76% 85%, 72% 95%, 68% 85%, 64% 95%, 60% 85%, 56% 95%, 52% 85%, 48% 95%, 44% 85%, 40% 95%, 36% 85%, 32% 95%, 28% 85%, 24% 95%, 20% 85%, 16% 95%, 12% 85%, 8% 95%, 4% 85%, 0% 95%)'
</script>

<template>
  <section class="relative">
    <svg
      viewBox="0 0 1440 80"
      preserveAspectRatio="none"
      class="block h-12 w-full"
      aria-hidden="true"
    >
      <path
        d="M0,40 C120,80 240,0 360,40 C480,80 600,0 720,40 C840,80 960,0 1080,40 C1200,80 1320,0 1440,40 L1440,80 L0,80 Z"
        fill="oklch(0.92 0.13 95)"
      />
    </svg>

    <div class="bg-[oklch(0.92_0.13_95)] pb-20 pt-10">
      <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid items-center gap-10 lg:grid-cols-2">
          <div class="min-w-0">
            <span
              class="inline-flex items-center gap-2 rounded-full bg-foreground/10 px-3 py-1 text-xs font-semibold text-foreground"
            >
              <Sparkles class="h-3.5 w-3.5" /> Untuk siswa & guru
            </span>
            <h2
              class="mt-4 text-2xl sm:text-4xl font-bold tracking-tight text-foreground md:text-5xl"
            >
              Pengalaman sekolah yang menyenangkan, dari hari pertama.
            </h2>
            <p class="mt-4 text-sm sm:text-base text-foreground/70">
              Gerbang Sekolah dirancang agar guru fokus mengajar dan orang tua tenang memantau, sementara
              yayasan punya kontrol penuh.
            </p>

            <ul class="mt-7 space-y-4">
              <li
                v-for="(item, index) in yellowList"
                :key="item"
                class="relative flex items-center gap-3 sm:gap-4 bg-background/70 px-4 sm:px-6 py-4 sm:py-5 text-foreground shadow-sm"
                :style="{ clipPath: waveClip }"
              >
                <span
                  class="flex h-7 w-7 sm:h-8 sm:w-8 shrink-0 items-center justify-center rounded-full bg-primary text-xs sm:text-sm font-bold text-primary-foreground"
                >
                  {{ index + 1 }}
                </span>
                <span class="font-medium text-sm sm:text-base">{{ item }}</span>
              </li>
            </ul>
          </div>

          <div class="relative min-w-0">
            <svg
              width="0"
              height="0"
              class="absolute"
            >
              <defs>
                <clipPath
                  id="waveCard"
                  clipPathUnits="objectBoundingBox"
                >
                  <path
                    d="M0,0.08 C0.1,0.0 0.25,0.16 0.4,0.06 C0.55,-0.04 0.75,0.14 1,0.04 L1,0.92 C0.88,1.02 0.7,0.86 0.55,0.96 C0.35,1.06 0.18,0.88 0,0.96 Z"
                  />
                </clipPath>
              </defs>
            </svg>

            <div class="relative overflow-hidden rounded-3xl bg-foreground/10 p-3">
              <div class="marquee-mask overflow-hidden">
                <div class="animate-carousel flex w-max gap-5">
                  <figure
                    v-for="(slide, index) in items"
                    :key="index"
                    class="relative h-[320px] w-[260px] shrink-0"
                  >
                    <div
                      class="absolute inset-0 bg-primary"
                      :style="{ clipPath: 'url(#waveCard)' }"
                    />
                    <div
                      class="absolute inset-[6px] overflow-hidden"
                      :style="{ clipPath: 'url(#waveCard)' }"
                    >
                      <img
                        :src="slide.src"
                        :alt="slide.label"
                        loading="lazy"
                        width="800"
                        height="800"
                        class="h-full w-full object-cover"
                      />
                    </div>
                    <figcaption
                      class="absolute inset-x-4 bottom-6 z-10 rounded-full bg-background/85 px-3 py-1.5 text-center text-xs font-semibold text-foreground"
                    >
                      {{ slide.label }}
                    </figcaption>
                  </figure>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="-mt-px">
      <svg
        viewBox="0 0 1440 80"
        preserveAspectRatio="none"
        class="block h-12 w-full"
        aria-hidden="true"
      >
        <path
          d="M0,40 C120,80 240,0 360,40 C480,80 600,0 720,40 C840,80 960,0 1080,40 C1200,80 1320,0 1440,40 L1440,0 L0,0 Z"
          fill="oklch(0.92 0.13 95)"
        />
      </svg>
    </div>
  </section>
</template>
