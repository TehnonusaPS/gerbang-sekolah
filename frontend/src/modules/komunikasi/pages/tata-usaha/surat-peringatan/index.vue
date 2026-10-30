<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { Plus, Mail, AlertTriangle, CreditCard, Check, ChevronsUpDown } from 'lucide-vue-next'
import { useSuratPeringatanStore } from '@/stores/suratPeringatanStore'
import { useAuthStore } from '@/stores/authStore'
import { echo } from '@/services/echoService'
import { usePagination } from '@/composables/usePagination'
import { toast } from 'vue-sonner'
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import SuratPeringatanPrintModal from '../../../components/SuratPeringatanPrintModal.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import StatCard from '@/components/stat-card/StatCard.vue'
import StatCardGrid from '@/components/stat-card/StatCardGrid.vue'
import { glassSlide, glassFade } from '@/config/motion'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command'
import { fetchAllSiswa } from '@/services/siswaService'

const store = useSuratPeringatanStore()
const auth = useAuthStore()

const isPrintModalOpen = ref(false)
const selectedSurat = ref(null)

// --- Inline Form State ---
const isFormSheetOpen = ref(false)
const isEditMode = ref(false)
const isLoadingSiswa = ref(false)
const isSiswaPopoverOpen = ref(false)
const siswaList = ref([])

const formItem = ref({
  id: '',
  jenisSurat: 'Surat Pelanggaran',
  siswaId: null,
  namaSiswa: '',
  nisn: '',
  kelas: '',
  tanggal: '',
  perihalPelanggaran: '',
  jumlahTunggakan: '',
  status: 'Selesai'
})

onMounted(async () => {
  isLoadingSiswa.value = true
  try {
    // Load real warning letters from backend
    await store.fetchItems()

    // Fetch and map active student database list correctly
    const resSiswa = await fetchAllSiswa()
    siswaList.value = (resSiswa.data || []).map(student => ({
      id: student.id,
      name: student.nama,
      nisn: student.nisn,
      kelas: student.kelas
    }))
  } catch (error) {
    toast.error('Gagal memuat data awal.')
    console.error(error)
  } finally {
    isLoadingSiswa.value = false
  }

  // Bind Pusher Realtime
  const schoolId = auth.user?.school_id
  if (schoolId && echo) {
    echo.private(`persuratan.${schoolId}`)
      .listen('StudentWarningCertificateCreated', (event) => {
        if (!store.items.some(item => item.id === event.id)) {
          store.items.unshift(event)
          toast.info('Surat Peringatan Baru Dibuat!', {
            description: `Surat peringatan untuk siswa ${event.namaSiswa} telah diterbitkan.`,
            duration: 5000
          })
        }
      })
  }
})

onUnmounted(() => {
  const schoolId = auth.user?.school_id
  if (schoolId && echo) {
    echo.leaveChannel(`persuratan.${schoolId}`)
  }
})

const computedSiswaList = computed(() => {
  const list = [...siswaList.value]
  if (formItem.value.namaSiswa && !list.some(s => s.name === formItem.value.namaSiswa)) {
    list.push({
      id: -1,
      name: formItem.value.namaSiswa,
      nisn: formItem.value.nisn || '',
      kelas: formItem.value.kelas || ''
    })
  }
  return list
})

const selectedSiswaId = computed(() => {
  if (!formItem.value.namaSiswa) return undefined
  const found = computedSiswaList.value.find(s => s.name === formItem.value.namaSiswa)
  return found ? found.id.toString() : undefined
})

function onSiswaSelected(siswaId) {
  const siswa = computedSiswaList.value.find(s => s.id === parseInt(siswaId))
  if (siswa) {
    formItem.value.siswaId = siswa.id
    formItem.value.namaSiswa = siswa.name
    formItem.value.nisn = siswa.nisn
    formItem.value.kelas = siswa.kelas || ''
  }
}

function formatRupiah(value) {
  if (!value) return ''
  const numericString = value.replace(/[^0-9]/g, '')
  return numericString.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
}

function handleJumlahTunggakanInput(e) {
  const formatted = formatRupiah(e.target.value)
  formItem.value.jumlahTunggakan = formatted
}

