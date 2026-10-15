<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import {
  Camera,
  Database,
  Download,
  Printer,
  Info,
  MoreVertical,
  RefreshCw,
  UserCheck,
  Clock,
  CheckCircle2,
  AlertCircle,
  XCircle,
  UserX,
  HelpCircle,
  ShieldCheck,
  Activity,
  ListFilter,
  Users,
  CheckCircle,
  CalendarDays,
  ArrowLeft,
  ArrowRight,
  Smile,
  RotateCcw,
  Sparkles,
  Eye,
  Pencil,
  CreditCard,
  IdCard,
  Target,
  Radio,
  Volume2,
  VolumeX,
} from 'lucide-vue-next'
import {
  getStudents,
  getLogs,
  updateStudentStatus,
  registerStudentFace,
  registerStudentRfid,
  scanRfid,
} from '@/services/api/absensi'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { toast } from 'vue-sonner'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter,
} from '@/components/ui/dialog'
import { useAuthStore } from '@/stores/authStore'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import { usePagination } from '@/composables/usePagination'
import { Badge } from '@/components/ui/badge'
import PageHeader from '@/components/page-header/PageHeader.vue'
import StatCard from '@/components/stat-card/StatCard.vue'
import StatCardGrid from '@/components/stat-card/StatCardGrid.vue'
import { glassFade } from '@/config/motion'
import { kelasList } from '../../../data/mockAbsensi'

const router = useRouter()
const auth = useAuthStore()

// ─── Tab State ───────────────────────────────────────────
const activeTab = ref('rekap') // 'rekap' | 'logs'

// ─── Filter State ────────────────────────────────────────
const filterValues = ref({
  search: '',
  kelas: 'semua',
  status: 'semua',
})

const statusOptions = [
  { label: 'Semua Status Registrasi', value: 'semua' },
  { label: 'Wajah Terdaftar (FaceID)', value: 'wajah_terdaftar' },
  { label: 'Wajah Belum Terdaftar', value: 'wajah_belum' },
]

const filters = computed(() => {
  return [
    { type: 'search', key: 'search', placeholder: 'Cari Nama atau NISN...' },
    {
      type: 'select',
      key: 'kelas',
      label: 'Kelas:',
      placeholder: 'Semua Kelas',
      options: [
        { label: 'Semua Kelas', value: 'semua' },
        ...kelasList.map((k) => ({ label: k, value: k })),
      ],
    },
    {
      type: 'select',
      key: 'status',
      label: 'Status Registrasi:',
      placeholder: 'Semua Status Registrasi',
      options: statusOptions,
    },
  ]
})

// ─── Table Columns ───────────────────────────────────────
const columns = computed(() => [
  { key: 'nama', label: 'Nama Siswa & Kelas' },
  { key: 'status_registrasi', label: 'Status Biometrik & Perangkat' },
  { key: 'actions', label: 'Aksi' },
])

const logColumns = computed(() => [
  { key: 'waktu', label: 'Waktu Scan', type: 'code' },
  { key: 'nama', label: 'Siswa & Kelas' },
  { key: 'nisn', label: 'NISN' },
  { key: 'tipe', label: 'Tipe Absen' },
])

// ─── Data & Loading State ────────────────────────────────
const absensiData = ref([])
const logsData = ref([])
const isLoading = ref(true)
const isError = ref(false)
const errorMessage = ref('')
let pollInterval = null

async function loadData(isPolling = false) {
  if (!isPolling) {
    isLoading.value = true
    isError.value = false
  }
  try {
    const [students, logs] = await Promise.all([
      getStudents(),
      getLogs(),
    ])
    absensiData.value = students || []
    logsData.value = logs || []
    isError.value = false
  } catch (err) {
    if (!isPolling) {
      isError.value = true
      errorMessage.value = err.message || 'Terjadi kesalahan sistem saat memuat data'
    }
  } finally {
    if (!isPolling) isLoading.value = false
  }
}

