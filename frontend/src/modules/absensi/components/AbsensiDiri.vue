<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import {
  CalendarDays,
  MapPin,
  Briefcase,
  UserCheck,
  Coffee,
  AlertCircle,
  Camera,
  CameraOff
} from 'lucide-vue-next'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Skeleton } from '@/components/ui/skeleton'
import { clockInStaff, getStaffAttendanceHistory } from '@/services/api/absensi'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { glassFade, glassSlide, tableRowFade } from '@/config/motion'

// ─── STATE ──────────────────────────────────────────────────
const authStore = useAuthStore()
const currentTime = ref('')
const currentDate = ref('')
let clockInterval = null

const hasClockedIn = ref(false)
const clockInTime = ref(null)

const isPageLoading = ref(true)

const recentLogs = ref([])
const summaryStats = ref({
  hadir: 0,
  terlambat: 0,
  izin_cuti: 0
})

// ─── GEOLOCATION STATE ────────────────────────────────────────
const locationStatus = ref('loading') // 'loading' | 'success' | 'error'
const locationCoords = ref({ latitude: null, longitude: null, accuracy: null })
const locationError = ref('')

// ─── CAMERA STATE ───────────────────────────────────────────
const isScanning = ref(false)
const videoRef = ref(null)
const mediaStream = ref(null)
const cameraStatus = ref('idle') // 'loading' | 'active' | 'error'
const cameraError = ref('')

// ─── METHODS ────────────────────────────────────────────────
function updateClock() {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

function requestLocation() {
  locationStatus.value = 'loading'
  locationError.value = ''

  if (!navigator.geolocation) {
    locationStatus.value = 'error'
    locationError.value = 'Geolokasi tidak didukung oleh browser ini.'
    toast.error('Gagal memuat geolokasi', { description: locationError.value })
    return
  }

  navigator.geolocation.getCurrentPosition(
    (position) => {
      locationCoords.value = {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy
      }
      locationStatus.value = 'success'
    },
    (err) => {
      locationStatus.value = 'error'
      if (err.code === 1) {
        locationError.value = 'Izin lokasi ditolak. Harap izinkan akses lokasi Anda untuk dapat melakukan absensi.'
      } else {
        locationError.value = `Gagal memuat geolokasi: ${err.message}`
      }
      toast.error('Akses lokasi diperlukan!', { description: locationError.value })
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  )
}

async function loadAttendanceData() {
  isPageLoading.value = true
  try {
    const response = await getStaffAttendanceHistory()
    if (response.success) {
      recentLogs.value = response.data.history
      summaryStats.value = response.data.summary

      // Check if user clocked in today
      const today = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
      const todayLog = recentLogs.value.find(log => log.tanggal === today && log.type === 'attendance')
      if (todayLog) {
        hasClockedIn.value = true
        clockInTime.value = todayLog.jamMasuk
      } else {
        hasClockedIn.value = false
        clockInTime.value = null
      }
    }
  } catch (error) {
    console.error(error)
    toast.error('Gagal mengambil riwayat absensi')
  } finally {
    isPageLoading.value = false
  }
}

async function openCamera() {
  // Enforce Geolocation check before opening camera
  if (locationStatus.value !== 'success') {
    requestLocation()
    if (locationStatus.value !== 'success') {
      toast.error('Absensi ditolak', { description: 'Anda harus memberikan izin lokasi untuk melakukan absensi.' })
      return
    }
  }

  isScanning.value = true
  cameraStatus.value = 'loading'
  cameraError.value = ''

  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: 'user',
        width: { ideal: 640 },
        height: { ideal: 480 },
      },
      audio: false,
    })
    mediaStream.value = stream
    cameraStatus.value = 'active'
    await nextTick()
    if (videoRef.value) {
      videoRef.value.srcObject = stream
      videoRef.value.play()
    }
  } catch (err) {
    mediaStream.value = null
    cameraStatus.value = 'error'
    if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
      cameraError.value = 'Akses kamera ditolak. Mohon aktifkan izin kamera di browser Anda.'
    } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
      cameraError.value = 'Kamera tidak ditemukan pada perangkat Anda.'
    } else {
      cameraError.value = `Gagal memuat kamera: ${err.message}`
    }
  }
}

