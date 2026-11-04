<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import { ChevronLeft, ChevronRight, Lock, Download, Printer, AlertCircle } from 'lucide-vue-next'
import {
  mockStudents,
  kelasList,
  tahunList,
  waliKelasAssignments,
  academicMonths
} from '../../../data/mockAbsensi'
import { getStudents, getMonthlyGrid, updateMonthlyCell } from '@/services/api/absensi'
import AttendanceTable from '../../../components/AttendanceTable.vue'
import AttendanceLegend from '../../../components/AttendanceLegend.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { glassSlide, glassFade } from '@/config/motion'

const auth = useAuthStore()

// --- Data Siswa ---
const students = ref([])

const isWaliKelas = computed(() => Boolean(auth.user?.is_wali_kelas))

// --- State Filter Aktif ---
const selectedTahun = ref('2026/2027')
const selectedKelas = ref('2-D')

const getCurrentAcademicMonthIdx = () => {
  const currentMonth = new Date().getMonth()
  const mapJsMonthToAcademicIdx = {
    6: 0,  // Juli
    7: 1,  // Agustus
    8: 2,  // September
    9: 3,  // Oktober
    10: 4, // November
    11: 5, // Desember
    0: 6,  // Januari
    1: 7,  // Februari
    2: 8,  // Maret
    3: 9,  // April
    4: 10, // Mei
    5: 11  // Juni
  }
  return mapJsMonthToAcademicIdx[currentMonth] ?? 0
}

// State Filter Aktif (Default to current ongoing academic month)
const activeMonthIdx = ref(getCurrentAcademicMonthIdx())

// --- Lock Class for Wali Kelas ---
watch(selectedTahun, newTahun => {
  if (isWaliKelas.value) {
    selectedKelas.value = waliKelasAssignments[newTahun] || '2-D'
  }
})

const loadGridData = async () => {
  try {
    const resStudents = await getStudents({ kelas: selectedKelas.value })
    students.value = resStudents.map(s => ({
      id: s.id,
      nama: s.nama,
      nis: s.nisn || '-',
      gender: s.gender || (s.jenisKelamin === 'Laki-laki' ? 'L' : (s.jenisKelamin === 'Perempuan' ? 'P' : 'L'))
    }))

    const resGrid = await getMonthlyGrid({
      kelas: selectedKelas.value,
      tahun: selectedTahun.value,
      monthIdx: activeMonthIdx.value
    })
    if (resGrid.success) {
      attendanceMap.value = resGrid.data
    }
  } catch (err) {
    toast.error('Gagal mengambil data absensi')
  }
}

onMounted(() => {
  if (isWaliKelas.value) {
    selectedKelas.value = waliKelasAssignments[selectedTahun.value] || '2-D'
  }
  loadGridData()
})

// --- Computed Date Calculations ---
const currentCalendarYear = computed(() => {
  const startYear = parseInt(selectedTahun.value.split('/')[0])
  const monthInfo = academicMonths[activeMonthIdx.value]
  return startYear + monthInfo.yearOffset
})

const selectedMonthVal = computed(() => {
  return academicMonths[activeMonthIdx.value].val
})

const selectedSemester = computed(() => {
  return academicMonths[activeMonthIdx.value].semester
})

const daysInMonth = computed(() => {
  const year = currentCalendarYear.value
  const month = selectedMonthVal.value
  const totalDays = new Date(year, month + 1, 0).getDate()

  const days = []
  const dayNamesShort = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab']

  for (let d = 1; d <= totalDays; d++) {
    const dateObj = new Date(year, month, d)
    const dayName = dayNamesShort[dateObj.getDay()]
    const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6
    days.push({
      dateNum: d,
      dayName,
      isWeekend
    })
  }
  return days
})

// --- Read-Only Validation (Past Academic Years are Read-Only) ---
const isReadOnly = computed(() => {
  return selectedTahun.value !== '2026/2027'
})

// --- Attendance State Map ---
// Key format: {kelas}_{tahun}_{monthIdx}_{studentId}_{day}
const attendanceMap = ref({})
const printUrl = ref('')

const getAttendanceKey = (kelas, tahun, monthIdx, studentId, day) => {
  return `${kelas}_${tahun}_${monthIdx}_${studentId}_${day}`
}