onMounted(() => {
  loadData()
  // Background polling every 5s for real-time synchronization with Scanner Kiosk
  pollInterval = setInterval(() => loadData(true), 5000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

// ─── Filtered Data & Pagination ──────────────────────────
const filteredData = computed(() => {
  return absensiData.value.filter((item) => {
    const matchKelas =
      filterValues.value.kelas === 'semua' ||
      item.kelas === filterValues.value.kelas
    const matchStatus =
      filterValues.value.status === 'semua' ||
      (filterValues.value.status === 'wajah_terdaftar' && item.is_face_registered) ||
      (filterValues.value.status === 'wajah_belum' && !item.is_face_registered)
    const searchLower = filterValues.value.search.toLowerCase()
    const matchSearch =
      !filterValues.value.search ||
      item.nama.toLowerCase().includes(searchLower) ||
      (item.nisn && item.nisn.toLowerCase().includes(searchLower))

    return matchKelas && matchStatus && matchSearch
  })
})

const filteredLogs = computed(() => {
  if (!filterValues.value.search) return logsData.value
  const searchLower = filterValues.value.search.toLowerCase()
  return logsData.value.filter(
    (log) =>
      log.nama.toLowerCase().includes(searchLower) ||
      (log.nisn && log.nisn.toLowerCase().includes(searchLower)) ||
      (log.kelas && log.kelas.toLowerCase().includes(searchLower))
  )
})

// Stats Computation
const totalSiswa = computed(() => absensiData.value.length)
const wajahTerdaftar = computed(() => absensiData.value.filter((d) => d.is_face_registered).length)
const rfidTerhubung = computed(() => absensiData.value.filter((d) => d.is_rfid_registered || d.rfid_number || d.rfid_uid).length)
const belumTerdaftar = computed(() => absensiData.value.filter((d) => !d.is_face_registered && !d.is_rfid_registered && !d.rfid_number && !d.rfid_uid).length)

const perPage = ref(10)
const { currentPage, total, from, to, paginatedItems: paginatedData } = usePagination(filteredData, perPage)

// Reset page to 1 ONLY when user actively changes search/filter parameters, NOT on background auto-sync
watch(
  () => [filterValues.value.search, filterValues.value.kelas, filterValues.value.status],
  () => {
    currentPage.value = 1
  }
)

// ─── Registrasi RFID Modal State ──────────────────────────
const isRfidRegisterModalOpen = ref(false)
const selectedStudentForRfid = ref(null)
const rfidInput = ref('')
const isSubmittingRfid = ref(false)
const rfidInputRef = ref(null)

function sanitizeRfidUid(val) {
  if (!val) return ''
  const str = val.trim()

  // 1. Check if string is a repeated pattern of an 8-12 char UID (e.g. "0006562226" repeated 2x, 3x, 4x)
  const match = str.match(/^([a-zA-Z0-9]{8,12})\1+$/)
  if (match) {
    return match[1]
  }

  // 2. Check if string contains multiple repeating chunks of length 8 to 12
  for (let len = 10; len >= 8; len--) {
    if (str.length >= len * 2) {
      const chunk = str.substring(0, len)
      if (str.replaceAll(chunk, '') === '') {
        return chunk
      }
    }
  }

  // 3. Fallback: If string is longer than 12 characters and starts with digits/hex
  if (str.length > 12) {
    const firstBlock = str.match(/^[a-zA-Z0-9]{8,10}/)
    if (firstBlock) {
      return firstBlock[0]
    }
  }

  return str
}

function focusRfidInput() {
  nextTick(() => {
    const inputEl = rfidInputRef.value?.$el?.querySelector?.('input') || rfidInputRef.value?.$el || rfidInputRef.value
    inputEl?.focus?.()
    inputEl?.select?.()
  })
}

function openRfidRegistration(student) {
  selectedStudentForRfid.value = student
  // Start with empty input so tapping replaces cleanly instead of appending to existing text
  rfidInput.value = ''
  isRfidRegisterModalOpen.value = true
  focusRfidInput()
}

function closeRfidRegistration() {
  isRfidRegisterModalOpen.value = false
  selectedStudentForRfid.value = null
  rfidInput.value = ''
  isSubmittingRfid.value = false
}

async function handleRfidRegistrationSubmit() {
  if (isSubmittingRfid.value) return // Prevent multiple submissions from fast taps
  
  const cleanUid = sanitizeRfidUid(rfidInput.value)
  if (!selectedStudentForRfid.value || !cleanUid) {
    toast.error('Gagal Registrasi', { description: 'Silakan tempelkan kartu RFID atau ketik nomor UID kartu!' })
    return
  }

  isSubmittingRfid.value = true
  try {
    const res = await registerStudentRfid(selectedStudentForRfid.value.id, cleanUid)
    if (res.success) {
      toast.success('Registrasi RFID Sukses! 💳', {
        description: res.message
      })
      // Update local item
      const item = absensiData.value.find(s => s.id === selectedStudentForRfid.value.id)
      if (item) {
        item.rfid_number = cleanUid
        item.rfid_uid = cleanUid
        item.is_rfid_registered = true
      }
      
      // Update the selectedStudentForRfid as well to reflect immediately if the modal stays open
      selectedStudentForRfid.value.rfid_number = cleanUid
      selectedStudentForRfid.value.rfid_uid = cleanUid
      selectedStudentForRfid.value.is_rfid_registered = true
      
      closeRfidRegistration()
      await loadData(true) // Force refresh list to ensure connection status propagates
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal mendaftarkan kartu RFID.'
    toast.error('Registrasi RFID Gagal', { description: msg })
    rfidInput.value = ''
    focusRfidInput()
  } finally {
    isSubmittingRfid.value = false
  }
}

// ─── Show Detail Modal State ──────────────────────────────
const isShowModalOpen = ref(false)
const detailStudent = ref(null)

function openDetailModal(student) {
  detailStudent.value = student
  isShowModalOpen.value = true
}

function closeDetailModal() {
  isShowModalOpen.value = false
  detailStudent.value = null
}

// ─── Edit Data & RFID Modal State ─────────────────────────
const isEditModalOpen = ref(false)
const editStudent = ref(null)
const rfidCardUid = ref('')

function openEditModal(student) {
  editStudent.value = student
  rfidCardUid.value = student.rfid_number || student.rfid_uid || ''
  isEditModalOpen.value = true
}

function closeEditModal() {
  isEditModalOpen.value = false
  editStudent.value = null
  rfidCardUid.value = ''
}

function saveEditModal() {
  if (!editStudent.value) return
  editStudent.value.rfid_number = rfidCardUid.value
  editStudent.value.rfid_uid = rfidCardUid.value
  editStudent.value.is_rfid_registered = !!rfidCardUid.value
  toast.success('Data RFID Berhasil Diperbarui', {
    description: `Nomor kartu RFID untuk ${editStudent.value.nama} telah disimpan.`
  })
  closeEditModal()
}

// ─── Action Handlers ─────────────────────────────────────
function openScanTab() {
  const route = router.resolve('/absensi/siswa/scan')
  window.open(route.href, '_blank')
}

function getStatusLabel(status) {
  switch (status) {
    case 'hadir': return 'Hadir'
    case 'terlambat': return 'Terlambat'
    case 'izin': return 'Izin'
    case 'sakit': return 'Sakit'
    case 'alpa': return 'Tanpa Keterangan'
    default: return 'Belum Absen'
  }
}

function getInitials(nama) {
  if (!nama) return 'S'
  const words = nama.trim().split(/\s+/)
  if (words.length === 1) return words[0].substring(0, 2).toUpperCase()
  return (words[0][0] + words[1][0]).toUpperCase()
}

function getPhotoUrl(photo) {
  if (!photo) return null
  if (photo.startsWith('http://') || photo.startsWith('https://')) return photo
  const cleanPath = photo.startsWith('/') ? photo : `/${photo}`
  return `http://localhost:8000${cleanPath}`
}

async function changeStatus(studentId, newStatus) {
  try {
    await updateStudentStatus(studentId, newStatus)
    toast.success('Status Berhasil Diperbarui', {
      description: `Status presensi telah diubah menjadi ${getStatusLabel(newStatus)}.`
    })
    await loadData(true)
  } catch (err) {
    toast.error('Gagal Mengubah Status', {
      description: err.message || 'Terjadi kesalahan sistem'
    })
  }
}

// ─── Face Registration Setup (Apple FaceID Multi-Angle Stepper) ─────────────────────────────
const isRegisterModalOpen = ref(false)
const selectedStudent = ref(null)
const videoRef = ref(null)
const canvasRef = ref(null)
const cameraStatus = ref('idle') // idle | loading | active | error
const isRegistering = ref(false)
let mediaStream = null

const faceSteps = [
  { key: 'front', label: 'Hadap Depan', desc: 'Menghadap lurus ke depan', hint: 'Posisikan wajah di dalam lingkaran' },
  { key: 'left', label: 'Tengok Kiri', desc: 'Tengokkan kepala sedikit ke kiri', hint: 'Kemiringan sekitar 20 derajat' },
  { key: 'right', label: 'Tengok Kanan', desc: 'Tengokkan kepala sedikit ke kanan', hint: 'Kemiringan sekitar 20 derajat' },
  { key: 'smile', label: 'Tersenyum', desc: 'Harap tersenyum ke kamera', hint: 'Ekspresi ramah' }
]

const currentStepIdx = ref(0)
const capturedBlobs = ref({ front: null, left: null, right: null, smile: null })
const capturedPreviews = ref({ front: null, left: null, right: null, smile: null })

const isAllSamplesCaptured = computed(() => {
  return (
    capturedBlobs.value.front &&
    capturedBlobs.value.left &&
    capturedBlobs.value.right &&
    capturedBlobs.value.smile
  )
})

import * as faceapi from 'face-api.js'

// ─── Auto-Detect & Pose Guided State ─────────────────────────────
const detectProgress = ref(0) // 0 to 100
const isAutoDetectActive = ref(true)
const autoDetectHint = ref('Mohon hadap lurus ke depan 🎯')
const isPoseMatched = ref(false)
const isFlashing = ref(false)
const isAiModelLoaded = ref(false)
const aiModelStatusText = ref('')
const isStepTransitioning = ref(false)
let detectTimer = null

function triggerFlashEffect() {
  isFlashing.value = true
  setTimeout(() => {
    isFlashing.value = false
  }, 300)
}

async function loadAiModels() {
  if (isAiModelLoaded.value) return true
  aiModelStatusText.value = 'Memuat AI Face Recognition...'
  try {
    const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model'
    await Promise.all([
      faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
      faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
      faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL)
    ])
    isAiModelLoaded.value = true
    aiModelStatusText.value = 'AI Face Engine Siap!'
    return true
  } catch (err) {
    console.warn('Gagal memuat model CDN, mencoba fallback...', err)
    try {
      const FALLBACK_URL = 'https://justadudewhohacks.github.io/face-api.js/models'
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(FALLBACK_URL),
        faceapi.nets.faceLandmark68Net.loadFromUri(FALLBACK_URL),
        faceapi.nets.faceExpressionNet.loadFromUri(FALLBACK_URL)
      ])
      isAiModelLoaded.value = true
      return true
    } catch (e) {
      aiModelStatusText.value = 'AI Detector Fallback'
      return false
    }
  }
}

function evaluatePoseWithAi(detection, currentStepKey) {
  if (!detection) {
    return { valid: false, message: 'Wajah tidak terdeteksi di kamera 🎯' }
  }

  const landmarks = detection.landmarks
  const nose = landmarks.getNose()[3]
  const leftEye = landmarks.getLeftEye()
  const rightEye = landmarks.getRightEye()

  const eyeCenterX = (leftEye[0].x + rightEye[rightEye.length - 1].x) / 2
  const noseOffset = nose.x - eyeCenterX
  const eyeDist = Math.abs(rightEye[rightEye.length - 1].x - leftEye[0].x)
  const relativeYaw = noseOffset / (eyeDist + 0.001)

  const expressions = detection.expressions
  const happyScore = expressions ? expressions.happy : 0

  switch (currentStepKey) {
    case 'front': {
      const isFront = Math.abs(relativeYaw) < 0.10
      if (isFront) {
        return { valid: true, message: `Posisi Lurus Sesuai. Menahan... ${Math.round(detectProgress.value)}%` }
      } else {
        return { valid: false, message: 'Mohon hadap lurus ke depan' }
      }
    }
    case 'left': {
      if (relativeYaw < -0.08) {
        return { valid: false, message: 'Wajah terdeteksi ke kanan. Mohon tengokkan kepala ke kiri' }
      }
      const isLeft = relativeYaw > 0.18
      if (isLeft) {
        return { valid: true, message: `Sudut Kiri Terdeteksi. Menahan... ${Math.round(detectProgress.value)}%` }
      } else {
        return { valid: false, message: 'Mohon tengokkan kepala ke kiri' }
      }
    }
    case 'right': {
      if (relativeYaw > 0.08) {
        return { valid: false, message: 'Wajah terdeteksi ke kiri. Mohon tengokkan kepala ke kanan' }
      }
      const isRight = relativeYaw < -0.18
      if (isRight) {
        return { valid: true, message: `Sudut Kanan Terdeteksi. Menahan... ${Math.round(detectProgress.value)}%` }
      } else {
        return { valid: false, message: 'Mohon tengokkan kepala ke kanan' }
      }
    }
    case 'smile': {
      const isSmile = happyScore > 0.45
      if (isSmile) {
        return { valid: true, message: `Senyum Terdeteksi. Menahan... ${Math.round(detectProgress.value)}%` }
      } else {
        return { valid: false, message: 'Mohon tersenyum manis ke kamera' }
      }
    }
    default:
      return { valid: false, message: 'Posisikan wajah di dalam lingkaran' }
  }
}

async function startAutoDetector() {
  stopAutoDetector()
  detectProgress.value = 0

  await loadAiModels()

  detectTimer = setInterval(async () => {
    if (cameraStatus.value !== 'active' || isRegistering.value || !isRegisterModalOpen.value || !isAutoDetectActive.value) {
      return
    }

    if (isAllSamplesCaptured.value) {
      stopAutoDetector()
      return
    }

    if (isStepTransitioning.value) {
      isPoseMatched.value = false
      autoDetectHint.value = 'Persiapan langkah berikutnya...'
      detectProgress.value = 0
      return
    }

    if (videoRef.value) {
      const video = videoRef.value

      if (video.videoWidth > 0 && video.videoHeight > 0) {
        let evaluation = { valid: false, message: 'Mencari posisi wajah...' }

        try {
          if (isAiModelLoaded.value) {
            const detection = await faceapi
              .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 }))
              .withFaceLandmarks()
              .withFaceExpressions()

            const currentStepKey = faceSteps[currentStepIdx.value]?.key
            evaluation = evaluatePoseWithAi(detection, currentStepKey)
          }
        } catch (err) {
          console.warn('Face AI error:', err)
        }

        isPoseMatched.value = evaluation.valid
        autoDetectHint.value = evaluation.message

        if (evaluation.valid) {
          detectProgress.value = Math.min(100, detectProgress.value + 35)

          if (detectProgress.value >= 100) {
            triggerFlashEffect()
            captureCurrentSample()
            detectProgress.value = 0
            isPoseMatched.value = false

            nextTick(() => {
              if (isAllSamplesCaptured.value) {
                stopAutoDetector()
                submitMultiAngleRegistration()
              }
            })
          }
        } else {
          detectProgress.value = Math.max(0, detectProgress.value - 20)
        }
      }
    }
  }, 250)
}

