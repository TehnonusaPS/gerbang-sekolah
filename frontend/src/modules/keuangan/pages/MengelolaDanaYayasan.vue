<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import {
  Wallet,
  Banknote,
  PiggyBank,
  PlusCircle,
  ChevronRight,
  CheckCircle2,
  Download,
  MoreHorizontal,
  Pencil
} from 'lucide-vue-next'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import StatCard from '@/components/stat-card/StatCard.vue'

const router = useRouter()

const goToTambahPengeluaran = () => {
  router.push('/keuangan/kelola-dana-yayasan/tambah')
}

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

const pengeluaranList = ref([])

onMounted(() => {
  const stored = localStorage.getItem('gerbangsekolah_dana_yayasan_pengeluaran') || localStorage.getItem('cerdasbangsa_dana_yayasan_pengeluaran')
  if (stored) {
    pengeluaranList.value = JSON.parse(stored)
  } else {
    pengeluaranList.value = [...defaultPengeluaran]
  }
  localStorage.setItem(
    'gerbangsekolah_dana_yayasan_pengeluaran',
    JSON.stringify(pengeluaranList.value)
  )
})

const formatDateWithBr = dateStr => {
  if (!dateStr) return ''
  const parts = dateStr.split(' ')
  if (parts.length >= 3) {
    return `${parts[0]} ${parts[1]}<br/>${parts[2]}`
  }
  return dateStr
}

const formatDescWithBr = descStr => {
  if (!descStr) return ''
  return descStr.replace(/\s/g, ' ')
}

const formatAmount = val => {
  return new Intl.NumberFormat('id-ID').format(val)
}

// Calculate total used and remaining dynamically
const totalAlokasi = 2450000000
const totalTerpakai = computed(() => {
  return pengeluaranList.value.reduce((acc, curr) => acc + curr.jumlah, 0)
})
const sisaAnggaran = computed(() => {
  return totalAlokasi - totalTerpakai.value
})
const realisasiPersen = computed(() => {
  return Math.round((totalTerpakai.value / totalAlokasi) * 100)
})
</script>

