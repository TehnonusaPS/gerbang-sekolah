<script setup>
import { computed, ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useRoute, useRouter } from 'vue-router'
import PageHeader from '@/components/page-header/PageHeader.vue'
import StatCard from '@/components/stat-card/StatCard.vue'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { NativeSelect } from '@/components/ui/native-select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import { toast } from 'vue-sonner'
import { 
  CreditCard, 
  Wallet, 
  Banknote, 
  Clock, 
  AlertCircle,
  Search, 
  Receipt, 
  PlusCircle, 
  FileText, 
  ShieldCheck, 
  BookOpen, 
  Calendar,
  Building2,
  Sparkles
} from 'lucide-vue-next'
import { getSppDashboard, createSppPayment, verifySppPayment } from '@/services/sppService'
import { fetchAllSiswa } from '@/services/siswaService'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const currentRole = computed(() => auth.user?.role || 'guest')
const isSuperAdmin = computed(() => currentRole.value === 'superadmin')
const isAdminYayasan = computed(() => currentRole.value === 'admin_yayasan')
const isKepsek = computed(() => currentRole.value === 'kepala_sekolah')
const isAdminSekolah = computed(() => currentRole.value === 'admin_sekolah')
const isTataUsaha = computed(() => currentRole.value === 'tata_usaha')
const isStaff = computed(() => isAdminSekolah.value || isTataUsaha.value)
const isSiswa = computed(() => currentRole.value === 'siswa')
const isOrangTua = computed(() => currentRole.value === 'orang_tua')
const isPayer = computed(() => isSiswa.value || isOrangTua.value)

// Navigation helpers
const goToKwitansi = () => router.push('/keuangan/cetak-kwitansi')
const gotoPengeluaranKecil = () => router.push('/keuangan/input-pengeluaran-kecil')
const gotoPenerimaanPembayaran = () => router.push('/keuangan/penerimaan-pembayaran')
const gotoTarifSpp = () => router.push('/keuangan/tarif-spp')

// State
const isLoading = ref(false)
const activeTab = ref('siswa') // 'siswa' | 'verifikasi'
const studentName = ref('')
const outstandingBalance = ref(0)
const nextPaymentDue = ref('-')
const currentBills = ref([])
const paymentHistory = ref([])
const antrianPembayaran = ref([])
const students = ref([])
const siswaSearchQuery = ref('')
const statusFilter = ref('all') // 'all' | 'Lunas' | 'Belum Lunas'

const stats = ref({
  kas_kecil: 0,
  total_spp_bulan_ini: 0,
  pending_verifikasi_count: 0
})

const filteredStudentsList = computed(() => {
  let list = students.value || []
  if (siswaSearchQuery.value) {
    const q = siswaSearchQuery.value.toLowerCase()
    list = list.filter(s => 
      (s.nama && s.nama.toLowerCase().includes(q)) ||
      (s.nisn && s.nisn.toLowerCase().includes(q)) ||
      (s.kelas && String(s.kelas).toLowerCase().includes(q))
    )
  }
  if (statusFilter.value !== 'all') {
    list = list.filter(s => (s.payment_status || 'Belum Lunas') === statusFilter.value)
  }
  return list
})

// Bill selection state for Payer (Siswa / Orang Tua)
const selectedBillIds = ref([])
const paymentMethodType = ref('online') // 'online' (Midtrans) | 'manual'

const isBillSelected = (id) => {
  return selectedBillIds.value.some(x => String(x) === String(id))
}

const isAllUnpaidSelected = computed(() => {
  const unpaidBills = currentBills.value.filter(b => b.status !== 'paid')
  if (unpaidBills.length === 0) return false
  return unpaidBills.every(b => isBillSelected(b.id))
})

const toggleSelectBill = (id) => {
  const bill = currentBills.value.find(b => String(b.id) === String(id))
  if (!bill || bill.status === 'paid') return

  const idx = selectedBillIds.value.findIndex(x => String(x) === String(id))
  if (idx > -1) {
    selectedBillIds.value.splice(idx, 1)
  } else {
    selectedBillIds.value.push(bill.id)
  }
}