function stopAutoDetector() {
  if (detectTimer) {
    clearInterval(detectTimer)
    detectTimer = null
  }
  detectProgress.value = 0
  isPoseMatched.value = false
}

async function startCamera() {
  cameraStatus.value = 'loading'
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: { ideal: 640 }, height: { ideal: 480 } },
      audio: false
    })
    mediaStream = stream
    cameraStatus.value = 'active'
    await nextTick()
    if (videoRef.value) {
      videoRef.value.srcObject = stream
      videoRef.value.play()
    }
    startAutoDetector()
  } catch (err) {
    cameraStatus.value = 'error'
    toast.error('Gagal mengakses kamera. Mohon aktifkan izin kamera.')
  }
}

function stopCamera() {
  stopAutoDetector()
  if (mediaStream) {
    mediaStream.getTracks().forEach(track => track.stop())
    mediaStream = null
  }
  if (videoRef.value) videoRef.value.srcObject = null
  cameraStatus.value = 'idle'
}

function openFaceRegistration(student) {
  selectedStudent.value = student
  isRegisterModalOpen.value = true
  currentStepIdx.value = 0
  capturedBlobs.value = { front: null, left: null, right: null, smile: null }
  capturedPreviews.value = { front: null, left: null, right: null, smile: null }
  startCamera()
}

