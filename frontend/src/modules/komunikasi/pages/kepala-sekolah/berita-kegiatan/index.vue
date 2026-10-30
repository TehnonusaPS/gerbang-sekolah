<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue-sonner'
import BeritaKegiatanTable from '../../../components/BeritaKegiatanTable.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion'
import DataSheet from '@/components/data-sheet/DataSheet.vue'
import { Plus, Megaphone, Trash2, UploadCloud } from 'lucide-vue-next'
import { useBeritaKegiatanStore } from '@/stores/beritaKegiatanStore'
import { useAuthStore } from '@/stores/authStore'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import StatCard from '@/components/stat-card/StatCard.vue'
import { glassSlide, glassFade } from '@/config/motion'

const store = useBeritaKegiatanStore()
const authStore = useAuthStore()
const route = useRoute()

const currentUser = authStore.user
const beritaKegiatans = computed(() => store.items)

const canManage = computed(() => {
  return currentUser?.role === 'kepala_sekolah' || currentUser?.role === 'admin_sekolah'
})

onMounted(async () => {
  await store.fetchItems()
  const queryId = route.query.id
  if (queryId) {
    const found = store.getById(queryId)
    if (found) {
      viewBerita(found)
    }
  }
})

// --- Inline State Variables ---
const isFormSheetOpen = ref(false)
const isDetailSheetOpen = ref(false)
const isEditMode = ref(false)
const selectedBerita = ref(null)

const formItem = ref({
  id: '',
  judul: '',
  kategori: '',
  tanggal: '',
  isi: '',
  gambar: null
})

const fileInput = ref(null)
const previewImage = ref(null)
const isDragging = ref(false)

// --- Upload Handlers ---
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
    formItem.value.gambar = file
    previewImage.value = URL.createObjectURL(file)
  } else {
    toast.error('Tolong unggah file gambar yang valid (JPG/PNG/WEBP)')
  }
}