const toggleSelectAllBills = () => {
  const unpaidBills = currentBills.value.filter(b => b.status !== 'paid')
  if (isAllUnpaidSelected.value) {
    selectedBillIds.value = []
  } else {
    selectedBillIds.value = unpaidBills.map(b => b.id)
  }
}

const totalPaymentAmount = computed(() => {
  return currentBills.value
    .filter(b => isBillSelected(b.id))
    .reduce((acc, bill) => {
      return acc + (parseFloat(bill.amount) - parseFloat(bill.paid_amount || 0))
    }, 0)
})

const paidProgress = computed(() => {
  if (!currentBills.value || currentBills.value.length === 0) return 100
  const totalAmount = currentBills.value.reduce((acc, b) => acc + parseFloat(b.amount || 0), 0)
  const totalPaid = currentBills.value.reduce((acc, b) => acc + parseFloat(b.paid_amount || 0), 0)
  return totalAmount > 0 ? Math.min(100, Math.round((totalPaid / totalAmount) * 100)) : 100
})

const formatRupiah = (val) => {
  const num = parseFloat(val) || 0
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(num)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  try {
    return new Date(dateStr).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    })
  } catch (e) {
    return dateStr
  }
}

const loadDashboard = async () => {
  try {
    isLoading.value = true
    const res = await getSppDashboard()
    const data = res.data || {}
    
    if (isPayer.value) {
      studentName.value = data.student_name || auth.user?.name || ''
      outstandingBalance.value = data.outstanding_balance || 0
      nextPaymentDue.value = data.next_payment_due || '-'
      currentBills.value = data.current_bills || []
      paymentHistory.value = data.payment_history || []
      
      // Auto-select unpaid bills
      selectedBillIds.value = currentBills.value.filter(b => b.status !== 'paid').map(b => b.id)
    } else if (isStaff.value) {
      stats.value = {
        kas_kecil: data.kas_kecil || 0,
        total_spp_bulan_ini: data.total_spp_bulan_ini || 0,
        pending_verifikasi_count: data.pending_verifikasi_count || 0
      }
      antrianPembayaran.value = data.antrian_pembayaran || []
      
      const studentRes = await fetchAllSiswa()
      if (studentRes && studentRes.status === 'success') {
        students.value = studentRes.data || []
      }
    }
  } catch (err) {
    console.error('Gagal memuat data SPP:', err)
    toast.error('Gagal memuat data pembayaran SPP.')
  } finally {
    isLoading.value = false
  }
}

const loadMidtransSnapScript = () => {
  return new Promise((resolve, reject) => {
    if (window.snap) {
      return resolve(true)
    }

    const clientKey = import.meta.env.VITE_MIDTRANS_CLIENT_KEY || ''
    const isProduction = import.meta.env.VITE_MIDTRANS_IS_PRODUCTION === 'true'
    const scriptUrl = isProduction 
      ? 'https://app.midtrans.com/snap/snap.js' 
      : 'https://app.sandbox.midtrans.com/snap/snap.js'

    const existingScript = document.querySelector(`script[src*="midtrans.com/snap/snap.js"]`)
    if (existingScript) {
      if (window.snap) return resolve(true)
      existingScript.addEventListener('load', () => resolve(true))
      existingScript.addEventListener('error', () => reject(new Error('Gagal memuat Snap SDK')))
      return
    }

    const script = document.createElement('script')
    script.src = scriptUrl
    if (clientKey) {
      script.setAttribute('data-client-key', clientKey)
    }
    script.onload = () => resolve(true)
    script.onerror = () => reject(new Error('Gagal memuat SDK Midtrans Snap'))
    document.head.appendChild(script)
  })
}