<template>
  <div class="space-y-6 w-full mx-auto px-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-foreground">Manajemen Dana Yayasan</h1>
        <p class="text-sm text-muted-foreground mt-1">Unit Sekolah: SMA Unggul Bangsa</p>
      </div>
      <Button
        @click="goToTambahPengeluaran"
        class="bg-foreground text-background hover:bg-foreground/90 font-semibold px-4"
      >
        <PlusCircle class="w-4 h-4 mr-2" /> Pengeluaran Baru
      </Button>
    </div>

    <!-- Top Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Card 1 -->
      <StatCard
        label="Total Alokasi Dana"
        value="Rp 2.450.000.000"
        sub="Tahun Ajaran 2024/2025"
        variant="blue"
        :icon="Wallet"
      />

      <!-- Card 2 -->
      <StatCard
        label="Dana Terpakai"
        :value="'Rp ' + formatAmount(totalTerpakai)"
        sub="Realisasi Anggaran"
        :progress="realisasiPersen"
        variant="primary"
        :icon="Banknote"
      />

      <!-- Card 3 -->
      <StatCard
        label="Sisa Anggaran"
        :value="'Rp ' + formatAmount(sisaAnggaran)"
        sub="Anggaran Tersedia"
        variant="emerald"
        :icon="PiggyBank"
      />
    </div>

    <!-- Middle Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Monitoring Pengeluaran (Left, span 2) -->
      <Card class="lg:col-span-2 shadow-sm border-border">
        <div class="p-5 flex justify-between items-center border-b border-border">
          <h3 class="font-bold text-lg text-foreground">Monitoring Pengeluaran Terbaru</h3>
          <Button
            variant="ghost"
            class="text-sm font-semibold p-0 h-auto hover:bg-transparent hover:text-primary"
          >
            Lihat Semua <ChevronRight class="w-4 h-4 ml-1" />
          </Button>
        </div>
        <Table>
          <TableHeader class="bg-muted/30">
            <TableRow class="hover:bg-transparent">
              <TableHead class="font-semibold text-muted-foreground py-4 w-[50px] text-center"
                >No</TableHead
              >
              <TableHead class="font-semibold text-muted-foreground py-4 w-[100px]"
                >Tanggal</TableHead
              >
              <TableHead class="font-semibold text-muted-foreground py-4">Deskripsi</TableHead>
              <TableHead class="font-semibold text-muted-foreground py-4">Kategori</TableHead>
              <TableHead class="font-semibold text-muted-foreground py-4">Jumlah</TableHead>
              <TableHead class="font-semibold text-muted-foreground text-right py-4"
                >Status</TableHead
              >
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow
              v-for="(item, index) in pengeluaranList"
              :key="item.id"
            >
              <TableCell class="py-4 text-sm text-foreground text-center font-medium">
                {{ index + 1 }}
              </TableCell>
              <TableCell
                class="py-4 text-sm text-foreground"
                v-html="formatDateWithBr(item.tanggal)"
              ></TableCell>
              <TableCell class="py-4">
                <p
                  class="font-bold text-foreground text-sm"
                  v-html="formatDescWithBr(item.deskripsi)"
                ></p>
              </TableCell>
              <TableCell class="py-4">
                <Badge
                  variant="secondary"
                  :class="[
                    'font-medium rounded text-[10px] shadow-none uppercase',
                    item.kategori === 'Infrastruktur'
                      ? 'bg-indigo-500/10 text-indigo-500'
                      : item.kategori === 'Salaries'
                        ? 'bg-amber-500/10 text-amber-500'
                        : item.kategori === 'Events'
                          ? 'bg-pink-500/10 text-pink-500'
                          : 'bg-emerald-500/10 text-emerald-500'
                  ]"
                  >{{ item.kategori }}</Badge
                >
              </TableCell>
              <TableCell class="py-4">
                <p class="text-[10px] text-muted-foreground font-semibold">Rp</p>
                <p class="font-bold text-foreground">{{ formatAmount(item.jumlah) }}</p>
              </TableCell>
              <TableCell class="text-right py-4">
                <Badge
                  :class="[
                    item.status === 'APPROVED'
                      ? 'bg-primary/15 text-primary'
                      : 'bg-blue-500/15 text-blue-600',
                    'border-none px-2.5 py-0.5 rounded-[4px] font-bold text-[9px] tracking-widest uppercase hover:bg-opacity-20'
                  ]"
                  >{{ item.status }}</Badge
                >
              </TableCell>
            </TableRow>

            <TableRow v-if="pengeluaranList.length === 0">
              <TableCell
                colspan="6"
                class="text-center py-8 text-muted-foreground font-medium"
              >
                Belum ada data pengeluaran.
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </Card>

      <!-- Right Column (Alokasi & Status LPJ) -->
      <div class="flex flex-col gap-6">
        <!-- Alokasi Anggaran -->
        <Card class="shadow-sm border-border">
          <CardHeader class="pb-4">
            <CardTitle class="text-base font-bold">Alokasi Anggaran</CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <div>
              <div class="flex justify-between items-end mb-2">
                <span class="text-sm font-bold text-foreground">Gaji & Tunjangan</span>
                <span class="text-sm font-bold text-foreground">55%</span>
              </div>
              <div class="h-2.5 w-full bg-secondary rounded-full overflow-hidden">
                <div
                  class="h-full bg-foreground"
                  style="width: 55%"
                ></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between items-end mb-2">
                <span class="text-sm font-bold text-foreground">Infrastruktur</span>
                <span class="text-sm font-bold text-foreground">30%</span>
              </div>
              <div class="h-2.5 w-full bg-secondary rounded-full overflow-hidden">
                <div
                  class="h-full bg-muted-foreground"
                  style="width: 30%"
                ></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between items-end mb-2">
                <span class="text-sm font-bold text-foreground">Kegiatan Siswa</span>
                <span class="text-sm font-bold text-foreground">15%</span>
              </div>
              <div class="h-2.5 w-full bg-secondary rounded-full overflow-hidden">
                <div
                  class="h-full bg-muted-foreground"
                  style="width: 15%"
                ></div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Status Laporan LPJ -->
        <Card class="shadow-sm border-border flex-1 flex flex-col">
          <CardHeader class="pb-4">
            <CardTitle class="text-base font-bold">Status Laporan (LPJ)</CardTitle>
          </CardHeader>
          <CardContent class="flex-1 flex flex-col space-y-4">
            <!-- Selesai -->
            <div class="bg-secondary/60 rounded-lg p-4 flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div
                  class="w-6 h-6 rounded-full bg-primary flex items-center justify-center shrink-0"
                >
                  <CheckCircle2 class="w-4 h-4 text-primary-foreground" />
                </div>
                <div>
                  <p class="text-sm font-bold text-foreground">LPJ Februari 2024</p>
                  <p
                    class="text-[9px] uppercase font-bold text-muted-foreground tracking-widest mt-0.5"
                  >
                    Selesai & Diverifikasi
                  </p>
                </div>
              </div>
              <Button
                variant="ghost"
                size="icon"
                class="h-8 w-8 text-foreground hover:bg-muted"
              >
                <Download class="w-4 h-4" />
              </Button>
            </div>

            <!-- Dalam Penyusunan -->
            <div
              class="bg-background border border-dashed border-border rounded-lg p-4 flex items-center justify-between"
            >
              <div class="flex items-center gap-3">
                <div
                  class="w-6 h-6 rounded-full border border-muted-foreground flex items-center justify-center shrink-0 text-muted-foreground"
                >
                  <MoreHorizontal class="w-4 h-4" />
                </div>
                <div>
                  <p class="text-sm font-bold text-foreground">LPJ Maret 2024</p>
                  <p
                    class="text-[9px] uppercase font-bold text-muted-foreground tracking-widest mt-0.5"
                  >
                    Dalam Penyusunan (75%)
                  </p>
                </div>
              </div>
              <Button
                size="icon"
                class="h-8 w-8 bg-foreground text-background hover:bg-foreground/90 rounded-md"
              >
                <Pencil class="w-3.5 h-3.5" />
              </Button>
            </div>

            <!-- Spacer -->
            <div class="flex-1"></div>

            <div class="w-full h-px bg-border/60 my-4"></div>

            <!-- Deadline -->
            <div>
              <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-semibold text-muted-foreground"
                  >Deadline LPJ Triwulan I</span
                >
                <span class="text-xs font-bold text-destructive">10 Hari Lagi</span>
              </div>
              <div class="h-1 w-full bg-secondary overflow-hidden">
                <div
                  class="h-full bg-destructive"
                  style="width: 75%"
                ></div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Audit Trail -->
    <Card class="shadow-sm border-border p-6">
      <h3 class="text-xs font-bold text-muted-foreground tracking-widest uppercase mb-6">
        Audit Trail Aktivitas Dana
      </h3>
      <div class="space-y-6">
        <!-- Item 1 -->
        <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-8">
          <div class="w-24 shrink-0 text-xs text-muted-foreground font-medium">09:15 AM</div>
          <div>
            <p class="text-sm text-foreground">
              <span class="font-bold">Admin Utama</span> mengunggah kuitansi renovasi lab komputer.
            </p>
            <p class="text-xs text-muted-foreground mt-0.5">ID Transaksi: #TRX-992120</p>
          </div>
        </div>

        <div class="w-full h-px bg-border/50"></div>

        <!-- Item 2 -->
        <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-8">
          <div class="w-24 shrink-0 text-xs text-muted-foreground font-medium">Kemarin</div>
          <div>
            <p class="text-sm text-foreground">
              <span class="font-bold">Yayasan Pusat</span> menyetujui alokasi dana darurat perbaikan
              atap.
            </p>
            <p class="text-xs text-muted-foreground mt-0.5">Otorisasi: Dr. H. Sulaiman</p>
          </div>
        </div>

        <div class="w-full h-px bg-border/50"></div>

        <!-- Item 3 -->
        <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-8">
          <div class="w-24 shrink-0 text-xs text-muted-foreground font-medium">12 Mar</div>
          <div>
            <p class="text-sm text-foreground">
              <span class="font-bold">Sistem</span> melakukan pembayaran otomatis gaji guru honorer.
            </p>
            <p class="text-xs text-muted-foreground mt-0.5">Batch: Payroll-HNR-03-24</p>
          </div>
        </div>
      </div>
    </Card>
  </div>
</template>
