<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { GraduationCap, Eye, EyeOff } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useAuthStore } from '@/stores/authStore'
import { currentLogo } from '@/config/logo'

const auth = useAuthStore()
const router = useRouter()

// 🔥 Akun Demo Quick Fill (boleh dihapus)
const demoAccounts = [
  { role: 'Super Admin', email: 'superadmin@mail.com' },
  { role: 'Admin Yayasan', email: 'adminyayasan@mail.com' },
  { role: 'Kepala Sekolah', email: 'kepalasekolah@mail.com' },
  { role: 'Admin Sekolah', email: 'adminsekolah@mail.com' },
  { role: 'Tata Usaha', email: 'tatausaha@mail.com' },
  { role: 'Guru Pengajar', email: 'guru@mail.com' },
  { role: 'Wali Kelas', email: 'walikelas@mail.com' },
  { role: 'Siswa', email: 'siswa@mail.com' },
  { role: 'Orang Tua / Wali', email: 'orangtua@mail.com' }
]

const isDemoDropdownOpen = ref(false)
const selectedDemoRole = ref('')

const toggleDemoDropdown = () => {
  isDemoDropdownOpen.value = !isDemoDropdownOpen.value
}

const selectDemoAccount = acc => {
  email.value = acc.email
  password.value = '123456'
  selectedDemoRole.value = acc.role
  isDemoDropdownOpen.value = false
}

const email = ref('')
const password = ref('')
const showPassword = ref(false)
const isLoading = ref(false)
const error = ref('')
const isTransitioning = ref(!!window.pendingViewTransition)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const handleLogin = async () => {
  isLoading.value = true
  error.value = ''

  try {
    const user = await auth.login(email.value, password.value)
    if (user) {
      if (document.startViewTransition) {
        document.startViewTransition(async () => {
          await router.push('/dashboard')
          await nextTick()
        })
      } else {
        router.push('/dashboard')
      }
    } else {
      error.value = 'Email atau password salah.'
    }
  } catch (e) {
    error.value = e.message || 'Terjadi kesalahan saat login.'
  } finally {
    isLoading.value = false
  }
}

// Close dropdown if clicked outside
let handleOutsideClick = null