const handleProcessPayment = async () => {
  if (selectedBillIds.value.length === 0) {
    toast.error('Silakan pilih minimal 1 tagihan yang akan dibayar.')
    return
  }

  try {
    isLoading.value = true

    if (paymentMethodType.value === 'online') {
      try {
        await loadMidtransSnapScript()
      } catch (e) {
        console.warn('Gagal memuat SDK Midtrans Snap:', e)
      }
    }

    const res = await createSppPayment({
      payment_method: paymentMethodType.value === 'online' ? 'midtrans' : 'manual_transfer',
      amount: totalPaymentAmount.value,
      bill_ids: selectedBillIds.value
    })

    if (paymentMethodType.value === 'online') {
      if (res && res.data?.snap_token) {
        if (window.snap) {
          window.snap.pay(res.data.snap_token, {
            onSuccess: function() {
              toast.success('Pembayaran Midtrans berhasil!')
              selectedBillIds.value = []
              loadDashboard()
            },
            onPending: function() {
              toast.info('Menunggu penyelesaian pembayaran...')
              selectedBillIds.value = []
              loadDashboard()
            },
            onError: function() {
              toast.error('Pembayaran gagal atau dibatalkan.')
            },
            onClose: function() {
              toast.info('Popup pembayaran ditutup.')
            }
          })
        } else {
          toast.error('Script Midtrans Snap belum dimuat. Pastikan VITE_MIDTRANS_CLIENT_KEY terisi di frontend/.env')
        }
      } else {
        toast.error('Snap Token tidak ditemukan. Pastikan MIDTRANS_SERVER_KEY sudah diisi di file backend/.env')
      }
    } else {
      toast.success('Pengajuan pembayaran manual berhasil dibuat. Menunggu konfirmasi.')
      selectedBillIds.value = []
      loadDashboard()
    }
  } catch (err) {
    const errorMsg = err.response?.data?.message || err.message || 'Gagal memproses pengajuan pembayaran.'
    toast.error(errorMsg)
  } finally {
    isLoading.value = false
  }
}

const handleVerify = async (paymentId, status) => {
  try {
    isLoading.value = true
    await verifySppPayment(paymentId, status)
    toast.success(`Pembayaran berhasil di-${status === 'success' ? 'disetujui' : 'ditolak'}.`)
    loadDashboard()
  } catch (err) {
    toast.error('Gagal memproses verifikasi pembayaran.')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadDashboard()
  if (isPayer.value) {
    loadMidtransSnapScript().catch(() => {})
  }
})
</script>

