<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { toast } from 'vue-sonner'
import {
  CalendarDays,
  Banknote,
  UserCheck,
  Download,
  Pencil,
  Trash2,
  Save,
  Search,
  X,
  Plus,
  RefreshCw,
  SlidersHorizontal
} from 'lucide-vue-next'
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import FormFooter from '@/components/forms/FormFooter.vue'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import { 
  getSppTariffs, 
  storeSppTariff, 
  updateSppTariff, 
  deleteSppTariff 
} from '@/services/sppService'
import { getClassrooms } from '@/services/managementService'

const auth = useAuthStore()

// State
const searchQuery = ref('')
const selectedTypeFilter = ref('semua')
const showFilters = ref(false)
const isLoading = ref(false)

const tariffs = ref([])
const classrooms = ref([])

// Form state
const initialForm = {
  id: null,
  name: '',
  amount: '',
  type: 'mandatory',
  classroom_id: 'all'
}
const form = ref({ ...initialForm })
const isEditing = ref(false)

// Helper function to format IDR currency
const formatCurrency = (value) => {
  if (value === undefined || value === null) return '0'
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 0
  }).format(value)
}

// Fetch Tariffs and Classrooms
const loadTariffs = async () => {
  try {
    isLoading.value = true
    const res = await getSppTariffs()
    if (res.status === 'success') {
      tariffs.value = res.data
    }
  } catch (err) {
    toast.error('Gagal mengambil data tarif SPP')
  } finally {
    isLoading.value = false
  }
}

const loadClassrooms = async () => {
  try {
    const res = await getClassrooms()
    if (res.status === 'success') {
      classrooms.value = res.data
    }
  } catch (err) {
    console.error('Gagal mengambil data kelas:', err)
  }
}

onMounted(() => {
  loadTariffs()
  loadClassrooms()
})

const validateTariff = () => {
  if (!form.value.name.trim()) {
    toast.error('Silakan isi nama biaya dengan benar!')
    return false
  }
  if (!form.value.amount || Number(form.value.amount) <= 0) {
    toast.error('Silakan isi nominal tarif dengan benar!')
    return false
  }
  return true
}

// Handle Form Submit
const handleSave = async () => {
  const payload = {
    name: form.value.name,
    amount: Number(form.value.amount),
    type: form.value.type,
    classroom_id: form.value.classroom_id === 'all' ? null : Number(form.value.classroom_id)
  }

  try {
    isLoading.value = true
    if (isEditing.value) {
      const res = await updateSppTariff(form.value.id, payload)
      if (res.status === 'success') {
        toast.success('Tarif SPP berhasil diperbarui!')
        resetForm()
        loadTariffs()
      }
    } else {
      const res = await storeSppTariff(payload)
      if (res.status === 'success') {
        toast.success('Tarif SPP baru berhasil disimpan!')
        resetForm()
        loadTariffs()
      }
    }
  } catch (err) {
    toast.error('Gagal menyimpan tarif SPP')
  } finally {
    isLoading.value = false
  }
}

// Trigger Edit Mode
const editTarif = (item) => {
  form.value = {
    id: item.id,
    name: item.name,
    amount: item.amount,
    type: item.type,
    classroom_id: item.classroom_id ? item.classroom_id.toString() : 'all'
  }
  isEditing.value = true
}

// Cancel Edit Mode
const cancelEdit = () => {
  resetForm()
}

// Reset Form
const resetForm = () => {
  form.value = { ...initialForm }
  isEditing.value = false
}

// Delete Action
const deleteTarif = async (item) => {
  if (!confirm(`Apakah Anda yakin ingin menghapus tarif "${item.name}"?`)) {
    return
  }
  try {
    isLoading.value = true
    const res = await deleteSppTariff(item.id)
    if (res.status === 'success') {
      toast.success('Tarif SPP berhasil dihapus!')
      loadTariffs()
    }
  } catch (err) {
    toast.error('Gagal menghapus tarif SPP')
  } finally {
    isLoading.value = false
  }
}

// Export Trigger
const handleExport = () => {
  toast.success('Data tarif SPP berhasil diekspor!')
}

// Computed Filtered Tariffs
const filteredTariffs = computed(() => {
  return tariffs.value.filter((t) => {
    // Search filter
    const matchesSearch =
      t.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      (t.classroom && t.classroom.name.toLowerCase().includes(searchQuery.value.toLowerCase()))

    // Type filter
    const matchesType =
      selectedTypeFilter.value === 'semua' ||
      t.type === selectedTypeFilter.value

    return matchesSearch && matchesType
  })
})
</script>

