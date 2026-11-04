<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { navMain } from '@/constants/navigation'

const route = useRoute()
const auth = useAuthStore()

// Filtered navigation items based on RBAC rules
const filteredNavMain = computed(() => {
  const currentRole = auth.user?.role

  return navMain
    .filter(item => {
      if (item.requiresWaliKelas && !auth.isWaliKelas) return false
      if (item.excludeRoles && item.excludeRoles.includes(currentRole)) return false
      if (!item.roles) return true
      return item.roles.includes(currentRole)
    })
    .map(item => {
      if (item.items) {
        return {
          ...item,
          items: item.items.filter(sub => {
            if (sub.requiresWaliKelas && !auth.isWaliKelas) return false
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

// Find active parent category that matches the current route
const activeParent = computed(() => {
  const currentPath = route.path
  
  return filteredNavMain.value.find(parent => {
    // 1. Prefix match on parent url (e.g. starts with '/manajemen-data')
    if (parent.url && parent.url !== '/' && currentPath.startsWith(parent.url)) {
      return true
    }
    // 2. Or if any child URL matches/prefixes
    if (parent.items) {
      return parent.items.some(child => {
        const cleanCurrent = currentPath.replace(/^\/|\/$/g, '')
        const cleanTarget = child.url.replace(/^\/|\/$/g, '')
        return cleanCurrent === cleanTarget || cleanCurrent.startsWith(cleanTarget + '/')
      })
    }
    return false
  })
})

// Active sub-menu items under the active parent
const subMenuItems = computed(() => {
  return activeParent.value?.items || []
})

// Check if a sub-page is active
const isPageActive = url => {
  if (!url || url === '#' || url === '') return false
  const cleanCurrent = route.path.replace(/^\/|\/$/g, '')
  const cleanTarget = url.replace(/^\/|\/$/g, '')
  return cleanCurrent === cleanTarget || cleanCurrent.startsWith(cleanTarget + '/')
}
</script>

<template>
  <div 
    v-if="subMenuItems.length > 1" 
    class="w-full bg-background/60 backdrop-blur-md py-2.5 border-b border-border/50 overflow-x-auto scrollbar-none flex items-center gap-2 px-4 sticky top-14 z-40 md:hidden"
  >
    <router-link
      v-for="sub in subMenuItems"
      :key="sub.title"
      :to="sub.url"
      class="px-4 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition-all duration-200"
      :class="[
        isPageActive(sub.url)
          ? 'bg-primary text-primary-foreground shadow-sm scale-[1.03]'
          : 'bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground border border-transparent'
      ]"
    >
      {{ sub.title }}
    </router-link>
  </div>
</template>

<style scoped>
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
