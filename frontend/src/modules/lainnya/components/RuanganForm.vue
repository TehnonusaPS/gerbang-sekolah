<script setup>
import { ref, watch } from 'vue'
import { Camera, X } from 'lucide-vue-next'

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import FormFooter from '@/components/forms/FormFooter.vue'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import { Checkbox } from '@/components/ui/checkbox'

const props = defineProps({
  initialData: {
    type: Object,
    default: () => ({})
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['submit', 'cancel'])

const form = ref({
  name: '',
  code: '',
  category: '',
  building: '',
  capacity: '',
  area: '',
  facilities: []
})

watch(
  () => props.initialData,
  newData => {
    if (newData && Object.keys(newData).length > 0) {
      // Clone to remove Pinia Proxy wrappers which can cause reactivity mismatches
      const plainData = JSON.parse(JSON.stringify(newData))

      form.value.name = plainData.name || ''
      form.value.code = plainData.code || ''
      form.value.category = plainData.category || ''
      form.value.building = plainData.building || ''
      form.value.capacity = plainData.capacity || ''
      form.value.area = plainData.area || ''

      let facs = plainData.facilities || []
      if (typeof facs === 'string') {
        facs = facs.split(',').map(s => s.trim().toLowerCase())
      }
      form.value.facilities = Array.isArray(facs) ? [...facs] : []
    }
  },
  { deep: true, immediate: true }
)

const isChecked = id => {
  return Array.isArray(form.value.facilities) && form.value.facilities.includes(id)
}

const toggleFacility = (id, checked) => {
  if (!Array.isArray(form.value.facilities)) {
    form.value.facilities = []
  }
  if (checked) {
    if (!form.value.facilities.includes(id)) {
      form.value.facilities.push(id)
    }
  } else {
    form.value.facilities = form.value.facilities.filter(f => f !== id)
  }
}

const facilitiesList = [
  { id: 'ac', label: 'AC' },
  { id: 'proyektor', label: 'Proyektor' },
  { id: 'sound_system', label: 'Sound System' },
  { id: 'cctv', label: 'CCTV' },
  { id: 'wifi', label: 'Wifi' },
  { id: 'whiteboard', label: 'Whiteboard' },
  { id: 'meja', label: 'Meja' },
  { id: 'dispenser', label: 'Dispenser' }
]

const fileInput = ref(null)
const photoPreview = ref(props.initialData.photo || null)

function triggerFileInput() {
  fileInput.value?.click()
}

function handleFileUpload(event) {
  const file = event.target.files[0]
  if (file && (file.type === 'image/jpeg' || file.type === 'image/png')) {
    photoPreview.value = URL.createObjectURL(file)
  }
}

function handleDrop(event) {
  const file = event.dataTransfer.files[0]
  if (file && (file.type === 'image/jpeg' || file.type === 'image/png')) {
    photoPreview.value = URL.createObjectURL(file)
  }
}

function removePhoto() {
  photoPreview.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function cancel() {
  emit('cancel')
}

function submit() {
  emit('submit', { ...form.value, photo: photoPreview.value })
}
</script>

<template>
  <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
    <!-- Kolom Kiri: Form Fields -->
    <div class="xl:col-span-8 space-y-6">
      <!-- Informasi Ruangan -->
      <Card class="rounded-2xl border-border bg-card shadow-xs">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Informasi Ruangan</CardTitle>
        </CardHeader>
        <CardContent class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Nama Ruangan</Label>
            <Input
              v-model="form.name"
              placeholder="Contoh : Ruang Kelas XI-A"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Kode Ruangan</Label>
            <Input
              v-model="form.code"
              placeholder="Contoh : RK-XI-A"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Tipe Ruangan</Label>
            <Select v-model="form.category">
              <SelectTrigger class="w-full rounded-xl !h-11 bg-background">
                <SelectValue placeholder="Pilih Tipe Ruangan" />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem value="Ruang Kelas">Ruang Kelas</SelectItem>
                <SelectItem value="Laboratorium">Laboratorium</SelectItem>
                <SelectItem value="Ruang Fasilitas">Ruang Fasilitas</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2"></div>
        </CardContent>
      </Card>

      <!-- Lokasi & Kapasitas -->
      <Card class="rounded-2xl border-border bg-card shadow-xs">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Lokasi & Kapasitas</CardTitle>
        </CardHeader>
        <CardContent class="p-5 grid grid-cols-1 md:grid-cols-3 gap-5">
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Gedung/Lantai</Label>
            <Input
              v-model="form.building"
              placeholder="Contoh : Gedung A"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Kapasitas (Orang)</Label>
            <Input
              v-model="form.capacity"
              placeholder="Contoh : 36"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Luas Ruangan (M²)</Label>
            <Input
              v-model="form.area"
              placeholder="Contoh : 64"
              class="rounded-xl h-11"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Fasilitas Pendukung -->
      <Card class="rounded-2xl border-border bg-card shadow-xs">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Fasilitas Pendukung</CardTitle>
        </CardHeader>
        <CardContent class="p-5 bg-muted/30">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div
              v-for="facility in facilitiesList"
              :key="facility.id"
              class="flex items-center space-x-3 border border-border bg-background p-4 rounded-xl shadow-sm hover:border-primary/50 transition-colors"
            >
              <div class="relative flex items-center justify-center size-4 shrink-0">
                <input
                  type="checkbox"
                  :id="facility.id"
                  :value="facility.id"
                  v-model="form.facilities"
                  class="peer absolute inset-0 opacity-0 w-full h-full cursor-pointer z-10"
                />
                <!-- Custom Checkbox Visuals -->
                <div
                  class="pointer-events-none size-4 rounded-[4px] border border-input bg-transparent peer-checked:bg-primary peer-checked:border-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ring flex items-center justify-center transition-colors"
                >
                  <svg
                    v-if="form.facilities && form.facilities.includes(facility.id)"
                    xmlns="http://www.w3.org/2000/svg"
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="lucide lucide-check size-3.5 text-primary-foreground"
                  >
                    <path d="M20 6 9 17l-5-5" />
                  </svg>
                </div>
              </div>
              <Label
                :for="facility.id"
                class="cursor-pointer flex-1 font-medium text-sm leading-none m-0"
              >
                {{ facility.label }}
              </Label>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Kolom Kanan: Foto Aset & Action Buttons -->
    <div class="xl:col-span-4 flex flex-col gap-6">
      <Card class="rounded-2xl border-border bg-card shadow-xs flex-1">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Foto Ruangan</CardTitle>
        </CardHeader>
        <CardContent
          class="p-5 flex flex-col items-center justify-center h-[calc(100%-60px)] min-h-[250px]"
        >
          <!-- Area Upload -->
          <div
            v-if="!photoPreview"
            @click="triggerFileInput"
            @dragover.prevent
            @drop.prevent="handleDrop"
            class="w-full h-full border-2 border-dashed border-border/60 hover:border-primary/50 hover:bg-primary/5 rounded-2xl flex flex-col items-center justify-center p-6 transition-colors cursor-pointer group mb-4"
          >
            <div class="p-4 bg-muted group-hover:bg-primary/10 rounded-full mb-4 transition-colors">
              <Camera
                class="size-8 text-muted-foreground group-hover:text-primary transition-colors"
              />
            </div>
            <p
              class="text-sm font-semibold text-muted-foreground group-hover:text-primary transition-colors text-center"
            >
              Klik atau seret untuk unggah foto
            </p>
          </div>

          <!-- Preview Area -->
          <div
            v-else
            class="w-full h-full rounded-2xl overflow-hidden relative group mb-4 border border-border"
          >
            <img
              :src="photoPreview"
              alt="Preview Foto Aset"
              class="w-full h-full object-cover"
            />
            <div
              class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm"
            >
              <Button
                variant="destructive"
                size="sm"
                @click="removePhoto"
                class="rounded-xl shadow-lg gap-2"
              >
                <X class="size-4" />
                Hapus Foto
              </Button>
            </div>
          </div>

          <!-- Hidden File Input -->
          <input
            type="file"
            ref="fileInput"
            class="hidden"
            accept="image/jpeg, image/png"
            @change="handleFileUpload"
          />

          <!-- Keterangan Upload -->
          <p class="text-xs text-muted-foreground text-center leading-relaxed max-w-[250px]">
            Max (2MB, JPG/PNG)
          </p>
        </CardContent>
      </Card>

      <!-- Bottom Buttons -->
      <FormFooter
        :submit-label="isEdit ? 'Simpan Perubahan' : 'Simpan'"
        :confirm-title="isEdit ? 'Konfirmasi Perubahan Ruangan' : 'Konfirmasi Simpan Ruangan'"
        :entity-name="form.name"
        @submit="submit"
      />
    </div>
  </div>
</template>