function removeImage() {
  formItem.value.gambar = null
  previewImage.value = null
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

// --- Action Handlers ---
function openCreateForm() {
  isEditMode.value = false
  formItem.value = {
    id: '',
    judul: '',
    kategori: '',
    tanggal: new Date().toISOString().split('T')[0],
    isi: '',
    gambar: null
  }
  previewImage.value = null
  isFormSheetOpen.value = true
}

function viewBerita(item) {
  selectedBerita.value = item
  isDetailSheetOpen.value = true
}

function editBerita(item) {
  isEditMode.value = true
  formItem.value = {
    id: item.id,
    judul: item.judul,
    kategori: item.kategori,
    tanggal: item.tanggal,
    isi: item.isi,
    gambar: item.gambar
  }
  previewImage.value = item.gambar || null
  isFormSheetOpen.value = true
}

function deleteBerita(item) {
  store.delete(item.id)
  toast.success('Berita kegiatan berhasil dihapus!')
}

function handleSave() {
  if (!formItem.value.judul || !formItem.value.kategori || !formItem.value.tanggal || !formItem.value.isi) {
    toast.error('Mohon lengkapi semua field yang wajib diisi.')
    return
  }

  const payload = {
    judul: formItem.value.judul,
    kategori: formItem.value.kategori,
    tanggal: formItem.value.tanggal,
    isi: formItem.value.isi,
    gambar: previewImage.value,
    sekolah: 'SD Tehnonusa I'
  }

  if (isEditMode.value) {
    store.update(formItem.value.id, payload)
    toast.success('Berita kegiatan berhasil diperbarui!')
  } else {
    store.add(payload)
    toast.success('Berita kegiatan berhasil dipublikasikan!')
  }

  isFormSheetOpen.value = false
}

// --- Computed Details for DataSheet ---
const detailSections = computed(() => {
  if (!selectedBerita.value) return []
  return [
    {
      id: 'general',
      title: 'Informasi Umum',
      fields: [
        { label: 'Judul Berita', value: selectedBerita.value.judul },
        { label: 'Kategori', value: selectedBerita.value.kategori },
        { label: 'Tanggal Publikasi', value: selectedBerita.value.tanggal }
      ]
    }
  ]
})
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 p-1"
  >
    <!-- Header & Stats Card -->
    <PageHeader
      title="Daftar Berita Kegiatan"
      description="Kelola dan Kirim Berita kegiatan"
      :actions="canManage ? [
        {
          label: 'Buat Berita Baru',
          icon: Plus,
          variant: 'default',
          click: openCreateForm
        }
      ] : []"
    />

    <div
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 100 } }"
    >
      <StatCard
        label="Total Berita Terbuat"
        :value="beritaKegiatans.length"
        :icon="Megaphone"
        illustration="open_book"
        variant="primary"
      />
    </div>

    <!-- Main Table -->
    <div
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 200 } }"
    >
      <BeritaKegiatanTable 
        :items="beritaKegiatans" 
        :can-manage="canManage"
        @view="viewBerita" 
        @edit="editBerita" 
        @delete="deleteBerita"
      />
    </div>

    <!-- Form Sheet (Inline Create/Edit) -->
    <Sheet v-model:open="isFormSheetOpen">
      <SheetContent :show-close-button="false" class="sm:max-w-[500px] flex flex-col h-full gap-2">
        <SheetHeader class="border-b border-border pb-3 text-left">
          <SheetTitle class="text-base font-bold text-foreground">
            {{ isEditMode ? 'Edit Berita Kegiatan' : 'Buat Berita Baru' }}
          </SheetTitle>
          <SheetDescription class="text-xs text-muted-foreground">
            {{ isEditMode ? 'Perbarui informasi berita kegiatan sekolah.' : 'Lengkapi formulir di bawah ini untuk mempublikasikan berita kegiatan terbaru.' }}
          </SheetDescription>
        </SheetHeader>

        <div class="flex-1 overflow-y-auto py-6 pr-1 space-y-6 no-scrollbar">
          <Accordion type="multiple" class="w-full" :default-value="['info']">
            <AccordionItem value="info">
              <AccordionTrigger class="text-sm font-semibold">
                Informasi Umum
              </AccordionTrigger>
              <AccordionContent class="space-y-4 pt-3 text-left">
                <div class="space-y-4">
                  <!-- Judul -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Judul Berita</label>
                    <Input v-model="formItem.judul" placeholder="Masukkan judul berita kegiatan..." class="h-10 rounded-xl" />
                  </div>

                  <!-- Kategori & Tanggal -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-muted-foreground">Kategori</label>
                      <Select v-model="formItem.kategori">
                        <SelectTrigger class="h-10 rounded-xl">
                          <SelectValue placeholder="Pilih Kategori" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="AKADEMIK">Akademik</SelectItem>
                          <SelectItem value="KEUANGAN">Keuangan</SelectItem>
                          <SelectItem value="UMUM">Umum</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-muted-foreground">Tanggal Publikasi</label>
                      <Input type="date" v-model="formItem.tanggal" @click="$event.target.showPicker()" class="h-10 rounded-xl" />
                    </div>
                  </div>

                  <!-- Isi -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Isi Berita</label>
                    <Textarea v-model="formItem.isi" placeholder="Ketik isi berita kegiatan di sini..." class="min-h-32 rounded-xl resize-y" />
                  </div>

                  <!-- Gambar Upload Zone -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Gambar / Foto Kegiatan Utama</label>
                    <div 
                      class="relative border border-dashed rounded-xl transition-all duration-200 overflow-hidden cursor-pointer"
                      :class="[
                        isDragging ? 'border-primary bg-primary/5' : 'border-border bg-muted/30 hover:bg-muted/50 hover:border-primary/50',
                        previewImage ? 'border-solid p-1 bg-card' : 'py-6 px-3'
                      ]"
                      @dragover="onDragOver"
                      @dragleave="onDragLeave"
                      @drop="onDrop"
                      @click="triggerFileInput"
                    >
                      <input type="file" ref="fileInput" class="hidden" accept="image/*" @change="handleFileSelect" />
                      <div v-if="!previewImage" class="flex flex-col items-center justify-center text-center gap-1.5">
                        <UploadCloud class="size-6 text-slate-400" />
                        <span class="text-xs font-semibold text-foreground">Klik/seret file ke sini</span>
                        <span class="text-[10px] text-muted-foreground">PNG, JPG, WEBP (Maks 5MB)</span>
                      </div>
                      <div v-else class="relative group rounded-lg overflow-hidden aspect-video max-h-[150px] w-full flex items-center justify-center">
                        <img :src="previewImage" alt="Preview" class="object-cover w-full h-full" />
                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                          <Button type="button" @click.stop="removeImage" variant="destructive" size="xs" class="gap-1.5 rounded-lg font-semibold shadow-lg">
                            <Trash2 class="size-3" />
                            Hapus
                          </Button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </div>

        <FormFooter
          :is-edit="isEditMode"
          entity-name="Berita Kegiatan"
          @save="handleSave"
        />
      </SheetContent>
    </Sheet>

    <!-- Detail Drawer Sheet -->
    <DataSheet
      v-model:open="isDetailSheetOpen"
      :item="selectedBerita"
      title-key="judul"
      description-key="kategori"
      description-prefix="Kategori: "
      badge-key="tanggal"
      :sections="detailSections"
    >
      <template #default>
        <div class="space-y-6 text-left py-4">
          <!-- Accordion for Konten Berita -->
          <Accordion type="multiple" class="w-full" :default-value="['content']">
            <AccordionItem value="content">
              <AccordionTrigger class="text-sm font-semibold">
                Konten Berita
              </AccordionTrigger>
              <AccordionContent class="space-y-4 pt-3">
                <div v-if="selectedBerita?.gambar" class="w-full rounded-xl overflow-hidden aspect-video bg-black/5 flex items-center justify-center mb-4">
                  <img :src="selectedBerita.gambar" class="object-cover w-full h-full" />
                </div>
                <div class="text-sm text-foreground leading-relaxed whitespace-pre-wrap">
                  {{ selectedBerita?.isi }}
                </div>
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </div>
      </template>
    </DataSheet>
  </div>
</template>
