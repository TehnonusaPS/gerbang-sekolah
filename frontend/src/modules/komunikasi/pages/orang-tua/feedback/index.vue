<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/authStore'
import { useFeedbackStore } from '@/stores/feedbackStore'
import FeedbackDetailModal from '../../../components/FeedbackDetailModal.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { glassSlide, glassFade } from '@/config/motion'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'
import { 
  MessageSquare, 
  ShieldCheck, 
  Lock, 
  Plus, 
  Eye,
  ChevronLeft
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const feedbackStore = useFeedbackStore()

// --- Parent Navigation State ---
// 'list' | 'create' | 'edit'
const currentView = ref('list')
const editingId = ref(null)

// --- Parent Form State ---
const form = ref({
  kategori: '',
  kelas: '',
  judul: '',
  pesan: ''
})

// --- Parent Filtered Feedbacks ---
const parentFeedbacks = computed(() => {
  return feedbackStore.items
})

onMounted(() => {
  feedbackStore.fetchFeedbacks()
})

// --- CRUD Operations ---
function openCreateView() {
  form.value = {
    kategori: '',
    kelas: '',
    judul: '',
    pesan: ''
  }
  currentView.value = 'create'
}

function openEditView(item) {
  editingId.value = item.id
  form.value = {
    kategori: item.kategori,
    kelas: item.kelas,
    judul: item.judul,
    pesan: item.pesan
  }
  currentView.value = 'edit'
}

function validateFeedback() {
  if (!form.value.kategori || !form.value.kelas || !form.value.judul || !form.value.pesan) {
    toast.error('Gagal mengirim aduan', {
      description: 'Mohon isi seluruh bidang formulir terlebih dahulu.'
    })
    return false
  }
  return true
}

async function submitFeedback() {
  try {
    if (currentView.value === 'create') {
      await feedbackStore.add({
        kategori: form.value.kategori,
        kelas: form.value.kelas,
        judul: form.value.judul,
        pesan: form.value.pesan
      })
      toast.success('Keluhan/Saran Berhasil Dikirim secara Anonim!', {
        description: 'Masukan Anda telah berhasil ditambahkan ke papan evaluasi Kepala Sekolah.'
      })
    } else if (currentView.value === 'edit') {
      feedbackStore.update(editingId.value, {
        kategori: form.value.kategori,
        kelas: form.value.kelas,
        judul: form.value.judul,
        pesan: form.value.pesan
      })
      toast.success('Keluhan/Saran Berhasil Diperbarui!', {
        description: 'Perubahan masukan Anda telah disimpan secara anonim.'
      })
    }
    // Return to list view
    currentView.value = 'list'
  } catch (error) {
    toast.error('Gagal mengirim aduan', {
      description: 'Terjadi kesalahan sistem saat mengirim aduan.'
    })
  }
}

const isDetailOpen = ref(false)
const selectedFeedback = ref(null)

// --- Detail View Action ---
function openDetail(item) {
  selectedFeedback.value = item
  isDetailOpen.value = true
}

function cancelForm() {
  currentView.value = 'list'
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const datePart = dateStr.includes('T') ? dateStr.split('T')[0] : dateStr.split(' ')[0]
  const [year, month, day] = datePart.split('-')
  return `${day}/${month}/${year}`
}

const categoryBadgeClass = (kategori) => {
  switch (kategori) {
    case 'AKADEMIK':
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 hover:bg-blue-500/20 shadow-xs'
    case 'FASILITAS':
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 shadow-xs'
    case 'PELAYANAN':
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 shadow-xs'
    case 'KEUANGAN':
      return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 hover:bg-purple-500/20 shadow-xs'
    default:
      return 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border border-slate-500/20 hover:bg-slate-500/20 shadow-xs'
  }
}
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 p-1"
  >
    <div class="space-y-6">
      
      <!-- VIEW: LIST / INDEX -->
      <div v-if="currentView === 'list'" class="space-y-6">
        <PageHeader
          title="Riwayat Feedback & Saran Anda"
          description="Kelola dan pantau keluhan, masukan, atau saran yang telah Anda kirimkan untuk bahan evaluasi sekolah secara aman."
          :actions="[
            {
              label: 'Buat Aduan Baru',
              icon: Plus,
              variant: 'default',
              click: openCreateView
            }
          ]"
        />

        <!-- History Table Card -->
        <Card
          v-motion
          :initial="glassSlide.initial"
          :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 100 } }"
          class="rounded-2xl border-border bg-card shadow-xs overflow-hidden"
        >
          <CardContent class="p-0">
            <div class="overflow-x-auto">
              <Table>
                <TableHeader class="bg-muted/40 border-b border-border/60">
                  <TableRow>
                    <TableHead class="font-bold text-foreground py-4 px-6 text-xs uppercase tracking-wider">JUDUL MASUKAN / SARAN</TableHead>
                    <TableHead class="font-bold text-foreground py-4 px-4 text-xs uppercase tracking-wider w-[140px]">KATEGORI</TableHead>
                    <TableHead class="font-bold text-foreground py-4 px-4 text-xs uppercase tracking-wider w-[130px]">TANGGAL</TableHead>
                    <TableHead class="font-bold text-foreground py-4 px-4 text-xs uppercase tracking-wider w-[150px] text-center">STATUS</TableHead>
                    <TableHead class="font-bold text-foreground py-4 px-6 text-xs uppercase tracking-wider text-center w-[100px]">AKSI</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow 
                    v-for="item in parentFeedbacks" 
                    :key="item.id" 
                    class="group transition-all border-b border-border/50"
                  >
                    <!-- Title & description -->
                    <TableCell class="py-4 px-6">
                      <div 
                        class="font-bold text-foreground leading-snug text-sm sm:text-base group-hover:text-primary transition-colors cursor-pointer text-left" 
                        @click="openDetail(item)"
                      >
                        {{ item.judul }}
                      </div>
                      <div class="text-xs text-muted-foreground mt-1 line-clamp-1 max-w-[450px] leading-relaxed text-left">
                        {{ item.pesan }}
                      </div>
                      <div class="flex items-center gap-1.5 mt-2 text-[10px] text-muted-foreground/90 font-medium text-left">
                        <Lock class="size-3 text-emerald-500" />
                        Aduan Kelas: <span class="font-semibold text-foreground/80">{{ item.kelas }}</span>
                      </div>
                    </TableCell>

                    <!-- Kategori -->
                    <TableCell class="py-4 px-4 text-left">
                      <Badge :class="categoryBadgeClass(item.kategori)" class="rounded-full px-2.5 py-0.5 font-bold uppercase tracking-wider text-[9px]">
                        {{ item.kategori }}
                      </Badge>
                    </TableCell>

                    <!-- Tanggal -->
                    <TableCell class="py-4 px-4 text-xs font-mono text-muted-foreground font-medium text-left">
                      {{ formatDate(item.tanggal) }}
                    </TableCell>

                    <!-- Status -->
                    <TableCell class="py-4 px-4 text-center">
                      <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        <ShieldCheck class="size-3" />
                        Terkirim Anonim
                      </span>
                    </TableCell>

                    <!-- Actions -->
                    <TableCell class="py-3 px-6 text-center">
                      <div class="flex items-center justify-center gap-3">
                        <!-- Detail Button -->
                        <button 
                          @click="openDetail(item)" 
                          class="flex flex-col items-center gap-0.5 text-blue-600 dark:text-blue-400 hover:text-blue-700 font-semibold text-xs cursor-pointer focus:outline-none"
                        >
                          <Eye class="size-4" />
                          <span>Detail</span>
                        </button>
                      </div>
                    </TableCell>
                  </TableRow>

                  <!-- Empty state for Parent -->
                  <TableRow v-if="parentFeedbacks.length === 0">
                    <TableCell colspan="5" class="py-16 text-center text-muted-foreground">
                      <div class="flex flex-col items-center justify-center gap-2 max-w-sm mx-auto">
                        <MessageSquare class="size-8 text-muted-foreground/60 animate-pulse" />
                        <p class="font-bold text-base text-foreground/80">Belum Ada Masukan</p>
                        <p class="text-xs text-muted-foreground/80 leading-relaxed">
                          Anda belum pernah mengirimkan keluhan atau saran ke sekolah. Klik tombol di atas untuk membuat masukan baru.
                        </p>
                        <Button @click="openCreateView" class="bg-emerald-600 hover:bg-emerald-500 text-white mt-2 text-xs rounded-xl">
                          Buat Aduan Baru
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- VIEW: CREATE OR EDIT FORM -->
      <div v-else-if="currentView === 'create' || currentView === 'edit'" class="space-y-6">
        <!-- Form Header -->
        <PageHeader
          :title="currentView === 'create' ? 'Hubungi Sekolah & Kirim Masukan' : 'Perbarui Masukan Anda'"
          :description="currentView === 'create' ? 'Kirimkan saran, kritik, atau keluhan Anda secara anonim/rahasia untuk mengevaluasi pelayanan sekolah.' : 'Edit judul, kategori, atau isi dari saran/keluhan yang telah Anda kirim sebelumnya.'"
          :actions="[
            {
              label: 'Kembali',
              icon: ChevronLeft,
              variant: 'outline',
              click: cancelForm
            }
          ]"
        />

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_350px] gap-6">
          <!-- Form Container -->
          <Card
            v-motion
            :initial="glassSlide.initial"
            :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 100 } }"
            class="rounded-2xl border-border bg-card shadow-sm overflow-hidden"
          >
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-border flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 bg-muted/30">
              <div class="flex items-center gap-2 font-bold text-xs sm:text-sm tracking-wider sm:tracking-widest uppercase text-left">
                <Lock class="w-4 h-4 sm:w-5 sm:h-5 text-emerald-500 shrink-0" />
                <span>{{ currentView === 'create' ? 'FORMULIR MASUKAN / SARAN SECURE' : 'FORMULIR EDIT ADUAN SECURE' }}</span>
              </div>
              <div class="text-[10px] sm:text-xs text-muted-foreground font-medium flex items-center gap-1.5 self-start sm:self-auto">
                <ShieldCheck class="w-4 h-4 text-emerald-500 shrink-0" />
                Anonim Terenkripsi
              </div>
            </div>

            <form @submit.prevent="submitFeedback" class="p-4 sm:p-6 space-y-4 sm:space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <!-- Category Selector -->
                <div class="space-y-2 text-left">
                  <label class="text-sm font-semibold text-foreground">Kategori Masukan / Keluhan</label>
                  <Select v-model="form.kategori" required>
                    <SelectTrigger class="h-11 rounded-xl">
                      <SelectValue placeholder="Pilih Kategori" />
                    </SelectTrigger>
                    <SelectContent class="rounded-xl shadow-md border-border bg-card w-[var(--reka-select-trigger-width)]">
                      <SelectItem value="AKADEMIK" class="whitespace-normal leading-snug">Akademik (Guru, Kurikulum, Pembelajaran)</SelectItem>
                      <SelectItem value="FASILITAS" class="whitespace-normal leading-snug">Fasilitas (Gedung, AC, Kelas, Kantin)</SelectItem>
                      <SelectItem value="PELAYANAN" class="whitespace-normal leading-snug">Pelayanan (Administrasi TU, Kebersihan)</SelectItem>
                      <SelectItem value="KEUANGAN" class="whitespace-normal leading-snug">Keuangan (SPP, Tagihan, Pembayaran)</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <!-- Student's Class Selector -->
                <div class="space-y-2 text-left">
                  <label class="text-sm font-semibold text-foreground">Kelas Anak Anda (Sebagai Bahan Analisis)</label>
                  <Select v-model="form.kelas" required>
                    <SelectTrigger class="h-11 rounded-xl">
                      <SelectValue placeholder="Pilih Kelas" />
                    </SelectTrigger>
                    <SelectContent class="rounded-xl shadow-md border-border bg-card w-[var(--reka-select-trigger-width)]">
                      <SelectItem v-for="c in ['1A', '1B', '2A', '2B', '3A', '3B', '4A', '4B', '5A', '5B', '6A', '6B']" :key="c" :value="c">
                        Kelas {{ c }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <!-- Subject Input -->
              <div class="space-y-2 text-left">
                <label class="text-sm font-semibold text-foreground">Judul Masukan / Keluhan</label>
                <Input 
                  v-model="form.judul" 
                  placeholder="Contoh: Opsi Pembayaran SPP menggunakan e-wallet" 
                  required 
                  class="h-11 rounded-xl focus-visible:ring-primary focus-visible:border-primary"
                />
              </div>

              <!-- Message Input -->
              <div class="space-y-2 text-left">
                <label class="text-sm font-semibold text-foreground">Isi Keluhan / Saran Secara Lengkap</label>
                <Textarea 
                  v-model="form.pesan" 
                  placeholder="Deskripsikan saran, keluhan, atau kritik Anda secara rinci. Harap tidak memasukkan nama pribadi atau nama anak Anda di dalam pesan jika ingin tetap menjaga privasi..." 
                  required 
                  class="min-h-[160px] rounded-xl resize-none focus-visible:ring-primary focus-visible:border-primary"
                />
              </div>

              <!-- Privacy Guarantee Alert -->
              <div class="bg-emerald-500/5 border border-emerald-500/10 p-4 rounded-xl flex gap-3 text-xs text-emerald-800 dark:text-emerald-400">
                <ShieldCheck class="w-5 h-5 shrink-0 text-emerald-500" />
                <div class="text-left leading-normal font-medium">
                  <p class="font-bold mb-1">Proteksi Keamanan Identitas Wali Murid</p>
                  <p>
                    Sistem manajemen sekolah secara otomatis merahasiakan identitas pengirim (Nama, Email, dsb.). Pihak manajemen dan Kepala Sekolah hanya dapat mengulas data masukan berdasarkan judul, pesan, kategori, dan kelas anak Anda.
                  </p>
                </div>
              </div>

              <FormFooter
                :is-edit="currentView === 'edit'"
                entity-name="Aduan & Masukan"
                :label="currentView === 'create' ? 'Kirim Aduan' : 'Simpan Perubahan'"
                :before-confirm="validateFeedback"
                @save="submitFeedback"
              />
            </form>
          </Card>

          <!-- Right Side Info Box -->
          <div
            v-motion
            :initial="glassSlide.initial"
            :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 200 } }"
            class="space-y-6 text-left"
          >
            <Card class="p-5 rounded-2xl shadow-xs border border-border">
              <h3 class="font-bold text-sm tracking-wider uppercase mb-3 text-primary">ALUR TINDAK LANJUT</h3>
              <ol class="space-y-3.5 text-xs text-muted-foreground font-medium list-decimal pl-4 leading-relaxed">
                <li>Keluhan/saran dikirimkan dan dienkripsi oleh server tanpa data nama pengirim.</li>
                <li>Masuk langsung ke **Papan Keluhan Kepala Sekolah** untuk ulasan mingguan.</li>
                <li>Kepala Sekolah meneruskan masukan ke jajaran guru/staf administrasi sesuai kategori.</li>
                <li>Evaluasi perbaikan segera dijadwalkan dan diterapkan di lingkungan sekolah.</li>
              </ol>
            </Card>


          </div>
        </div>
      </div>
    </div>

    <!-- Details Modal Dialog -->
    <FeedbackDetailModal 
      v-model:open="isDetailOpen" 
      :feedback="selectedFeedback" 
    />
  </div>
</template>
