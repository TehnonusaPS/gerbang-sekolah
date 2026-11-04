<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import GuruPesan from './guru/pesan/index.vue'
import WaliKelasPesan from './wali-kelas/pesan/index.vue'
import SiswaPesan from './siswa/pesan/index.vue'
import OrangTuaPesan from './orang-tua/pesan/index.vue'
import PlaceholderPage from '@/components/PlaceholderPage.vue'

const auth = useAuthStore()

const currentRole = computed(() => auth.user?.role)
</script>

<template>
  <WaliKelasPesan v-if="currentRole === 'guru' && auth.isWaliKelas" />
  <GuruPesan v-else-if="currentRole === 'guru'" />
  <SiswaPesan v-else-if="currentRole === 'siswa'" />
  <OrangTuaPesan v-else-if="currentRole === 'orang_tua'" />
  <PlaceholderPage v-else />
</template>
