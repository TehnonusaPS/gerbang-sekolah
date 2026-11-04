<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import AdminYayasanNotif from './admin-yayasan/notifikasi/index.vue'
import KepalaSekolahNotif from './kepala-sekolah/notifikasi/index.vue'
import AdminSekolahNotif from './admin-sekolah/notifikasi/index.vue'
import TataUsahaNotif from './tata-usaha/notifikasi/index.vue'
import GuruNotif from './guru/notifikasi/index.vue'
import WaliKelasNotif from './wali-kelas/notifikasi/index.vue'
import SiswaNotif from './siswa/notifikasi/index.vue'
import OrangTuaNotif from './orang-tua/notifikasi/index.vue'
import PlaceholderPage from '@/components/PlaceholderPage.vue'

const auth = useAuthStore()

const currentRole = computed(() => auth.user?.role)
</script>

<template>
  <AdminYayasanNotif v-if="currentRole === 'admin_yayasan'" />
  <KepalaSekolahNotif v-else-if="currentRole === 'kepala_sekolah'" />
  <AdminSekolahNotif v-else-if="currentRole === 'admin_sekolah'" />
  <TataUsahaNotif v-else-if="currentRole === 'tata_usaha'" />
  <WaliKelasNotif v-else-if="currentRole === 'guru' && auth.isWaliKelas" />
  <GuruNotif v-else-if="currentRole === 'guru'" />
  <SiswaNotif v-else-if="currentRole === 'siswa'" />
  <OrangTuaNotif v-else-if="currentRole === 'orang_tua'" />
  <PlaceholderPage v-else />
</template>
