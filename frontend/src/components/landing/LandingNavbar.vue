<script setup>
import { ref } from 'vue'
import { School, Menu, X, Download } from 'lucide-vue-next'
import { usePwa } from '@/composables/usePwa'
import { currentLogo } from '@/config/logo'

const { triggerInstall } = usePwa()

defineProps({
  brandName: {
    type: String,
    default: 'Gerbang Sekolah'
  },
  loginPath: {
    type: String,
    default: '/login'
  },
  links: {
    type: Array,
    default: () => [
      { label: 'Fitur', href: '#fitur' },
      { label: 'Modul', href: '#modul' },
      { label: 'Harga', href: '#harga' },
      { label: 'Testimoni', href: '#testimoni' },
      { label: 'Hubungi Kami', href: '#kontak' }
    ]
  }
})

const isMobileMenuOpen = ref(false)

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const closeMobileMenu = () => {
  isMobileMenuOpen.value = false
}
</script>

<template>
  <header
    class="fixed top-0 left-0 right-0 z-50 w-full border-b border-border/40 bg-background/80 backdrop-blur-md transition-all duration-300 overflow-hidden"
  >
    <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
      <div class="flex h-14 sm:h-16 items-center justify-between gap-2">
        <!-- Logo -->
        <router-link
          to="/"
          class="flex items-center gap-2.5 sm:gap-3 min-w-0 shrink group py-1"
          @click="closeMobileMenu"
        >
          <img
            :src="currentLogo"
            alt="Gerbang Sekolah"
            class="h-9 sm:h-10 md:h-11 w-auto object-contain shrink-0 transition-transform duration-200 group-hover:scale-105"
          />
          <div
            class="flex flex-col gap-2 font-montserrat font-extrabold uppercase text-[#0071FF] dark:text-white select-none shrink-0"
          >
            <span class="text-xs sm:text-[16px] md:text-[22px] leading-none">GERBANG</span>
            <span
              class="text-xs sm:text-[16px] md:text-[22px] tracking-[0.04em] leading-none -mt-1 sm:-mt-1.5 md:-mt-2"
              >SEKOLAH</span
            >
          </div>
        </router-link>

        <!-- Desktop Navigation Links -->
        <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-muted-foreground">
          <a
            v-for="link in links"
            :key="link.label"
            :href="link.href"
            class="transition hover:text-foreground relative py-2"
          >
            {{ link.label }}
          </a>
        </nav>

        <!-- Actions -->
        <div class="flex items-center gap-2 shrink-0">
          <!-- PWA Install Button -->
          <button
            @click="triggerInstall"
            class="hidden sm:inline-flex items-center gap-1.5 rounded-full border border-border/80 px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-foreground hover:bg-muted/60 transition duration-200 cursor-pointer"
          >
            <Download class="h-3.5 w-3.5" />
            <span>Install Apps</span>
          </button>

          <!-- "Masuk" Button - Always visible -->
          <router-link
            :to="loginPath"
            class="rounded-full bg-primary px-4 sm:px-5 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-primary-foreground shadow-sm hover:opacity-90 transition duration-200 whitespace-nowrap"
            @click="closeMobileMenu"
          >
            Masuk
          </router-link>

          <!-- Mobile Toggle Button -->
          <button
            class="inline-flex md:hidden h-8 w-8 sm:h-9 sm:w-9 items-center justify-center rounded-lg border border-border/40 hover:bg-muted/50 transition focus:outline-none shrink-0"
            aria-label="Toggle Menu"
            @click="toggleMobileMenu"
          >
            <component
              :is="isMobileMenuOpen ? X : Menu"
              class="h-5 w-5 text-foreground"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="-translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="-translate-y-2 opacity-0"
    >
      <div
        v-if="isMobileMenuOpen"
        class="md:hidden border-t border-border/20 bg-background/95 backdrop-blur-lg px-4 py-3 shadow-lg"
      >
        <div class="space-y-1">
          <!-- PWA Install Mobile Action -->
          <button
            @click="
              () => {
                closeMobileMenu()
                triggerInstall()
              }
            "
            class="w-full flex items-center gap-2.5 rounded-lg px-3 py-2.5 text-base font-medium text-muted-foreground hover:bg-primary/5 hover:text-primary transition text-left cursor-pointer"
          >
            <Download class="h-5 w-5 text-primary" />
            <span>Install Apps</span>
          </button>

          <a
            v-for="link in links"
            :key="link.label"
            :href="link.href"
            class="block rounded-lg px-3 py-2.5 text-base font-medium text-muted-foreground hover:bg-primary/5 hover:text-primary transition"
            @click="closeMobileMenu"
          >
            {{ link.label }}
          </a>
        </div>
      </div>
    </transition>
  </header>
</template>