const filterValues = ref({
  search: ''
})

const filters = computed(() => [
  { type: 'search', key: 'search', placeholder: 'Cari Nama Siswa ......' }
])

const columns = computed(() => [
  { key: 'namaSiswa', label: 'Nama Siswa' },
  { key: 'tanggalDibuat', label: 'Tanggal Dibuat', type: 'date' },
  { key: 'jenisSurat', label: 'Jenis Surat' },
  { key: 'actions', label: 'Aksi' }
])

const itemsPerPage = ref(5)

const suratList = computed(() => {
  if (!filterValues.value.search) return store.items
  const query = filterValues.value.search.toLowerCase()
  return store.items.filter(item => item.namaSiswa.toLowerCase().includes(query))
})

const { currentPage, total, from, to, paginatedItems: paginatedSuratList } = usePagination(suratList, itemsPerPage)

watch(suratList, () => {
  currentPage.value = 1
})

const totalPelanggaran = computed(() => {
  return store.items.filter(i => i.jenisSurat === 'Surat Pelanggaran').length
})

const totalTunggakan = computed(() => {
  return store.items.filter(i => i.jenisSurat === 'Surat Tunggakan').length
})

function openCreateForm() {
  isEditMode.value = false
  formItem.value = {
    id: '',
    jenisSurat: 'Surat Pelanggaran',
    siswaId: null,
    namaSiswa: '',
    nisn: '',
    kelas: '',
    tanggal: new Date().toISOString().split('T')[0],
    perihalPelanggaran: '',
    jumlahTunggakan: '',
    status: 'Selesai'
  }
  isFormSheetOpen.value = true
}

function viewSurat(id, item) {
  if (item && typeof item === 'object') {
    selectedSurat.value = item
  } else if (id && typeof id === 'object') {
    selectedSurat.value = id
  } else if (id) {
    selectedSurat.value = store.getById(id)
  }
  isPrintModalOpen.value = true
}

function editSurat(id, item) {
  isEditMode.value = true
  const resolvedItem = (item && typeof item === 'object') ? item : id
  
  // Find database latest student details if possible
  const currentStudent = siswaList.value.find(s => s.id === resolvedItem.studentId || s.name === resolvedItem.namaSiswa || s.nisn === resolvedItem.nisn)

  formItem.value = {
    id: resolvedItem.id,
    jenisSurat: resolvedItem.jenisSurat,
    siswaId: currentStudent ? currentStudent.id : resolvedItem.siswaId,
    namaSiswa: resolvedItem.namaSiswa,
    nisn: resolvedItem.nisn,
    kelas: currentStudent ? (currentStudent.kelas || '') : resolvedItem.kelas,
    tanggal: resolvedItem.tanggal,
    perihalPelanggaran: resolvedItem.perihalPelanggaran || '',
    jumlahTunggakan: resolvedItem.jumlahTunggakan || '',
    status: resolvedItem.status || 'Selesai'
  }
  isFormSheetOpen.value = true
}

async function deleteSurat(id) {
  try {
    await store.remove(id)
    toast.success('Surat peringatan berhasil dihapus!')
  } catch (error) {
    toast.error('Gagal menghapus surat peringatan.')
  }
}

