<script setup>
import { ref, onMounted, computed } from 'vue'
import { Plus, Pencil, Trash2, Shield, Zap, HelpCircle } from 'lucide-vue-next'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import FormFooter from '@/components/forms/FormFooter.vue'
import { toast } from 'vue-sonner'
import * as financeService from '@/services/superAdminFinanceService'
import { useAuthStore } from '@/stores/authStore'
import { getFoundation } from '@/services/managementService'

const plans = ref([])
const isLoading = ref(false)
const isModalOpen = ref(false)
const isEditing = ref(false)
const currentPlanId = ref(null)

const auth = useAuthStore()
const isAdminYayasan = computed(() => auth.user?.role === 'admin_yayasan')
const foundationData = ref(null)
const activeSubscription = ref(null)

const form = ref({
  name: '',
  code: '',
  price: 0,
  billing_cycle: 'monthly',
  max_schools: 1,
  max_students: 150,
  features: '',
  is_active: true
})

const loadPlans = async () => {
  isLoading.value = true
  try {
    if (isAdminYayasan.value) {
      if (auth.user?.foundation_id) {
        const res = await getFoundation(auth.user.foundation_id)
        if (res.status === 'success') {
          foundationData.value = res.data
          activeSubscription.value = res.data.active_subscription
        }
      }
    } else {
      const res = await financeService.getPlans()
      if (res.status === 'success') {
        plans.value = res.data
      }
    }
  } catch (error) {
    toast.error(isAdminYayasan.value ? 'Gagal memuat status langganan.' : 'Gagal memuat paket langganan.')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadPlans()
})

const openAddModal = () => {
  isEditing.value = false
  currentPlanId.value = null
  form.value = {
    name: '',
    code: '',
    price: 0,
    billing_cycle: 'monthly',
    max_schools: 1,
    max_students: 150,
    features: '',
    is_active: true
  }
  isModalOpen.value = true
}

const openEditModal = (plan) => {
  isEditing.value = true
  currentPlanId.value = plan.id
  form.value = {
    name: plan.name,
    code: plan.code,
    price: parseFloat(plan.price),
    billing_cycle: plan.billing_cycle,
    max_schools: plan.max_schools,
    max_students: plan.max_students,
    features: Array.isArray(plan.features) ? plan.features.join('\n') : '',
    is_active: plan.is_active
  }
  isModalOpen.value = true
}

const validatePlan = () => {
  if (!form.value.name || !form.value.code) {
    toast.error('Mohon isi nama dan kode paket!')
    return false
  }
  return true
}

const handleSave = async () => {
  const payload = {
    ...form.value,
    features: form.value.features ? form.value.features.split('\n').filter(Boolean) : []
  }

  try {
    if (isEditing.value) {
      await financeService.updatePlan(currentPlanId.value, payload)
      toast.success('Paket langganan berhasil diperbarui!')
    } else {
      await financeService.createPlan(payload)
      toast.success('Paket langganan baru berhasil dibuat!')
    }
    isModalOpen.value = false
    loadPlans()
  } catch (error) {
    toast.error('Gagal menyimpan paket langganan.', {
      description: error.message || 'Pastikan kode paket unik.'
    })
  }
}

const handleDelete = async (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus paket langganan ini?')) {
    try {
      await financeService.deletePlan(id)
      toast.success('Paket langganan berhasil dihapus!')
      loadPlans()
    } catch (error) {
      toast.error('Gagal menghapus paket langganan.', {
        description: error.message || 'Paket mungkin sedang aktif digunakan.'
      })
    }
  }
}

const formatCurrency = (val) => {
  if (!val && val !== 0) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val)
}
</script>

