<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { Trash2, UploadCloud } from 'lucide-vue-next'
import { useBeritaKegiatanStore } from '@/stores/beritaKegiatanStore'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { glassFade } from '@/config/motion'

const router = useRouter()
const store = useBeritaKegiatanStore()

const formData = ref({
  judul: '',
  kategori: '',
  tanggal: '',
  isi: '',
  gambar: null
})

const fileInput = ref(null)
const previewImage = ref(null)
const isDragging = ref(false)

function triggerFileInput() {
  if (fileInput.value) {
    fileInput.value.click()
  }
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    processFile(file)
  }
}

function onDragOver(event) {
  event.preventDefault()
  isDragging.value = true
}

function onDragLeave(event) {
  event.preventDefault()
  isDragging.value = false
}

function onDrop(event) {
  event.preventDefault()
  isDragging.value = false
  const file = event.dataTransfer.files[0]
  if (file) {
    processFile(file)
  }
}

function processFile(file) {
  if (file.type.startsWith('image/')) {
    if (file.size > 5 * 1024 * 1024) {
      toast.error('Ukuran file maksimal adalah 5MB.')
      return
    }
    formData.value.gambar = file
    previewImage.value = URL.createObjectURL(file)
  } else {
    toast.error('Tolong unggah file gambar yang valid (JPG/PNG/WEBP)')
  }
}

function removeImage(event) {
  if (event) {
    event.stopPropagation()
  }
  formData.value.gambar = null
  previewImage.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

function handleCancel() {
  router.push('/komunikasi/berita-kegiatan')
}

function validateForm() {
  if (
    !formData.value.judul ||
    !formData.value.kategori ||
    !formData.value.tanggal ||
    !formData.value.isi
  ) {
    toast.error('Mohon lengkapi semua field yang wajib diisi.')
    return false
  }
  return true
}

function handleSave() {
  store.add({
    judul: formData.value.judul,
    kategori: formData.value.kategori,
    tanggal: formData.value.tanggal,
    isi: formData.value.isi,
    gambar: previewImage.value,
    sekolah: 'SD Tehnonusa I'
  })

  toast.success('Berita kegiatan berhasil dipublikasikan!')
  router.push('/komunikasi/berita-kegiatan')
}
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 p-1"
  >
    <!-- Header -->
    <PageHeader
      title="Buat Berita Kegiatan"
      description="Lengkapi Formulir dibawah ini untuk mempublikasikan berita kegiatan sekolah terbaru"
      back
      @back="handleCancel"
    />

    <!-- Form Container -->
    <Card class="rounded-2xl border-border bg-card shadow-xs overflow-hidden">
      <CardContent class="p-6 sm:p-8 space-y-8">
        <!-- Judul Berita -->
        <div class="space-y-2.5">
          <Label
            for="judul"
            class="text-sm font-semibold flex gap-1"
          >
            Judul Berita <span class="text-rose-500">*</span>
          </Label>
          <Input
            id="judul"
            v-model="formData.judul"
            placeholder="Masukkan judul berita kegiatan..."
            class="h-12 bg-muted/30 border-border rounded-xl focus-visible:ring-primary/20 focus-visible:border-primary px-4 transition-all"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Kategori -->
          <div class="space-y-2.5">
            <Label
              for="kategori"
              class="text-sm font-semibold flex gap-1"
            >
              Kategori <span class="text-rose-500">*</span>
            </Label>
            <Select v-model="formData.kategori">
              <SelectTrigger
                id="kategori"
                class="w-full !h-12 bg-muted/30 border-border rounded-xl focus-visible:ring-primary/20 focus-visible:border-primary px-4 transition-all"
              >
                <SelectValue placeholder="Pilih Kategori" />
              </SelectTrigger>
              <SelectContent class="rounded-xl border-border">
                <SelectItem value="AKADEMIK">Akademik</SelectItem>
                <SelectItem value="KEUANGAN">Keuangan</SelectItem>
                <SelectItem value="UMUM">Umum</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Tanggal Publikasi -->
          <div class="space-y-2.5">
            <Label
              for="tanggal"
              class="text-sm font-semibold flex gap-1"
            >
              Tanggal Publikasi <span class="text-rose-500">*</span>
            </Label>
            <Input
              id="tanggal"
              type="date"
              v-model="formData.tanggal"
              @click="$event.target.showPicker()"
              class="h-12 bg-muted/30 border-border rounded-xl focus-visible:ring-primary/20 focus-visible:border-primary px-4 transition-all cursor-pointer font-medium"
            />
          </div>
        </div>

        <!-- Isi Berita -->
        <div class="space-y-2.5">
          <Label
            for="isi"
            class="text-sm font-semibold flex gap-1"
          >
            Isi Berita <span class="text-rose-500">*</span>
          </Label>
          <Textarea
            id="isi"
            v-model="formData.isi"
            placeholder="Ketik isi berita kegiatan di sini..."
            class="min-h-[200px] bg-muted/30 border-border rounded-xl focus-visible:ring-primary/20 focus-visible:border-primary p-4 resize-y leading-relaxed"
          />
        </div>

        <!-- Gambar / Foto Kegiatan Utama -->
        <div class="space-y-2.5">
          <Label class="text-sm font-semibold flex gap-1"> Gambar / Foto Kegiatan Utama </Label>

          <div
            class="relative border-2 border-dashed rounded-2xl transition-all duration-200 overflow-hidden cursor-pointer"
            :class="[
              isDragging
                ? 'border-primary bg-primary/5'
                : 'border-border bg-muted/30 hover:bg-muted/50 hover:border-primary/50',
              previewImage ? 'border-solid p-1 border-border bg-card' : 'py-8 px-4'
            ]"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            @click="triggerFileInput"
          >
            <!-- Hidden Input -->
            <input
              type="file"
              ref="fileInput"
              class="hidden"
              accept="image/jpeg, image/png, image/webp"
              @change="handleFileSelect"
            />

            <!-- Empty State -->
            <div
              v-if="!previewImage"
              class="flex flex-col items-center justify-center text-center gap-2"
            >
              <UploadCloud class="size-9 text-slate-800 dark:text-slate-200" />
              <div class="text-xs sm:text-sm text-muted-foreground leading-relaxed">
                <span class="font-bold text-foreground">Klik untuk mengunggah</span> atau seret dan
                lepas file kesini
              </div>
              <div class="text-[10px] sm:text-xs text-muted-foreground/80">
                PNG, JPG, atau WEBP (Maks 5MB)
              </div>
            </div>

            <!-- Preview State -->
            <div
              v-else
              class="relative group rounded-xl overflow-hidden aspect-video max-h-[300px] w-full bg-black/5 flex items-center justify-center"
            >
              <img
                :src="previewImage"
                alt="Preview"
                class="object-cover w-full h-full"
              />

              <!-- Hover Overlay for Removal -->
              <div
                class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
              >
                <Button
                  type="button"
                  @click="removeImage"
                  variant="destructive"
                  size="sm"
                  class="gap-2 rounded-xl font-semibold shadow-lg"
                >
                  <Trash2 class="size-4" />
                  Hapus Gambar
                </Button>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Bottom Action Buttons -->
    <FormFooter
      entity-name="Berita Kegiatan"
      :before-confirm="validateForm"
      @save="handleSave"
    />
  </div>
</template>
