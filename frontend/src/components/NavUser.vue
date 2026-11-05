<script setup>
import {
  BadgeCheck,
  Bell,
  ChevronsUpDown,
  LogOut,
  Palette,
  Layers,
  Sparkles
} from 'lucide-vue-next'
import { onMounted, ref, computed, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { toast } from 'vue-sonner'

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar
} from '@/components/ui/sidebar'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle
} from '@/components/ui/alert-dialog'

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

const userInitials = computed(() => {
  const name = props.user?.name || ''
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return (name.slice(0, 2) || 'GS').toUpperCase()
})

const { isMobile } = useSidebar()
const auth = useAuthStore()
const router = useRouter()
const showLogoutDialog = ref(false)

const confirmLogout = async () => {
  // 1. Aktifkan status logging out untuk memicu transisi keluar sidebar & topbar (CSS)
  auth.isLoggingOut = true

  // 2. Berikan jeda 600ms agar animasi keluar sidebar & topbar selesai bergerak
  await new Promise(resolve => setTimeout(resolve, 600))

  // 3. Jalankan transisi tirai menutup kembali ke login
  if (document.startViewTransition) {
    document.documentElement.classList.add('transition-logout')
    const transition = document.startViewTransition(async () => {
      auth.logout()
      await router.push('/login')
      await nextTick()
    })
    window.pendingViewTransition = transition
    transition.finished.finally(() => {
      document.documentElement.classList.remove('transition-logout')
      auth.isLoggingOut = false // Reset status
      if (window.pendingViewTransition === transition) {
        window.pendingViewTransition = null
      }
    })
  } else {
    auth.logout()
    router.push('/login')
    auth.isLoggingOut = false // Reset status
  }
}

// Nama tema: 'blue' | 'emerald' | 'indigo' | 'bronze' | 'navy' | 'zinc'
const activeThemeStyle = ref('blue')

/** Hapus semua class theme-* dari body */
const clearThemeClasses = () => {
  document.body.classList.forEach(cls => {
    if (cls.startsWith('theme-')) {
      document.body.classList.remove(cls)
    }
  })
}

/** Terapkan class tema ke body */
const applyThemeClass = styleName => {
  clearThemeClasses()
  document.body.classList.add(`theme-${styleName}`)
}

const setThemeStyle = styleName => {
  activeThemeStyle.value = styleName
  applyThemeClass(styleName)
  localStorage.setItem('themeStyle', styleName)
}

const cycleThemeStyle = () => {
  const themes = ['blue', 'emerald', 'indigo', 'bronze', 'navy', 'zinc']
  const currentIndex = themes.indexOf(activeThemeStyle.value)
  const newStyle = themes[(currentIndex + 1) % themes.length]
  setThemeStyle(newStyle)
}

const themeNames = {
  blue: 'Blue',
  emerald: 'Emerald',
  indigo: 'Indigo',
  bronze: 'Bronze',
  navy: 'Navy',
  zinc: 'Zinc'
}

// --- Background Style Logic ---
const activeBackgroundStyle = ref('animated')

const setBackgroundStyle = styleName => {
  const resolvedStyle = styleName === 'school' ? 'solid' : styleName
  activeBackgroundStyle.value = resolvedStyle
  document.body.classList.remove(
    'bg-animated',
    'bg-static_squares',
    'bg-glass',
    'bg-school',
    'bg-solid',
    'bg-modern_minimalist'
  )
  document.body.classList.add(`bg-${resolvedStyle}`)
  localStorage.setItem('backgroundStyle', resolvedStyle)

  // Auto finish and vector visibility based on theme: solid AND modern_minimalist use solid finish (with vector illustrations)
  if (resolvedStyle === 'solid' || resolvedStyle === 'modern_minimalist') {
    setThemeFinish('solid')
  } else {
    setThemeFinish('glossy')
  }
}

const cycleBackgroundStyle = () => {
  const styles = ['animated', 'static_squares', 'glass', 'solid', 'modern_minimalist']
  const currentIndex = styles.indexOf(activeBackgroundStyle.value)
  const newStyle = styles[(currentIndex + 1) % styles.length]
  setBackgroundStyle(newStyle)
}

const backgroundNames = {
  animated: 'Animated Squares',
  static_squares: 'Static Squares',
  glass: 'Glass Effect',
  solid: 'School Illustration',
  modern_minimalist: 'Modern Minimalist'
}

// --- Finish Style Logic (Glossy/Solid) ---
const activeThemeFinish = ref('glossy')

const setThemeFinish = finishName => {
  activeThemeFinish.value = finishName
  if (finishName === 'solid') {
    document.body.classList.add('finish-solid')
  } else {
    document.body.classList.remove('finish-solid')
  }
  localStorage.setItem('themeFinish', finishName)
}