function stopCamera() {
  if (mediaStream.value) {
    mediaStream.value.getTracks().forEach(track => track.stop())
    mediaStream.value = null
  }
  if (videoRef.value) videoRef.value.srcObject = null
  isScanning.value = false
  cameraStatus.value = 'idle'
}

const isSubmitting = ref(false)

async function takePhotoAndSubmit() {
  if (isSubmitting.value) return
  isSubmitting.value = true
  
  try {
    // 1. Capture Image from video
    const canvas = document.createElement('canvas')
    canvas.width = videoRef.value.videoWidth || 640
    canvas.height = videoRef.value.videoHeight || 480
    const ctx = canvas.getContext('2d')
    ctx.drawImage(videoRef.value, 0, 0, canvas.width, canvas.height)
    
    // Convert to blob
    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.8))
    if (!blob) throw new Error('Gagal memproses gambar.')

    // 2. Prepare FormData
    const formData = new FormData()
    formData.append('photo', blob, 'selfie.jpg')
    formData.append('latitude', locationCoords.value.latitude)
    formData.append('longitude', locationCoords.value.longitude)

    // 3. Send to API
    const response = await clockInStaff(formData)
    
    if (response.success) {
      toast.success('Absensi Berhasil', { description: response.message })
      hasClockedIn.value = true
      clockInTime.value = response.data.time_in
      loadAttendanceData()
      stopCamera()
    } else {
      toast.error('Absensi Gagal', { description: response.message })
    }
  } catch (error) {
    console.error(error)
    const errorMsg = error.response?.data?.message || 'Terjadi kesalahan sistem saat melakukan absensi.'
    toast.error('Absensi Gagal', { description: errorMsg })
  } finally {
    isSubmitting.value = false
  }
}

// ─── LIFECYCLE ──────────────────────────────────────────────
onMounted(() => {
  updateClock()
  clockInterval = setInterval(updateClock, 1000)
  requestLocation()
  loadAttendanceData()
})