<template>
  <div class="space-y-6 w-full mx-auto px-0 text-left">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
      <div>
        <h1 class="text-3xl font-bold tracking-tight text-foreground my-0">Pengaturan Tarif SPP</h1>
        <p class="text-sm text-muted-foreground mt-1">Unit Sekolah: SMA Nusantara</p>
      </div>
    </div>

    <!-- Top Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <!-- Tahun Ajaran Aktif -->
      <Card class="shadow-sm border-border p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-4">
          <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">Tahun Ajaran Aktif</p>
          <div class="w-10 h-10 rounded-lg bg-secondary flex items-center justify-center">
            <CalendarDays class="w-5 h-5 text-foreground" />
          </div>
        </div>
        <div>
          <p class="text-2xl lg:text-3xl font-extrabold tracking-tight text-foreground">2023/2024</p>
        </div>
      </Card>

      <!-- Dana Teralokasi -->
      <Card class="shadow-sm border-border p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-4">
          <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">Dana Teralokasi</p>
          <div class="w-10 h-10 rounded-lg bg-emerald-500/10 dark:bg-emerald-500/20 flex items-center justify-center">
            <Banknote class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
          </div>
        </div>
        <div>
          <p class="text-2xl lg:text-3xl font-extrabold tracking-tight text-emerald-600 dark:text-emerald-400">
            Rp 2.450.000.000
          </p>
        </div>
      </Card>

      <!-- Sisa Kuota Beasiswa -->
      <Card class="shadow-sm border-border p-5 flex flex-col justify-between">
        <div class="flex justify-between items-start mb-4">
          <p class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">Sisa Kuota Beasiswa</p>
          <div class="w-10 h-10 rounded-lg bg-rose-500/10 dark:bg-rose-500/20 flex items-center justify-center">
            <UserCheck class="w-5 h-5 text-rose-600 dark:text-rose-400" />
          </div>
        </div>
        <div>
          <p class="text-2xl lg:text-3xl font-extrabold tracking-tight text-rose-600 dark:text-rose-400">
            42 Siswa
          </p>
        </div>
      </Card>
    </div>

    <!-- Main Content Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column (Daftar Tarif Aktif) -->
      <Card class="lg:col-span-2 shadow-sm border-border flex flex-col h-full">
        <div class="p-5 flex flex-col sm:flex-row justify-between sm:items-center gap-4 border-b border-border">
          <h3 class="font-bold text-lg text-foreground">Daftar Tarif Aktif</h3>
          <div class="flex items-center gap-2">
            <!-- Search Toggle / Input -->
            <div class="relative w-44 sm:w-56">
              <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                type="text"
                v-model="searchQuery"
                placeholder="Cari tarif..."
                class="pl-8 h-9 rounded-md bg-muted/40"
              />
            </div>
            <!-- Filter Button -->
            <Button
              variant="outline"
              size="icon"
              class="h-9 w-9"
              @click="showFilters = !showFilters"
              :class="{ 'bg-secondary': showFilters }"
            >
              <SlidersHorizontal class="w-4 h-4 text-foreground" />
            </Button>
            <!-- Export Button -->
            <Button variant="outline" size="icon" class="h-9 w-9" @click="handleExport">
              <Download class="w-4 h-4 text-foreground" />
            </Button>
          </div>
        </div>

        <!-- Filter Dropdown Drawer/Row -->
        <div v-if="showFilters" class="px-5 py-3 bg-muted/20 border-b border-border flex items-center gap-4">
          <span class="text-xs font-semibold text-muted-foreground">Tipe Pembayaran:</span>
          <div class="w-40">
            <Select v-model="selectedTypeFilter">
              <SelectTrigger class="h-8">
                <SelectValue placeholder="Pilih Tipe" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="semua">Semua Tipe</SelectItem>
                <SelectItem value="mandatory">Wajib (Mandatory)</SelectItem>
                <SelectItem value="addon">Pilihan (Addon)</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <!-- Table -->
        <div class="flex-grow overflow-x-auto">
          <Table>
            <TableHeader class="bg-muted/30">
              <TableRow class="hover:bg-transparent">
                <TableHead class="font-bold text-xs uppercase tracking-wider text-muted-foreground py-3">Nama Biaya & Lingkup</TableHead>
                <TableHead class="font-bold text-xs uppercase tracking-wider text-muted-foreground py-3">Tipe</TableHead>
                <TableHead class="font-bold text-xs uppercase tracking-wider text-muted-foreground py-3">Nominal</TableHead>
                <TableHead class="font-bold text-xs uppercase tracking-wider text-muted-foreground py-3 text-center">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-if="filteredTariffs.length === 0">
                <TableCell colspan="4" class="text-center py-8 text-muted-foreground text-sm">
                  Tidak ada data tarif SPP ditemukan.
                </TableCell>
              </TableRow>
              <TableRow v-for="item in filteredTariffs" :key="item.id" class="group hover:bg-muted/10">
                <!-- Tipe & Lingkup -->
                <TableCell class="py-4">
                  <p class="font-bold text-foreground text-sm">{{ item.name }}</p>
                  <p class="text-xs text-muted-foreground mt-0.5">
                    {{ item.classroom ? `Khusus Kelas: ${item.classroom.name}` : 'Semua Kelas' }}
                  </p>
                </TableCell>
                <!-- Kategori -->
                <TableCell class="py-4 text-sm">
                  <Badge variant="outline" :class="item.type === 'mandatory' ? 'bg-blue-500/10 text-blue-500 border-blue-100 font-semibold' : 'bg-amber-500/10 text-amber-500 border-amber-100 font-semibold'">
                    {{ item.type === 'mandatory' ? 'Wajib' : 'Pilihan (Addon)' }}
                  </Badge>
                </TableCell>
                <!-- Nominal -->
                <TableCell class="py-4">
                  <span class="text-xs text-muted-foreground font-semibold mr-1">Rp</span>
                  <span class="font-extrabold text-foreground">{{ formatCurrency(item.amount) }}</span>
                </TableCell>
                <!-- Aksi -->
                <TableCell class="py-4">
                  <div class="flex items-center justify-center gap-1">
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-8 w-8 hover:bg-secondary"
                      @click="editTarif(item)"
                    >
                      <Pencil class="w-4 h-4 text-foreground" />
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      class="h-8 w-8 text-destructive hover:bg-destructive/10"
                      @click="deleteTarif(item)"
                    >
                      <Trash2 class="w-4 h-4" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </Card>

      <!-- Right Column (Atur Tarif Baru) -->
      <Card class="shadow-sm border-border h-fit">
        <CardHeader class="pb-4 border-b border-border">
          <CardTitle class="text-lg font-bold flex items-center justify-between">
            <span>{{ isEditing ? 'Edit Tarif' : 'Atur Tarif Baru' }}</span>
            <Button
              v-if="isEditing"
              variant="ghost"
              size="icon"
              class="h-7 w-7 text-muted-foreground"
              @click="cancelEdit"
            >
              <X class="w-4 h-4" />
            </Button>
          </CardTitle>
        </CardHeader>
        <CardContent class="p-5 space-y-4">
          <!-- Nama Biaya -->
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Nama Biaya / Tipe Tarif</label>
            <Input
              type="text"
              v-model="form.name"
              placeholder="Contoh: SPP Bulanan, Lab Fees"
              class="w-full h-10 bg-muted/30 border-border rounded-md px-3"
            />
          </div>

          <!-- Tipe Pembayaran -->
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Tipe Pembayaran</label>
            <Select v-model="form.type">
              <SelectTrigger class="w-full bg-muted/30">
                <SelectValue placeholder="Pilih Tipe Pembayaran" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="mandatory">Wajib (Mandatory)</SelectItem>
                <SelectItem value="addon">Pilihan (Addon)</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Lingkup Kelas -->
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Lingkup Kelas</label>
            <Select v-model="form.classroom_id">
              <SelectTrigger class="w-full bg-muted/30">
                <SelectValue placeholder="Semua Kelas" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Semua Kelas</SelectItem>
                <SelectItem v-for="cls in classrooms" :key="cls.id" :value="cls.id.toString()">
                  {{ cls.name }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Nominal -->
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Nominal Tarif (Rp)</label>
            <Input
              type="number"
              v-model="form.amount"
              placeholder="Contoh: 150000"
              class="w-full h-10 bg-muted/30 border-border rounded-md px-3"
            />
          </div>

          <!-- Submit Button -->
          <FormFooter
            :loading="isLoading"
            :is-edit="isEditing"
            entity-name="Tarif SPP"
            :label="isEditing ? 'Perbarui Tarif' : 'Simpan Tarif'"
            :before-confirm="validateTariff"
            @save="handleSave"
          />
        </CardContent>
      </Card>
    </div>
  </div>
</template>
