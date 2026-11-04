<script setup lang="ts">
import { useAuthStore } from '@/stores/authStore'
import SuperadminStatCards from '../components/SuperadminStatCards.vue'
import SuperadminGrowthChart from '../components/SuperadminGrowthChart.vue'
import SuperadminActivityLog from '../components/SuperadminActivityLog.vue'
import YayasanStatCards from '../components/YayasanStatCards.vue'
import YayasanSekolahSDM from '../components/YayasanSekolahSDM.vue'
import YayasanFinanceChart from '../components/YayasanFinanceChart.vue'
import KepsekStatCards from '../components/KepsekStatCards.vue'
import KepsekKehadiran from '../components/KepsekKehadiran.vue'
import KepsekAgenda from '../components/KepsekAgenda.vue'
import KepsekPerforma from '../components/KepsekPerforma.vue'
import AdminSekolahStatCards from '../components/AdminSekolahStatCards.vue'
import AdminSekolahAbsensi from '../components/AdminSekolahAbsensi.vue'
import AdminSekolahSPP from '../components/AdminSekolahSPP.vue'
import GuruTopSection from '../components/GuruTopSection.vue'
import GuruJadwal from '../components/GuruJadwal.vue'
import GuruSidePanel from '../components/GuruSidePanel.vue'
import GuruPenilaian from '../components/GuruPenilaian.vue'
import SiswaStatCards from '../components/SiswaStatCards.vue'
import SiswaAkademik from '../components/SiswaAkademik.vue'
import SiswaJadwal from '../components/SiswaJadwal.vue'
import SiswaTugas from '../components/SiswaTugas.vue'
import OrangTuaStudentCard from '../components/OrangTuaStudentCard.vue'
import OrangTuaSPP from '../components/OrangTuaSPP.vue'
import OrangTuaPerforma from '../components/OrangTuaPerforma.vue'
import OrangTuaKehadiran from '../components/OrangTuaKehadiran.vue'

const auth = useAuthStore()
</script>

<template>
  <!-- ── Superadmin Dashboard ── -->
  <div
    v-if="auth.user?.role === 'superadmin'"
    class="space-y-6 relative"
  >
    <SuperadminStatCards />
    <div class="grid gap-4 lg:grid-cols-5">
      <SuperadminGrowthChart :delay="700" />
      <SuperadminActivityLog :delay="850" />
    </div>
  </div>

  <!-- ── Admin Yayasan Dashboard ── -->
  <div
    v-else-if="auth.user?.role === 'admin_yayasan'"
    class="space-y-6"
  >
    <YayasanStatCards />
    <YayasanSekolahSDM :delay="550" />
    <YayasanFinanceChart :delay="700" />
  </div>

  <!-- ── Kepala Sekolah Dashboard ── -->
  <div
    v-else-if="auth.user?.role === 'kepala_sekolah'"
    class="space-y-6"
  >
    <KepsekStatCards />
    <div class="grid gap-4 lg:grid-cols-5">
      <KepsekKehadiran :delay="700" />
      <KepsekAgenda :delay="850" />
    </div>
    <KepsekPerforma :delay="1000" />
  </div>

  <!-- ── Admin Sekolah & TU Dashboard ── -->
  <div
    v-else-if="auth.user?.role === 'admin_sekolah' || auth.user?.role === 'tata_usaha'"
    class="space-y-6"
  >
    <AdminSekolahStatCards />
    <div class="grid gap-4 lg:grid-cols-5">
      <AdminSekolahAbsensi :delay="700" />
      <AdminSekolahSPP :delay="850" />
    </div>
  </div>

  <!-- ── Guru Dashboard ── -->
  <div
    v-else-if="auth.user?.role === 'guru'"
    class="space-y-6"
  >
    <GuruTopSection />
    <div class="grid gap-4 lg:grid-cols-5">
      <GuruJadwal :delay="550" />
      <GuruSidePanel :delay="700" />
    </div>
    <GuruPenilaian :delay="850" />
  </div>

  <!-- ── Siswa Dashboard ── -->
  <div
    v-else-if="auth.user?.role === 'siswa'"
    class="space-y-6"
  >
    <SiswaStatCards />
    <div class="grid gap-4 lg:grid-cols-5">
      <SiswaAkademik :delay="550" />
      <SiswaJadwal :delay="700" />
    </div>
    <SiswaTugas :delay="850" />
  </div>

  <!-- ── Orang Tua Dashboard ── -->
  <div
    v-else-if="auth.user?.role === 'orang_tua'"
    class="space-y-6"
  >
    <div class="grid gap-4 lg:grid-cols-[3fr_2fr]">
      <OrangTuaStudentCard :delay="100" />
      <OrangTuaSPP :delay="250" />
    </div>
    <div class="grid gap-4 lg:grid-cols-2">
      <OrangTuaPerforma :delay="400" />
      <OrangTuaKehadiran :delay="550" />
    </div>
  </div>
</template>