onUnmounted(() => {
  stopCamera()
  if (clockInterval) clearInterval(clockInterval)
})
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 animate-in fade-in duration-300 w-full pb-10"
  >
    
    <!-- ── Header ── -->
    <PageHeader
      title="Absensi Mandiri Staff"
      :description="`Selamat datang kembali, ${authStore.user?.name ?? 'User'} (${authStore.user?.role === 'guru' ? (authStore.isWaliKelas ? 'Wali Kelas / Guru' : 'Guru Pengajar') : authStore.user?.role === 'kepala_sekolah' ? 'Kepala Sekolah' : authStore.user?.role === 'tata_usaha' ? 'Tata Usaha' : 'Admin Sekolah'})`"
    />

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      
      <!-- ── Left Column: Action Card ── -->
      <div class="md:col-span-1 space-y-6">
        
        <!-- Action Card -->
        <Card
          v-motion
          :initial="glassSlide.initial"
          :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 80 } }"
          class="overflow-hidden relative min-h-[380px] flex flex-col justify-center"
        >
          <!-- decorative bg -->
          <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/50 to-white dark:from-indigo-950/20 dark:to-transparent pointer-events-none"></div>
          
          <!-- NORMAL IDLE MODE -->
          <div class="p-6 relative z-10 flex flex-col items-center text-center">
            
            <div class="text-4xl font-extrabold tracking-tighter text-foreground mb-1 font-mono">
              {{ currentTime }}
            </div>
            <div class="flex items-center gap-1.5 text-sm text-muted-foreground mb-6">
              <CalendarDays class="size-4" />
              <span>{{ currentDate }}</span>
            </div>

            <!-- Geolocation Status Alert Banner -->
            <div 
              class="w-full rounded-lg p-3 flex items-center justify-center gap-2 mb-6 border text-xs font-medium"
              :class="{
                'bg-muted/50 border-border/50': locationStatus === 'loading',
                'bg-green-50 dark:bg-green-950/30 border-green-200 dark:border-green-900 text-green-700 dark:text-green-400': locationStatus === 'success',
                'bg-destructive/10 border-destructive/20 text-destructive': locationStatus === 'error'
              }"
            >
              <MapPin class="size-4 shrink-0" :class="locationStatus === 'success' ? 'text-green-500' : 'text-muted-foreground'" />
              <div class="text-left">
                <p v-if="locationStatus === 'loading'">Mencari lokasi...</p>
                <p v-else-if="locationStatus === 'success'">
                  Lokasi Terdeteksi (Akurasi: {{ Math.round(locationCoords.accuracy) }}m)
                </p>
                <p v-else class="line-clamp-2">{{ locationError }}</p>
              </div>
              <Button 
                v-if="locationStatus === 'error'" 
                variant="outline" 
                size="sm" 
                class="ml-auto h-7 px-2 text-[10px]" 
                @click="requestLocation"
              >
                Coba Lagi
              </Button>
            </div>

            <div class="w-full space-y-3">
              <!-- Clock In Button -->
              <Button 
                v-if="!hasClockedIn"
                @click="openCamera"
                :disabled="locationStatus !== 'success'"
                class="w-full h-12 text-base gap-2 bg-primary hover:bg-primary/90 text-primary-foreground shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5"
              >
                <Camera class="size-5" />
                Clock In (Scan Wajah)
              </Button>

              <!-- Clocked In Status Box -->
              <div v-else class="w-full">
                <div class="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-900 rounded-lg p-4 text-green-700 dark:text-green-400 text-sm flex flex-col items-center gap-2">
                  <UserCheck class="size-8 text-green-600 dark:text-green-400" />
                  <span class="font-bold">Sudah Hadir Hari Ini</span>
                  <span class="text-xs text-muted-foreground">Clock In tercatat pada pukul: {{ clockInTime }}</span>
                </div>
              </div>
            </div>
          </div>
        </Card>

        <!-- Summary Statistics -->
        <Card
          v-motion
          :initial="glassSlide.initial"
          :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 180 } }"
        >
          <CardHeader class="pb-2">
            <CardTitle class="text-sm font-semibold uppercase tracking-wider text-muted-foreground">Ringkasan Bulan Ini</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg">
                  <UserCheck class="size-4" />
                </div>
                <span class="text-sm font-medium">Hadir</span>
              </div>
              <Skeleton v-if="isPageLoading" class="h-6 w-8" />
              <span v-else class="font-bold text-lg">{{ summaryStats.hadir }}</span>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-lg">
                  <AlertCircle class="size-4" />
                </div>
                <span class="text-sm font-medium">Terlambat</span>
              </div>
              <Skeleton v-if="isPageLoading" class="h-6 w-8" />
              <span v-else class="font-bold text-lg">{{ summaryStats.terlambat }}</span>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                  <Coffee class="size-4" />
                </div>
                <span class="text-sm font-medium">Izin / Cuti</span>
              </div>
              <Skeleton v-if="isPageLoading" class="h-6 w-8" />
              <span v-else class="font-bold text-lg">{{ summaryStats.izin_cuti }}</span>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- ── Right Column: Table History ── -->
      <div class="md:col-span-2 space-y-6">
        
        <!-- Top stat cards (Horizontal) -->
        <div class="grid grid-cols-2 gap-4">
          <Card
            v-motion
            :initial="glassSlide.initial"
            :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 120 } }"
            class="p-4 flex items-center gap-4"
          >
            <div class="p-3 bg-primary/10 text-primary rounded-xl">
              <Briefcase class="size-5" />
            </div>
            <div>
              <p class="text-sm text-muted-foreground font-medium">Status Kehadiran</p>
              <Skeleton v-if="isPageLoading" class="h-8 w-20 mt-1" />
              <p v-else class="text-xl font-bold text-primary">
                {{ hasClockedIn ? 'AKTIF' : 'BELUM PRESENSI' }}
              </p>
            </div>
          </Card>
          <Card
            v-motion
            :initial="glassSlide.initial"
            :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 160 } }"
            class="p-4 flex items-center gap-4"
          >
            <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-xl">
              <CalendarDays class="size-5" />
            </div>
            <div>
              <p class="text-sm text-muted-foreground font-medium">Bulan Ini</p>
              <Skeleton v-if="isPageLoading" class="h-8 w-20 mt-1" />
              <p v-else class="text-2xl font-bold">{{ summaryStats.hadir + summaryStats.terlambat }} <span class="text-sm font-normal text-muted-foreground ml-1">Hari Kerja</span></p>
            </div>
          </Card>
        </div>

        <!-- History Table -->
        <Card
          v-motion
          :initial="glassSlide.initial"
          :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 220 } }"
          class="overflow-hidden flex flex-col h-full max-h-[500px]"
        >
          <div class="p-4 border-b bg-muted/20 flex items-center justify-between">
            <h2 class="font-semibold">Riwayat Absensi Terakhir (30 Hari)</h2>
          </div>
          <CardContent class="overflow-auto flex-1 p-0">
            <Table>
              <TableHeader>
                <TableRow class="bg-muted/50 hover:bg-muted/50">
                  <TableHead>Tanggal</TableHead>
                  <TableHead>Masuk</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Tipe Log</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="isPageLoading">
                  <TableRow v-for="i in 3" :key="`skel-log-${i}`">
                    <TableCell><Skeleton class="h-5 w-24" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-12" /></TableCell>
                    <TableCell><Skeleton class="h-6 w-20 rounded-full" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-16" /></TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow 
                    v-for="(log, index) in recentLogs" 
                    :key="log.id"
                    v-motion
                    :initial="tableRowFade.initial"
                    :visible-once="{ ...tableRowFade.visible, transition: { ...tableRowFade.visible.transition, delay: index * 60 } }"
                    class="hover:bg-muted/30 transition-colors"
                  >
                    <TableCell class="font-medium">{{ log.tanggal }}</TableCell>
                    <TableCell class="font-mono text-sm">{{ log.jamMasuk }}</TableCell>
                    <TableCell>
                      <Badge
                        :variant="log.status === 'Hadir' ? 'default' : (log.status === 'Terlambat' ? 'destructive' : 'secondary')"
                        :class="{
                          'bg-green-100 text-green-700 hover:bg-green-100 dark:bg-green-900/40 dark:text-green-400': log.status === 'Hadir',
                          'bg-amber-100 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/40 dark:text-amber-400': log.status === 'Terlambat',
                          'bg-blue-100 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-400': ['Izin', 'Cuti'].includes(log.status),
                        }"
                      >
                        {{ log.status }}
                      </Badge>
                    </TableCell>
                    <TableCell class="capitalize text-muted-foreground text-sm">
                      {{ log.type === 'attendance' ? 'Kehadiran Mandiri' : 'Pengajuan ' + log.status }}
                    </TableCell>
                  </TableRow>
                  
                  <TableRow v-if="recentLogs.length === 0">
                    <TableCell colspan="4" class="h-24 text-center text-muted-foreground">
                      Belum ada riwayat absensi.
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </CardContent>
        </Card>

      </div>
    </div>

  </div>

  <!-- Modal Scan Wajah -->
  <Dialog :open="isScanning" @update:open="(val) => { if(!val) stopCamera() }">
    <DialogContent class="sm:max-w-3xl p-0 overflow-hidden bg-black border-border shadow-2xl">
      <DialogHeader class="sr-only">
        <DialogTitle>Scan Wajah</DialogTitle>
        <DialogDescription>Posisikan wajah Anda pada area pemindaian</DialogDescription>
      </DialogHeader>

      <!-- CAMERA MODE -->
      <div class="relative w-full aspect-[3/4] sm:aspect-[4/3] bg-black flex flex-col">
        <video 
          v-if="cameraStatus === 'active'" 
          ref="videoRef" 
          autoplay 
          playsinline 
          class="absolute inset-0 w-full h-full object-cover" 
        />
        
        <!-- Loading State -->
        <div v-if="cameraStatus === 'loading'" class="absolute inset-0 flex flex-col items-center justify-center text-white/70">
          <div class="w-8 h-8 border-4 border-white/20 border-t-white/80 rounded-full animate-spin mb-3"></div>
          <p class="text-xs">Menyiapkan Kamera...</p>
        </div>
        
        <!-- Error State -->
        <div v-if="cameraStatus === 'error'" class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center bg-black/90">
          <CameraOff class="size-8 text-red-400 mb-3" />
          <p class="text-xs text-red-300 mb-4">{{ cameraError }}</p>
          <Button @click="stopCamera" variant="secondary" size="sm">Batal</Button>
        </div>

        <!-- Overlays (Timestamp & Location Watermark) -->
        <div v-if="cameraStatus === 'active'" class="absolute inset-0 pointer-events-none flex flex-col justify-between">
          
          <div class="flex justify-end p-3">
            <!-- Close handled by dialog outside -->
          </div>

          <!-- Face Guide Box (Rectangular Scanner) -->
          <div class="absolute inset-0 pointer-events-none z-10">
            <!-- Corners -->
            <span class="absolute top-[10%] left-[20%] w-10 h-10 border-t-[3px] border-l-[3px] border-primary rounded-tl-[16px]"></span>
            <span class="absolute top-[10%] right-[20%] w-10 h-10 border-t-[3px] border-r-[3px] border-primary rounded-tr-[16px]"></span>
            <span class="absolute bottom-[35%] left-[20%] w-10 h-10 border-b-[3px] border-l-[3px] border-primary rounded-bl-[16px]"></span>
            <span class="absolute bottom-[35%] right-[20%] w-10 h-10 border-b-[3px] border-r-[3px] border-primary rounded-br-[16px]"></span>
            
            <!-- Scan Line -->
            <div class="cam-scan-line"></div>
          </div>
          
          <div class="flex flex-col gap-3 p-4 z-20">
            <!-- Watermark Box -->
            <div class="bg-black/50 backdrop-blur-md rounded-lg p-3 text-white border border-white/10 shadow-lg">
              <p class="text-sm font-bold text-blue-400">{{ currentDate }} {{ currentTime }}</p>
              <div class="flex items-start gap-1.5 mt-1.5 text-white/90">
                <MapPin class="size-3 mt-0.5 shrink-0 text-red-400" />
                <p class="text-[10px] leading-tight">
                  Lat: {{ locationCoords.latitude?.toFixed(6) }}, Long: {{ locationCoords.longitude?.toFixed(6) }}<br/>
                  Akurasi: {{ Math.round(locationCoords.accuracy) }} Meter
                </p>
              </div>
              <div class="flex items-center gap-1.5 mt-2 pt-2 border-t border-white/10">
                <UserCheck class="size-3 text-green-400" />
                <p class="text-[10px] font-medium text-green-400">{{ authStore.user?.name }}</p>
              </div>
            </div>

            <!-- Action Button -->
            <div class="flex justify-center pointer-events-auto pb-2">
              <Button 
                @click="takePhotoAndSubmit"
                :disabled="isSubmitting"
                class="rounded-full shadow-xl shadow-black/50 bg-primary hover:bg-primary/90 text-primary-foreground font-bold px-8 h-12"
              >
                <div v-if="isSubmitting" class="w-4 h-4 border-2 border-white/20 border-t-white/80 rounded-full animate-spin mr-2"></div>
                <Camera v-else class="size-4 mr-2" />
                {{ isSubmitting ? 'Menyimpan...' : 'Scan Wajah Masuk' }}
              </Button>
            </div>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<style scoped>
.cam-scan-line {
  position: absolute; left: 20%; right: 20%; height: 3px;
  background: linear-gradient(90deg, transparent, var(--primary), transparent);
  animation: kscanGuru 2.5s cubic-bezier(0.4, 0, 0.2, 1) infinite;
  box-shadow: 0 0 12px var(--primary);
  border-radius: 999px;
  z-index: 20;
}
@keyframes kscanGuru {
  0% { top: 10%; opacity: 0; }
  10% { opacity: 1; }
  90% { opacity: 1; }
  100% { top: 65%; opacity: 0; }
}
</style>
