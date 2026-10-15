<script setup>
import { School, MapPin, Phone, Mail, ChevronRight } from 'lucide-vue-next'
import { currentLogo } from '@/config/logo'

defineProps({
  brandName: {
    type: String,
    default: 'Gerbang Sekolah'
  },
  year: {
    type: [String, Number],
    default: new Date().getFullYear()
  }
})
</script>

<template>
  <footer
    id="kontak"
    class="relative overflow-hidden bg-slate-950 pt-20 pb-8"
  >
    <!-- Decorative Background Elements -->
    <div
      class="absolute inset-0 bg-[size:24px_24px]"
      :style="{
        backgroundImage:
          'linear-gradient(to right, color-mix(in oklab, var(--primary) 6%, transparent) 1px, transparent 1px), linear-gradient(to bottom, color-mix(in oklab, var(--primary) 6%, transparent) 1px, transparent 1px)'
      }"
    ></div>
    <div
      class="absolute left-0 top-0 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-primary/10 rounded-full blur-[100px]"
    ></div>
    <div
      class="absolute right-0 bottom-0 translate-x-1/3 translate-y-1/3 w-96 h-96 bg-primary/5 rounded-full blur-[100px]"
    ></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
        <!-- Kolom 1: Brand & Deskripsi -->
        <div class="space-y-6">
          <div class="flex items-center gap-2.5 sm:gap-3 group py-1">
            <img
              :src="currentLogo"
              alt="Gerbang Sekolah"
              class="h-9 sm:h-10 md:h-11 w-auto object-contain shrink-0 transition-transform duration-200 group-hover:scale-105"
            />
            <div
              class="flex flex-col gap-2 font-montserrat font-extrabold uppercase text-[#0071FF] dark:text-white select-none shrink-0"
            >
              <span class="text-xs text-white sm:text-[16px] md:text-[22px] leading-none"
                >GERBANG</span
              >
              <span
                class="text-xs text-white sm:text-[16px] md:text-[22px] tracking-[0.04em] leading-none -mt-1 sm:-mt-1.5 md:-mt-2"
                >SEKOLAH</span
              >
            </div>
          </div>
          <p class="text-slate-400 text-sm leading-relaxed max-w-sm font-medium">
            Platform manajemen sekolah modern yang mengintegrasikan seluruh kegiatan akademik,
            administrasi, dan komunikasi dalam satu pintu secara profesional.
          </p>
        </div>

        <!-- Kolom 2: Tautan Cepat -->
        <div>
          <h3 class="text-white font-bold mb-8 uppercase tracking-widest text-sm">Tautan Cepat</h3>
          <ul class="space-y-4">
            <li
              v-for="(link, index) in [
                { label: 'Fitur', href: '#fitur' },
                { label: 'Modul', href: '#modul' },
                { label: 'Harga', href: '#harga' },
                { label: 'Testimoni', href: '#testimoni' },
                { label: 'Hubungi Kami', href: '#kontak' }
              ]"
              :key="index"
            >
              <a
                :href="link.href"
                class="text-slate-400 hover:text-primary transition-colors text-sm font-medium flex items-center gap-2 group"
              >
                <ChevronRight
                  class="w-4 h-4 text-primary group-hover:text-white group-hover:translate-x-1 transition-all"
                />
                {{ link.label }}
              </a>
            </li>
          </ul>
        </div>

        <!-- Kolom 3: Layanan / Modul Utama -->
        <div>
          <h3 class="text-white font-bold mb-8 uppercase tracking-widest text-sm">Modul Utama</h3>
          <ul class="space-y-4">
            <li
              v-for="(modul, index) in [
                'Sistem Akademik',
                'Manajemen Keuangan',
                'Penerimaan Siswa',
                'Portal Pegawai'
              ]"
              :key="index"
              class="text-slate-400 text-sm font-medium flex items-center gap-2"
            >
              <ChevronRight class="w-4 h-4 text-primary/70" />
              {{ modul }}
            </li>
          </ul>
        </div>

        <!-- Kolom 4: Hubungi Kami -->
        <div>
          <h3 class="text-white font-bold mb-8 uppercase tracking-widest text-sm">Hubungi Kami</h3>
          <ul class="space-y-5">
            <li class="flex items-start gap-3 group">
              <div
                class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center shrink-0 group-hover:bg-primary/10 group-hover:border-primary/30 transition-colors"
              >
                <MapPin class="w-4 h-4 text-primary" />
              </div>
              <p class="text-slate-400 text-[13px] font-medium leading-relaxed">
                Jl. Pendidikan No. 123<br />Jakarta Selatan, Indonesia
              </p>
            </li>
            <li class="flex items-center gap-3 group">
              <div
                class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center shrink-0 group-hover:bg-primary/10 group-hover:border-primary/30 transition-colors"
              >
                <Phone class="w-4 h-4 text-primary" />
              </div>
              <a
                href="tel:02112345678"
                class="text-slate-400 hover:text-primary transition-colors text-[13px] font-medium"
                >021-1234 5678</a
              >
            </li>
            <li class="flex items-center gap-3 group">
              <div
                class="w-8 h-8 rounded-lg bg-slate-900 border border-slate-800 flex items-center justify-center shrink-0 group-hover:bg-primary/10 group-hover:border-primary/30 transition-colors"
              >
                <Mail class="w-4 h-4 text-primary" />
              </div>
              <a
                href="mailto:halo@sekolahku.id"
                class="text-slate-400 hover:text-primary transition-colors text-[13px] font-medium"
                >halo@sekolahku.id</a
              >
            </li>
          </ul>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div
        class="pt-8 border-t border-slate-900 text-slate-500 text-xs flex flex-col md:flex-row justify-between items-center gap-4"
      >
        <p>
          &copy; {{ year }} <span class="text-primary font-medium">{{ brandName }}</span
          >. Hak Cipta Dilindungi.
        </p>
        <div class="flex gap-6">
          <a
            href="#"
            class="hover:text-primary transition-colors"
            >Kebijakan Privasi</a
          >
          <a
            href="#"
            class="hover:text-primary transition-colors"
            >Syarat & Ketentuan</a
          >
        </div>
      </div>
    </div>
  </footer>
</template>
