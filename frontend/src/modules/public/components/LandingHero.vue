<script setup>
import { useRouter } from 'vue-router'
import { Star, ChevronDown, ArrowRight } from 'lucide-vue-next'
import heroImg from '@/assets/images/landing-hero.png'

const emit = defineEmits(['scrollTo'])
const router = useRouter()

const scrollTo = id => emit('scrollTo', id)
</script>

<template>
  <section
    id="home"
    class="relative min-h-screen flex items-center overflow-hidden"
  >
    <!-- Background Image -->
    <div class="absolute inset-0 z-0">
      <img
        :src="heroImg"
        alt="Sekolah Indonesia"
        class="w-full h-full object-cover"
      />
      <div
        class="absolute inset-0 bg-gradient-to-r from-blue-950/90 via-blue-900/75 to-blue-700/40"
      ></div>
    </div>

    <!-- Content -->
    <div class="relative z-10 max-w-7xl mx-auto px-6 py-36 lg:py-44">
      <div class="max-w-2xl">
        <div
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-white/20 text-white text-sm font-medium mb-6"
        >
          <Star class="w-4 h-4 text-yellow-400" />
          Platform Manajemen Yayasan & Unit Sekolah #1 di Indonesia
        </div>

        <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white leading-tight mb-6">
          Satu Yayasan,<br />
          <span class="text-yellow-400">Semua Sekolah Terkelola</span>
        </h1>

        <p class="text-lg text-blue-100 mb-8 leading-relaxed max-w-xl">
          Gerbang Sekolah adalah platform manajemen pendidikan untuk yayasan. Daftarkan yayasan Anda,
          tambahkan unit sekolah sebanyak apapun, dan kelola seluruh operasional dari satu dasbor
          terpusat.
        </p>

        <div class="flex flex-wrap gap-3">
          <button
            @click="scrollTo('fitur')"
            class="flex items-center gap-2 px-7 py-3.5 rounded-xl bg-yellow-400 text-blue-900 font-bold text-sm hover:bg-yellow-300 transition-all shadow-lg"
          >
            Lihat Fitur Lengkap <ArrowRight class="w-4 h-4" />
          </button>
          <button
            @click="scrollTo('kontak')"
            class="px-7 py-3.5 rounded-xl text-white border border-white/30 font-semibold text-sm hover: transition-all"
          >
            Jadwalkan Demo
          </button>
        </div>
      </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-10 text-white animate-bounce">
      <ChevronDown class="w-7 h-7 opacity-70" />
    </div>
  </section>
</template>
