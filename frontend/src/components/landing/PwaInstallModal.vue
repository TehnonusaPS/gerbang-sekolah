<script setup>
import { computed } from 'vue'
import { usePwa } from '@/composables/usePwa'
import { X, Download, Monitor, Smartphone, CheckCircle, Info } from 'lucide-vue-next'

const { showInstallModal, installProgress, installState } = usePwa()

const statusText = computed(() => {
  const progress = installProgress.value
  if (progress < 25) return 'Menghubungkan ke server Gerbang Sekolah...'
  if (progress < 55) return 'Mengunduh paket data PWA offline...'
  if (progress < 80) return 'Mengonfigurasi cache & basis data lokal...'
  if (progress < 100) return 'Menyelesaikan pemasangan ikon aplikasi...'
  return 'Aplikasi Berhasil Dipasang!'
})

const isIos = computed(() => {
  return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
})

const isSafari = computed(() => {
  return /^((?!chrome|android).)*safari/i.test(navigator.userAgent)
})

const isAndroid = computed(() => {
  return /Android/i.test(navigator.userAgent)
})

const isDesktop = computed(() => {
  return !isIos.value && !isAndroid.value
})

const closeModal = () => {
  showInstallModal.value = false
}
</script>

<template>
  <transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div
      v-if="showInstallModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-md"
    >
      <div 
        class="relative w-full max-w-md rounded-3xl bg-card border border-border shadow-2xl p-6 overflow-hidden"
      >
        <!-- Close button (only active when completed or idle) -->
        <button
          @click="closeModal"
          class="absolute top-4 right-4 p-1.5 rounded-full hover:bg-muted text-muted-foreground transition"
          v-if="installState !== 'installing'"
        >
          <X class="h-5 w-5" />
        </button>

        <div class="text-center py-4">
          <!-- ICON HEADER -->
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary mb-4 animate-bounce">
            <Download class="h-7 w-7" />
          </div>

          <h3 class="text-lg font-bold text-foreground">
            {{ installState === 'installing' ? 'Memasang Aplikasi' : 'Pemasangan Selesai' }}
          </h3>
          <p class="text-xs text-muted-foreground mt-1 px-4">
            Gerbang Sekolah kini dapat diakses langsung dari home screen komputer atau HP Anda.
          </p>

          <!-- INSTALLING STATE -->
          <div v-if="installState === 'installing'" class="mt-6 space-y-4">
            <!-- Progress Bar -->
            <div class="relative w-full h-3 bg-muted rounded-full overflow-hidden">
              <div 
                class="absolute left-0 top-0 h-full bg-primary rounded-full transition-all duration-300 ease-out"
                :style="{ width: `${installProgress}%` }"
              ></div>
            </div>
            
            <div class="flex justify-between items-center text-xs px-1">
              <span class="text-muted-foreground font-medium animate-pulse">{{ statusText }}</span>
              <span class="font-bold text-primary">{{ installProgress }}%</span>
            </div>
          </div>

          <!-- COMPLETED STATE -->
          <div v-else-if="installState === 'completed'" class="mt-6 space-y-5">
            <div class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-left">
              <div class="flex gap-3">
                <CheckCircle class="h-5 w-5 text-emerald-500 shrink-0 mt-0.5" />
                <div class="min-w-0">
                  <h4 class="text-sm font-bold text-emerald-600 dark:text-emerald-500">PWA Sukses Didaftarkan</h4>
                  <p class="text-xs text-muted-foreground mt-1 leading-relaxed">
                    Aplikasi terdaftar dengan baik. Jika ikon belum otomatis muncul di homescreen Anda, silakan ikuti petunjuk manual di bawah ini.
                  </p>
                </div>
              </div>
            </div>

            <!-- iOS Safari Install Tutorial Helper -->
            <div v-if="isIos || isSafari" class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-left text-xs text-muted-foreground space-y-2">
              <div class="flex gap-2 text-amber-600 dark:text-amber-500 font-bold items-center mb-1">
                <Info class="h-4 w-4" />
                <span>Panduan Instalasi iOS (Safari)</span>
              </div>
              <p>Karena batasan sistem iOS, silakan ikuti langkah manual berikut:</p>
              <ol class="list-decimal pl-4 space-y-1.5">
                <li>Ketuk tombol <strong>Bagikan (Share)</strong> di bagian bawah Safari.</li>
                <li>Gulir ke bawah dan pilih <strong>Tambahkan ke Layar Utama (Add to Home Screen)</strong>.</li>
                <li>Ketuk <strong>Tambah (Add)</strong> di pojok kanan atas.</li>
              </ol>
            </div>

            <!-- Android Chrome/Firefox Tutorial Helper -->
            <div v-else-if="isAndroid" class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-left text-xs text-muted-foreground space-y-2">
              <div class="flex gap-2 text-amber-600 dark:text-amber-500 font-bold items-center mb-1">
                <Info class="h-4 w-4" />
                <span>Panduan Instalasi Android (Chrome/Firefox)</span>
              </div>
              <p>Jika dialog otomatis tidak muncul, ikuti langkah berikut:</p>
              <ol class="list-decimal pl-4 space-y-1.5">
                <li>Ketuk ikon <strong>tiga titik (⋮)</strong> di pojok kanan atas browser Anda.</li>
                <li>Pilih menu <strong>Instal Aplikasi</strong> atau <strong>Tambahkan ke Layar Utama</strong>.</li>
                <li>Pilih <strong>Tambah / Instal</strong> pada dialog konfirmasi.</li>
              </ol>
            </div>

            <!-- Desktop Chrome/Edge/Safari Tutorial Helper -->
            <div v-else-if="isDesktop" class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-left text-xs text-muted-foreground space-y-2">
              <div class="flex gap-2 text-amber-600 dark:text-amber-500 font-bold items-center mb-1">
                <Info class="h-4 w-4" />
                <span>Panduan Instalasi Komputer (Desktop)</span>
              </div>
              <p>Untuk memunculkan ikon aplikasi di Desktop / Layar Utama Komputer:</p>
              <ol class="list-decimal pl-4 space-y-1.5">
                <li>Lihat ke sebelah kanan bilah alamat (URL bar) di atas browser Anda.</li>
                <li>Klik ikon <strong>Instal (layar monitor dengan panah bawah)</strong> yang berada di sebelah kanan sebelum tombol bintang bookmark.</li>
                <li>Klik tombol <strong>Instal</strong> pada dialog konfirmasi yang muncul.</li>
              </ol>
            </div>

            <button
              @click="closeModal"
              class="w-full py-3 px-4 rounded-xl bg-primary text-primary-foreground font-bold hover:opacity-95 transition text-sm"
            >
              Selesai & Buka Beranda
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>