// Seed random realistic data for the grid
const seedAttendance = () => {
  const currentKelas = selectedKelas.value
  const currentTahun = selectedTahun.value
  const currentMonthIdx = activeMonthIdx.value

  const testKey = getAttendanceKey(currentKelas, currentTahun, currentMonthIdx, 1, 1)
  if (attendanceMap.value[testKey] !== undefined) return

  students.value.forEach(student => {
    const daysCount = daysInMonth.value.length
    for (let d = 1; d <= daysCount; d++) {
      const dateObj = new Date(currentCalendarYear.value, selectedMonthVal.value, d)
      const isWeekend = dateObj.getDay() === 0 || dateObj.getDay() === 6
      const key = getAttendanceKey(currentKelas, currentTahun, currentMonthIdx, student.id, d)

      if (isWeekend) {
        attendanceMap.value[key] = null
      } else {
        // Mock seeding values: 75% Hadir, 5% Sakit, 5% Izin, 3% Alpha, 12% Kosong
        const r = Math.random()
        if (r < 0.75) {
          attendanceMap.value[key] = 'H'
        } else if (r < 0.8) {
          attendanceMap.value[key] = 'S'
        } else if (r < 0.85) {
          attendanceMap.value[key] = 'I'
        } else if (r < 0.88) {
          attendanceMap.value[key] = 'A'
        } else {
          attendanceMap.value[key] = null
        }
      }
    }
  })
}

// Watch filters to trigger loading for new combinations
watch([selectedKelas, selectedTahun, activeMonthIdx], () => {
  loadGridData()
})

const getStatus = (studentId, dayNum) => {
  const key = getAttendanceKey(
    selectedKelas.value,
    selectedTahun.value,
    activeMonthIdx.value,
    studentId,
    dayNum
  )
  return attendanceMap.value[key] || null
}

// --- Toggle Status (H -> T -> I -> S -> A -> ?) ---
const toggleStatus = async (studentId, dayNum) => {
  if (isReadOnly.value) {
    toast.error('Mode Riwayat Aktif', {
      description: 'Absensi di semester atau tahun pelajaran lampau tidak dapat diubah.'
    })
    return
  }

  const key = getAttendanceKey(
    selectedKelas.value,
    selectedTahun.value,
    activeMonthIdx.value,
    studentId,
    dayNum
  )
  const currentVal = attendanceMap.value[key]
  
  // Cycle: null (?) -> H -> T -> I -> S -> A -> null (?)
  let newVal = null
  if (!currentVal) newVal = 'H'
  else if (currentVal === 'H') newVal = 'T'
  else if (currentVal === 'T') newVal = 'I'
  else if (currentVal === 'I') newVal = 'S'
  else if (currentVal === 'S') newVal = 'A'
  else if (currentVal === 'A') newVal = null
  
  try {
    await updateMonthlyCell({
      kelas: selectedKelas.value,
      tahun: selectedTahun.value,
      monthIdx: activeMonthIdx.value,
      studentId,
      dayNum,
      status: newVal
    })
    attendanceMap.value[key] = newVal
    
    // Tampilkan konfirmasi penyimpanan otomatis
    toast.success('Absensi diperbarui', {
      id: 'attendance-update',
      duration: 1500
    })
  } catch (err) {
    toast.error('Gagal memperbarui absensi bulanan.')
  }
}

// Calculate total checkmarks dynamically
const getStudentTotal = (studentId, statusType) => {
  let count = 0
  const daysCount = daysInMonth.value.length
  const currentKelas = selectedKelas.value
  const currentTahun = selectedTahun.value
  const currentMonthIdx = activeMonthIdx.value

  for (let d = 1; d <= daysCount; d++) {
    const key = getAttendanceKey(currentKelas, currentTahun, currentMonthIdx, studentId, d)
    if (attendanceMap.value[key] === statusType) {
      count++
    }
  }
  return count > 0 ? count : ''
}

// --- Month Pager Actions ---
const prevMonth = () => {
  if (activeMonthIdx.value > 0) {
    activeMonthIdx.value--
  } else {
    toast.info('Batas Semester', {
      description: 'Sudah mencapai batas awal semester ganjil pada tahun ajaran ini.'
    })
  }
}

const nextMonth = () => {
  if (activeMonthIdx.value < 11) {
    activeMonthIdx.value++
  } else {
    toast.info('Batas Semester', {
      description: 'Sudah mencapai batas akhir semester genap pada tahun ajaran ini.'
    })
  }
}

