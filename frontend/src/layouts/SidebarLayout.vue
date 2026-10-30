<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppSidebar from '@/components/AppSidebar.vue'
import AppMobileNavigation from '@/components/AppMobileNavigation.vue'
import AppSubNavChips from '@/components/AppSubNavChips.vue'
import { PageLoader } from '@/components/ui/page-loader'
import { useApiLoading, triggerPageLoad } from '@/composables/useApiLoading'
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator
} from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Bell, Search, UserPlus, CreditCard, MessageCircle, MessageSquare, Sun, Moon, Monitor, Megaphone } from 'lucide-vue-next'
import { ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { sidebarSlide, topbarSlide } from '@/config/motion'
import { getNotifications, markAsRead as apiMarkAsRead } from '@/services/notificationService'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const { isPageLoading } = useApiLoading()

// Aktifkan PageLoader setiap berpindah rute halaman
watch(
  () => route.path,
  () => {
    triggerPageLoad(150)
  },
  { immediate: true }
)

const loaderTitle = computed(() => {
  const pageTitle = route.meta?.title
  return pageTitle ? `Memuat ${pageTitle}` : 'Memuat Halaman'
})

const isLoginTransition = computed(() => auth.isJustLoggedIn)
const sidebarDelay = computed(() => isLoginTransition.value ? 1000 : 100)
const topbarDelay = computed(() => isLoginTransition.value ? 1200 : 200)

const chatRoute = computed(() => {
  const role = auth.user?.role
  if (role === 'guru') return '/komunikasi/guru/chat'
  if (role === 'wali_kelas') return '/komunikasi/wali-kelas/chat'
  if (role === 'siswa') return '/komunikasi/siswa/chat'
  if (role === 'orang_tua') return '/komunikasi/orang-tua/chat'
  return '/komunikasi/chat'
})

// --- Color Mode Logic (System/Light/Dark) ---
const colorMode = ref('system')

const applyColorMode = (mode) => {
  if (mode === 'dark' || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
}

const cycleColorMode = () => {
  const modes = ['system', 'light', 'dark']
  const nextMode = modes[(modes.indexOf(colorMode.value) + 1) % modes.length]
  colorMode.value = nextMode
  localStorage.setItem('theme', nextMode)
  applyColorMode(nextMode)
}

const notifications = ref([])

const loadNotifications = async () => {
  if (!auth.token) return
  try {
    const data = await getNotifications()
    notifications.value = data.notifications.slice(0, 5) // Ambil 5 terbaru untuk dropdown
  } catch (error) {
    console.error('Gagal mengambil notifikasi:', error)
  }
}

const handleNotificationClick = async (n) => {
  if (!n.read_at) {
    try {
      await apiMarkAsRead(n.id)
      n.read_at = new Date().toISOString()
      auth.fetchUnreadNotificationsCount()
    } catch (e) {
      console.error(e)
    }
  }

  if (n.type === 'ACTIVITY_NEWS' && n.data?.activity_news_id) {
    router.push({
      path: '/komunikasi/berita-kegiatan',
      query: { id: n.data.activity_news_id }
    })
  } else if (n.type === 'ANNOUNCEMENT' && n.data?.announcement_id) {
    router.push({
      path: '/komunikasi/pengumuman',
      query: { id: n.data.announcement_id }
    })
  } else {
    router.push('/komunikasi/notifikasi')
  }
}

const formatTime = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  if (auth.isJustLoggedIn) {
    setTimeout(() => {
      auth.isJustLoggedIn = false
    }, 1200)
  }

  // Restore Color Mode (Terang/Gelap/Sistem)
  const savedTheme = localStorage.getItem('theme') || 'system'
  colorMode.value = savedTheme
  applyColorMode(savedTheme)

  // Initialize global unread chat count & WebSocket listener
  auth.fetchUnreadCount()
  auth.setupGlobalChatListener()

  // Initialize notifications
  auth.fetchUnreadNotificationsCount()
  auth.setupGlobalNotificationListener()
  loadNotifications()

  // Listen to incoming notifications in realtime to update the dropdown list
  const unsubscribe = auth.onIncomingNotification(() => {
    loadNotifications()
  })

  onMountedUnsubscribe.value = unsubscribe
})

const onMountedUnsubscribe = ref(null)
onUnmounted(() => {
  if (onMountedUnsubscribe.value) {
    onMountedUnsubscribe.value()
  }
})

