<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { getPhotoUrl } from '@/utils/getPhotoUrl'
import { navMain } from '@/constants/navigation'
import {
  LayoutDashboard,
  MoreHorizontal,
  LogOut,
  Palette,
  Layers,
  User,
  Settings
} from 'lucide-vue-next'
import {
  Drawer,
  DrawerContent,
  DrawerDescription,
  DrawerHeader,
  DrawerTitle,
  DrawerTrigger,
  DrawerClose
} from '@/components/ui/drawer'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
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

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const isDrawerOpen = ref(false)
const showLogoutDialog = ref(false)

const currentUser = computed(() => ({
  name: auth.user?.name || 'Pengguna',
  email: auth.user?.email || auth.user?.role || 'guest',
  role: auth.user?.roleLabel || auth.user?.role || 'guest',
  avatar: getPhotoUrl(auth.user?.photo) || ''
}))

// Filtered navigation items based on RBAC rules
const filteredNavMain = computed(() => {
  const currentRole = auth.user?.role

  return navMain
    .filter(item => {
      if (item.excludeRoles && item.excludeRoles.includes(currentRole)) return false
      if (!item.roles) return true
      return item.roles.includes(currentRole)
    })
    .map(item => {
      if (item.items) {
        return {
          ...item,
          items: item.items.filter(sub => {
            if (sub.excludeRoles && sub.excludeRoles.includes(currentRole)) return false
            if (!sub.roles) return true
            return sub.roles.includes(currentRole)
          })
        }
      }
      return item
    })
    .filter(item => {
      if (item.items && item.items.length === 0) return false
      return true
    })
})

// Deciding which items go to bottom nav and which go to "Lainnya" drawer
const maxBottomNavItems = 4

const bottomNavItems = computed(() => {
  return filteredNavMain.value.slice(0, maxBottomNavItems)
})

const drawerItems = computed(() => {
  return filteredNavMain.value.slice(maxBottomNavItems)
})

// Helper to determine if a main route is active
const isItemActive = item => {
  const currentPath = route.path
  
  // Direct check
  if (item.url && item.url !== '/' && currentPath.startsWith(item.url)) {
    return true
  }
  
  // Children check
  if (item.items) {
    return item.items.some(sub => {
      const cleanCurrent = currentPath.replace(/^\/|\/$/g, '')
      const cleanTarget = sub.url.replace(/^\/|\/$/g, '')
      return cleanCurrent === cleanTarget || cleanCurrent.startsWith(cleanTarget + '/')
    })
  }
  
  return false
}

// Navigate to item's destination (first child if it has sub-menu, else its url)
const navigateTo = item => {
  isDrawerOpen.value = false
  if (item.items && item.items.length > 0) {
    router.push(item.items[0].url)
  } else if (item.url) {
    router.push(item.url)
  }
}

// Drawer open indicator for styling
const isDrawerTriggerActive = computed(() => {
  return isDrawerOpen.value || drawerItems.value.some(isItemActive)
})

// --- LOGOUT LOGIC ---
const confirmLogout = async () => {
  isDrawerOpen.value = false
  auth.isLoggingOut = true
  await new Promise(resolve => setTimeout(resolve, 600))

  if (document.startViewTransition) {
    document.documentElement.classList.add('transition-logout')
    const transition = document.startViewTransition(async () => {
      auth.logout()
      await router.push('/login')
    })
    transition.finished.finally(() => {
      document.documentElement.classList.remove('transition-logout')
      auth.isLoggingOut = false
    })
  } else {
    auth.logout()
    router.push('/login')
    auth.isLoggingOut = false
  }
}

