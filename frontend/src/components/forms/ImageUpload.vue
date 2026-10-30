<script setup>
import { ref, watch, nextTick } from 'vue'
import { ImagePlus, Camera } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'

const props = defineProps({
  label: {
    type: String,
    default: 'Upload Gambar'
  },
  note: String,
  preview: String,
  error: String,
  allowCamera: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  fit: {
    type: String,
    default: 'cover' // 'cover' | 'contain'
  }
})

const emit = defineEmits(['change'])

const hasError = ref(false)
watch(() => props.preview, () => {
  hasError.value = false
})

const handleFileChange = event => {
  const file = event.target.files?.[0]
  if (!file) return
  emit('change', file)
}

// ─── File Picker Setup ─────────────────────────────────────
const fileInputRef = ref(null)

const triggerFilePicker = () => {
  if (fileInputRef.value) {
    fileInputRef.value.click()
  }
}

// ─── Camera Setup ────────────────────────────────────────
const isCameraOpen = ref(false)
const videoRef = ref(null)
const canvasRef = ref(null)
const cameraStatus = ref('idle') // idle | loading | active | error
let mediaStream = null

const startCamera = async () => {
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
  } catch (err) {
    cameraStatus.value = 'error'
    console.error('Camera access failed:', err)
  }
}

const stopCamera = () => {
  if (mediaStream) {
    mediaStream.getTracks().forEach(track => track.stop())
    mediaStream = null
  }
  if (videoRef.value) videoRef.value.srcObject = null
  cameraStatus.value = 'idle'
}

const openCameraModal = () => {
  isCameraOpen.value = true
  startCamera()
}

const closeCameraModal = () => {
  stopCamera()
  isCameraOpen.value = false
}

const capturePhoto = () => {
  if (!videoRef.value || !canvasRef.value) return

  const video = videoRef.value
  const canvas = canvasRef.value
  const context = canvas.getContext('2d')

  // Crop square dari video feed 1:1
  const size = Math.min(video.videoWidth, video.videoHeight)
  const sx = (video.videoWidth - size) / 2
  const sy = (video.videoHeight - size) / 2

  canvas.width = 480
  canvas.height = 480
  context.drawImage(video, sx, sy, size, size, 0, 0, 480, 480)

  canvas.toBlob((blob) => {
    if (blob) {
      const file = new File([blob], 'camera_capture.jpg', { type: 'image/jpeg' })
      emit('change', file)
      closeCameraModal()
    }
  }, 'image/jpeg', 0.90)
}
</script>