function closeFaceRegistration() {
  stopCamera()
  isRegisterModalOpen.value = false
  selectedStudent.value = null
  currentStepIdx.value = 0
  capturedBlobs.value = { front: null, left: null, right: null, smile: null }
  capturedPreviews.value = { front: null, left: null, right: null, smile: null }
}

function resetFaceRegistration() {
  currentStepIdx.value = 0
  capturedBlobs.value = { front: null, left: null, right: null, smile: null }
  capturedPreviews.value = { front: null, left: null, right: null, smile: null }
  startAutoDetector()
}

async function captureCurrentSample() {
  if (!videoRef.value || !canvasRef.value) return

  const video = videoRef.value
  const canvas = canvasRef.value
  const context = canvas.getContext('2d')

  const size = Math.min(video.videoWidth, video.videoHeight)
  const sx = (video.videoWidth - size) / 2
  const sy = (video.videoHeight - size) / 2

  canvas.width = 480
  canvas.height = 480
  context.drawImage(video, sx, sy, size, size, 0, 0, 480, 480)

  const currentStep = faceSteps[currentStepIdx.value]
  const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.85))
  const previewUrl = canvas.toDataURL('image/jpeg', 0.85)

  capturedBlobs.value[currentStep.key] = blob
  capturedPreviews.value[currentStep.key] = previewUrl

  if (currentStepIdx.value < faceSteps.length - 1) {
    currentStepIdx.value++
    isStepTransitioning.value = true
    detectProgress.value = 0
    setTimeout(() => {
      isStepTransitioning.value = false
    }, 850)
  } else {
    // 4th sample captured — auto submit immediately without manual button click
    isStepTransitioning.value = false
    detectProgress.value = 100
    nextTick(() => {
      submitMultiAngleRegistration()
    })
  }
}

