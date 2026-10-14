<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { GraduationCap, Menu, X, LayoutDashboard, User, LogOut } from 'lucide-vue-next'
import { currentLogo } from '@/config/logo'

const props = defineProps({
  isScrolled: Boolean
})

const emit = defineEmits(['scrollTo'])

const auth = useAuthStore()
const router = useRouter()
const isMenuOpen = ref(false)

const scrollTo = id => {
  isMenuOpen.value = false
  emit('scrollTo', id)
}

const handleLogout = () => {
  auth.logout()
  router.push('/login')
}

const navLinks = [
  ['fitur', 'Fitur'],
  ['solusi', 'Solusi'],
  ['tentang', 'Tentang'],
  ['kontak', 'Kontak']
]
</script>

<template>
  <nav
    :class="[
      'fixed top-0 w-full z-50 transition-all duration-300',
      isScrolled ? 'bg-white shadow-md py-2' : 'bg-transparent py-4'
    ]"
  >
    <div class="max-w-7xl mx-auto px-4 sm:px-6 flex items-center justify-between">
      <!-- Logo -->
      <a
        href="#"
        @click.prevent="scrollTo('home')"
        class="flex items-center gap-2.5 sm:gap-3 group py-1"
      >
        <img
          :src="currentLogo"
          alt="Gerbang Sekolah"
          :class="[
            'h-7 sm:h-8 md:h-[34px] w-auto object-contain shrink-0 transition-transform duration-200 group-hover:scale-105',
            isScrolled ? '' : 'brightness-0 invert'
          ]"
        />
        <div
          :class="[
            'flex flex-col gap-1 font-montserrat font-extrabold uppercase select-none shrink-0 transition-colors',
            isScrolled ? 'text-[#0071FF] dark:text-white' : 'text-white'
          ]"
        >
          <span class="text-[11px] sm:text-[13px] md:text-[15px] leading-none">GERBANG</span>
          <span
            class="text-[11px] sm:text-[13px] md:text-[15px] tracking-[0.04em] leading-none -mt-0.5 sm:-mt-1"
            >SEKOLAH</span
          >
        </div>
      </a>

      <!-- Nav Links Desktop -->
      <div class="hidden md:flex items-center gap-6">
        <button
          v-for="[id, label] in navLinks"
          :key="id"
          @click="scrollTo(id)"
          :class="[
            'text-sm font-medium transition-colors hover:text-blue-400',
            isScrolled ? 'text-gray-600' : 'text-white/90'
          ]"
        >
          {{ label }}
        </button>
      </div>

      <!-- Auth Desktop -->
      <div class="hidden md:flex items-center gap-2">
        <template v-if="!auth.user">
          <button
            @click="router.push('/login')"
            class="px-5 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-all shadow-md"
          >
            Login
          </button>
        </template>
        <template v-else>
          <button
            @click="router.push('/dashboard')"
            class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-all"
          >
            <LayoutDashboard class="w-4 h-4" /> Dashboard
          </button>
          <span
            :class="[
              'flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium',
              isScrolled
                ? 'text-gray-700 bg-gray-50 border border-gray-200'
                : 'text-white  border border-white/20'
            ]"
          >
            <User class="w-4 h-4" /> {{ auth.user.name }}
          </span>
          <button
            @click="handleLogout"
            class="flex items-center gap-1 px-3 py-2 rounded-xl bg-red-500 text-white text-sm font-semibold hover:bg-red-600 transition-all"
          >
            <LogOut class="w-4 h-4" />
          </button>
        </template>
      </div>

      <!-- Hamburger -->
      <button
        @click="isMenuOpen = !isMenuOpen"
        class="md:hidden"
        :class="isScrolled ? 'text-gray-700' : 'text-white'"
      >
        <Menu
          v-if="!isMenuOpen"
          class="w-6 h-6"
        />
        <X
          v-else
          class="w-6 h-6"
        />
      </button>
    </div>

    <!-- Mobile Menu -->
    <div
      v-if="isMenuOpen"
      class="md:hidden bg-white border-t border-gray-100 px-4 py-4 flex flex-col gap-3 shadow-xl"
    >
      <button
        v-for="[id, label] in navLinks"
        :key="id"
        @click="scrollTo(id)"
        class="text-left text-gray-700 font-medium py-2 border-b border-gray-50 hover:text-blue-600 transition-colors"
      >
        {{ label }}
      </button>
      <template v-if="!auth.user">
        <button
          @click="router.push('/login')"
          class="mt-2 w-full py-2.5 rounded-xl bg-blue-600 text-white font-semibold text-center"
        >
          Login
        </button>
      </template>
      <template v-else>
        <button
          @click="router.push('/dashboard')"
          class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-blue-600 text-white font-semibold"
        >
          <LayoutDashboard class="w-4 h-4" /> Dashboard
        </button>
        <button
          @click="handleLogout"
          class="flex items-center justify-center gap-2 py-2.5 rounded-xl bg-red-500 text-white font-semibold"
        >
          <LogOut class="w-4 h-4" /> Logout
        </button>
      </template>
    </div>
  </nav>
</template>
