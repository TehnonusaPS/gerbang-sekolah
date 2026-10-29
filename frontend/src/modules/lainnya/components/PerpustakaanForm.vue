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
import { Textarea } from '@/components/ui/textarea'

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
  name: props.initialData.name || '',
  isbn: props.initialData.isbn || '',
  tahunTerbit: props.initialData.tahunTerbit || '',
  penulis: props.initialData.penulis || '',
  penerbit: props.initialData.penerbit || '',
  kategori: props.initialData.kategori || '',
  jumlahStok: props.initialData.jumlahStok || '',
  lokasiRak: props.initialData.lokasiRak || '',
  deskripsi: props.initialData.deskripsi || ''
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

const currentYear = new Date().getFullYear()
const years = Array.from({ length: 50 }, (_, i) => currentYear - i)

const fileInput = ref(null)
const photoPreview = ref(props.initialData.photo || null)

function triggerFileInput() {
  fileInput.value?.click()
}

function handleFileUpload(event) {
  const file = event.target.files[0]
  if (file && (file.type === 'image/jpeg' || file.type === 'image/png')) {
    form.value.photo = file // Store the actual File object
    photoPreview.value = URL.createObjectURL(file)
  }
}

function handleDrop(event) {
  const file = event.dataTransfer.files[0]
  if (file && (file.type === 'image/jpeg' || file.type === 'image/png')) {
    form.value.photo = file // Store the actual File object
    photoPreview.value = URL.createObjectURL(file)
  }
}

function removePhoto() {
  photoPreview.value = null
  form.value.photo = null // Clear the File object
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function cancel() {
  emit('cancel')
}

function submit() {
  emit('submit', { ...form.value })
}
</script>

<template>
  <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
    <!-- Kolom Kiri: Form Fields -->
    <div class="xl:col-span-8 space-y-6">
      <!-- Informasi Buku -->
      <Card class="rounded-2xl border-border bg-card shadow-xs">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Informasi Buku</CardTitle>
        </CardHeader>
        <CardContent class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="space-y-2 md:col-span-2">
            <Label class="text-xs sm:text-sm font-semibold">Judul Buku</Label>
            <Input
              v-model="form.name"
              placeholder="Contoh : Buku Fisika Dasar"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">ISBN</Label>
            <Input
              v-model="form.isbn"
              placeholder="Contoh : 978-602-8256-07-0"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Tahun Terbit</Label>
            <Select v-model="form.tahunTerbit">
              <SelectTrigger class="w-full rounded-xl !h-11 bg-background">
                <SelectValue placeholder="Pilih Tahun Terbit" />
              </SelectTrigger>
              <SelectContent class="rounded-xl max-h-[250px]">
                <SelectItem
                  v-for="year in years"
                  :key="year"
                  :value="year.toString()"
                >
                  {{ year }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Penulis</Label>
            <Input
              v-model="form.penulis"
              placeholder="Contoh : Andrea Hirata"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Penerbit</Label>
            <Input
              v-model="form.penerbit"
              placeholder="Contoh : Bentang Pustaka"
              class="rounded-xl h-11"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Kategori & Inventaris -->
      <Card class="rounded-2xl border-border bg-card shadow-xs">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Kategori & Inventaris</CardTitle>
        </CardHeader>
        <CardContent class="p-5 grid grid-cols-1 md:grid-cols-3 gap-5">
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Kategori</Label>
            <Select
              v-model="form.kategori"
              placeholder="Contoh : Fiksi"
              class="rounded-xl h-11"
            >
              <SelectTrigger class="w-full rounded-xl !h-11 bg-background">
                <SelectValue placeholder="Pilih Kategori" />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem value="Sains">Sains</SelectItem>
                <SelectItem value="Bahasa">Bahasa</SelectItem>
                <SelectItem value="Sosial">Sosial</SelectItem>
                <SelectItem value="Sejarah">Sejarah</SelectItem>
                <SelectItem value="Fiksi">Fiksi</SelectItem>
                <SelectItem value="TIK">TIK</SelectItem>
              </SelectContent>
            </Select>
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Jumlah Stok</Label>
            <Input
              v-model="form.jumlahStok"
              placeholder="Contoh : 36"
              class="rounded-xl h-11"
            />
          </div>
          <div class="space-y-2">
            <Label class="text-xs sm:text-sm font-semibold">Lokasi Rak</Label>
            <Input
              v-model="form.lokasiRak"
              placeholder="Contoh :Rak A1"
              class="rounded-xl h-11"
            />
          </div>
        </CardContent>
      </Card>

      <!-- Deskripsi & Sinopsis -->
      <Card class="rounded-2xl border-border bg-card shadow-xs">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Deskripsi & Sinopsis</CardTitle>
        </CardHeader>
        <CardContent class="p-5 bg-muted/30">
          <div class="space-y-2">
            <Textarea
              v-model="form.deskripsi"
              class="rounded-xl min-h-[120px]"
            />
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Kolom Kanan: Foto Aset & Action Buttons -->
    <div class="xl:col-span-4 flex flex-col gap-6">
      <Card class="rounded-2xl border-border bg-card shadow-xs flex-1">
        <CardHeader class="pb-3 border-b border-border/50 bg-muted/20">
          <CardTitle class="text-base sm:text-lg font-bold">Foto Buku</CardTitle>
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
        :confirm-title="isEdit ? 'Konfirmasi Perubahan Buku' : 'Konfirmasi Simpan Buku'"
        :entity-name="form.title"
        @submit="submit"
      />
    </div>
  </div>
</template>