async function submitMultiAngleRegistration() {
  if (!selectedStudent.value || !isAllSamplesCaptured.value) return
  isRegistering.value = true

  try {
    const formData = new FormData()
    formData.append('images[front]', capturedBlobs.value.front, 'front.jpg')
    formData.append('images[left]', capturedBlobs.value.left, 'left.jpg')
    formData.append('images[right]', capturedBlobs.value.right, 'right.jpg')
    formData.append('images[smile]', capturedBlobs.value.smile, 'smile.jpg')

    const res = await registerStudentFace(selectedStudent.value.id, formData)
    if (res.success) {
      toast.success('Registrasi Biometrik Sukses', {
        description: `4 Sampel sudut wajah ${selectedStudent.value.nama} berhasil didaftarkan!`
      })
      closeFaceRegistration()
      await loadData(true)
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal meregistrasi biometrik wajah.'
    toast.error('Registrasi Gagal', { description: msg })
  } finally {
    isRegistering.value = false
  }
}

const tableActions = computed(() => [
  {
    label: 'Refresh Data',
    icon: RefreshCw,
    variant: 'outline',
    click: () => loadData()
  }
])
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 animate-in fade-in duration-300"
  >
    <!-- ── Page Header ── -->
    <PageHeader
      title="Pendaftaran Absensi & Biometrik Siswa"
      description="Kelola pendaftaran biometrik wajah (FaceID), nomor kartu RFID, dan status perangkat absensi siswa."
      :actions="[
        {
          label: 'Buka Monitoring Absensi',
          icon: Camera,
          variant: 'default',
          click: openScanTab
        }
      ]"
    />

    <!-- ── Stat Cards ── -->
    <StatCardGrid cols="4">
      <StatCard
        label="Total Siswa"
        :value="isLoading ? '-' : totalSiswa"
        :icon="Users"
        illustration="graduation_cap"
        variant="primary"
      />
      <StatCard
        label="Wajah Terdaftar (FaceID)"
        :value="isLoading ? '-' : wajahTerdaftar"
        :icon="Sparkles"
        illustration="school_bell"
        variant="emerald"
      />
      <StatCard
        label="Kartu RFID Terhubung"
        :value="isLoading ? '-' : rfidTerhubung"
        :icon="CreditCard"
        illustration="ruler"
        variant="blue"
      />
      <StatCard
        label="Belum Terdaftar Biometrik"
        :value="isLoading ? '-' : belumTerdaftar"
        :icon="UserX"
        illustration="paper_sheet"
        variant="amber"
      />
    </StatCardGrid>

    <!-- ── Custom Glass Navigation Tabs ── -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b border-slate-200 dark:border-slate-800 pb-3">
      <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 w-full sm:w-auto p-1 bg-slate-100 dark:bg-slate-900/60 rounded-xl border border-slate-200/60 dark:border-slate-800">
        <button
          @click="activeTab = 'rekap'"
          class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer"
          :class="activeTab === 'rekap' 
            ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200/50 dark:border-slate-700' 
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
        >
          <Users class="size-4" />
          Daftar Biometrik Siswa
          <Badge variant="secondary" class="ml-1 text-[10px] px-1.5 py-0.2">
            {{ filteredData.length }}
          </Badge>
        </button>

        <button
          @click="activeTab = 'logs'"
          class="flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold transition-all duration-200 cursor-pointer"
          :class="activeTab === 'logs' 
            ? 'bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 shadow-sm border border-slate-200/50 dark:border-slate-700' 
            : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
        >
          <Activity class="size-4" />
          Riwayat Absensi Siswa Hari Ini
          <Badge variant="secondary" class="ml-1 text-[10px] px-1.5 py-0.2 bg-indigo-500/10 text-indigo-500">
            {{ filteredLogs.length }}
          </Badge>
        </button>
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
        <Button variant="ghost" size="sm" class="h-8 gap-1.5 text-xs text-slate-600 dark:text-slate-300 cursor-pointer" @click="loadData(false)">
          <RefreshCw class="size-3.5" :class="{ 'animate-spin': isLoading }" />
          Refresh
        </Button>
      </div>
    </div>

    <!-- ── TAB 1: REKAP BIOMETRIK SISWA ── -->
    <div v-if="activeTab === 'rekap'">
      <DataTableCard
        :columns="columns"
        :items="paginatedData"
        :filters="filters"
        :actions="tableActions"
        v-model:filterValues="filterValues"
        v-model:perPage="perPage"
        illustration="school_bell"
        :from="from"
        :to="to"
        :total="total"
        :page="currentPage"
        @update:page="currentPage = $event"
      >
        <template #cell-nama="{ item }">
          <div class="flex items-center gap-3">
            <div class="relative size-10 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center shrink-0">
              <img v-if="item.foto" :src="getPhotoUrl(item.foto)" :alt="item.nama" class="size-full object-cover" />
              <span v-else class="font-bold text-xs text-indigo-600 dark:text-indigo-400">
                {{ getInitials(item.nama) }}
              </span>
            </div>
            <div class="flex flex-col text-left">
              <span class="font-semibold text-sm text-slate-900 dark:text-slate-100 leading-tight">
                {{ item.nama }}
              </span>
              <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                <span>NISN: <code class="font-mono">{{ item.nisn || '-' }}</code></span>
                <span>•</span>
                <span class="font-medium text-slate-700 dark:text-slate-300">Kelas {{ item.kelas || '-' }}</span>
              </div>
            </div>
          </div>
        </template>

        <template #cell-status_registrasi="{ item }">
          <div class="flex items-center gap-2 flex-wrap">
            <Badge
              :variant="item.is_face_registered ? 'default' : 'secondary'"
              :class="item.is_face_registered 
                ? 'bg-emerald-500/10 text-emerald-600 hover:bg-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-400 border border-emerald-500/30' 
                : 'bg-amber-500/10 text-amber-600 hover:bg-amber-500/20 dark:bg-amber-500/20 dark:text-amber-400 border border-amber-500/30'"
              class="text-[11px] gap-1 px-2.5 py-0.5 font-semibold"
            >
              <ShieldCheck v-if="item.is_face_registered" class="size-3" />
              <UserX v-else class="size-3" />
              {{ item.is_face_registered ? 'Wajah Terdaftar (AI)' : 'Wajah Belum Terdaftar' }}
            </Badge>

            <Badge
              v-if="item.is_rfid_registered || item.rfid_number || item.rfid_uid"
              class="bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400 border border-indigo-500/30 text-[11px] gap-1 px-2.5 py-0.5 font-mono font-medium"
            >
              <CreditCard class="size-3" />
              RFID: {{ item.rfid_number || item.rfid_uid }}
            </Badge>

            <Badge
              v-else
              class="bg-slate-100 text-slate-500 dark:bg-slate-800/60 dark:text-slate-400 border border-slate-200 dark:border-slate-700 text-[11px] gap-1 px-2.5 py-0.5 font-mono font-medium"
            >
              <CreditCard class="size-3" />
              RFID: Belum
            </Badge>
          </div>
        </template>

        <template #cell-actions="{ item }">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" class="size-8 cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-800">
                <span class="sr-only">Buka menu</span>
                <MoreVertical class="size-4 text-slate-600 dark:text-slate-400" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
              <DropdownMenuLabel class="text-xs text-slate-500">Aksi Registrasi Siswa</DropdownMenuLabel>
              
              <!-- 1. SHOW DETAIL -->
              <DropdownMenuItem class="cursor-pointer gap-2" @click="openDetailModal(item)">
                <Eye class="size-4 text-slate-500" />
                <span>Show Detail Biometrik</span>
              </DropdownMenuItem>

              <!-- 2. EDIT RFID / DATA -->
              <DropdownMenuItem class="cursor-pointer gap-2 text-blue-600 dark:text-blue-400" @click="openEditModal(item)">
                <Pencil class="size-4" />
                <span>Edit Data RFID</span>
              </DropdownMenuItem>

              <DropdownMenuSeparator />

              <!-- 3. REGISTRASI WAJAH FACEID -->
              <DropdownMenuItem class="cursor-pointer gap-2 text-indigo-600 dark:text-indigo-400 font-semibold" @click="openFaceRegistration(item)">
                <Camera class="size-4 text-indigo-500" />
                <span>Registrasi Wajah (FaceID)</span>
              </DropdownMenuItem>

              <!-- 4. REGISTRASI RFID -->
              <DropdownMenuItem class="cursor-pointer gap-2 text-emerald-600 dark:text-emerald-400 font-semibold" @click="openRfidRegistration(item)">
                <IdCard class="size-4 text-emerald-500" />
                <span>Registrasi RFID</span>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </template>
      </DataTableCard>
    </div>

    <!-- ── TAB 2: LOG ACTIVITY PEMINDAI REAL-TIME ── -->
    <div v-if="activeTab === 'logs'" class="space-y-4">
      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/60 p-5 shadow-sm backdrop-blur-md">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
          <div>
            <h3 class="font-semibold text-base text-slate-900 dark:text-white flex items-center gap-2">
              <Activity class="size-4 shrink-0 text-indigo-500" />
              <span>Riwayat Absensi Siswa Hari Ini</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
              Riwayat siswa yang melakukan verifikasi presensi via Wajah AI, RFID, atau Fingerprint.
            </p>
          </div>
          <Badge variant="outline" class="gap-1 font-mono text-xs self-start sm:self-auto whitespace-nowrap">
            Total {{ filteredLogs.length }} Aktivitas
          </Badge>
        </div>

        <!-- Logs Table / Empty State -->
        <div v-if="filteredLogs.length === 0" class="flex flex-col items-center justify-center py-12 text-center text-slate-400">
          <Activity class="size-12 stroke-[1.5] mb-3 text-slate-300 dark:text-slate-700 animate-pulse" />
          <p class="font-medium text-sm text-slate-600 dark:text-slate-400">Belum ada aktivitas scan hari ini</p>
          <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Buka terminal kiosk presensi untuk memulai verifikasi scan siswa.</p>
        </div>

        <div v-else class="relative overflow-x-auto rounded-xl border border-slate-200/80 dark:border-slate-800">
          <table class="w-full text-sm text-left">
            <thead class="text-xs uppercase text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800/60 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="px-4 py-3 font-semibold whitespace-nowrap">Waktu Scan</th>
                <th class="px-4 py-3 font-semibold whitespace-nowrap">Nama Siswa</th>
                <th class="px-4 py-3 font-semibold whitespace-nowrap">NISN</th>
                <th class="px-4 py-3 font-semibold whitespace-nowrap">Kelas</th>
                <th class="px-4 py-3 font-semibold whitespace-nowrap">Tipe Absen</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
              <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                <td class="px-4 py-3 font-mono font-semibold text-xs text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                  {{ log.waktu }}
                </td>
                <td class="px-4 py-3 font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <div class="size-7 rounded-full bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center text-[10px] font-bold shrink-0">
                      {{ log.inisial || getInitials(log.nama) }}
                    </div>
                    <span>{{ log.nama }}</span>
                  </div>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                  {{ log.nisn || '-' }}
                </td>
                <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300 font-medium whitespace-nowrap">
                  {{ log.kelas || '-' }}
                </td>
                <td class="px-4 py-3 whitespace-nowrap">
                  <Badge
                    :variant="log.tipe === 'Masuk' ? 'default' : 'secondary'"
                    :class="log.tipe === 'Masuk' 
                      ? 'bg-emerald-500/15 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400 border-emerald-500/30' 
                      : 'bg-indigo-500/15 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400 border-indigo-500/30'"
                    class="border text-xs px-2.5 py-0.5 font-semibold"
                  >
                    {{ log.tipe || 'Masuk' }}
                  </Badge>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── Modal Registrasi Biometrik Multi-Angle FaceID ── -->
    <Dialog :open="isRegisterModalOpen" @update:open="val => !val && closeFaceRegistration()">
      <DialogContent class="sm:max-w-[540px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-base font-bold">
            <Sparkles class="size-5 text-indigo-500" />
            Registrasi Biometrik Wajah (Multi-Angle FaceID)
          </DialogTitle>
          <DialogDescription class="text-xs">
            Mendaftarkan 4 sudut sampel wajah untuk <strong>{{ selectedStudent?.nama }}</strong> (NISN: {{ selectedStudent?.nisn || '-' }}).
          </DialogDescription>
        </DialogHeader>

        <div class="flex flex-col items-center justify-center p-2 space-y-4">
          <!-- Stepper Progress Bar & Badge -->
          <div class="w-full space-y-2">
            <div class="flex items-center justify-between text-xs font-semibold text-slate-700 dark:text-slate-200">
              <span class="flex items-center gap-1.5">
                <span class="size-5 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold">
                  {{ currentStepIdx + 1 }}
                </span>
                {{ faceSteps[currentStepIdx]?.label }}
              </span>
              <span class="text-slate-400 font-normal">
                Langkah {{ Math.min(currentStepIdx + 1, 4) }} dari 4
              </span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
              <div
                class="h-full bg-indigo-500 transition-all duration-300 rounded-full"
                :style="{ width: `${((currentStepIdx + (isAllSamplesCaptured ? 1 : 0)) / 4) * 100}%` }"
              />
            </div>
          </div>

          <!-- Kamera Feed dengan Circular Guide Ring -->
          <div class="relative w-full aspect-square max-w-[340px] rounded-3xl overflow-hidden bg-slate-900 border-2 border-slate-800 flex items-center justify-center shadow-xl group">
            <video
              v-if="cameraStatus === 'active'"
              ref="videoRef"
              autoplay
              playsinline
              class="w-full h-full object-cover scale-x-[-1]"
            />

            <!-- Flash Shutter Effect -->
            <div v-if="isFlashing" class="absolute inset-0 bg-white/80 transition-opacity animate-pulse z-20 pointer-events-none" />

            <!-- Circular Guide Ring & Dynamic SVG Progress Ring (FaceID Style) -->
            <div v-if="cameraStatus === 'active'" class="absolute inset-0 pointer-events-none flex items-center justify-center">
              <svg class="absolute size-64 -rotate-90">
                <circle
                  cx="50%"
                  cy="50%"
                  r="120"
                  fill="transparent"
                  stroke="currentColor"
                  stroke-width="3"
                  class="text-emerald-500/20"
                />
                <circle
                  cx="50%"
                  cy="50%"
                  r="120"
                  fill="transparent"
                  stroke="currentColor"
                  stroke-width="5"
                  stroke-linecap="round"
                  class="text-emerald-400 transition-all duration-200"
                  :style="{
                    strokeDasharray: 753.98,
                    strokeDashoffset: 753.98 - (753.98 * detectProgress) / 100
                  }"
                />
              </svg>

              <div class="size-60 rounded-full border-2 border-dashed border-emerald-400/80 animate-pulse flex flex-col items-center justify-center bg-emerald-500/5 shadow-[0_0_30px_rgba(52,211,153,0.25)]">
                <!-- Visual Direction Icons -->
                <ArrowLeft v-if="faceSteps[currentStepIdx]?.key === 'left'" class="size-10 text-emerald-400 animate-bounce" />
                <ArrowRight v-else-if="faceSteps[currentStepIdx]?.key === 'right'" class="size-10 text-emerald-400 animate-bounce" />
                <Smile v-else-if="faceSteps[currentStepIdx]?.key === 'smile'" class="size-10 text-emerald-400 animate-pulse" />
                <UserCheck v-else class="size-10 text-emerald-400/70" />
              </div>
            </div>

            <!-- Hint Overlay dengan Dynamic Feedback Status -->
            <div
              v-if="cameraStatus === 'active'"
              class="absolute bottom-3 inset-x-3 backdrop-blur-md rounded-xl p-2.5 text-center text-xs text-white border shadow-lg transition-all duration-200"
              :class="isPoseMatched 
                ? 'bg-emerald-950/85 border-emerald-500/40 shadow-emerald-500/10' 
                : 'bg-amber-950/85 border-amber-500/40 shadow-amber-500/10'"
            >
              <p class="font-bold flex items-center justify-center gap-1.5" :class="isPoseMatched ? 'text-emerald-400' : 'text-amber-300'">
                <Sparkles v-if="isPoseMatched" class="size-3.5 animate-spin shrink-0" />
                <AlertCircle v-else class="size-3.5 text-amber-400 shrink-0" />
                <span>{{ autoDetectHint }}</span>
                <ArrowLeft v-if="faceSteps[currentStepIdx]?.key === 'left'" class="size-3.5 shrink-0" />
                <ArrowRight v-else-if="faceSteps[currentStepIdx]?.key === 'right'" class="size-3.5 shrink-0" />
                <Smile v-else-if="faceSteps[currentStepIdx]?.key === 'smile'" class="size-3.5 shrink-0" />
                <Target v-else class="size-3.5 shrink-0" />
              </p>
              <p class="text-[11px] text-slate-300 mt-0.5">
                {{ faceSteps[currentStepIdx]?.hint }}
              </p>
            </div>

            <div v-if="cameraStatus === 'loading'" class="flex flex-col items-center gap-2 text-white text-xs">
              <span class="animate-spin rounded-full h-6 w-6 border-b-2 border-white"></span>
              <span>Menyalakan kamera...</span>
            </div>

            <div v-if="cameraStatus === 'idle' || cameraStatus === 'error'" class="flex flex-col items-center gap-2 text-slate-400 text-xs">
              <Camera class="size-8" />
              <span>Kamera belum aktif</span>
            </div>
          </div>

          <!-- Canvas hidden untuk capture -->
          <canvas ref="canvasRef" class="hidden" width="480" height="480" />

          <!-- 4 Thumbnail Previews -->
          <div class="grid grid-cols-4 gap-2 w-full pt-1">
            <div
              v-for="(step, idx) in faceSteps"
              :key="step.key"
              class="flex flex-col items-center gap-1 p-1.5 rounded-xl border transition-all text-center"
              :class="capturedPreviews[step.key]
                ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                : idx === currentStepIdx
                ? 'border-indigo-500 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-bold'
                : 'border-slate-200 dark:border-slate-800 text-slate-400'"
            >
              <div class="relative size-12 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 flex items-center justify-center border">
                <img
                  v-if="capturedPreviews[step.key]"
                  :src="capturedPreviews[step.key]"
                  class="w-full h-full object-cover scale-x-[-1]"
                />
                <span v-else class="text-xs font-bold">{{ idx + 1 }}</span>

                <div v-if="capturedPreviews[step.key]" class="absolute top-0.5 right-0.5 bg-emerald-500 text-white rounded-full p-0.5">
                  <CheckCircle2 class="size-3" />
                </div>
              </div>
              <span class="text-[10px] font-medium leading-tight truncate w-full">
                {{ step.label }}
              </span>
            </div>
          </div>
        </div>

        <DialogFooter class="flex items-center justify-between gap-2 border-t pt-3">
          <div class="flex gap-1.5">
            <Button variant="ghost" size="sm" class="text-xs gap-1 text-slate-500" @click="closeFaceRegistration">
              Batal
            </Button>
            <Button
              v-if="capturedPreviews.front"
              variant="outline"
              size="sm"
              class="text-xs gap-1"
              @click="resetFaceRegistration"
            >
              <RotateCcw class="size-3.5" />
              Ulangi
            </Button>
          </div>

          <div class="flex gap-2">
            <Button v-if="cameraStatus !== 'active'" size="sm" @click="startCamera">
              Nyalakan Kamera
            </Button>

            <!-- Manual Fallback Button -->
            <Button
              v-else-if="!isAllSamplesCaptured"
              size="sm"
              variant="outline"
              class="text-xs gap-1.5 text-slate-700 dark:text-slate-200"
              @click="triggerFlashEffect(); captureCurrentSample()"
            >
              <Camera class="size-3.5" />
              Ambil Manual
            </Button>

            <div v-else-if="isRegistering" class="flex items-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400 animate-pulse">
              <Sparkles class="size-4 animate-spin text-emerald-500" />
              <span>Menyimpan 4 sampel biometrik otomatis...</span>
            </div>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- ── Modal Show Detail Biometrik Siswa ── -->
    <Dialog :open="isShowModalOpen" @update:open="val => !val && closeDetailModal()">
      <DialogContent class="sm:max-w-[480px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-base font-bold">
            <Eye class="size-5 text-indigo-500" />
            Detail Biometrik & Perangkat Siswa
          </DialogTitle>
          <DialogDescription class="text-xs">
            Informasi registrasi biometrik wajah dan kartu RFID siswa.
          </DialogDescription>
        </DialogHeader>

        <div v-if="detailStudent" class="space-y-4 py-2">
          <!-- Student Card -->
          <div class="flex items-center gap-3 p-3 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border">
            <div class="size-12 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex items-center justify-center shrink-0 border">
              <img v-if="detailStudent.foto" :src="getPhotoUrl(detailStudent.foto)" :alt="detailStudent.nama" class="size-full object-cover" />
              <span v-else class="font-bold text-sm text-indigo-600 dark:text-indigo-400">
                {{ getInitials(detailStudent.nama) }}
              </span>
            </div>
            <div>
              <h4 class="font-bold text-sm text-slate-900 dark:text-white">{{ detailStudent.nama }}</h4>
              <p class="text-xs text-slate-500">NISN: <code class="font-mono">{{ detailStudent.nisn || '-' }}</code> • Kelas {{ detailStudent.kelas || '-' }}</p>
            </div>
          </div>

          <!-- Status Grid -->
          <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="p-3 rounded-xl border bg-slate-50/50 dark:bg-slate-900/40 space-y-1">
              <span class="text-slate-400 font-medium">Status Biometrik Wajah</span>
              <div class="flex items-center gap-1.5 pt-0.5">
                <Badge :variant="detailStudent.is_face_registered ? 'default' : 'secondary'" class="text-[11px] gap-1">
                  <ShieldCheck v-if="detailStudent.is_face_registered" class="size-3" />
                  {{ detailStudent.is_face_registered ? 'Terdaftar (4 Sudut AI)' : 'Belum Terdaftar' }}
                </Badge>
              </div>
            </div>

            <div class="p-3 rounded-xl border bg-slate-50/50 dark:bg-slate-900/40 space-y-1">
              <span class="text-slate-400 font-medium">Kartu RFID (UID)</span>
              <div class="flex items-center gap-1.5 pt-0.5 font-mono">
                <Badge v-if="detailStudent.rfid_number || detailStudent.rfid_uid" class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[11px] gap-1 font-semibold">
                  <CreditCard class="size-3 text-emerald-500" />
                  {{ detailStudent.rfid_number || detailStudent.rfid_uid }}
                </Badge>
                <span v-else class="text-slate-400 italic">Belum Terhubung</span>
              </div>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" size="sm" @click="closeDetailModal">Tutup</Button>
          <Button size="sm" class="gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white" @click="closeDetailModal(); openFaceRegistration(detailStudent)">
            <Camera class="size-4" />
            Daftarkan Wajah
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- ── Modal Edit Data RFID Siswa ── -->
    <Dialog :open="isEditModalOpen" @update:open="val => !val && closeEditModal()">
      <DialogContent class="sm:max-w-[440px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-base font-bold">
            <Pencil class="size-5 text-blue-500" />
            Edit Data RFID & Biometrik Siswa
          </DialogTitle>
          <DialogDescription class="text-xs">
            Hubungkan kartu fisik RFID / ubah identitas perangkat siswa.
          </DialogDescription>
        </DialogHeader>

        <div v-if="editStudent" class="space-y-4 py-2">
          <!-- Student Info Header -->
          <div class="text-xs p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-between">
            <span class="font-semibold text-slate-800 dark:text-slate-200">{{ editStudent.nama }}</span>
            <span class="text-slate-500 font-mono">Kelas {{ editStudent.kelas || '-' }}</span>
          </div>

          <!-- RFID UID Form Input -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
              <CreditCard class="size-3.5 text-indigo-500" />
              Nomor Seri UID Kartu RFID
            </label>
            <Input
              v-model="rfidCardUid"
              placeholder="Contoh: 1049283711"
              class="font-mono text-xs"
            />
            <p class="text-[11px] text-slate-400">
              Tempelkan kartu ke pemindai RFID USB untuk membaca nomor UID secara otomatis.
            </p>
          </div>
        </div>

        <DialogFooter class="flex gap-2 justify-end">
          <Button variant="outline" size="sm" @click="closeEditModal">Batal</Button>
          <Button size="sm" class="bg-blue-600 hover:bg-blue-700 text-white gap-1.5" @click="saveEditModal">
            Simpan Perubahan
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <!-- ── Modal Registrasi RFID Siswa ── -->
    <Dialog :open="isRfidRegisterModalOpen" @update:open="val => !val && closeRfidRegistration()">
      <DialogContent class="sm:max-w-[440px]">
        <DialogHeader>
          <DialogTitle class="flex items-center gap-2 text-base font-bold text-emerald-600 dark:text-emerald-400">
            <IdCard class="size-5 text-emerald-500" />
            Registrasi Kartu RFID Siswa
          </DialogTitle>
          <DialogDescription class="text-xs">
            Tempelkan kartu RFID pada alat pemindai USB untuk mendaftarkan kartu siswa.
          </DialogDescription>
        </DialogHeader>

        <div v-if="selectedStudentForRfid" class="space-y-4 py-2">
          <!-- Student Card Info -->
          <div class="flex items-center gap-3 p-3 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-500/20">
            <div class="size-12 rounded-full overflow-hidden bg-slate-200 dark:bg-slate-700 flex items-center justify-center shrink-0 border">
              <img v-if="selectedStudentForRfid.foto" :src="getPhotoUrl(selectedStudentForRfid.foto)" :alt="selectedStudentForRfid.nama" class="size-full object-cover" />
              <span v-else class="font-bold text-sm text-emerald-600 dark:text-emerald-400">
                {{ getInitials(selectedStudentForRfid.nama) }}
              </span>
            </div>
            <div>
              <h4 class="font-bold text-sm text-slate-900 dark:text-white">{{ selectedStudentForRfid.nama }}</h4>
              <p class="text-xs text-slate-500">NISN: <code class="font-mono">{{ selectedStudentForRfid.nisn || '-' }}</code> • Kelas {{ selectedStudentForRfid.kelas || '-' }}</p>
            </div>
          </div>

          <!-- RFID UID Form Input with Auto-focus & Tap indicator -->
          <form @submit.prevent="handleRfidRegistrationSubmit" class="space-y-3">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                <CreditCard class="size-4 text-emerald-500 animate-pulse" />
                Tempelkan Kartu RFID ke Pemindai USB:
              </label>
              <div v-if="selectedStudentForRfid.rfid_number || selectedStudentForRfid.rfid_uid" class="p-2 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-between text-xs mb-1">
                <span class="text-slate-500 font-medium">Kartu Terdaftar Saat Ini:</span>
                <Badge class="bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20 font-mono text-xs">
                  {{ selectedStudentForRfid.rfid_number || selectedStudentForRfid.rfid_uid }}
                </Badge>
              </div>

              <Input
                ref="rfidInputRef"
                v-model="rfidInput"
                placeholder="Menunggu tempelan kartu RFID..."
                class="font-mono text-sm border-emerald-500/40 focus:border-emerald-500 shadow-sm"
                autofocus
                @focus="$event.target.select()"
                @click="$event.target.select()"
              />
              <p class="text-[11px] text-slate-400 flex items-center gap-1">
                <Info class="size-3 text-emerald-500" />
                USB RFID Reader akan mengetikkan UID kartu dan menyimpan otomatis saat kartu di-tap.
              </p>
            </div>

            <DialogFooter class="flex gap-2 justify-end pt-2">
              <Button type="button" variant="outline" size="sm" @click="closeRfidRegistration">Batal</Button>
              <Button type="submit" size="sm" class="bg-emerald-600 hover:bg-emerald-700 text-white gap-1.5" :disabled="isSubmittingRfid">
                <IdCard class="size-4" />
                {{ isSubmittingRfid ? 'Menyimpan...' : 'Simpan Kartu RFID' }}
              </Button>
            </DialogFooter>
          </form>
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>