// Fungsi untuk menelusuri hierarki breadcrumb secara rekursif
const breadcrumbs = computed(() => {
  const crumbs = []
  let current = route

  crumbs.unshift({
    title: current.meta.title || 'Halaman',
    active: true
  })

  let parentTitle = current.meta.parent
  while (parentTitle) {
    const candidateRoutes = router.getRoutes().filter(r => r.meta && r.meta.title === parentTitle)
    let parentRoute = null
    if (candidateRoutes.length === 1) {
      parentRoute = candidateRoutes[0]
    } else if (candidateRoutes.length > 1) {
      parentRoute = candidateRoutes.find(r => {
        const basePath = r.path.split('/:')[0].replace(/\/$/, '')
        return basePath && route.path.startsWith(basePath)
      }) || candidateRoutes[0]
    }

    if (parentRoute) {
      let resolved = null
      if (parentRoute.name) {
        try {
          const res = router.resolve({ name: parentRoute.name, params: route.params })
          if (res && res.matched.length > 0) {
            resolved = res
          }
        } catch {
          resolved = null
        }
      } else if (parentRoute.path) {
        let targetPath = parentRoute.path
        if (route.params) {
          targetPath = targetPath.replace(/:([a-zA-Z0-9_]+)(?:\(.*?\))?[?*+]?/g, (match, key) => {
            const val = route.params[key]
            if (val === undefined || val === null) return match
            return Array.isArray(val)
              ? val.map(encodeURIComponent).join('/')
              : encodeURIComponent(String(val))
          })
        }
        if (!targetPath.includes(':')) {
          try {
            const normalized = targetPath.startsWith('/') ? targetPath : '/' + targetPath
            const res = router.resolve(normalized)
            if (res && res.matched.length > 0) {
              resolved = res
            }
          } catch {
            resolved = null
          }
        }
      }

      crumbs.unshift({
        title: parentRoute.meta.title,
        path: resolved ? resolved.fullPath : null,
        href: resolved ? resolved.href : null,
        active: false
      })
      parentTitle = parentRoute.meta.parent
    } else {
      crumbs.unshift({
        title: parentTitle,
        path: null,
        href: null,
        active: false
      })
      parentTitle = null
    }
  }

  return crumbs
})

const handleCrumbClick = (e, path) => {
  if (!path) {
    e.preventDefault()
    return
  }
  if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
    return
  }
  e.preventDefault()
  router.push(path)
}
</script>