const handleExport = () => {
  const currentKelas = selectedKelas.value
  const currentTahun = selectedTahun.value
  const currentMonthIdx = activeMonthIdx.value
  const monthName = academicMonths[currentMonthIdx].name
  const year = currentCalendarYear.value
  const days = daysInMonth.value

  let html = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
      <meta charset="utf-8">
      <style>
        table { border-collapse: collapse; }
        th, td { border: 1px solid black; padding: 5px; text-align: center; vertical-align: middle; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-left { text-align: left; }
      </style>
    </head>
    <body>
      <h3>Laporan Kehadiran Kelas ${currentKelas} - ${monthName} ${year}</h3>
      <table>
        <thead>
          <tr>
            <th rowspan="2">No</th>
            <th rowspan="2" class="text-left">Nama Siswa</th>
            <th rowspan="2">NIS</th>
            <th rowspan="2">L/P</th>
            <th colspan="${days.length}">Tanggal</th>
            <th colspan="5">Jumlah</th>
          </tr>
          <tr>
  `
  days.forEach(d => {
    html += `<th>${d.dateNum}</th>`
  })
  html += `<th>H</th><th>T</th><th>I</th><th>S</th><th>A</th></tr></thead><tbody>`

  students.value.forEach((student, index) => {
    html += `
      <tr>
        <td>${index + 1}</td>
        <td class="text-left">${student.nama}</td>
        <td style="mso-number-format:'\@'">${student.nis}</td>
        <td>${student.gender}</td>
    `
    days.forEach(d => {
      const status = getStatus(student.id, d.dateNum) || ''
      html += `<td>${status}</td>`
    })

    const totalH = getStudentTotal(student.id, 'H')
    const totalT = getStudentTotal(student.id, 'T')
    const totalI = getStudentTotal(student.id, 'I')
    const totalS = getStudentTotal(student.id, 'S')
    const totalA = getStudentTotal(student.id, 'A')

    html += `
        <td>${totalH}</td>
        <td>${totalT}</td>
        <td>${totalI}</td>
        <td>${totalS}</td>
        <td>${totalA}</td>
      </tr>
    `
  })

  html += `</tbody></table></body></html>`

  const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' })
  const url = URL.createObjectURL(blob)

  const link = document.createElement('a')
  link.setAttribute('href', url)

  const sanitizedKelas = currentKelas.replace(/\s+/g, '_')
  link.setAttribute('download', `Absensi_Kelas_${sanitizedKelas}_${monthName}_${year}.xls`)

  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)

  toast.success('Ekspor Excel Berhasil!', {
    description: `Lembar kehadiran kelas ${selectedKelas.value} untuk bulan ${monthName} ${year} telah diunduh.`
  })
}

const handlePrint = () => {
  // Simpan data presensi aktif ke localStorage agar terbaca di tab cetak khusus
  localStorage.setItem('print_attendance_map', JSON.stringify(attendanceMap.value))
  localStorage.setItem('print_students_list', JSON.stringify(students.value))

  const basePath = isWaliKelas.value ? '/absensi/wali-kelas/input-kehadiran/print' : '/absensi/guru/input-kehadiran/print'
  printUrl.value = `${basePath}?kelas=${selectedKelas.value}&tahun=${selectedTahun.value}&monthIdx=${activeMonthIdx.value}&t=${Date.now()}`
}
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 p-1 max-w-full overflow-hidden"
  >
    <!-- Page Header Title -->
    <PageHeader
      title="Kehadiran Siswa"
      description="Isi lembar kehadiran siswa secara bulanan dengan format rekap H (Hadir), S (Sakit), I (Izin), A (Alpha) secara otomatis."
      :actions="[
        {
          label: 'Ekspor Data',
          icon: Download,
          variant: 'outline',
          click: handleExport
        },
        {
          label: 'Cetak Halaman',
          icon: Printer,
          variant: 'outline',
          click: handlePrint
        }
      ]"
    />

    <!-- Active Class Information Box (Wireframe Match) -->
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_350px] gap-6">
      <div class="space-y-6 min-w-0">
        
        <!-- Wireframe Info Panel & Pagers -->
        <Card
          v-motion
          :initial="glassSlide.initial"
          :visible-once="{
            ...glassSlide.visible,
            transition: { ...glassSlide.visible.transition, delay: 100 }
          }"
          class="rounded-2xl border-border bg-card shadow-xs overflow-hidden"
        >
          <CardContent class="p-6 space-y-6">
            <div
              class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6"
            >
              <!-- Info Box (Match wireframe left block) -->
              <div class="space-y-2.5 text-left border-l-4 border-primary pl-4 py-1">
                <div class="text-lg font-bold text-foreground flex items-center gap-2">
                  <span>Kelas : {{ selectedKelas }}</span>
                  <span
                    v-if="isWaliKelas"
                    class="inline-flex items-center gap-1 text-[10px] bg-primary/10 text-primary border border-primary/20 px-2 py-0.5 rounded-full font-bold"
                  >
                    <Lock class="w-3 h-3" /> Wali Kelas
                  </span>
                </div>
                <div class="text-sm font-semibold text-muted-foreground">
                  Tahun Pelajaran : <span class="text-foreground/95">{{ selectedTahun }}</span> -
                  Semester {{ selectedSemester }}
                </div>
                <div class="text-sm font-semibold text-muted-foreground">
                  Bulan :
                  <span class="text-foreground/95"
                    >{{ academicMonths[activeMonthIdx].name }} {{ currentCalendarYear }}</span
                  >
                </div>
              </div>

              <!-- Pagers & Selectors (Right) -->
              <div class="flex flex-wrap items-center gap-4 w-full md:w-auto">
                <!-- Select Tahun Pelajaran -->
                <div class="flex flex-col gap-1.5 text-left">
                  <label
                    class="text-[10px] uppercase font-bold tracking-wider text-muted-foreground"
                    >Tahun Pelajaran</label
                  >
                  <Select v-model="selectedTahun">
                    <SelectTrigger class="w-[160px] h-10 rounded-xl">
                      <SelectValue placeholder="Pilih Tahun" />
                    </SelectTrigger>
                    <SelectContent class="rounded-xl bg-card border-border shadow-md">
                      <SelectItem
                        v-for="t in tahunList"
                        :key="t"
                        :value="t"
                        >{{ t }}</SelectItem
                      >
                    </SelectContent>
                  </Select>
                </div>

                <!-- Pager Month Slider -->
                <div class="flex flex-col gap-1.5 text-left">
                  <label
                    class="text-[10px] uppercase font-bold tracking-wider text-muted-foreground"
                    >Slide Bulan</label
                  >
                  <div
                    class="flex items-center bg-muted/60 p-0.5 rounded-xl border border-border/50"
                  >
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-9 w-9 rounded-lg hover:bg-card cursor-pointer"
                      @click="prevMonth"
                    >
                      <ChevronLeft class="size-4" />
                    </Button>
                    <div
                      class="px-3 min-w-[110px] text-center font-bold text-xs text-foreground uppercase tracking-wide"
                    >
                      {{ academicMonths[activeMonthIdx].name }}
                    </div>
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-9 w-9 rounded-lg hover:bg-card cursor-pointer"
                      @click="nextMonth"
                    >
                      <ChevronRight class="size-4" />
                    </Button>
                  </div>
                </div>

                <!-- Select Kelas (Disabled for Wali Kelas) -->
                <div class="flex flex-col gap-1.5 text-left">
                  <label
                    class="text-[10px] uppercase font-bold tracking-wider text-muted-foreground"
                    >Pilih Kelas</label
                  >
                  <Select
                    v-model="selectedKelas"
                    :disabled="isWaliKelas"
                  >
                    <SelectTrigger class="w-[120px] h-10 rounded-xl">
                      <SelectValue placeholder="Pilih Kelas" />
                    </SelectTrigger>
                    <SelectContent class="rounded-xl bg-card border-border shadow-md">
                      <SelectItem
                        v-for="k in kelasList"
                        :key="k"
                        :value="k"
                        >Kelas {{ k }}</SelectItem
                      >
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>

            <!-- Read Only Banner -->
            <div
              v-if="isReadOnly"
              class="flex items-center gap-3 bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 p-4 rounded-xl text-sm"
            >
              <AlertCircle class="w-5 h-5 shrink-0" />
              <div class="text-left font-medium">
                <span class="font-bold">Mode Riwayat Aktif:</span> Lembar absensi ini dikunci (Hanya
                Lihat). Anda tidak dapat memodifikasi data presensi di tahun ajaran atau semester
                masa lalu.
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Attendance Spreadsheet-like Table Grid -->
        <Card
          v-motion
          :initial="glassSlide.initial"
          :visible-once="{
            ...glassSlide.visible,
            transition: { ...glassSlide.visible.transition, delay: 200 }
          }"
          class="rounded-2xl border-border bg-card shadow-sm overflow-hidden"
        >
          <CardContent class="p-0">
            <AttendanceTable
              :students="students"
              :days-in-month="daysInMonth"
              :is-read-only="isReadOnly"
              :get-status="getStatus"
              :get-student-total="getStudentTotal"
              @toggle-status="toggleStatus"
            />
          </CardContent>
        </Card>
      </div>

      <!-- Right Side Legend/Helper Info Box -->
      <AttendanceLegend
        v-motion
        :initial="glassSlide.initial"
        :visible-once="{
          ...glassSlide.visible,
          transition: { ...glassSlide.visible.transition, delay: 300 }
        }"
        :is-read-only="isReadOnly"
      />
    </div>

    <!-- Hidden iframe for same-page print preview -->
    <iframe
      v-if="printUrl"
      :src="printUrl"
      style="
        position: absolute;
        left: -9999px;
        top: -9999px;
        width: 1px;
        height: 1px;
        overflow: hidden;
        border: none;
      "
    ></iframe>
  </div>
</template>