const cycleThemeFinish = () => {
  const finishes = ['glossy', 'solid']
  const currentIndex = finishes.indexOf(activeThemeFinish.value)
  const newFinish = finishes[(currentIndex + 1) % finishes.length]
  setThemeFinish(newFinish)
}

const finishNames = {
  glossy: 'Glossy (Glass)',
  solid: 'Solid (Flat)'
}

onMounted(() => {
  // Restore theme style (support legacy 'tahoe' → map ke 'blue')
  const savedThemeStyle = localStorage.getItem('themeStyle') || 'blue'
  const mappedStyle = savedThemeStyle === 'tahoe' ? 'blue' : savedThemeStyle
  activeThemeStyle.value = mappedStyle

  applyThemeClass(mappedStyle)
  // Simpan ulang jika ada konversi dari 'tahoe'
  if (savedThemeStyle === 'tahoe') localStorage.setItem('themeStyle', 'blue')

  // Restore background style (which automatically updates the finish)
  const savedBgStyle = localStorage.getItem('backgroundStyle') || 'animated'
  setBackgroundStyle(savedBgStyle)
})
</script>

<template>
  <SidebarMenu>
    <SidebarMenuItem>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <SidebarMenuButton
            size="lg"
            class="glass-mini text-sidebar-accent-foreground hover:bg-sidebar-accent/50 data-[state=open]:bg-sidebar-accent/50 data-[state=open]:text-sidebar-accent-foreground transition-all duration-300"
          >
            <Avatar class="h-8 w-8 rounded-lg">
              <AvatarImage
                :src="user.avatar"
                :alt="user.name"
              />
              <AvatarFallback class="rounded-lg bg-primary/10 text-primary font-semibold"> {{ userInitials }} </AvatarFallback>
            </Avatar>
            <div class="grid flex-1 text-left text-sm leading-tight">
              <span class="truncate font-medium">{{ user.name }}</span>
              <span class="truncate text-xs text-muted-foreground uppercase font-semibold">{{
                user.role
              }}</span>
            </div>
            <ChevronsUpDown class="ml-auto size-4" />
          </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
          :side="isMobile ? 'bottom' : 'right'"
          align="end"
          :side-offset="4"
        >
          <DropdownMenuLabel class="p-0 font-normal">
            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
              <Avatar class="h-8 w-8 rounded-lg">
                <AvatarImage
                  :src="user.avatar"
                  :alt="user.name"
                />
                <AvatarFallback class="rounded-lg bg-primary/10 text-primary font-semibold"> {{ userInitials }} </AvatarFallback>
              </Avatar>
              <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-semibold">{{ user.name }}</span>
                <span class="truncate text-xs">{{ user.email }}</span>
              </div>
            </div>
          </DropdownMenuLabel>
          <DropdownMenuSeparator />
          <DropdownMenuGroup>
            <DropdownMenuLabel class="text-xs text-muted-foreground"
              >Tampilan &amp; Tema</DropdownMenuLabel
            >
            <!-- Background Style Cycle -->
            <DropdownMenuItem
              @select.prevent="cycleBackgroundStyle"
              class="cursor-pointer"
            >
              <Layers class="size-4" />
              <div class="truncate">
                Tema:
                <span class="font-semibold text-primary ml-1">{{
                  backgroundNames[activeBackgroundStyle] || 'Animated Squares'
                }}</span>
              </div>
            </DropdownMenuItem>
            <!-- Color Theme Cycle -->
            <DropdownMenuItem
              @select.prevent="cycleThemeStyle"
              class="cursor-pointer"
            >
              <Palette class="size-4" />
              <div class="truncate">
                Warna:
                <span class="font-semibold text-primary ml-1">{{
                  themeNames[activeThemeStyle] || 'Blue'
                }}</span>
              </div>
            </DropdownMenuItem>
          </DropdownMenuGroup>
          <DropdownMenuSeparator />
          <DropdownMenuGroup>
            <DropdownMenuItem
              @click="router.push('/akun-setting')"
              class="cursor-pointer"
            >
              <BadgeCheck />
              Account
            </DropdownMenuItem>
            <DropdownMenuItem>
              <Bell />
              Notifications
            </DropdownMenuItem>
          </DropdownMenuGroup>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            class="text-destructive focus:bg-destructive/10 focus:text-destructive cursor-pointer"
            @click="showLogoutDialog = true"
          >
            <LogOut />
            Log out
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </SidebarMenuItem>
  </SidebarMenu>

  <!-- Dialog Konfirmasi Logout -->
  <AlertDialog
    :open="showLogoutDialog"
    @update:open="showLogoutDialog = $event"
  >
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Konfirmasi Keluar</AlertDialogTitle>
        <AlertDialogDescription>
          Apakah Anda yakin ingin keluar dari sistem? Sesi Anda akan diakhiri.
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel>Batal</AlertDialogCancel>
        <AlertDialogAction
          class="bg-destructive text-white hover:bg-destructive/90"
          @click="confirmLogout"
        >
          Ya, Keluar
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