onMounted(async () => {
  // Jika ada transisi halaman yang sedang berjalan (logout/login), tunggu hingga selesai
  // agar perubahan tema global tidak membuat dashboard/halaman lama berkedip (glitch)
  if (window.pendingViewTransition) {
    try {
      await window.pendingViewTransition.finished
    } catch (e) {
      // Abaikan jika transisi diinterupsi
    }
  }

  // Terapkan color mode berdasarkan preferensi tersimpan (bukan paksa light mode)
  const savedTheme = localStorage.getItem('theme') || 'system'
  if (savedTheme === 'dark' || (savedTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }

  // Bersihkan class tema/background yang mungkin terbawa dari halaman lain
  document.body.classList.forEach(cls => {
    if (
      cls.startsWith('theme-') ||
      [
        'bg-animated',
        'bg-static_squares',
        'bg-glass',
        'bg-school',
        'bg-solid',
        'bg-modern_minimalist',
        'finish-solid'
      ].includes(cls)
    ) {
      document.body.classList.remove(cls)
    }
  })

  // Kunci tema Halaman Login agar selalu biru konsisten sesuai identitas logo
  document.body.classList.add('theme-blue')

  // Terapkan juga background style yang tersimpan
  const savedBgStyle = localStorage.getItem('backgroundStyle') || 'animated'
  document.body.classList.add(`bg-${savedBgStyle}`)

  handleOutsideClick = e => {
    const dropdownContainer = document.getElementById('demo-dropdown-container')
    if (dropdownContainer && !dropdownContainer.contains(e.target)) {
      isDemoDropdownOpen.value = false
    }
  }
  document.addEventListener('click', handleOutsideClick)
})

onUnmounted(() => {
  if (handleOutsideClick) {
    document.removeEventListener('click', handleOutsideClick)
  }
})
</script>

<template>
  <div
    class="grid min-h-screen w-full overflow-x-hidden lg:grid-cols-[450px_1fr] xl:grid-cols-[500px_1fr]"
  >
    <!-- Left panel (Glassmorphism on mobile/tablet, clean minimal on desktop) -->
    <div
      style="view-transition-name: login-left"
      :class="[
        'flex flex-col gap-4 p-4 sm:p-6 md:p-10 bg-background relative min-w-0 overflow-hidden justify-between',
        !isTransitioning ? 'login-left-entrance' : ''
      ]"
    >
      <!-- Mobile/Tablet Background Image Overlay -->
      <div class="absolute inset-0 lg:hidden overflow-hidden z-0">
        <img
          src="@/assets/images/login-bg.png"
          alt="Suasana Sekolah Indonesia"
          loading="eager"
          decoding="sync"
          class="absolute inset-0 h-full w-full object-cover"
        />
        <div class="absolute inset-0 bg-primary/80 backdrop-blur-[3px] mix-blend-multiply"></div>
      </div>

      <!-- Header Logo (Centered in the space between top of screen and Sistem Informasi Sekolah, with equal top/bottom gap) -->
      <div class="flex-[1.4] flex flex-col items-center justify-center text-center z-10 w-full min-h-[180px] py-4">
        <router-link
          to="/"
          class="flex flex-col items-center justify-center text-center group py-1"
        >
          <div class="inline-flex flex-col items-center justify-center text-center">
            <img
              :src="currentLogo"
              alt="Gerbang Sekolah"
              class="w-[72px] sm:w-[84px] md:w-[98px] h-auto object-contain shrink-0 transition-transform duration-300 group-hover:scale-105 max-lg:brightness-0 max-lg:invert mb-2 sm:mb-2.5 drop-shadow-sm"
            />
            <div
              class="flex flex-col items-center gap-2 font-montserrat font-extrabold uppercase text-[#0071FF] dark:text-white max-lg:text-white select-none text-center shrink-0"
            >
              <span class="text-xs sm:text-[15px] md:text-[20px] leading-none">GERBANG</span>
              <span
                class="text-xs sm:text-[15px] md:text-[20px] tracking-[0.04em] leading-none -mt-1 sm:-mt-1.5 md:-mt-2"
                >SEKOLAH</span
              >
            </div>
          </div>
        </router-link>
      </div>

      <!-- Main Login Card / Form Container -->
      <div class="flex-none w-full max-w-sm mx-auto z-20 py-1">
        <div
          class="w-full max-w-sm p-6 sm:p-8 rounded-2xl border border-white/10 bg-white/10 backdrop-blur-md shadow-2xl lg:border-none lg:bg-transparent lg:backdrop-blur-none lg:shadow-none lg:p-0 transition-all duration-300"
        >
          <form
            @submit.prevent="handleLogin"
            class="flex flex-col gap-5 sm:gap-6"
          >
            <div class="flex flex-col items-center gap-2 text-center px-1 sm:px-2">
              <h1
                class="text-xl sm:text-2xl font-bold tracking-tight text-white lg:text-foreground"
              >
                Sistem Informasi Sekolah
              </h1>
              <p class="text-primary-foreground/80 lg:text-muted-foreground text-xs sm:text-sm">
                Silakan masukkan akun Anda untuk mengelola data sekolah.
              </p>
            </div>

            <div class="grid gap-3.5 sm:gap-4">
              <div class="grid gap-1.5 text-left">
                <label
                  for="email"
                  class="text-xs sm:text-sm font-semibold text-white lg:text-foreground/90"
                >
                  Email
                </label>
                <Input
                  id="email"
                  type="email"
                  v-model="email"
                  placeholder="Masukkan Email Anda"
                  required
                  class="h-10 px-3 text-sm rounded-lg border-input bg-white/95 text-slate-900 lg:bg-background lg:text-foreground shadow-xs focus-visible:ring-2 focus-visible:ring-[#0071FF]/30 focus-visible:border-[#0071FF] transition-all"
                />
              </div>

              <div class="grid gap-1.5 text-left">
                <div class="flex items-center justify-between">
                  <label
                    for="password"
                    class="text-xs sm:text-sm font-semibold text-white lg:text-foreground/90"
                  >
                    Password
                  </label>
                  <a
                    href="#"
                    class="text-xs font-semibold text-[#0071FF] hover:underline shrink-0 max-lg:text-white/90"
                  >
                    Lupa sandi?
                  </a>
                </div>
                <div class="relative">
                  <Input
                    id="password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Masukkan Password Anda"
                    v-model="password"
                    required
                    class="h-10 pl-3 pr-10 text-sm rounded-lg border-input bg-white/95 text-slate-900 lg:bg-background lg:text-foreground shadow-xs focus-visible:ring-2 focus-visible:ring-[#0071FF]/30 focus-visible:border-[#0071FF] transition-all"
                  />
                  <button
                    type="button"
                    @click="togglePassword"
                    class="absolute cursor-pointer inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-800 lg:text-muted-foreground lg:hover:text-foreground transition-colors shrink-0"
                  >
                    <Eye
                      v-if="!showPassword"
                      class="size-4"
                    />
                    <EyeOff
                      v-else
                      class="size-4"
                    />
                  </button>
                </div>
              </div>

              <!-- Akun Demo Quick Fill -->
              <div
                id="demo-dropdown-container"
                class="grid gap-1.5 text-left relative"
              >
                <label
                  class="text-xs sm:text-sm font-semibold text-white lg:text-foreground/90"
                >
                  Uji Coba dengan Akun Demo
                </label>
                <div class="relative">
                  <button
                    type="button"
                    @click="toggleDemoDropdown"
                    class="flex h-10 w-full items-center justify-between rounded-lg border border-input bg-white/95 text-slate-900 px-3 text-sm shadow-xs focus:outline-none focus:ring-2 focus:ring-[#0071FF]/30 focus:border-[#0071FF] disabled:cursor-not-allowed disabled:opacity-50 lg:border-input lg:bg-background lg:text-foreground transition-all cursor-pointer"
                  >
                    <span class="truncate font-medium">{{
                      selectedDemoRole || '-- Pilih Akun Demo --'
                    }}</span>
                    <span class="text-slate-500 pointer-events-none ml-2 text-xs">▼</span>
                  </button>

                  <!-- Custom Dropdown Options Menu -->
                  <div
                    v-if="isDemoDropdownOpen"
                    class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200/80 bg-white shadow-2xl ring-1 ring-black ring-opacity-5 focus:outline-none"
                  >
                    <div class="py-1">
                      <button
                        type="button"
                        v-for="acc in demoAccounts"
                        :key="acc.email"
                        @click="selectDemoAccount(acc)"
                        class="flex w-full items-center px-4 py-2 text-sm text-slate-700 hover:bg-[#0071FF]/10 hover:text-[#0071FF] font-semibold transition-colors text-left cursor-pointer border-b border-slate-100 last:border-0"
                      >
                        {{ acc.role }}
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <p
                v-if="error"
                class="text-sm text-destructive lg:text-destructive font-medium break-words bg-destructive/10 px-3 py-1.5 rounded-lg"
              >
                {{ error }}
              </p>

              <Button
                type="submit"
                class="w-full h-10 text-sm font-semibold mt-1 !bg-[#0071FF] hover:!bg-[#0060db] active:!bg-[#0052bd] !text-white rounded-lg shadow-md shadow-[#0071FF]/25 transition-all [&::after]:!hidden [&::before]:!hidden !border-none cursor-pointer"
                :disabled="isLoading"
              >
                {{ isLoading ? 'Memproses...' : 'Masuk Sekarang' }}
              </Button>
            </div>
          </form>
        </div>
      </div>

      <!-- Footer Copyright -->
      <div
        class="flex-[0.7] flex items-end justify-center text-center text-[10px] sm:text-xs text-primary-foreground/70 lg:text-muted-foreground pb-4 px-4 z-10 min-h-[36px]"
      >
        &copy; {{ new Date().getFullYear() }} Gerbang Sekolah. Sistem Manajemen Sekolah Terintegrasi.
      </div>
    </div>

    <!-- Right panel for laptop/desktop screen (hidden on mobile/tablet) -->
    <div
      style="view-transition-name: login-right"
      :class="[
        'bg-muted relative hidden lg:block border-l overflow-hidden min-w-0',
        !isTransitioning ? 'login-right-entrance' : ''
      ]"
    >
      <!-- Menggunakan gambar sekolah Indonesia yang hangat dan natural -->
      <img
        src="@/assets/images/login-bg.png"
        alt="Suasana Sekolah Indonesia"
        loading="eager"
        decoding="sync"
        class="absolute inset-0 h-full w-full object-cover"
      />
      <!-- Gradasi Hitam (Kuat di paling bawah, memudar halus ke atas melintasi baris teks, tidak over) -->
      <div
        class="absolute inset-x-0 bottom-0 h-[52%] pointer-events-none"
        style="background: linear-gradient(to top, rgba(0, 0, 0, 0.96) 0%, rgba(0, 0, 0, 0.82) 20%, rgba(0, 0, 0, 0.48) 50%, rgba(0, 0, 0, 0.15) 78%, transparent 100%);"
      ></div>

      <div class="absolute inset-0 flex items-end p-8 xl:p-12 z-10 pointer-events-none">
        <div class="text-white max-w-lg min-w-0">
          <h2 class="text-3xl xl:text-4xl font-bold leading-tight drop-shadow-md">
            Membangun Masa Depan Melalui Teknologi
          </h2>
          <p class="mt-4 text-lg xl:text-xl text-white/90 drop-shadow-sm">
            Platform manajemen sekolah modern yang dirancang khusus untuk pendidikan di Indonesia.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