// --- THEME CUSTOMIZATION LOGIC (SYNCED WITH NAVUSER.VUE) ---
const activeThemeStyle = ref('blue')
const clearThemeClasses = () => {
  document.body.classList.forEach(cls => {
    if (cls.startsWith('theme-')) {
      document.body.classList.remove(cls)
    }
  })
}
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

  // solid AND modern_minimalist both use solid finish (with vector illustrations)
  if (resolvedStyle === 'solid' || resolvedStyle === 'modern_minimalist') {
    document.body.classList.add('finish-solid')
  } else {
    document.body.classList.remove('finish-solid')
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

onMounted(() => {
  const savedThemeStyle = localStorage.getItem('themeStyle') || 'blue'
  const mappedStyle = savedThemeStyle === 'tahoe' ? 'blue' : savedThemeStyle
  activeThemeStyle.value = mappedStyle

  const savedBgStyle = localStorage.getItem('backgroundStyle') || 'animated'
  setBackgroundStyle(savedBgStyle)
})
</script>

<template>
  <div class="fixed bottom-0 left-0 right-0 z-50 md:hidden bg-background/85 backdrop-blur-lg border-t border-border/80 px-2 py-1 shadow-[0_-4px_16px_rgba(0,0,0,0.05)]">
    <div class="flex items-center justify-around h-14 max-w-lg mx-auto">
      <!-- MAIN TABS -->
      <button
        v-for="item in bottomNavItems"
        :key="item.title"
        @click="navigateTo(item)"
        class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center transition-all duration-200"
        :class="[
          isItemActive(item)
            ? 'text-primary font-bold scale-105'
            : 'text-muted-foreground hover:text-foreground font-medium'
        ]"
      >
        <component :is="item.icon" class="h-5 w-5 mb-0.5" />
        <span class="text-[10px] tracking-tight truncate max-w-full">{{ item.title }}</span>
      </button>

      <!-- MORE TAB ("Lainnya") -->
      <Drawer v-model:open="isDrawerOpen">
        <DrawerTrigger as-child>
          <button
            class="flex flex-col items-center justify-center flex-1 h-full py-1 text-center transition-all duration-200"
            :class="[
              isDrawerTriggerActive
                ? 'text-primary font-bold scale-105'
                : 'text-muted-foreground hover:text-foreground font-medium'
            ]"
          >
            <MoreHorizontal class="h-5 w-5 mb-0.5" />
            <span class="text-[10px] tracking-tight">Lainnya</span>
          </button>
        </DrawerTrigger>

        <!-- DRAWER CONTENT -->
        <DrawerContent class="bg-background/95 backdrop-blur-xl border-t border-border/80 outline-none max-h-[85vh]">
          <div class="mx-auto w-12 h-1.5 bg-muted rounded-full my-3"></div>
          
          <!-- Profile Banner inside Drawer -->
          <div class="px-6 py-4 flex items-center gap-3 bg-muted/20 border-b border-border/50">
            <Avatar class="h-10 w-10 border border-primary/20">
              <AvatarImage :src="currentUser.avatar" :alt="currentUser.name" />
              <AvatarFallback class="bg-primary text-primary-foreground font-bold">
                {{ currentUser.name.slice(0, 2).toUpperCase() }}
              </AvatarFallback>
            </Avatar>
            <div class="flex-1 min-w-0 text-left">
              <h4 class="text-sm font-bold text-foreground truncate">{{ currentUser.name }}</h4>
              <p class="text-xs text-muted-foreground truncate uppercase tracking-wider font-semibold">{{ currentUser.role }}</p>
            </div>
            <button 
              @click="navigateTo({ url: '/akun-setting' })"
              class="p-2 rounded-full hover:bg-muted text-muted-foreground transition-colors"
            >
              <Settings class="h-4 w-4" />
            </button>
          </div>

          <!-- Navigation Grid -->
          <div class="overflow-y-auto px-6 py-4 flex-1">
            <div class="grid grid-cols-3 gap-3 mb-6">
              <button
                v-for="item in drawerItems"
                :key="item.title"
                @click="navigateTo(item)"
                class="flex flex-col items-center justify-center p-3 rounded-xl transition-all duration-200 border border-transparent"
                :class="[
                  isItemActive(item)
                    ? 'bg-primary/10 text-primary border-primary/20 font-bold'
                    : 'bg-muted/30 text-muted-foreground hover:bg-muted/60 hover:text-foreground font-medium'
                ]"
              >
                <div class="p-2.5 rounded-lg mb-2" :class="isItemActive(item) ? 'bg-primary/20' : 'bg-muted'">
                  <component :is="item.icon" class="h-5 w-5" />
                </div>
                <span class="text-xs text-center leading-tight truncate w-full">{{ item.title }}</span>
              </button>
            </div>

            <!-- Customization Settings Group -->
            <div class="space-y-3.5 pt-2 border-t border-border/80">
              <h5 class="text-xs text-left font-bold text-muted-foreground uppercase tracking-wider">Tampilan & Tema</h5>
              
              <div class="grid grid-cols-2 gap-3">
                <!-- Theme Color Style cycle -->
                <button
                  @click="cycleThemeStyle"
                  class="flex items-center gap-2.5 p-3 text-left rounded-xl bg-muted/40 hover:bg-muted/70 transition-colors text-xs font-semibold text-foreground"
                >
                  <Palette class="h-4 w-4 text-primary shrink-0" />
                  <div class="min-w-0">
                    <p class="text-[10px] text-muted-foreground uppercase font-semibold">Warna</p>
                    <p class="font-bold truncate text-primary">{{ themeNames[activeThemeStyle] || 'Blue' }}</p>
                  </div>
                </button>

                <!-- Background style cycle -->
                <button
                  @click="cycleBackgroundStyle"
                  class="flex items-center gap-2.5 p-3 text-left rounded-xl bg-muted/40 hover:bg-muted/70 transition-colors text-xs font-semibold text-foreground"
                >
                  <Layers class="h-4 w-4 text-primary shrink-0" />
                  <div class="min-w-0">
                    <p class="text-[10px] text-muted-foreground uppercase font-semibold">Latar Belakang</p>
                    <p class="font-bold truncate text-primary">{{ backgroundNames[activeBackgroundStyle] || 'Animated' }}</p>
                  </div>
                </button>
              </div>
            </div>

            <!-- Action buttons inside Drawer -->
            <div class="pt-5 mt-4 border-t border-border/80">
              <button
                @click="showLogoutDialog = true"
                class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-destructive/10 text-destructive hover:bg-destructive/15 transition-colors text-sm font-bold"
              >
                <LogOut class="h-4 w-4" />
                <span>Keluar dari Sistem</span>
              </button>
            </div>
          </div>
        </DrawerContent>
      </Drawer>
    </div>
  </div>

  <!-- Logout Alert Dialog -->
  <AlertDialog :open="showLogoutDialog" @update:open="showLogoutDialog = $event">
    <AlertDialogContent class="w-[90%] max-w-sm rounded-2xl p-6 bg-background/95 backdrop-blur-xl border border-border/50">
      <AlertDialogHeader>
        <AlertDialogTitle class="text-lg font-bold">Konfirmasi Keluar</AlertDialogTitle>
        <AlertDialogDescription class="text-sm text-muted-foreground mt-2">
          Apakah Anda yakin ingin keluar dari sistem? Sesi Anda akan diakhiri.
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter class="flex flex-col sm:flex-row gap-2 mt-4">
        <AlertDialogCancel class="w-full sm:w-auto rounded-xl">Batal</AlertDialogCancel>
        <AlertDialogAction
          class="w-full sm:w-auto bg-destructive text-white hover:bg-destructive/90 rounded-xl font-bold"
          @click="confirmLogout"
        >
          Ya, Keluar
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
