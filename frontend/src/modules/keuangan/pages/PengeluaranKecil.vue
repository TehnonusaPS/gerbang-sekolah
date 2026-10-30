<script setup>
import { ref } from 'vue'
import { toast } from 'vue-sonner'
import { Card } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import FormFooter from '@/components/forms/FormFooter.vue'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import {
  PlusCircle,
  Banknote,
  Receipt,
  UploadCloud,
  CheckCircle2,
  Wallet,
  Lightbulb
} from 'lucide-vue-next'

const paymentMethod = ref('cash')
const isSaving = ref(false)

const handleSavePengeluaran = () => {
  isSaving.value = true
  setTimeout(() => {
    isSaving.value = false
    toast.success('Pengeluaran kas kecil berhasil disimpan')
  }, 500)
}

const recentActivities = ref([
  {
    id: 1,
    amount: 'Rp 45.000',
    category: 'REFRESHMENTS',
    desc: 'Air Mineral Gelas 2 Dus (Rapat Guru)',
    date: '24 Okt, 10:15',
    badgeClass: 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400'
  },
  {
    id: 2,
    amount: 'Rp 120.000',
    category: 'TRANSPORTATION',
    desc: 'Grab Express - Kirim Dokumen...',
    date: '23 Okt, 14:30',
    badgeClass: 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-400'
  },
  {
    id: 3,
    amount: 'Rp 15.000',
    category: 'OFFICE SUPPLIES',
    desc: 'Materai 10000 (1 pcs)',
    date: '23 Okt, 09:12',
    badgeClass: 'bg-emerald-900 text-emerald-100 dark:bg-emerald-500/20 dark:text-emerald-400'
  }
])
</script>