<template>
  <div class="space-y-6 w-full text-left">
    <!-- View for Admin Yayasan -->
    <template v-if="isAdminYayasan">
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Status Paket Langganan</h1>
        <p class="text-muted-foreground mt-2 text-sm">
          Informasi paket langganan dan lisensi aktif aplikasi Gerbang Sekolah untuk yayasan Anda.
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex flex-col justify-center items-center py-20 gap-3">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
        <span class="text-muted-foreground text-sm font-medium">Memuat data subscription...</span>
      </div>

      <!-- Subscription Data Available -->
      <div v-else-if="activeSubscription" class="grid gap-6 md:grid-cols-3">
        <!-- Main subscription card -->
        <Card class="md:col-span-2 overflow-hidden border-primary/20 shadow-md">
          <div class="bg-gradient-to-r from-primary/10 to-primary/5 p-6 border-b border-border flex justify-between items-start">
            <div>
              <Badge class="mb-2 bg-emerald-500 hover:bg-emerald-600 text-white font-semibold capitalize border-none text-[10px] px-2 py-0.5">
                {{ activeSubscription.status === 'active' ? 'Aktif' : activeSubscription.status === 'trial' ? 'Masa Percobaan' : activeSubscription.status }}
              </Badge>
              <h2 class="text-xl font-bold text-foreground">{{ activeSubscription.plan?.name }}</h2>
              <p class="text-xs text-muted-foreground mt-1 font-mono">Kode Paket: {{ activeSubscription.plan?.code }}</p>
            </div>
            <div class="text-right">
              <div class="text-base font-bold text-primary">
                {{ formatCurrency(activeSubscription.plan?.price) }}
              </div>
              <div class="text-[10px] text-muted-foreground uppercase font-bold tracking-wider mt-0.5">
                Per {{ activeSubscription.plan?.billing_cycle === 'monthly' ? 'Bulan' : activeSubscription.plan?.billing_cycle === 'yearly' ? 'Tahun' : 'Selamanya' }}
              </div>
            </div>
          </div>
          <CardContent class="p-6 space-y-6">
            <!-- Period & Details -->
            <div class="grid grid-cols-2 gap-4 border-b pb-6">
              <div>
                <span class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">Tanggal Aktivasi</span>
                <p class="text-sm font-semibold text-foreground mt-1">
                  {{ new Date(activeSubscription.starts_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                </p>
              </div>
              <div>
                <span class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider">Tanggal Berakhir</span>
                <p class="text-sm font-semibold text-foreground mt-1">
                  {{ new Date(activeSubscription.ends_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                </p>
              </div>
            </div>

            <!-- Limit and Capacity -->
            <div class="space-y-4">
              <h3 class="text-xs font-bold text-foreground uppercase tracking-wider">Batasan & Kapasitas Paket</h3>
              <div class="grid sm:grid-cols-2 gap-4">
                <!-- Limit 1: Max Schools -->
                <div class="bg-muted/40 p-4 rounded-xl border border-border/50">
                  <div class="text-[10px] font-semibold text-muted-foreground uppercase">Batas Unit Sekolah</div>
                  <div class="text-lg font-bold text-foreground mt-1">
                    {{ activeSubscription.plan?.max_schools }} <span class="text-xs text-muted-foreground font-medium">Unit</span>
                  </div>
                </div>

                <!-- Limit 2: Max Students -->
                <div class="bg-muted/40 p-4 rounded-xl border border-border/50">
                  <div class="text-[10px] font-semibold text-muted-foreground uppercase">Batas Jumlah Siswa</div>
                  <div class="text-lg font-bold text-foreground mt-1">
                    {{ activeSubscription.plan?.max_students }} <span class="text-xs text-muted-foreground font-medium">Siswa</span>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Features list card -->
        <Card class="h-fit">
          <CardHeader class="pb-3 border-b">
            <CardTitle class="text-xs font-bold uppercase tracking-wider">Fitur Paket Yang Aktif</CardTitle>
          </CardHeader>
          <CardContent class="p-6">
            <ul v-if="activeSubscription.plan?.features && activeSubscription.plan.features.length > 0" class="space-y-3">
              <li v-for="(feat, idx) in activeSubscription.plan.features" :key="idx" class="flex items-start gap-2.5 text-xs font-medium text-foreground/90">
                <span class="w-4 h-4 rounded-full bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center shrink-0 mt-0.5 text-[10px] font-black">✓</span>
                <span>{{ feat }}</span>
              </li>
            </ul>
            <div v-else class="text-xs text-muted-foreground font-medium">
              Tidak ada detail fitur khusus yang tercatat untuk paket ini.
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- No Subscription -->
      <Card v-else class="border-amber-200 bg-amber-50/20 dark:bg-amber-950/10 p-8 text-center max-w-2xl mx-auto mt-6">
        <div class="w-16 h-16 bg-amber-100 dark:bg-amber-900/40 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4">
          <HelpCircle class="w-8 h-8" />
        </div>
        <h2 class="text-xl font-bold text-foreground">Tidak Ada Paket Langganan Aktif</h2>
        <p class="text-muted-foreground text-sm mt-2 leading-relaxed">
          Yayasan Anda belum memiliki paket langganan aktif, atau masa aktif paket langganan Anda telah berakhir.
          Silakan hubungi administrator sistem (Super Admin) untuk membeli atau memperbarui paket langganan Anda agar dapat mengakses modul penuh aplikasi Gerbang Sekolah.
        </p>
      </Card>
    </template>

    <!-- View for Superadmin / Plan Management -->
    <template v-else>
      <!-- Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Manajemen Paket Langganan</h1>
          <p class="text-muted-foreground mt-2 text-sm">Kelola produk, harga, limitasi, dan fitur paket lisensi Gerbang Sekolah.</p>
        </div>
        <Button @click="openAddModal" class="h-10 bg-primary text-primary-foreground font-semibold flex items-center gap-2">
          <Plus class="w-4 h-4" /> Buat Paket Baru
        </Button>
      </div>

      <!-- plans list -->
      <Card>
        <Table>
          <TableHeader class="bg-muted/50">
            <TableRow>
              <TableHead class="font-semibold text-xs uppercase text-muted-foreground w-[60px] text-center">NO</TableHead>
              <TableHead class="font-semibold text-xs uppercase text-muted-foreground">PAKET (CODE)</TableHead>
              <TableHead class="font-semibold text-xs uppercase text-muted-foreground">TARIF</TableHead>
              <TableHead class="font-semibold text-xs uppercase text-muted-foreground">BILLING CYCLE</TableHead>
              <TableHead class="font-semibold text-xs uppercase text-muted-foreground">LIMITASI</TableHead>
              <TableHead class="font-semibold text-xs uppercase text-muted-foreground">STATUS</TableHead>
              <TableHead class="font-semibold text-xs uppercase text-muted-foreground">AKSI</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(plan, index) in plans" :key="plan.id">
              <TableCell class="text-center font-medium text-muted-foreground">
                {{ index + 1 }}
              </TableCell>
              <TableCell>
                <div>
                  <div class="font-bold text-foreground">{{ plan.name }}</div>
                  <div class="text-xs text-muted-foreground font-mono">{{ plan.code }}</div>
                </div>
              </TableCell>
              <TableCell>
                <div class="font-bold text-foreground">{{ formatCurrency(plan.price) }}</div>
              </TableCell>
              <TableCell>
                <Badge variant="outline" class="uppercase text-[10px]">{{ plan.billing_cycle }}</Badge>
              </TableCell>
              <TableCell>
                <div class="text-xs space-y-0.5 text-muted-foreground font-medium">
                  <div>Max Unit: <strong class="text-foreground">{{ plan.max_schools }}</strong></div>
                  <div>Max Siswa: <strong class="text-foreground">{{ plan.max_students }}</strong></div>
                </div>
              </TableCell>
              <TableCell>
                <Badge :variant="plan.is_active ? 'default' : 'secondary'">
                  {{ plan.is_active ? 'Aktif' : 'Non-Aktif' }}
                </Badge>
              </TableCell>
              <TableCell>
                <div class="flex items-center gap-3 text-muted-foreground">
                  <button @click="openEditModal(plan)" class="hover:text-foreground"><Pencil class="w-4 h-4" /></button>
                  <button @click="handleDelete(plan.id)" class="hover:text-destructive"><Trash2 class="w-4 h-4" /></button>
                </div>
              </TableCell>
            </TableRow>
            
            <TableRow v-if="plans.length === 0">
              <TableCell colspan="7" class="text-center py-8 text-muted-foreground font-medium">
                {{ isLoading ? 'Memuat data paket...' : 'Tidak ada paket langganan yang tersedia.' }}
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </Card>

      <!-- Modal Form (Simple Overlay) -->
      <div v-if="isModalOpen" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <Card class="w-full max-w-lg bg-card border-border overflow-hidden">
          <CardHeader class="border-b pb-4">
            <CardTitle class="text-xl font-bold">{{ isEditing ? 'Edit Paket Langganan' : 'Buat Paket Langganan Baru' }}</CardTitle>
          </CardHeader>
          <CardContent class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-xs font-bold text-muted-foreground uppercase">Nama Paket</label>
                <input v-model="form.name" type="text" placeholder="Contoh: Paket Premium" class="w-full h-10 px-3 bg-background border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
              </div>
              <div class="space-y-2">
                <label class="text-xs font-bold text-muted-foreground uppercase">Kode Paket (Unique Code)</label>
                <input v-model="form.code" type="text" placeholder="Contoh: plan_premium" :disabled="isEditing" class="w-full h-10 px-3 bg-background border border-border rounded-lg text-sm focus:outline-none focus:border-primary disabled:opacity-50" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-xs font-bold text-muted-foreground uppercase">Harga Paket (IDR)</label>
                <input v-model.number="form.price" type="number" placeholder="0" class="w-full h-10 px-3 bg-background border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
              </div>
              <div class="space-y-2">
                <label class="text-xs font-bold text-muted-foreground uppercase">Siklus Penagihan</label>
                <select v-model="form.billing_cycle" class="w-full h-10 px-3 bg-background border border-border rounded-lg text-sm focus:outline-none focus:border-primary">
                  <option value="monthly">Bulanan</option>
                  <option value="yearly">Tahunan</option>
                  <option value="lifetime">Sekali Bayar (Lifetime)</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label class="text-xs font-bold text-muted-foreground uppercase">Batas Maks Unit Sekolah</label>
                <input v-model.number="form.max_schools" type="number" class="w-full h-10 px-3 bg-background border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
              </div>
              <div class="space-y-2">
                <label class="text-xs font-bold text-muted-foreground uppercase">Batas Maks Siswa</label>
                <input v-model.number="form.max_students" type="number" class="w-full h-10 px-3 bg-background border border-border rounded-lg text-sm focus:outline-none focus:border-primary" />
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-bold text-muted-foreground uppercase">Fitur Unggulan (Satu fitur per baris)</label>
              <textarea v-model="form.features" placeholder="Contoh:&#10;Akses modul Akademik lengkap&#10;WhatsApp Notifikasi Otomatis" rows="4" class="w-full p-3 bg-background border border-border rounded-lg text-sm focus:outline-none focus:border-primary resize-none"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-2">
              <input v-model="form.is_active" type="checkbox" id="isActive" class="w-4 h-4 rounded text-primary border-border" />
              <label for="isActive" class="text-sm font-semibold select-none cursor-pointer">Aktifkan dan Publikasikan Paket Ini</label>
            </div>

            <FormFooter
              :is-edit="isEditing"
              entity-name="Paket Langganan"
              :before-confirm="validatePlan"
              @save="handleSave"
            />
          </CardContent>
        </Card>
      </div>
    </template>
  </div>
</template>

