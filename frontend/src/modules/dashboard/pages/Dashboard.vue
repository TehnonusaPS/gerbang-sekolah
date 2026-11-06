<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { getSuperadminDashboard, getSuperadminGrowthChart } from '@/services/superadminDashboardService'
import { getYayasanDashboard } from '@/services/yayasanDashboardService'
import SuperadminStatCards from '../components/SuperadminStatCards.vue'
import SuperadminGrowthChart from '../components/SuperadminGrowthChart.vue'
import SuperadminActivityLog from '../components/SuperadminActivityLog.vue'
import YayasanStatCards from '../components/YayasanStatCards.vue'
import YayasanSchoolComparisonChart from '../components/YayasanSchoolComparisonChart.vue'
import YayasanUnitSummaryWidget from '../components/YayasanUnitSummaryWidget.vue'
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

const superadminData = ref<{
  stats?: any
  growth_chart?: any[]
  growth_summary?: any
  available_years?: number[]
  selected_year?: number | string
  activities?: any[]
} | null>(null)

const isDashboardLoading = ref(false)
const isGrowthLoading = ref(false)

const yayasanData = ref<{
  stats?: any
  school_comparison?: any[]
  recent_activities?: any[]
} | null>(null)

const isYayasanLoading = ref(false)

async function loadYayasanData() {
  if (auth.user?.role !== 'admin_yayasan') return
  isYayasanLoading.value = true
  try {
    const data = await getYayasanDashboard()
    if (data) {
      yayasanData.value = data
    }
  } catch (error) {
    console.error('Gagal memuat data dashboard yayasan:', error)
  } finally {
    isYayasanLoading.value = false
  }
}

async function loadSuperadminData() {
  if (auth.user?.role !== 'superadmin') return
  isDashboardLoading.value = true
  try {
    const data = await getSuperadminDashboard()
    if (data) {
      superadminData.value = data
    }
  } catch (error) {
    console.error('Gagal memuat data superadmin dari API:', error)
  } finally {
    isDashboardLoading.value = false
  }
}

async function handleGrowthYearChange(year: number) {
  isGrowthLoading.value = true
  try {
    const data = await getSuperadminGrowthChart(year)
    if (data && superadminData.value) {
      superadminData.value.growth_chart = data.growth_chart
      superadminData.value.growth_summary = data.growth_summary
      superadminData.value.selected_year = data.selected_year
      if (data.available_years) {
        superadminData.value.available_years = data.available_years
      }
    }
  } catch (error) {
    console.error('Gagal memfilter tren pertumbuhan per tahun:', error)
  } finally {
    isGrowthLoading.value = false
  }
}

watch(
  () => auth.user?.role,
  (role) => {
    if (role === 'superadmin' && !superadminData.value) {
      loadSuperadminData()
    } else if (role === 'admin_yayasan' && !yayasanData.value) {
      loadYayasanData()
    }
  },
  { immediate: true }
)

onMounted(() => {
  if (auth.user?.role === 'superadmin' && !superadminData.value) {
    loadSuperadminData()
  } else if (auth.user?.role === 'admin_yayasan' && !yayasanData.value) {
    loadYayasanData()
  }
})
</script>

<template>
  <!-- ── Superadmin Dashboard ── -->
  <div
    v-if="auth.user?.role === 'superadmin'"
    class="space-y-3.5 relative lg:max-h-[calc(100vh-7.5rem)] lg:flex lg:flex-col lg:justify-between"
  >
    <SuperadminStatCards
      :stats="superadminData?.stats"
      :loading="isDashboardLoading"
    />
    <div class="grid gap-3.5 lg:grid-cols-5 lg:flex-1 lg:min-h-0">
      <SuperadminGrowthChart
        :delay="700"
        :chart-data="superadminData?.growth_chart"
        :summary="superadminData?.growth_summary"
        :available-years="superadminData?.available_years"
        :selected-year="superadminData?.selected_year"
        :loading="isGrowthLoading"
        @year-change="handleGrowthYearChange"
      />
      <SuperadminActivityLog
        :delay="850"
        :activities="superadminData?.activities"
      />
    </div>
  </div>

  <!-- ── Admin Yayasan Dashboard ── -->
  <div
    v-else-if="auth.user?.role === 'admin_yayasan'"
    class="space-y-3.5 relative lg:max-h-[calc(100vh-7.5rem)] lg:flex lg:flex-col lg:justify-between"
  >
    <YayasanStatCards
      :stats="yayasanData?.stats"
      :loading="isYayasanLoading"
    />
    <div class="grid gap-3.5 lg:grid-cols-5 lg:flex-1 lg:min-h-0">
      <YayasanSchoolComparisonChart
        :delay="550"
        :comparison-data="yayasanData?.school_comparison"
        :loading="isYayasanLoading"
      />
      <YayasanUnitSummaryWidget
        :delay="700"
        :schools="yayasanData?.school_comparison"
        :loading="isYayasanLoading"
      />
    </div>
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