<template>
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset class="min-w-0 overflow-x-clip">
      <div class="sticky top-0 z-50 w-full pt-2">
        <header
          v-motion
          :initial="topbarSlide.initial"
          :enter="{ ...topbarSlide.enter, transition: { ...topbarSlide.enter.transition, delay: topbarDelay } }"
          :class="[
            'flex h-14 shrink-0 items-center gap-2 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 glass-mini mx-2 px-4 justify-between',
            auth.isLoggingOut ? 'topbar-exit-active' : ''
          ]"
        >
          <!-- SISI KIRI: Sidebar Trigger & Breadcrumb -->
          <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1 hidden md:inline-flex" />
            <Separator orientation="vertical" class="mr-2 h-4 my-auto !self-center hidden md:block" />
            <Breadcrumb>
              <BreadcrumbList>
                <template v-for="(crumb, index) in breadcrumbs" :key="index">
                  <BreadcrumbItem>
                    <BreadcrumbLink
                      v-if="!crumb.active && crumb.path"
                      :href="crumb.href || crumb.path"
                      @click="handleCrumbClick($event, crumb.path)"
                    >
                      {{ crumb.title }}
                    </BreadcrumbLink>
                    <span
                      v-else-if="!crumb.active"
                      class="text-muted-foreground text-sm font-normal cursor-default select-none"
                    >
                      {{ crumb.title }}
                    </span>
                    <BreadcrumbPage v-else>
                      {{ crumb.title }}
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator v-if="index < breadcrumbs.length - 1" />
                </template>
              </BreadcrumbList>
            </Breadcrumb>
          </div>

          <!-- SISI KANAN: Search & Notifications -->
          <div class="flex items-center gap-4">
            <div class="relative hidden md:block w-64">
              <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                type="search"
                name="nav-search"
                autocomplete="off"
                placeholder="Cari sesuatu..."
                class="pl-9 h-9 w-full bg-muted/50 focus-visible:bg-background transition-colors"
              />
            </div>

            <!-- Chat Icon (Guru, Siswa, Wali Kelas, & Orang Tua) -->
            <div v-if="auth.user?.role === 'guru' || auth.user?.role === 'siswa' || auth.user?.role === 'wali_kelas' || auth.user?.role === 'orang_tua'" class="relative">
              <Button
                variant="ghost"
                size="icon"
                class="rounded-full hover:bg-muted"
                @click="router.push(chatRoute)"
              >
                <MessageSquare class="h-5 w-5" />
              </Button>
              <span
                v-if="auth.unreadCount > 0"
                class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-600 px-0.5 text-[9px] font-extrabold text-white"
              >
                {{ auth.unreadCount }}
              </span>
            </div>

            <!-- Notification Dropdown -->
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <div class="relative cursor-pointer">
                  <Button variant="ghost" size="icon" class="rounded-full hover:bg-muted">
                    <Bell class="h-5 w-5" />
                  </Button>
                  <span v-if="auth.unreadNotificationsCount > 0" class="absolute top-2 right-2 flex h-2 w-2">
                    <span
                      class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"
                    ></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                  </span>
                </div>
              </DropdownMenuTrigger>

              <DropdownMenuContent class="w-80 rounded-2xl border-border bg-card p-2 shadow-xl" align="end">
                <DropdownMenuLabel class="font-bold flex justify-between items-center px-3 py-2 text-sm">
                  Notifikasi Terbaru
                  <span 
                    v-if="auth.unreadNotificationsCount > 0" 
                    class="text-[10px] bg-red-600/10 text-red-600 px-2 py-0.5 rounded-full font-extrabold"
                  >
                    {{ auth.unreadNotificationsCount }} Baru
                  </span>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuGroup class="max-h-[300px] overflow-y-auto">
                  <DropdownMenuItem 
                    v-for="n in notifications" 
                    :key="n.id" 
                    class="flex items-start gap-3 p-3.5 cursor-pointer rounded-xl transition-all duration-200 border border-transparent hover:border-border/50 hover:bg-muted/40 relative overflow-hidden"
                    :class="[
                      !n.read_at 
                        ? 'bg-primary/[0.03] hover:bg-primary/[0.06] border-l-3 border-l-primary' 
                        : 'hover:bg-muted/40'
                    ]"
                    @click="handleNotificationClick(n)"
                  >
                    <!-- Icon container -->
                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-colors"
                         :class="!n.read_at ? 'bg-primary/10 text-primary font-bold shadow-xs' : 'bg-muted text-muted-foreground'">
                      <Megaphone class="h-4.5 w-4.5" />
                    </div>

                    <!-- Text Information -->
                    <div class="flex-1 min-w-0 space-y-1 text-left">
                      <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-extrabold leading-tight text-foreground truncate max-w-[150px]">
                          {{ n.title }}
                        </p>
                        <!-- Unread pulsing indicator -->
                        <span v-if="!n.read_at" class="h-1.5 w-1.5 shrink-0 rounded-full bg-primary animate-pulse"></span>
                      </div>
                      <p class="text-[11px] leading-relaxed text-muted-foreground/90 line-clamp-2 pr-2">
                        {{ n.content }}
                      </p>
                      <div class="flex items-center gap-1.5 pt-0.5">
                        <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded-md bg-muted/80 text-muted-foreground tracking-wider uppercase">
                          {{ n.data?.category || 'UMUM' }}
                        </span>
                        <span class="text-[9px] text-muted-foreground/60 font-medium">
                          • {{ formatTime(n.created_at) }}
                        </span>
                      </div>
                    </div>
                  </DropdownMenuItem>
                  <div v-if="notifications.length === 0" class="p-6 text-center text-xs text-muted-foreground">
                    Tidak ada notifikasi baru.
                  </div>
                </DropdownMenuGroup>

                <DropdownMenuSeparator />
                <DropdownMenuItem
                  class="justify-center text-primary font-bold text-xs cursor-pointer py-2.5 rounded-xl hover:bg-primary/5"
                  @click="router.push('/komunikasi/notifikasi')"
                >
                  Lihat Semua Notifikasi
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>

            <!-- Mode Tampilan (Dark/Light/Sistem) -->
            <Button
              variant="ghost"
              size="icon"
              class="rounded-full hover:bg-muted"
              @click="cycleColorMode"
              title="Ganti Mode Tampilan"
            >
              <Monitor v-if="colorMode === 'system'" class="h-5 w-5" />
              <Sun v-else-if="colorMode === 'light'" class="h-5 w-5" />
              <Moon v-else class="h-5 w-5" />
            </Button>
          </div>
        </header>
        <AppSubNavChips />
      </div>

      <div
        :class="[
          'relative flex flex-1 flex-col gap-4 p-6 pb-20 md:pb-6 text-left transition-all duration-500 ease-[cubic-bezier(0.76,0,0.24,1)] min-w-0',
          auth.isLoggingOut ? 'content-exit-active' : ''
        ]"
      >
        <PageLoader v-if="isPageLoading" :title="loaderTitle" />
        <div v-show="!isPageLoading" class="w-full min-w-0 flex-1">
          <router-view />
        </div>
      </div>
      <AppMobileNavigation />
    </SidebarInset>
  </SidebarProvider>
</template>