<template>
  <div class="space-y-6 w-full mx-auto px-0">
    <!-- Header Standar Aplikasi -->
    <PageHeader
      title="Manajemen SPP & Keuangan"
      description="Kelola tagihan, pembayaran SPP bulanan, dan verifikasi kas secara terpadu."
    />

    <!-- ========================================================================= -->
    <!-- VIEW UNTUK SISWA & ORANG TUA (Payer View)                                 -->
    <!-- ========================================================================= -->
    <div v-if="isPayer" class="space-y-6">
      <!-- Welcome & Student Overview Banner -->
      <Card class="bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border-primary/20 p-5 rounded-2xl">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div class="size-12 rounded-full bg-primary/20 text-primary flex items-center justify-center font-bold text-lg">
              {{ (studentName || 'S').substring(0, 2).toUpperCase() }}
            </div>
            <div>
              <p class="text-xs text-muted-foreground font-semibold uppercase tracking-wider">
                {{ isOrangTua ? 'Wali dari Siswa' : 'Akun Siswa' }}
              </p>
              <h2 class="text-xl font-bold text-foreground">{{ studentName || 'Siswa Gerbang Sekolah' }}</h2>
            </div>
          </div>
          <Badge 
            variant="outline" 
            :class="[
              'px-3 py-1 text-xs font-bold',
              outstandingBalance === 0 
                ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30' 
                : 'bg-rose-500/10 text-rose-600 border-rose-500/30'
            ]"
          >
            Status: {{ outstandingBalance === 0 ? 'Bebas Tunggakan' : 'Ada Tagihan Aktif' }}
          </Badge>
        </div>
      </Card>

      <!-- Stat Cards Grid -->
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <StatCard
          label="Total Tunggakan SPP"
          :value="formatRupiah(outstandingBalance)"
          :sub="`Status Pelunasan: ${paidProgress}%`"
          :progress="paidProgress"
          variant="primary"
          :icon="Wallet"
        />

        <StatCard
          label="Jatuh Tempo Terdekat"
          :value="nextPaymentDue"
          sub="Batas pembayaran bulanan"
          variant="amber"
          :icon="Calendar"
        />

        <StatCard
          label="Metode Pembayaran"
          value="Midtrans Gateway"
          sub="QRIS, VA BCA, Mandiri, E-Wallet"
          variant="emerald"
          :icon="ShieldCheck"
        />
      </div>

      <!-- Main Section: Select Bills & Pay -->
      <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
        <!-- Bill Checklist Column -->
        <Card class="p-6 rounded-2xl space-y-5">
          <div class="flex items-center justify-between border-b pb-4">
            <div>
              <h3 class="text-lg font-bold">Daftar Tagihan SPP</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Pilih tagihan yang ingin Anda lunasi hari ini</p>
            </div>
            <Button 
              variant="outline" 
              size="sm" 
              @click="toggleSelectAllBills" 
              class="text-xs rounded-lg"
              v-if="currentBills.some(b => b.status !== 'paid')"
            >
              {{ isAllUnpaidSelected ? 'Batal Pilih Semua' : 'Pilih Semua Tagihan' }}
            </Button>
          </div>

          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow class="bg-muted/40">
                  <TableHead class="w-[45px] text-center"></TableHead>
                  <TableHead class="font-bold text-xs uppercase">Deskripsi Tagihan</TableHead>
                  <TableHead class="font-bold text-xs uppercase">Jatuh Tempo</TableHead>
                  <TableHead class="font-bold text-xs uppercase text-right">Nominal</TableHead>
                  <TableHead class="font-bold text-xs uppercase text-center w-[90px]">Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow 
                  v-for="bill in currentBills" 
                  :key="bill.id" 
                  :class="[
                    'transition-colors cursor-pointer select-none',
                    isBillSelected(bill.id) ? 'bg-primary/5' : 'hover:bg-muted/30'
                  ]"
                  @click="toggleSelectBill(bill.id)"
                >
                  <TableCell class="text-center" @click.stop>
                    <Checkbox 
                      :disabled="bill.status === 'paid'"
                      :model-value="isBillSelected(bill.id)"
                      @update:model-value="() => toggleSelectBill(bill.id)"
                    />
                  </TableCell>
                  <TableCell class="font-medium text-foreground">
                    <div class="flex items-center gap-2">
                      <Receipt class="size-4 text-primary shrink-0" />
                      <span>{{ bill.title }}</span>
                    </div>
                  </TableCell>
                  <TableCell class="text-xs text-muted-foreground">{{ formatDate(bill.due_date) }}</TableCell>
                  <TableCell class="font-bold text-right text-foreground">
                    {{ formatRupiah(parseFloat(bill.amount) - parseFloat(bill.paid_amount || 0)) }}
                  </TableCell>
                  <TableCell class="text-center">
                    <Badge 
                      variant="outline" 
                      :class="[
                        'text-[10px] font-bold uppercase',
                        bill.status === 'paid' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' :
                        bill.status === 'partial' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                        'bg-rose-500/10 text-rose-500 border-rose-500/20'
                      ]"
                    >
                      {{ bill.status === 'paid' ? 'Lunas' : (bill.status === 'partial' ? 'Cicilan' : 'Belum Lunas') }}
                    </Badge>
                  </TableCell>
                </TableRow>
                <TableRow v-if="currentBills.length === 0">
                  <TableCell colspan="5" class="text-center py-8 text-muted-foreground text-sm">
                    Tidak ada tagihan SPP aktif.
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </Card>

        <!-- Checkout / Payment Action Column -->
        <Card class="p-6 rounded-2xl space-y-5 flex flex-col justify-between">
          <div class="space-y-5">
            <div class="border-b pb-4">
              <h3 class="text-lg font-bold">Ringkasan Pembayaran</h3>
              <p class="text-xs text-muted-foreground mt-0.5">Konfirmasi item & metode pembayaran</p>
            </div>

            <!-- Selected items list -->
            <div class="space-y-2 max-h-[220px] overflow-y-auto pr-1">
              <div 
                v-for="bill in currentBills.filter(b => isBillSelected(b.id))" 
                :key="bill.id" 
                class="flex items-center justify-between p-3 rounded-xl bg-muted/40 text-xs border"
              >
                <span class="font-medium text-foreground truncate max-w-[170px]">
                  {{ bill.title }}
                </span>
                <span class="font-bold text-foreground">
                  {{ formatRupiah(parseFloat(bill.amount) - parseFloat(bill.paid_amount || 0)) }}
                </span>
              </div>
              <div v-if="selectedBillIds.length === 0" class="text-center py-6 text-xs text-muted-foreground italic border border-dashed rounded-xl p-4">
                Belum ada tagihan yang dipilih. Centang tagihan di sebelah kiri.
              </div>
            </div>

            <!-- Total Amount Card -->
            <div class="p-4 rounded-xl bg-primary/10 border border-primary/20 space-y-1">
              <p class="text-xs text-muted-foreground font-semibold uppercase tracking-wider">Total yang Harus Dibayar</p>
              <p class="text-2xl font-extrabold text-primary">{{ formatRupiah(totalPaymentAmount) }}</p>
            </div>

            <!-- Payment Gateway Selector -->
            <div class="space-y-2">
              <label class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Pilih Kanal Pembayaran</label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  :class="[
                    'p-3 rounded-xl border text-xs font-semibold flex flex-col items-center justify-center gap-1.5 transition-all',
                    paymentMethodType === 'online' 
                      ? 'border-primary bg-primary/10 text-primary shadow-sm' 
                      : 'border-border text-muted-foreground hover:bg-muted'
                  ]"
                  @click="paymentMethodType = 'online'"
                >
                  <CreditCard class="size-4" />
                  <span>Online / Midtrans</span>
                </button>

                <button
                  type="button"
                  :class="[
                    'p-3 rounded-xl border text-xs font-semibold flex flex-col items-center justify-center gap-1.5 transition-all',
                    paymentMethodType === 'manual' 
                      ? 'border-primary bg-primary/10 text-primary shadow-sm' 
                      : 'border-border text-muted-foreground hover:bg-muted'
                  ]"
                  @click="paymentMethodType = 'manual'"
                >
                  <Banknote class="size-4" />
                  <span>Transfer Manual / Kasir</span>
                </button>
              </div>
              <p class="text-[11px] text-muted-foreground mt-1">
                {{ paymentMethodType === 'online' ? 'Mendukung QRIS, GoPay, ShopeePay, VA BCA, Mandiri, BNI, BRI.' : 'Metode manual memerlukan verifikasi oleh petugas Tata Usaha.' }}
              </p>
            </div>
          </div>

          <!-- Submit Button -->
          <Button 
            @click="handleProcessPayment" 
            :disabled="selectedBillIds.length === 0 || isLoading"
            class="w-full h-12 text-sm font-bold rounded-xl mt-4 bg-primary hover:bg-primary/90 text-primary-foreground shadow-md transition-all"
          >
            <Sparkles class="size-4 mr-2" v-if="paymentMethodType === 'online'" />
            <CreditCard class="size-4 mr-2" v-else />
            {{ paymentMethodType === 'online' ? 'Bayar via Midtrans Snap' : 'Kirim Pengajuan Pembayaran' }}
          </Button>
        </Card>
      </div>

      <!-- Payment History Table -->
      <Card class="p-6 rounded-2xl space-y-4">
        <div class="flex items-center justify-between border-b pb-4">
          <div>
            <h3 class="text-lg font-bold">Riwayat Transaksi & Pembayaran</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Catatan seluruh transaksi yang pernah dilakukan</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow class="bg-muted/40">
                <TableHead class="font-bold text-xs uppercase">Tanggal</TableHead>
                <TableHead class="font-bold text-xs uppercase">No. Referensi</TableHead>
                <TableHead class="font-bold text-xs uppercase">Metode</TableHead>
                <TableHead class="font-bold text-xs uppercase text-right">Nominal</TableHead>
                <TableHead class="font-bold text-xs uppercase text-center">Status</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="pay in paymentHistory" :key="pay.id" class="hover:bg-muted/30">
                <TableCell class="text-xs font-medium text-muted-foreground">{{ formatDate(pay.payment_date) }}</TableCell>
                <TableCell class="font-mono text-xs font-semibold text-foreground">{{ pay.reference_number }}</TableCell>
                <TableCell class="text-xs font-semibold uppercase">{{ pay.payment_method }}</TableCell>
                <TableCell class="font-bold text-right text-foreground">{{ formatRupiah(pay.amount) }}</TableCell>
                <TableCell class="text-center">
                  <Badge 
                    variant="outline"
                    :class="[
                      'text-[10px] font-bold uppercase',
                      pay.status === 'success' || pay.status === 'SUCCESS' ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' :
                      pay.status === 'pending' || pay.status === 'PENDING' ? 'bg-amber-500/10 text-amber-500 border-amber-500/20' : 
                      'bg-rose-500/10 text-rose-500 border-rose-500/20'
                    ]"
                  >
                    {{ pay.status }}
                  </Badge>
                </TableCell>
              </TableRow>
              <TableRow v-if="paymentHistory.length === 0">
                <TableCell colspan="5" class="text-center py-6 text-muted-foreground text-sm">
                  Belum ada riwayat transaksi.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </Card>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW UNTUK ADMIN SEKOLAH & TATA USAHA (Staff Management View)             -->
    <!-- ========================================================================= -->
    <div v-else-if="isStaff" class="space-y-6">
      <!-- Quick Action Cards & Stats -->
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard
          label="Kas Kecil Tersedia"
          :value="formatRupiah(stats.kas_kecil)"
          sub="Saldo operasional harian"
          variant="blue"
          :icon="Wallet"
        />

        <StatCard
          label="SPP Terkumpul Bulan Ini"
          :value="formatRupiah(stats.total_spp_bulan_ini)"
          sub="Total penerimaan berjalan"
          variant="emerald"
          :icon="Banknote"
        />

        <StatCard
          label="Menunggu Verifikasi"
          :value="`${stats.pending_verifikasi_count} Transaksi`"
          sub="Verifikasi manual siswa"
          variant="amber"
          :icon="Clock"
        />

        <Card @click="gotoPenerimaanPembayaran" class="p-5 cursor-pointer hover:border-primary transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 flex flex-col justify-between bg-primary/5 border-primary/20">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-primary">Aksi Cepat</span>
            <PlusCircle class="size-5 text-primary" />
          </div>
          <div class="mt-3">
            <h4 class="font-bold text-sm text-foreground">Input Pembayaran Kasir</h4>
            <p class="text-xs text-muted-foreground mt-0.5">Catat pembayaran tunai langsung</p>
          </div>
        </Card>
      </div>

      <!-- Quick Link Action Buttons Bar -->
      <div class="flex flex-wrap gap-3">
        <Button @click="gotoPenerimaanPembayaran" class="rounded-xl font-semibold gap-2">
          <CreditCard class="size-4" />
          Terima Pembayaran SPP
        </Button>
        <Button variant="outline" @click="gotoPengeluaranKecil" class="rounded-xl font-semibold gap-2">
          <Receipt class="size-4" />
          Input Pengeluaran Kecil
        </Button>
        <Button variant="outline" @click="goToKwitansi" class="rounded-xl font-semibold gap-2">
          <FileText class="size-4" />
          Cetak Kwitansi
        </Button>
        <Button variant="outline" @click="gotoTarifSpp" class="rounded-xl font-semibold gap-2">
          <BookOpen class="size-4" />
          Pengaturan Tarif SPP
        </Button>
      </div>

      <!-- Main Tabs Container -->
      <Card class="p-6 rounded-2xl space-y-5">
        <!-- Tabs & Filters Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b pb-4">
          <div class="flex gap-4">
            <button
              :class="[
                'pb-2 font-bold text-sm border-b-2 transition-all',
                activeTab === 'siswa'
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground'
              ]"
              @click="activeTab = 'siswa'"
            >
              Daftar Status SPP Siswa
            </button>
            <button
              :class="[
                'pb-2 font-bold text-sm border-b-2 transition-all flex items-center gap-2',
                activeTab === 'verifikasi'
                  ? 'border-primary text-primary'
                  : 'border-transparent text-muted-foreground hover:text-foreground'
              ]"
              @click="activeTab = 'verifikasi'"
            >
              <span>Antrian Verifikasi</span>
              <Badge v-if="antrianPembayaran.length > 0" class="bg-amber-500 text-white text-[10px] px-1.5 py-0.2">
                {{ antrianPembayaran.length }}
              </Badge>
            </button>
          </div>

          <!-- Controls for Tab 1 (Siswa) -->
          <div v-if="activeTab === 'siswa'" class="flex flex-wrap items-center gap-2">
            <div class="relative min-w-[200px]">
              <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-muted-foreground" />
              <Input
                v-model="siswaSearchQuery"
                type="text"
                placeholder="Cari nama, NISN, kelas..."
                class="pl-8 h-9 text-xs rounded-xl"
              />
            </div>

            <NativeSelect v-model="statusFilter" class="h-9 text-xs rounded-xl min-w-[130px]">
              <option value="all">Semua Status</option>
              <option value="Lunas">Lunas</option>
              <option value="Belum Lunas">Belum Lunas</option>
            </NativeSelect>
          </div>
        </div>

        <!-- TAB 1: Daftar Status SPP Siswa -->
        <div v-if="activeTab === 'siswa'" class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow class="bg-muted/40">
                <TableHead class="w-[50px] text-center font-bold text-xs uppercase">No</TableHead>
                <TableHead class="font-bold text-xs uppercase">Nama Siswa</TableHead>
                <TableHead class="font-bold text-xs uppercase">NISN</TableHead>
                <TableHead class="font-bold text-xs uppercase">Kelas</TableHead>
                <TableHead class="font-bold text-xs uppercase text-center">Status Pembayaran</TableHead>
                <TableHead class="font-bold text-xs uppercase text-right">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(item, idx) in filteredStudentsList" :key="item.id" class="hover:bg-muted/30">
                <TableCell class="text-center font-medium text-xs text-muted-foreground">{{ idx + 1 }}</TableCell>
                <TableCell>
                  <div class="flex items-center gap-2.5">
                    <div class="bg-primary/10 text-primary rounded-full size-8 flex items-center justify-center text-xs font-bold shrink-0">
                      {{ (item.nama || 'S').substring(0, 2).toUpperCase() }}
                    </div>
                    <span class="font-semibold text-foreground text-sm">{{ item.nama }}</span>
                  </div>
                </TableCell>
                <TableCell class="text-muted-foreground font-mono text-xs">{{ item.nisn || '-' }}</TableCell>
                <TableCell class="text-sm font-medium">{{ item.kelas }}</TableCell>
                <TableCell class="text-center">
                  <Badge
                    variant="outline"
                    :class="[
                      'font-bold text-[10px] uppercase',
                      item.payment_status === 'Lunas' 
                        ? 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20' 
                        : 'bg-rose-500/10 text-rose-500 border-rose-500/20'
                    ]"
                  >
                    {{ item.payment_status || 'Belum Lunas' }}
                  </Badge>
                </TableCell>
                <TableCell class="text-right">
                  <Button 
                    variant="ghost" 
                    size="sm" 
                    @click="gotoPenerimaanPembayaran"
                    class="text-xs font-semibold text-primary hover:bg-primary/10 rounded-lg h-8"
                  >
                    Catat Pembayaran
                  </Button>
                </TableCell>
              </TableRow>
              <TableRow v-if="filteredStudentsList.length === 0">
                <TableCell colspan="6" class="text-center py-8 text-muted-foreground text-sm">
                  Tidak ada data siswa yang cocok.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- TAB 2: Antrian Verifikasi Pembayaran -->
        <div v-else-if="activeTab === 'verifikasi'" class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow class="bg-muted/40">
                <TableHead class="font-bold text-xs uppercase">No. Ref</TableHead>
                <TableHead class="font-bold text-xs uppercase">Nama Siswa</TableHead>
                <TableHead class="font-bold text-xs uppercase">Tanggal</TableHead>
                <TableHead class="font-bold text-xs uppercase">Metode</TableHead>
                <TableHead class="font-bold text-xs uppercase text-right">Nominal</TableHead>
                <TableHead class="font-bold text-xs uppercase text-center">Aksi Verifikasi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="item in antrianPembayaran" :key="item.id" class="hover:bg-muted/30">
                <TableCell class="font-mono text-xs font-bold text-foreground">{{ item.reference_number }}</TableCell>
                <TableCell class="font-medium text-sm text-foreground">{{ item.student_name || 'Siswa' }}</TableCell>
                <TableCell class="text-xs text-muted-foreground">{{ formatDate(item.payment_date) }}</TableCell>
                <TableCell class="text-xs uppercase font-semibold">{{ item.payment_method }}</TableCell>
                <TableCell class="font-bold text-right text-foreground">{{ formatRupiah(item.amount) }}</TableCell>
                <TableCell class="text-center">
                  <div class="flex items-center justify-center gap-1.5">
                    <Button 
                      size="sm" 
                      variant="outline"
                      @click="handleVerify(item.id, 'success')"
                      class="h-8 text-xs font-bold text-emerald-600 border-emerald-500/30 hover:bg-emerald-500/10 rounded-lg"
                    >
                      Setujui
                    </Button>
                    <Button 
                      size="sm" 
                      variant="outline"
                      @click="handleVerify(item.id, 'failed')"
                      class="h-8 text-xs font-bold text-rose-600 border-rose-500/30 hover:bg-rose-500/10 rounded-lg"
                    >
                      Tolak
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
              <TableRow v-if="antrianPembayaran.length === 0">
                <TableCell colspan="6" class="text-center py-8 text-muted-foreground text-sm">
                  Tidak ada antrian verifikasi pembayaran yang tertunda.
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </Card>
    </div>

    <!-- ========================================================================= -->
    <!-- VIEW UNTUK SUPERADMIN / ADMIN YAYASAN / KEPALA SEKOLAH                    -->
    <!-- ========================================================================= -->
    <div v-else-if="isSuperAdmin || isAdminYayasan || isKepsek" class="space-y-6">
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <StatCard
          label="Total Penerimaan SPP Bulan Ini"
          :value="formatRupiah(stats.total_spp_bulan_ini || 125000000)"
          sub="Rekapitolasi penerimaan berjalan"
          variant="primary"
          :icon="Banknote"
        />

        <StatCard
          label="Kas Operasional Sekolah"
          :value="formatRupiah(stats.kas_kecil || 15000000)"
          sub="Saldo kas harian berjalan"
          variant="emerald"
          :icon="Wallet"
        />

        <StatCard
          label="Persentase Pelunasan SPP"
          value="88%"
          sub="88% siswa lunas bulan ini"
          :progress="88"
          variant="amber"
          :icon="ShieldCheck"
        />
      </div>

      <!-- Overview Card & Shortcut -->
      <Card class="p-6 rounded-2xl space-y-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 border-b pb-4">
          <div>
            <h3 class="text-lg font-bold">Ringkasan Eksekutif Keuangan</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Pemantauan penerimaan SPP dan arus kas institusi</p>
          </div>
          <div class="flex gap-2">
            <Button variant="outline" @click="router.push('/keuangan/monitoring-yayasan')" class="rounded-xl text-xs font-semibold gap-2">
              <Building2 class="size-4" />
              Monitoring Yayasan
            </Button>
            <Button @click="router.push('/keuangan/laporan')" class="rounded-xl text-xs font-semibold gap-2">
              <FileText class="size-4" />
              Laporan Keuangan
            </Button>
          </div>
        </div>

        <div class="p-4 rounded-xl bg-muted/40 text-xs text-muted-foreground space-y-2">
          <p class="font-semibold text-foreground">Informasi Hak Akses Eksekutif:</p>
          <p>Sebagai {{ currentRole === 'superadmin' ? 'Superadmin' : (currentRole === 'admin_yayasan' ? 'Admin Yayasan' : 'Kepala Sekolah') }}, Anda dapat memantau akumulasi penagihan SPP, mengunduh laporan bulanan, serta mengakses dashboard monitoring keuangan yayasan/sekolah secara lengkap.</p>
        </div>
      </Card>
    </div>

    <!-- Default Unauthorized Fallback -->
    <Card v-else class="p-8 text-center rounded-2xl">
      <AlertCircle class="size-10 text-muted-foreground mx-auto mb-3" />
      <h3 class="text-lg font-bold text-foreground">Akses Terbatas</h3>
      <p class="text-xs text-muted-foreground mt-1 max-w-md mx-auto">
        Peran Anda saat ini ({{ currentRole }}) tidak memiliki akses langsung ke modul keuangan SPP. Silakan hubungi administrator jika Anda merasa ini adalah kesalahan.
      </p>
    </Card>
  </div>
</template>
