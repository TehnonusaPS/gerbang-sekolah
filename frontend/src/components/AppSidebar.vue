<script setup>
import NavMain from '@/components/NavMain.vue'
import NavUser from '@/components/NavUser.vue'
import { Avatar } from '@/components/ui/avatar'
import { DropdownMenu, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useTenantBrand } from '@/composables/useTenantBrand'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarRail,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem
} from '@/components/ui/sidebar'
import { sidebarSlide } from '@/config/motion'
import { navMain } from '@/constants/navigation'

const auth = useAuthStore()

const currentUser = computed(() => ({
  name: auth.user?.name || 'Pengguna',
  email: auth.user?.email || auth.user?.role || 'guest',
  role: auth.user?.roleLabel || auth.user?.role || 'guest',
  avatar: ''
}))

const { tenantName, tenantLogo, onLogoError } = useTenantBrand()

// Struktur RBAC Dinamis
const filteredNavMain = computed(() => {
  const currentRole = auth.user?.role
  const isLandingPageEnabled = auth.user?.school?.landing_page_enabled || auth.user?.foundation?.landing_page_enabled || false

  return (
    navMain
      // 1. Filter parent menu
      .filter(item => {
        if (item.requiresLandingPageEnabled && !isLandingPageEnabled) return false
        if (item.excludeRoles && item.excludeRoles.includes(currentRole)) return false
        if (!item.roles) return true // Terbuka untuk semua jika tidak ada pembatasan
        return item.roles.includes(currentRole)
      })
      // 2. Filter children menu (jika ada)
      .map(item => {
        if (item.items) {
          return {
            ...item,
            items: item.items.filter(sub => {
              if (sub.requiresLandingPageEnabled && !isLandingPageEnabled) return false
              if (sub.excludeRoles && sub.excludeRoles.includes(currentRole)) return false
              if (!sub.roles) return true
              return sub.roles.includes(currentRole)
            })
          }
        }
        return item
      })
      // 3. Sembunyikan parent jika semua childnya tersembunyi (opsional, tapi disarankan)
      .filter(item => {
        if (item.items && item.items.length === 0) return false
        return true
      })
  )
})
</script>

<template>
  <Sidebar collapsible="icon" variant="floating">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <SidebarMenuButton size="lg"
                class="glass-mini text-sidebar-accent-foreground cursor-default hover:bg-transparent active:bg-transparent data-[state=open]:bg-transparent transition-colors duration-300">
                <Avatar class="h-8 w-8 rounded-lg">
                  <img :src="tenantLogo" :alt="tenantName" class="h-full w-full object-cover" @error="onLogoError" />
                </Avatar>
                <div class="grid flex-1 text-left text-sm leading-tight">
                  <span class="truncate font-extrabold text-sidebar-foreground tracking-tight">{{ tenantName }}</span>
                  <span class="truncate text-[10px] capitalize text-sidebar-foreground/70 font-medium">
                    {{ auth.user?.roleLabel || auth.user?.role || 'guest' }}
                  </span>
                </div>
              </SidebarMenuButton>
            </DropdownMenuTrigger>
          </DropdownMenu>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>
    <SidebarContent>
      <NavMain :items="filteredNavMain" />
    </SidebarContent>
    <SidebarFooter>
      <NavUser :user="currentUser" />
    </SidebarFooter>
    <SidebarRail />
  </Sidebar>
</template>