async function handleSave() {
  if (!formItem.value.namaSiswa || !formItem.value.jenisSurat) {
    toast.error('Mohon lengkapi Jenis Surat dan Nama Siswa.')
    return
  }

  if (formItem.value.jenisSurat === 'Surat Pelanggaran') {
    if (!formItem.value.perihalPelanggaran) {
      toast.error('Mohon lengkapi Perihal Pelanggaran.')
      return
    }
    formItem.value.jumlahTunggakan = ''
  }

  if (formItem.value.jenisSurat === 'Surat Tunggakan') {
    if (!formItem.value.jumlahTunggakan) {
      toast.error('Mohon lengkapi Jumlah Tunggakan.')
      return
    }
    formItem.value.perihalPelanggaran = ''
  }

  const payload = {
    jenisSurat: formItem.value.jenisSurat,
    studentId: formItem.value.siswaId,
    kelas: formItem.value.kelas,
    tanggal: formItem.value.tanggal,
    perihalPelanggaran: formItem.value.perihalPelanggaran,
    jumlahTunggakan: formItem.value.jumlahTunggakan,
    status: formItem.value.status || 'Selesai'
  }

  try {
    if (isEditMode.value) {
      await store.update(formItem.value.id, payload)
      toast.success('Surat peringatan berhasil diperbarui!')
    } else {
      await store.add(payload)
      toast.success('Surat peringatan berhasil dibuat!')
    }
    isFormSheetOpen.value = false
  } catch (error) {
    toast.error('Gagal menyimpan surat.')
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
    <!-- Header & Stats Card -->
    <PageHeader
      title="Daftar Surat Peringatan Kedisiplinan/Tunggakan"
      description="Kelola Surat Peringatan Kedisiplinan/Tunggakan"
      :actions="[
        {
          label: 'Buat Surat',
          icon: Plus,
          variant: 'default',
          click: openCreateForm
        }
      ]"
    />

    <StatCardGrid
      cols="3"
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 100 } }"
    >
      <StatCard
        label="Total Surat Dibuat"
        :value="store.items.length"
        :icon="Mail"
        illustration="paper_sheet"
        variant="primary"
      />
      <StatCard
        label="Surat Kedisiplinan"
        :value="totalPelanggaran"
        :icon="AlertTriangle"
        illustration="school_bell"
        variant="amber"
      />
      <StatCard
        label="Surat Tunggakan"
        :value="totalTunggakan"
        :icon="CreditCard"
        illustration="bag"
        variant="violet"
      />
    </StatCardGrid>

    <!-- Filters & Table using DataTableCard -->
    <div
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 200 } }"
    >
      <DataTableCard
        :columns="columns"
        :items="paginatedSuratList"
        :filters="filters"
        v-model:filterValues="filterValues"
        illustration="paper_sheet"
        v-model:perPage="itemsPerPage"
        :from="from"
        :to="to"
        :total="total"
        :page="currentPage"
        @update:page="currentPage = $event"
        @view="viewSurat"
        @edit="editSurat"
        @delete="deleteSurat"
        delete-label="namaSiswa"
      >
        <template #cell-namaSiswa="{ item }">
          <div class="flex flex-col text-left">
            <span class="font-medium text-foreground">{{ item.namaSiswa }}</span>
            <span class="text-[10px] text-muted-foreground">{{ item.nisn }}</span>
          </div>
        </template>

        <template #cell-jenisSurat="{ value }">
          <span class="font-medium text-xs sm:text-sm" :class="value === 'Surat Pelanggaran' ? 'text-amber-600' : 'text-rose-600'">
            {{ value === 'Surat Pelanggaran' ? 'Kedisiplinan' : 'Tunggakan' }}
          </span>
        </template>
      </DataTableCard>
    </div>
    
    <!-- Form Sheet (Inline Create/Edit) -->
    <Sheet v-model:open="isFormSheetOpen">
      <SheetContent :show-close-button="false" class="sm:max-w-[500px] flex flex-col h-full gap-2">
        <SheetHeader class="border-b border-border pb-3 text-left">
          <SheetTitle class="text-base font-bold text-foreground">
            {{ isEditMode ? 'Edit Surat Peringatan' : 'Buat Surat Peringatan' }}
          </SheetTitle>
          <SheetDescription class="text-xs text-muted-foreground">
            {{ isEditMode ? 'Perbarui informasi surat peringatan siswa.' : 'Lengkapi formulir di bawah ini untuk menerbitkan surat peringatan baru.' }}
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
                  <!-- Jenis Surat -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Jenis Surat</label>
                    <Select v-model="formItem.jenisSurat">
                      <SelectTrigger class="h-10 rounded-xl">
                        <SelectValue placeholder="Pilih Jenis Surat" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="Surat Pelanggaran">Surat Pelanggaran (Kedisiplinan)</SelectItem>
                        <SelectItem value="Surat Tunggakan">Surat Tunggakan</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Nama Siswa (Searchable Popover Combobox) -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Nama Siswa</label>
                    <Popover v-model:open="isSiswaPopoverOpen">
                      <PopoverTrigger as-child>
                        <Button
                          variant="outline"
                          role="combobox"
                          :aria-expanded="isSiswaPopoverOpen"
                          class="h-10 w-full justify-between rounded-xl px-3 font-normal text-muted-foreground hover:bg-background/50 border-input shadow-none"
                          :disabled="isLoadingSiswa"
                        >
                          <span v-if="formItem.namaSiswa" class="text-foreground font-medium">
                            {{ formItem.namaSiswa }} - {{ formItem.nisn }}
                          </span>
                          <span v-else>
                            {{ isLoadingSiswa ? 'Memuat data siswa...' : 'Pilih Siswa...' }}
                          </span>
                          <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </Button>
                      </PopoverTrigger>
                      <PopoverContent class="w-[var(--reka-popover-trigger-width)] p-0 rounded-xl shadow-xl border border-border/50" align="start">
                        <Command>
                          <CommandInput placeholder="Cari nama, NISN, atau kelas..." class="h-9" />
                          <CommandList class="max-h-[250px] overflow-y-auto">
                            <CommandEmpty>Siswa tidak ditemukan.</CommandEmpty>
                            <CommandGroup>
                              <CommandItem
                                v-for="siswa in computedSiswaList"
                                :key="siswa.id"
                                :value="`${siswa.name} ${siswa.nisn} ${siswa.kelas}`"
                                @select="() => {
                                  onSiswaSelected(siswa.id.toString())
                                  isSiswaPopoverOpen = false
                                }"
                                class="flex items-center justify-between cursor-pointer py-2 px-3 text-xs"
                              >
                                <div class="flex flex-col text-left">
                                  <span class="font-semibold text-foreground text-sm">{{ siswa.name }}</span>
                                  <span class="text-[10px] text-muted-foreground">NISN: {{ siswa.nisn }} | Kelas: {{ siswa.kelas || '-' }}</span>
                                </div>
                                <Check
                                  v-if="selectedSiswaId === siswa.id.toString()"
                                  class="h-4 w-4 text-primary shrink-0 ml-2"
                                />
                              </CommandItem>
                            </CommandGroup>
                          </CommandList>
                        </Command>
                      </PopoverContent>
                    </Popover>
                  </div>

                  <!-- Kelas -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Kelas</label>
                    <Input v-model="formItem.kelas" placeholder="Terisi otomatis" readonly class="h-10 bg-muted/40 cursor-default rounded-xl" />
                  </div>

                  <!-- Tanggal Surat -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Tanggal Surat</label>
                    <Input type="date" v-model="formItem.tanggal" class="h-10 rounded-xl" />
                  </div>

                  <!-- CONDITIONAL: Surat Pelanggaran -->
                  <div v-if="formItem.jenisSurat === 'Surat Pelanggaran'" class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Perihal Pelanggaran</label>
                    <Textarea v-model="formItem.perihalPelanggaran" placeholder="Masukkan keterangan perihal pelanggaran..." class="min-h-24 rounded-xl resize-none" />
                  </div>

                  <!-- CONDITIONAL: Surat Tunggakan -->
                  <div v-if="formItem.jenisSurat === 'Surat Tunggakan'" class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Jumlah Tunggakan</label>
                    <div class="relative">
                      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground font-medium select-none text-sm">Rp</span>
                      <Input 
                        type="text" 
                        v-model="formItem.jumlahTunggakan"
                        @input="handleJumlahTunggakanInput"
                        placeholder="1.500.000" 
                        style="padding-left: 2.2rem;"
                        class="h-10 rounded-xl" 
                      />
                    </div>
                  </div>
                </div>
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </div>

        <FormFooter
          :is-edit="isEditMode"
          entity-name="Surat Peringatan"
          @save="handleSave"
        />
      </SheetContent>
    </Sheet>

    <!-- Print Modal -->
    <SuratPeringatanPrintModal 
      v-model:open="isPrintModalOpen" 
      :data="selectedSurat" 
    />
  </div>
</template>
