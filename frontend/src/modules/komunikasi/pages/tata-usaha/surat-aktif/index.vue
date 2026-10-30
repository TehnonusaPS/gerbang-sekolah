<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { Plus, Mail, Check, ChevronsUpDown } from 'lucide-vue-next'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command'
import { useSuratAktifStore } from '@/stores/suratAktifStore'
import { useAuthStore } from '@/stores/authStore'
import { echo } from '@/services/echoService'
import { usePagination } from '@/composables/usePagination'
import { toast } from 'vue-sonner'
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import SuratAktifPrintModal from '../../../components/SuratAktifPrintModal.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import StatCard from '@/components/stat-card/StatCard.vue'
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
import { fetchAllSiswa } from '@/services/siswaService'
import { fetchAllAcademicYears } from '@/services/academicYearService'

const store = useSuratAktifStore()
const auth = useAuthStore()

const isPrintModalOpen = ref(false)
const selectedSurat = ref(null)

// --- Inline Form State ---
const isFormSheetOpen = ref(false)
const isEditMode = ref(false)
const isLoadingSiswa = ref(false)
const siswaList = ref([])
const academicYears = ref([])
const isSiswaPopoverOpen = ref(false)

const formItem = ref({
  id: '',
  nama: '',
  nisn: '',
  academicYearId: '',
  tahunAkademik: '',
  semester: 'Genap',
  kelas: '',
  tanggalLahir: '',
  alamat: ''
})