<template>
  <div class="h-full space-y-6 text-foreground pb-10">
    <!-- Header / Breadcrumb -->
    <div>
      <div class="flex items-center gap-2 text-sm text-muted-foreground mb-4">
        <span>Dashboard</span>
        <span>›</span>
        <span>Kas Kecil</span>
        <span>›</span>
        <span class="font-semibold text-foreground">Input Pengeluaran</span>
      </div>
      <h1 class="text-3xl font-bold tracking-tight">Input Pengeluaran Kas Kecil</h1>
      <p class="text-muted-foreground mt-2 text-sm">
        Catat pengeluaran operasional harian sekolah secara detail untuk audit.
      </p>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_350px] gap-6">
      <!-- Left Column: Form -->
      <Card class="overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-muted/30">
          <div class="flex items-center gap-2 font-bold text-sm tracking-widest uppercase">
            <PlusCircle class="w-5 h-5" />
            FORMULIR PENGELUARAN
          </div>
          <div class="text-xs text-muted-foreground font-medium">ID Transaksi: AUTO-PC-2405</div>
        </div>

        <div class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-sm font-semibold">Tanggal Transaksi</label>
              <Input
                type="date"
                value="2026-05-28"
                class="text-muted-foreground"
              />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-semibold">Kategori Pengeluaran</label>
              <Select default-value="office">
                <SelectTrigger>
                  <SelectValue placeholder="Pilih Kategori" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="office">Office Supplies</SelectItem>
                  <SelectItem value="transport">Transportation</SelectItem>
                  <SelectItem value="refresh">Refreshments</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold">Jumlah Nominal (Rp)</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 font-bold text-muted-foreground"
                >Rp</span
              >
              <Input
                type="text"
                value="0"
                class="pl-12 text-2xl font-bold h-14"
              />
            </div>
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold">Deskripsi / Catatan</label>
            <Textarea
              placeholder="Contoh: Pembelian kertas A4 5 rim untuk ruang guru"
              class="min-h-[120px] resize-none"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-sm font-semibold">Metode Pembayaran</label>
              <div class="flex gap-3">
                <Button
                  type="button"
                  :variant="paymentMethod === 'cash' ? 'default' : 'outline'"
                  class="flex-1 flex items-center gap-2 h-11"
                  @click="paymentMethod = 'cash'"
                  :class="paymentMethod === 'cash' ? 'bg-primary text-primary-foreground' : ''"
                >
                  <Banknote class="w-4 h-4" />
                  Cash
                </Button>
                <Button
                  type="button"
                  :variant="paymentMethod === 'reimburse' ? 'default' : 'outline'"
                  class="flex-1 flex items-center gap-2 h-11"
                  @click="paymentMethod = 'reimburse'"
                  :class="paymentMethod === 'reimburse' ? 'bg-primary text-primary-foreground' : ''"
                >
                  <Receipt class="w-4 h-4" />
                  Reimburse
                </Button>
              </div>
            </div>
            <div class="space-y-2">
              <label class="text-sm font-semibold">Unggah Kuitansi / Nota</label>
              <div
                class="border-2 border-dashed border-border rounded-lg h-11 flex items-center justify-center text-sm text-muted-foreground hover:bg-muted/50 cursor-pointer transition-colors gap-2"
              >
                <UploadCloud class="w-4 h-4" />
                <span>Klik untuk unggah (Max 2MB)</span>
              </div>
            </div>
          </div>

          <FormFooter
            :loading="isSaving"
            entity-name="Pengeluaran Kas Kecil"
            label="Simpan Pengeluaran"
            @save="handleSavePengeluaran"
          />
        </div>
      </Card>

      <!-- Right Column: Info & Stats -->
      <div class="space-y-6">
        <!-- Recent Activities -->
        <Card class="shadow-sm">
          <div
            class="px-5 py-4 flex justify-between items-center border-b border-border bg-muted/20"
          >
            <h3 class="font-bold text-sm tracking-widest uppercase">AKTIVITAS TERAKHIR</h3>
            <a
              href="#"
              class="text-xs font-semibold hover:underline"
              >Lihat Semua</a
            >
          </div>
          <div class="divide-y divide-border">
            <div
              v-for="act in recentActivities"
              :key="act.id"
              class="p-5"
            >
              <div class="flex justify-between items-start mb-1">
                <p class="font-bold text-base">{{ act.amount }}</p>
                <span
                  class="text-[9px] font-bold tracking-wider px-2 py-0.5 rounded-full uppercase"
                  :class="act.badgeClass"
                >
                  {{ act.category }}
                </span>
              </div>
              <p class="text-sm text-foreground font-medium mb-1 truncate">{{ act.desc }}</p>
              <p class="text-xs text-muted-foreground font-medium italic">{{ act.date }}</p>
            </div>
          </div>
        </Card>

        <!-- Sisa Anggaran Box -->
        <div
          class="bg-slate-900 text-white p-6 rounded-xl shadow-md border border-slate-800 relative overflow-hidden"
        >
          <div
            class="absolute inset-0 bg-gradient-to-br from-slate-800/50 to-transparent pointer-events-none"
          ></div>
          <div class="relative z-10">
            <div class="flex items-center gap-2 mb-4 text-slate-300">
              <Wallet class="w-5 h-5" />
              <h3 class="font-semibold text-sm">Sisa Anggaran Operasional</h3>
            </div>
            <div class="flex items-baseline gap-2 mb-3">
              <h2 class="text-3xl font-black tracking-tight">Rp 2.450.000</h2>
              <span class="text-xs font-medium text-slate-400">dari Rp 5jt</span>
            </div>
            <!-- Progress Bar -->
            <div class="h-2.5 w-full bg-slate-800 rounded-full mb-3 overflow-hidden flex">
              <div
                class="h-full bg-emerald-400 w-[49%] rounded-full shadow-[0_0_10px_rgba(52,211,153,0.5)]"
              ></div>
            </div>
            <p class="text-[11px] text-slate-400 italic font-medium">
              *Anggaran periode Oktober 2023
            </p>
          </div>
        </div>

        <!-- Tips Box -->
        <div class="bg-muted/50 p-5 rounded-xl border border-border shadow-sm">
          <div class="flex gap-3">
            <div class="mt-0.5">
              <Lightbulb class="w-5 h-5 text-foreground" />
            </div>
            <div>
              <h4 class="font-bold text-sm mb-1.5">Tips Keamanan</h4>
              <p class="text-xs text-muted-foreground leading-relaxed font-medium">
                Selalu pastikan foto kuitansi terlihat jelas dan nomor nota terbaca untuk
                mempermudah proses audit bulanan.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
