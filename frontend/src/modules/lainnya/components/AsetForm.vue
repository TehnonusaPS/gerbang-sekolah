<script setup>
import { ref, watch, computed } from 'vue'
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

import { useRuanganStore } from '@/stores/ruanganStore'
const ruanganStore = useRuanganStore()

const availableRooms = computed(() => {
  return [...new Set(ruanganStore.items.map(r => r.name))]
})

const form = ref({
  name: props.initialData.name || '',
  code: props.initialData.code || '',
  category: props.initialData.category || '',
  brand: props.initialData.brand || '',
  building: props.initialData.building || '',
  floor: props.initialData.floor || '',
  room: props.initialData.room || '',
  condition: props.initialData.condition || 'Baik',
  date: props.initialData.date || '',
  value: props.initialData.value || ''
})

watch(
  () => props.initialData,
  newData => {
    if (newData) {
      form.value = { ...form.value, ...newData }
    }
  },
  { deep: true, immediate: true }
)

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
  const payload = { ...form.value }
  const file = fileInput.value?.files?.[0]
  
  if (file) {
    payload.photo = file
  } else if (!photoPreview.value) {
    payload.photo = null
  }
  // If photoPreview exists but no new file, we don't send 'photo' field so backend doesn't overwrite it
  
  emit('submit', payload)
}
</script>

<template>
  <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
    <!-- Kolom Kiri: Form Fields -->
    <div class="xl:col-span-8 space-y-6">
      <!-- Informasi Aset -->
      <Card class="rounded-2xl border-border bg-card shadow-xs">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Informasi Aset</CardTitle>
        </CardHeader>
        <CardContent class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Nama Aset</Label>
            <Input
              v-model="form.name"
              placeholder="Contoh : Laptop ROG"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Kode Aset</Label>
            <Input
              v-model="form.code"
              placeholder="Masukkan kode aset"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Kategori Aset</Label>
            <Select v-model="form.category">
              <SelectTrigger class="w-full rounded-xl !h-11 bg-background">
                <SelectValue placeholder="Pilih Kategori" />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem value="Elektronik">Elektronik</SelectItem>
                <SelectItem value="Furniture">Furniture</SelectItem>
                <SelectItem value="Peralatan Pembelajaran">Alat Pembelajaran</SelectItem>
                <SelectItem value="Alat Kebersihan">Alat Kebersihan</SelectItem>
                <SelectItem value="Peralatan Kantor">Peralatan Kantor</SelectItem>
                <SelectItem value="Peralatan Olahraga">Peralatan Olahraga</SelectItem>
                <SelectItem value="Peralatan Laboratorium">Peralatan Laboratorium</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Merk/Tipe</Label>
            <Input
              v-model="form.brand"
              placeholder="Contoh : Asus"
              class="rounded-xl h-11"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Lokasi & Penempatan -->
      <Card class="rounded-2xl border-border bg-card shadow-xs">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Lokasi & Penempatan</CardTitle>
        </CardHeader>
        <CardContent class="p-5 grid grid-cols-1 md:grid-cols-3 gap-5">
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Gedung/Unit</Label>
            <Input
              v-model="form.building"
              placeholder="Nama Gedung"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Lantai</Label>
            <Select v-model="form.floor">
              <SelectTrigger class="w-full rounded-xl !h-11 bg-background">
                <SelectValue placeholder="Pilih Lantai" />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem value="1">Lantai 1</SelectItem>
                <SelectItem value="2">Lantai 2</SelectItem>
                <SelectItem value="3">Lantai 3</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Ruangan</Label>
            <Select v-model="form.room">
              <SelectTrigger class="w-full rounded-xl !h-11 bg-background">
                <SelectValue placeholder="Pilih Ruangan" />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem
                  v-for="room in availableRooms"
                  :key="room"
                  :value="room"
                >
                  {{ room }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <!-- Kondisi & Status -->
      <Card class="rounded-2xl border-border bg-card shadow-xs">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Kondisi & Status</CardTitle>
        </CardHeader>
        <CardContent class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Kondisi Awal</Label>
            <div
              class="h-11 flex items-center bg-background border border-border rounded-xl px-4 shadow-sm overflow-x-auto"
            >
              <RadioGroup
                v-model="form.condition"
                class="flex items-center gap-4 sm:gap-6 w-full min-w-max"
              >
                <div class="flex items-center space-x-2">
                  <RadioGroupItem
                    id="baik"
                    value="Baik"
                    class="size-4 shrink-0"
                  />
                  <Label
                    for="baik"
                    class="text-sm cursor-pointer font-medium m-0"
                    >Baik</Label
                  >
                </div>
                <div class="flex items-center space-x-2">
                  <RadioGroupItem
                    id="rusak_ringan"
                    value="Rusak Ringan"
                    class="size-4 shrink-0"
                  />
                  <Label
                    for="rusak_ringan"
                    class="text-sm cursor-pointer font-medium m-0 whitespace-nowrap"
                    >Rusak Ringan</Label
                  >
                </div>
                <div class="flex items-center space-x-2">
                  <RadioGroupItem
                    id="rusak_berat"
                    value="Rusak Berat"
                    class="size-4 shrink-0"
                  />
                  <Label
                    for="rusak_berat"
                    class="text-sm cursor-pointer font-medium m-0 whitespace-nowrap"
                    >Rusak Berat</Label
                  >
                </div>
              </RadioGroup>
            </div>
          </div>

          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Tanggal Perolehan</Label>
            <Input
              v-model="form.date"
              type="date"
              @click="$event.target.showPicker()"
              class="rounded-xl h-11 w-full cursor-pointer"
            />
          </div>

          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Nilai Perolehan</Label>
            <div class="relative">
              <span
                class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground"
                >Rp</span
              >
              <Input
                v-model="form.value"
                placeholder="0"
                class="rounded-xl h-11 pl-9"
              />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Kolom Kanan: Foto Aset & Action Buttons -->
    <div class="xl:col-span-4 flex flex-col gap-6">
      <Card class="rounded-2xl border-border bg-card shadow-xs flex-1">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Foto Aset</CardTitle>
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
        :confirm-title="isEdit ? 'Konfirmasi Perubahan Aset' : 'Konfirmasi Simpan Aset'"
        :entity-name="form.name"
        @submit="submit"
      />
    </div>
  </div>
</template>