onMounted(async () => {
  isLoadingSiswa.value = true
  try {
    await store.fetchItems()

    const resSiswa = await fetchAllSiswa()
    siswaList.value = (resSiswa.data || []).map(student => ({
      id: student.id,
      name: student.nama,
      nisn: student.nisn,
      kelas: student.kelas,
      tanggalLahir: student.tanggal_lahir,
      alamat: student.alamat
    }))

    const resAcademic = await fetchAllAcademicYears()
    academicYears.value = resAcademic.data || []
  } catch (error) {
    toast.error('Gagal memuat data awal.')
    console.error(error)
  } finally {
    isLoadingSiswa.value = false
  }

  // Bind Laravel Echo for Realtime Surat Keterangan Aktif
  const schoolId = auth.user?.school_id
  if (schoolId && echo) {
    echo.private(`persuratan.${schoolId}`)
      .listen('ActiveStudentCertificateCreated', (event) => {
        // Prevent duplication if the current tab made the request
        if (!store.items.some(item => item.id === event.id)) {
          store.items.unshift(event)
          toast.info('Surat Aktif Baru Dibuat!', {
            description: `Surat keterangan aktif siswa ${event.nama} (NISN: ${event.nisn}) telah diterbitkan.`,
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
  if (formItem.value.nama && !list.some(s => s.name === formItem.value.nama)) {
    list.push({
      id: -1,
      name: formItem.value.nama,
      nisn: formItem.value.nisn || '',
      kelas: formItem.value.kelas || '',
      tanggalLahir: formItem.value.tanggalLahir || '',
      alamat: formItem.value.alamat || ''
    })
  }
  return list
})

const selectedSiswaId = computed(() => {
  if (!formItem.value.nama) return undefined
  const found = computedSiswaList.value.find(s => s.name === formItem.value.nama)
  return found ? found.id.toString() : undefined
})

function onSiswaSelected(siswaId) {
  const siswa = computedSiswaList.value.find(s => s.id === parseInt(siswaId))
  if (siswa) {
    formItem.value.nama = siswa.name
    formItem.value.nisn = siswa.nisn
    formItem.value.kelas = siswa.kelas || ''
    formItem.value.tanggalLahir = siswa.tanggalLahir || ''
    formItem.value.alamat = siswa.alamat || ''
  }
}

const filterValues = ref({
  search: '',
  tanggalDibuat: ''
})

const filters = computed(() => [
  { type: 'search', key: 'search', placeholder: 'Cari Nama Siswa ......' },
  { type: 'date', key: 'tanggalDibuat', label: 'Tanggal:' }
])

const columns = computed(() => [
  { key: 'nama', label: 'Nama Siswa' },
  { key: 'tanggalDibuat', label: 'Tanggal Dibuat', type: 'date' },
  { key: 'actions', label: 'Aksi' }
])

const itemsPerPage = ref(5)

const suratList = computed(() => {
  let list = store.items
  
  if (filterValues.value.search) {
    const query = filterValues.value.search.toLowerCase()
    list = list.filter(item => item.nama.toLowerCase().includes(query))
  }
  
  if (filterValues.value.tanggalDibuat) {
    list = list.filter(item => item.tanggalDibuat === filterValues.value.tanggalDibuat)
  }
  
  return list
})

const { currentPage, total, from, to, paginatedItems: paginatedSuratList } = usePagination(suratList, itemsPerPage)

watch(suratList, () => {
  currentPage.value = 1
})

function openCreateForm() {
  isEditMode.value = false
  formItem.value = {
    id: '',
    nama: '',
    nisn: '',
    academicYearId: '',
    tahunAkademik: '',
    semester: 'Genap',
    kelas: '',
    tanggalLahir: '',
    alamat: ''
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
  
  // Cari data terbaru siswa di database/state
  const currentStudent = siswaList.value.find(s => s.id === resolvedItem.studentId || s.name === resolvedItem.nama || s.nisn === resolvedItem.nisn)
  
  formItem.value = {
    id: resolvedItem.id,
    nama: resolvedItem.nama,
    nisn: resolvedItem.nisn,
    academicYearId: resolvedItem.academicYearId ? resolvedItem.academicYearId.toString() : '',
    tahunAkademik: resolvedItem.tahunAkademik,
    semester: resolvedItem.semester,
    kelas: currentStudent ? (currentStudent.kelas || '') : resolvedItem.kelas,
    tanggalLahir: currentStudent ? (currentStudent.tanggalLahir || '') : resolvedItem.tanggalLahir,
    alamat: currentStudent ? (currentStudent.alamat || '') : (resolvedItem.alamat || '')
  }
  isFormSheetOpen.value = true
}

async function deleteSurat(id) {
  try {
    await store.remove(id)
    toast.success('Surat berhasil dihapus!')
  } catch (error) {
    toast.error('Gagal menghapus surat.')
  }
}

async function handleSave() {
  if (!formItem.value.nama || !formItem.value.academicYearId || !formItem.value.semester || !formItem.value.kelas || !formItem.value.tanggalLahir || !formItem.value.alamat) {
    toast.error('Mohon lengkapi semua kolom yang wajib diisi.')
    return
  }

  const yearObj = academicYears.value.find(y => y.id === parseInt(formItem.value.academicYearId))
  if (yearObj) {
    formItem.value.tahunAkademik = yearObj.name
  }

  try {
    if (isEditMode.value) {
      await store.update(formItem.value.id, {
        ...formItem.value,
        studentId: selectedSiswaId.value
      })
      toast.success('Surat keterangan aktif berhasil diperbarui!')
    } else {
      await store.add({
        ...formItem.value,
        studentId: selectedSiswaId.value
      })
      toast.success('Surat keterangan aktif berhasil dibuat!')
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
      title="Daftar Surat Keterangan Aktif"
      description="Kelola Surat Keterangan Aktif"
      :actions="[
        {
          label: 'Buat Surat Keterangan Aktif',
          icon: Plus,
          variant: 'default',
          click: openCreateForm
        }
      ]"
    />

    <div
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 100 } }"
    >
      <StatCard
        label="Total Surat Keterangan Aktif Dibuat"
        :value="store.items.length"
        :icon="Mail"
        illustration="paper_sheet"
        variant="primary"
      />
    </div>

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
        delete-label="nama"
      >
        <template #cell-nama="{ item }">
          <div class="flex flex-col text-left">
            <span class="font-medium text-foreground">{{ item.nama }}</span>
            <span class="text-[10px] text-muted-foreground">{{ item.nisn }}</span>
          </div>
        </template>
      </DataTableCard>
    </div>
    
    <!-- Form Sheet (Inline Create/Edit) -->
    <Sheet v-model:open="isFormSheetOpen">
      <SheetContent :show-close-button="false" class="sm:max-w-[500px] flex flex-col h-full gap-2">
        <SheetHeader class="border-b border-border pb-3 text-left">
          <SheetTitle class="text-base font-bold text-foreground">
            {{ isEditMode ? 'Edit Surat Keterangan Aktif' : 'Buat Surat Keterangan Aktif' }}
          </SheetTitle>
          <SheetDescription class="text-xs text-muted-foreground">
            {{ isEditMode ? 'Perbarui informasi surat keterangan siswa aktif.' : 'Lengkapi formulir di bawah ini untuk menerbitkan surat aktif baru.' }}
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
                  <!-- Nama Siswa (Searchable Combobox) -->
                  <div class="space-y-1.5 flex flex-col">
                    <label class="text-xs font-semibold text-muted-foreground text-left">Nama Siswa</label>
                    <Popover v-model:open="isSiswaPopoverOpen">
                      <PopoverTrigger as-child>
                        <Button
                          variant="outline"
                          role="combobox"
                          :aria-expanded="isSiswaPopoverOpen"
                          class="h-10 w-full justify-between rounded-xl px-3 font-normal text-muted-foreground hover:bg-background/50 border-input shadow-none"
                          :disabled="isLoadingSiswa"
                        >
                          <span v-if="formItem.nama" class="text-foreground font-medium">
                            {{ formItem.nama }} - {{ formItem.nisn }}
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

                  <!-- Tahun Akademik & Semester -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-muted-foreground">Tahun Akademik</label>
                      <Select v-model="formItem.academicYearId">
                        <SelectTrigger class="h-10 w-full rounded-xl">
                          <SelectValue placeholder="Pilih Tahun Akademik" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem 
                            v-for="year in academicYears" 
                            :key="year.id" 
                            :value="year.id.toString()"
                          >
                            {{ year.name }} ({{ year.semester === 'even' ? 'Genap' : 'Ganjil' }})
                          </SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-muted-foreground">Semester</label>
                      <Select v-model="formItem.semester">
                        <SelectTrigger class="h-10 w-full rounded-xl">
                          <SelectValue placeholder="Pilih Semester" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="Ganjil">Ganjil</SelectItem>
                          <SelectItem value="Genap">Genap</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>

                  <!-- Kelas & Tanggal Lahir -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-muted-foreground">Kelas</label>
                      <Input v-model="formItem.kelas" placeholder="Misal: VI A" class="h-10 rounded-xl" />
                    </div>

                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-muted-foreground">Tanggal Lahir</label>
                      <Input type="date" v-model="formItem.tanggalLahir" class="h-10 rounded-xl" />
                    </div>
                  </div>

                  <!-- Alamat -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Alamat</label>
                    <Textarea v-model="formItem.alamat" placeholder="Masukkan alamat lengkap siswa..." class="min-h-24 rounded-xl resize-none" />
                  </div>
                </div>
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </div>

        <FormFooter
          :is-edit="isEditMode"
          entity-name="Surat Keterangan Aktif"
          @save="handleSave"
        />
      </SheetContent>
    </Sheet>

    <!-- Print Preview Modal -->
    <SuratAktifPrintModal v-model:open="isPrintModalOpen" :data="selectedSurat" />
  </div>
</template>
