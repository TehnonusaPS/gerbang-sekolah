<script setup>
import { CheckCircle } from 'lucide-vue-next'
import classroomImg from '@/assets/images/landing-classroom.png'

const whyUs = [
  'Yayasan mendaftar, lalu tambahkan unit sekolah sebanyak apapun',
  'Setiap unit sekolah punya data & laporan tersendiri',
  'Manajemen hak akses per unit sekolah berbasis peran (RBAC)',
  'Laporan otomatis per sekolah & rekap yayasan siap cetak',
  'Aman dan terenkripsi — data tiap sekolah terlindungi',
  'Dukungan teknis responsif untuk semua yayasan mitra'
]
</script>

<template>
  <section
    id="tentang"
    class="py-20 bg-gradient-to-br from-blue-700 to-blue-950 text-white"
  >
    <div class="max-w-7xl mx-auto px-6">
      <div class="grid lg:grid-cols-2 gap-14 items-center">
        <!-- Image -->
        <div class="rounded-3xl overflow-hidden shadow-2xl shadow-blue-950/50 order-2 lg:order-1">
          <img
            :src="classroomImg"
            alt="Suasana belajar di sekolah mitra"
            class="w-full h-80 object-cover"
          />
        </div>

        <!-- Text -->
        <div class="order-1 lg:order-2">
          <span
            class="inline-block px-3 py-1 rounded-full bg-white/15 text-blue-100 text-sm font-semibold mb-4"
          >
            Mengapa Gerbang Sekolah?
          </span>
          <h2 class="text-3xl md:text-4xl font-extrabold mb-8 leading-tight">
            Teknologi yang <span class="text-yellow-400">Memudahkan</span><br />Bukan Menyulitkan
          </h2>
          <ul class="space-y-3">
            <li
              v-for="item in whyUs"
              :key="item"
              class="flex items-start gap-3 text-sm text-blue-100"
            >
              <CheckCircle class="w-5 h-5 text-yellow-400 shrink-0 mt-0.5" />
              {{ item }}
            </li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</template>
