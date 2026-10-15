<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { toast } from 'vue-sonner'
import PageHeader from '@/components/page-header/PageHeader.vue'
import DatePicker from '@/components/date-picker/DatePicker.vue'
import { getLocalTimeZone, today } from '@internationalized/date'
import { formatDate } from '@/utils/formatDate'
import { glassFade } from '@/config/motion'
import { Field, FieldContent, FieldDescription, FieldLabel } from '@/components/ui/field'
import { InputGroup, InputGroupText, InputGroupInput } from '@/components/ui/input-group'
import {
  Building2,
  Banknote,
  CheckCircle2,
  Sparkles,
  ShieldCheck,
  Zap,
  Info,
  HelpCircle,
  Layers,
  Wallet,
  PiggyBank,
  Calendar
} from 'lucide-vue-next'

const router = useRouter()

const activeDate = ref(today(getLocalTimeZone()))

const form = ref({
  deskripsi: '',
  kategori: 'Infrastruktur',
  jumlah: 0,
  catatan: ''
})

const formatCurrency = val => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val)
}

const isSubmitting = ref(false)

const defaultPengeluaran = [
  {
    id: 'TRX-101',
    tanggal: '14 Mar 2024',
    tanggalRaw: { year: 2024, month: 3, day: 14 },
    deskripsi: 'Renovasi Lab Komputer',
    kategori: 'Infrastruktur',
    jumlah: 45000000,
    status: 'APPROVED'
  },
  {
    id: 'TRX-102',
    tanggal: '12 Mar 2024',
    tanggalRaw: { year: 2024, month: 3, day: 12 },
    deskripsi: 'Gaji Guru Honorer Mar',
    kategori: 'Salaries',
    jumlah: 128500000,
    status: 'APPROVED'
  },
  {
    id: 'TRX-103',
    tanggal: '10 Mar 2024',
    tanggalRaw: { year: 2024, month: 3, day: 10 },
    deskripsi: 'Seminar Pendidikan Karakter',
    kategori: 'Events',
    jumlah: 12000000,
    status: 'PENDING'
  },
  {
    id: 'TRX-104',
    tanggal: '08 Mar 2024',
    tanggalRaw: { year: 2024, month: 3, day: 8 },
    deskripsi: 'Pengadaan Buku Perpustakaan',
    kategori: 'Infrastruktur',
    jumlah: 35200000,
    status: 'APPROVED'
  }
]