<template>
  <div class="space-y-3 flex flex-col items-center">
    <div
      class="flex flex-col items-center justify-center sm:w-32 md:w-40 lg:w-64 aspect-square mx-auto rounded-xl border-2 overflow-hidden relative bg-muted/20"
      :class="[
        disabled ? 'border-border/60 bg-muted/10' : 'border-dashed',
        error
          ? 'border-destructive'
          : disabled
            ? ''
            : 'border-muted-foreground/25'
      ]"
    >
      <template v-if="preview && !hasError">
        <img
          :src="preview"
          alt="Preview"
          class="w-full h-full"
          :class="fit === 'contain' ? 'object-contain p-2' : 'object-cover'"
          @error="hasError = true"
        />
      </template>

      <template v-else>
        <div class="flex flex-col items-center gap-3 text-muted-foreground">
          <ImagePlus class="w-8 h-8 md:w-8 md:h-8 lg:w-10 lg:h-10 opacity-60" />
          <div class="text-xs text-center px-4 font-medium">
            {{ disabled ? 'Belum ada gambar / logo' : 'Foto belum dipilih' }}
          </div>
        </div>
      </template>
    </div>

    <input
      v-if="!disabled"
      ref="fileInputRef"
      type="file"
      accept="image/*"
      class="hidden"
      @change="handleFileChange"
    />

    <!-- Action Buttons Group (Hidden when disabled) -->
    <div v-if="!disabled" class="flex flex-wrap justify-center gap-3 w-full">
      <!-- Gallery Upload Button -->
      <Button
        type="button"
        variant="outline"
        size="sm"
        class="gap-2 text-xs cursor-pointer shadow-sm border-dashed"
        @click="triggerFilePicker"
      >
        <ImagePlus class="size-4 text-primary" />
        Ambil dari Galeri
      </Button>

      <!-- Web Camera Button (if allowed) -->
      <Button
        v-if="allowCamera"
        type="button"
        variant="outline"
        size="sm"
        class="gap-2 text-xs cursor-pointer shadow-sm"
        @click="openCameraModal"
      >
        <Camera class="size-4" />
        Ambil dari Kamera
      </Button>
    </div>

    <p v-if="error" class="text-sm text-destructive text-center w-full">{{ error }}</p>
    
    <div v-if="note" class="p-4 rounded-lg bg-white border border-primary/10 text-xs text-muted-foreground italic w-full text-center">
        {{ note }}
    </div>

    <!-- Modal Dialog untuk Mengambil Foto -->
    <Dialog :open="isCameraOpen" @update:open="val => !val && closeCameraModal()">
      <DialogContent class="sm:max-w-[480px]">
        <DialogHeader>
          <DialogTitle>Kamera Ambil Foto</DialogTitle>
          <DialogDescription>
            Posisikan wajah Anda tegak lurus di depan kamera, lalu klik Ambil Foto.
          </DialogDescription>
        </DialogHeader>

        <div class="flex flex-col items-center justify-center p-4 space-y-4">
          <div class="relative w-full aspect-video rounded-2xl overflow-hidden bg-slate-900 border border-slate-800 flex items-center justify-center">
            <video v-if="cameraStatus === 'active'" ref="videoRef" autoplay playsinline class="w-full h-full object-cover scale-x-[-1]" />
            
            <!-- Target Face Guidelines Overlay -->
            <div v-if="cameraStatus === 'active'" class="absolute inset-0 pointer-events-none flex flex-col items-center justify-center">
              <!-- Corner borders -->
              <span class="absolute top-4 left-4 w-6 h-6 border-t-2 border-l-2 border-emerald-400"></span>
              <span class="absolute top-4 right-4 w-6 h-6 border-t-2 border-r-2 border-emerald-400"></span>
              <span class="absolute bottom-4 left-4 w-6 h-6 border-b-2 border-l-2 border-emerald-400"></span>
              <span class="absolute bottom-4 right-4 w-6 h-6 border-b-2 border-r-2 border-emerald-400"></span>

              <!-- Glowing Target Oval in Center -->
              <div class="w-[180px] h-[220px] rounded-[50%] border-2 border-dashed border-emerald-400/50 flex items-center justify-center relative shadow-[0_0_20px_rgba(52,211,153,0.15)] bg-emerald-400/5">
                <div class="absolute inset-0 rounded-[50%] border-2 border-emerald-400 animate-pulse"></div>
              </div>
              <span class="mt-4 text-[10px] text-emerald-400 bg-slate-950/80 px-2 py-0.5 rounded-full font-bold uppercase tracking-wider shadow-md">Posisikan Wajah Anda di Sini</span>
            </div>

            <div v-if="cameraStatus === 'loading'" class="flex flex-col items-center gap-2 text-white text-xs">
              <span class="animate-spin rounded-full h-6 w-6 border-b-2 border-white"></span>
              <span>Menyalakan kamera...</span>
            </div>

            <div v-if="cameraStatus === 'error'" class="flex flex-col items-center gap-2 text-destructive text-xs">
              <Camera class="size-8" />
              <span>Gagal mengakses kamera. Pastikan izin diaktifkan.</span>
            </div>
          </div>
          <canvas ref="canvasRef" class="hidden" width="480" height="480" />
        </div>

        <DialogFooter class="flex gap-2 justify-end">
          <Button variant="outline" @click="closeCameraModal">Batal</Button>
          <Button v-if="cameraStatus === 'active'" @click="capturePhoto">Ambil Foto</Button>
          <Button v-else @click="startCamera">Ulangi Kamera</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