const handleSubmit = () => {
  if (!form.value.deskripsi) {
    toast.error('Mohon isi deskripsi pengeluaran!')
    return
  }
  if (!form.value.jumlah || form.value.jumlah <= 0) {
    toast.error('Mohon isi nominal pengeluaran yang valid!')
    return
  }

  isSubmitting.value = true

  setTimeout(() => {
    const stored = localStorage.getItem('gerbangsekolah_dana_yayasan_pengeluaran') || localStorage.getItem('cerdasbangsa_dana_yayasan_pengeluaran')
    let list = stored ? JSON.parse(stored) : [...defaultPengeluaran]

    const newExpense = {
      id: `TRX-${Math.floor(100 + Math.random() * 900)}`,
      tanggal: formatDate(activeDate.value),
      tanggalRaw: {
        year: activeDate.value.year,
        month: activeDate.value.month,
        day: activeDate.value.day
      },
      deskripsi: form.value.deskripsi,
      kategori: form.value.kategori,
      jumlah: form.value.jumlah,
      status: 'APPROVED' // Auto-approved in mock
    }

    // Insert at the beginning of the list
    list.unshift(newExpense)

    localStorage.setItem('gerbangsekolah_dana_yayasan_pengeluaran', JSON.stringify(list))

    // Update the "Dana Terpakai" calculations locally if they exist
    // (Since we are using standard localStorage, we can keep the sum updated or let it load dynamically)

    toast.success('Pengeluaran Yayasan Berhasil Diinput!')
    isSubmitting.value = false
    router.push('/keuangan/kelola-dana-yayasan')
  }, 1000)
}
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="h-full space-y-6 text-foreground pb-10 max-w-6xl mx-auto w-full text-left"
  >
    <!-- Header -->
    <PageHeader
      title="Input Pengeluaran Yayasan Baru"
      description="Lengkapi formulir di bawah ini untuk mencatat pengeluaran baru dari alokasi dana yayasan."
      back
      @back="router.push('/keuangan/kelola-dana-yayasan')"
    />

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-6 items-start">
      <!-- Left Column: Form -->
      <Card class="shadow-sm border-border bg-card/60 backdrop-blur-md overflow-hidden rounded-xl">
        <div
          class="px-6 py-5 border-b border-border/80 flex justify-between items-center bg-muted/20"
        >
          <div class="flex items-center gap-2.5">
            <div class="p-1.5 bg-primary/10 rounded-lg text-primary">
              <Layers class="w-4.5 h-4.5" />
            </div>
            <div>
              <h2 class="font-bold text-sm tracking-wide text-foreground uppercase">
                FORMULIR PENGELUARAN YAYASAN
              </h2>
              <p class="text-[10px] text-muted-foreground mt-0.5">
                Entri data alokasi dana secara resmi
              </p>
            </div>
          </div>
        </div>

        <form
          @submit.prevent="handleSubmit"
          class="p-6 sm:p-8 space-y-6"
        >
          <!-- Tanggal & Kategori -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Tanggal -->
            <DatePicker
              v-model="activeDate"
              label="Tanggal Pengeluaran"
              placeholder="Pilih Tanggal"
            />

            <!-- Kategori -->
            <Field>
              <FieldLabel>Kategori Pengeluaran</FieldLabel>
              <FieldContent>
                <Select v-model="form.kategori">
                  <SelectTrigger class="h-11 bg-background/50 border-border">
                    <SelectValue placeholder="Pilih Kategori" />
                  </SelectTrigger>
                  <SelectContent class="bg-card border-border">
                    <SelectItem value="Infrastruktur">Infrastruktur</SelectItem>
                    <SelectItem value="Salaries">Gaji & Tunjangan (Salaries)</SelectItem>
                    <SelectItem value="Events">Kegiatan Siswa / Acara (Events)</SelectItem>
                    <SelectItem value="Operasional">Operasional</SelectItem>
                    <SelectItem value="Lainnya">Lain-Lain</SelectItem>
                  </SelectContent>
                </Select>
              </FieldContent>
            </Field>
          </div>

          <!-- Deskripsi Pengeluaran -->
          <Field>
            <FieldLabel
              >Deskripsi / Nama Pengeluaran <span class="text-destructive">*</span></FieldLabel
            >
            <FieldContent>
              <InputGroup>
                <InputGroupText>
                  <Building2 class="w-4 h-4 text-muted-foreground" />
                </InputGroupText>
                <InputGroupInput
                  v-model="form.deskripsi"
                  placeholder="Contoh: Pembelian Projector Lab Fisika"
                  required
                />
              </InputGroup>
            </FieldContent>
          </Field>

          <!-- Nominal -->
          <Field>
            <FieldLabel>Nilai Nominal (Rp) <span class="text-destructive">*</span></FieldLabel>
            <FieldContent>
              <InputGroup>
                <InputGroupText class="font-bold text-xs">Rp</InputGroupText>
                <InputGroupInput
                  v-model.number="form.jumlah"
                  type="number"
                  placeholder="0"
                  min="1"
                  required
                />
              </InputGroup>
            </FieldContent>
            <FieldDescription class="flex items-center gap-1">
              <HelpCircle class="w-3 h-3 shrink-0" />
              Masukkan nominal bersih yang dikeluarkan untuk pembiayaan ini.
            </FieldDescription>
          </Field>

          <!-- Catatan / Memo -->
          <Field>
            <FieldLabel>Catatan Tambahan (Memo Internal)</FieldLabel>
            <FieldContent>
              <Textarea
                v-model="form.catatan"
                placeholder="Tambahkan informasi khusus seperti vendor pelaksana, nomor nota fisik, penanggung jawab lapangan, dll..."
                class="min-h-[100px] resize-none bg-background/50 border-border focus-visible:ring-primary/20 text-sm"
              />
            </FieldContent>
          </Field>

          <!-- Buttons -->
          <div class="border-t border-border/80 pt-6 flex items-center justify-end gap-3.5">
            <Button
              type="button"
              variant="outline"
              class="px-5 h-11 rounded-lg font-semibold border-2 hover:bg-muted/50"
              @click="router.push('/keuangan/kelola-dana-yayasan')"
            >
              Batal
            </Button>

            <Button
              type="submit"
              class="px-6 h-11 rounded-lg font-bold shadow-md bg-primary text-primary-foreground hover:bg-primary/90 transition-all hover:-translate-y-0.5 flex items-center gap-2"
              :disabled="isSubmitting"
            >
              <span
                v-if="isSubmitting"
                class="flex items-center gap-1.5"
              >
                <span
                  class="w-4 h-4 border-2 border-primary-foreground border-t-transparent animate-spin rounded-full"
                ></span>
                Menyimpan...
              </span>
              <span
                v-else
                class="flex items-center gap-2"
              >
                Simpan Pengeluaran
                <CheckCircle2 class="w-4.5 h-4.5" />
              </span>
            </Button>
          </div>
        </form>
      </Card>

      <!-- Right Column: Premium Live Preview -->
      <div class="space-y-6">
        <!-- Live Card Preview -->
        <div
          class="relative overflow-hidden rounded-2xl shadow-xl border border-primary/20 bg-gradient-to-br from-primary via-primary/90 to-primary/75 p-6 text-primary-foreground transition-all duration-300 hover:scale-[1.02]"
        >
          <div
            class="absolute -right-16 -top-16 w-40 h-40 rounded-full bg-white/10 blur-2xl pointer-events-none"
          ></div>
          <div
            class="absolute -left-12 -bottom-12 w-36 h-36 rounded-full bg-black/10 blur-xl pointer-events-none"
          ></div>

          <div class="relative z-10 space-y-6 flex flex-col justify-between min-h-[260px]">
            <!-- Top Section -->
            <div class="flex justify-between items-start gap-4">
              <div class="space-y-1.5 flex-1 min-w-0 pr-2">
                <span
                  class="text-[9px] tracking-widest font-black uppercase text-primary-foreground/70"
                  >KUITANSI PENGELUARAN DANA YAYASAN</span
                >
                <h3 class="text-xl font-extrabold tracking-tight leading-tight truncate">
                  {{ form.deskripsi || 'Nama/Deskripsi Pengeluaran' }}
                </h3>
                <div
                  class="inline-flex items-center gap-1 bg-white/10 px-2 py-0.5 rounded text-[10px] font-mono border border-white/15"
                >
                  UNIT: SMA Unggul Bangsa
                </div>
              </div>
              <div
                class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md flex items-center justify-center border border-white/20 shadow-inner shrink-0"
              >
                <Banknote class="w-5.5 h-5.5 text-primary-foreground" />
              </div>
            </div>

            <!-- Middle Section: Kategori & Nominal -->
            <div class="py-4 border-y border-white/10 flex justify-between items-center">
              <div>
                <p
                  class="text-[9px] uppercase tracking-wider text-primary-foreground/75 font-semibold"
                >
                  Kategori
                </p>
                <div class="mt-1">
                  <Badge
                    class="bg-white/20 text-white border border-white/30 text-[10px] px-2.5 py-0.5 font-semibold tracking-wider uppercase"
                  >
                    {{ form.kategori }}
                  </Badge>
                </div>
              </div>
              <div class="text-right">
                <p
                  class="text-[9px] uppercase tracking-wider text-primary-foreground/75 font-semibold"
                >
                  Nominal Bersih
                </p>
                <p class="text-xl font-black mt-0.5 tracking-tight">
                  {{ formatCurrency(form.jumlah || 0) }}
                </p>
              </div>
            </div>

            <!-- Bottom Section: Dates & Status -->
            <div class="flex justify-between items-end gap-4">
              <div class="space-y-3">
                <div>
                  <span class="text-[8px] uppercase text-primary-foreground/60 block"
                    >Tanggal Transaksi</span
                  >
                  <span class="text-[11px] font-bold text-white/95 flex items-center gap-1 mt-0.5">
                    <Calendar class="w-3 h-3 text-white/50" />
                    {{ formatDate(activeDate) }}
                  </span>
                </div>
              </div>
              <Badge
                class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 shrink-0 mb-0.5 shadow-sm text-[10px] px-2 py-0.5"
              >
                ● APPROVED
              </Badge>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep([data-slot='select-trigger']) {
  width: 100% !important;
  height: 2.75rem !important; /* matches h-11 */
  background-color: var(--background);
  border-color: var(--border);
  padding-left: 0.875rem !important;
  border-radius: var(--radius) !important;
}
</style>
