<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { Plus, Mail, Trash2, Check, ChevronsUpDown } from 'lucide-vue-next'
import { useSuratDispensasiStore } from '@/stores/suratDispensasiStore'
import { useAuthStore } from '@/stores/authStore'
import { echo } from '@/services/echoService'
import { usePagination } from '@/composables/usePagination'
import { toast } from 'vue-sonner'
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import SuratDispensasiPrintModal from '../../../components/SuratDispensasiPrintModal.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import StatCard from '@/components/stat-card/StatCard.vue'
import { glassSlide, glassFade } from '@/config/motion'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command'
import { fetchAllSiswa } from '@/services/siswaService'

const store = useSuratDispensasiStore()
const auth = useAuthStore()

const isPrintModalOpen = ref(false)
const selectedSurat = ref(null)

// --- Inline Form State ---
const isFormSheetOpen = ref(false)
const isEditMode = ref(false)
const isLoadingSiswa = ref(false)
const siswaOptions = ref([])

const selectedSiswa = ref([
  { id: Date.now().toString(), siswaId: null, nama: '', nisn: '', kelas: '', isPopoverOpen: false }
])

const formItem = ref({
  id: '',
  tanggalAwal: '',
  tanggalAkhir: '',
  perihal: ''
})

onMounted(async () => {
  isLoadingSiswa.value = true
  try {
    // Load real dispensations from backend
    await store.fetchItems()

    // Fetch and map active student database list correctly
    const resSiswa = await fetchAllSiswa()
    siswaOptions.value = (resSiswa.data || []).map(student => ({
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
      .listen('StudentDispensationCertificateCreated', (event) => {
        if (!store.items.some(item => item.id === event.id)) {
          store.items.unshift(event)
          toast.info('Surat Dispensasi Baru Dibuat!', {
            description: `Surat dispensasi baru telah diterbitkan.`,
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

function addSiswaField() {
  selectedSiswa.value.push({ 
    id: Date.now().toString() + Math.random(), 
    siswaId: null, 
    nama: '', 
    nisn: '', 
    kelas: '',
    isPopoverOpen: false
  })
}

function removeSiswaField(index) {
  if (selectedSiswa.value.length > 1) {
    selectedSiswa.value.splice(index, 1)
  }
}

function onSiswaSelected(index, siswaId) {
  const siswa = siswaOptions.value.find(s => s.id === parseInt(siswaId))
  if (siswa) {
    selectedSiswa.value[index].siswaId = siswa.id
    selectedSiswa.value[index].nama = siswa.name
    selectedSiswa.value[index].nisn = siswa.nisn
    selectedSiswa.value[index].kelas = siswa.kelas
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
  { key: 'siswa', label: 'Nama Siswa' },
  { key: 'tanggalDibuat', label: 'Tanggal Dibuat', type: 'date' },
  { key: 'actions', label: 'Aksi' }
])

const itemsPerPage = ref(5)

const suratList = computed(() => {
  let list = store.items
  
  if (filterValues.value.search) {
    const query = filterValues.value.search.toLowerCase()
    list = list.filter(item => {
      return item.siswa.some(s => s.nama.toLowerCase().includes(query))
    })
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
    tanggalAwal: '',
    tanggalAkhir: '',
    perihal: ''
  }
  selectedSiswa.value = [
    { id: Date.now().toString(), siswaId: null, nama: '', nisn: '', kelas: '', isPopoverOpen: false }
  ]
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
  formItem.value = {
    id: resolvedItem.id,
    tanggalAwal: resolvedItem.tanggalAwal,
    tanggalAkhir: resolvedItem.tanggalAkhir,
    perihal: resolvedItem.perihal
  }
  
  if (resolvedItem.siswa && resolvedItem.siswa.length > 0) {
    selectedSiswa.value = resolvedItem.siswa.map(s => {
      // Find matching siswa in options to set selected ID and load current database details
      const opt = siswaOptions.value.find(o => o.name === s.nama || o.nisn === s.nisn)
      return {
        id: String(Math.random()),
        siswaId: opt ? opt.id : null,
        nama: opt ? opt.name : s.nama,
        nisn: opt ? opt.nisn : s.nisn,
        kelas: opt ? opt.kelas : s.kelas,
        isPopoverOpen: false
      }
    })
  } else {
    selectedSiswa.value = [
      { id: Date.now().toString(), siswaId: null, nama: '', nisn: '', kelas: '', isPopoverOpen: false }
    ]
  }
  isFormSheetOpen.value = true
}

async function deleteSurat(id) {
  try {
    await store.remove(id)
    toast.success('Surat dispensasi berhasil dihapus!')
  } catch (error) {
    toast.error('Gagal menghapus surat dispensasi.')
  }
}

function formatNamaSiswa(siswaList) {
  if (!siswaList || siswaList.length === 0) return '-'
  const first = siswaList[0].nama
  if (siswaList.length > 1) {
    return `${first} & ${siswaList.length - 1} lainnya`
  }
  return first
}

function formatNisnSiswa(siswaList) {
  if (!siswaList || siswaList.length === 0) return '-'
  const first = siswaList[0].nisn
  if (siswaList.length > 1) {
    return `${first} (+${siswaList.length - 1})`
  }
  return first
}

async function handleSave() {
  const validSiswa = selectedSiswa.value.filter(s => s.siswaId !== null)
  if (validSiswa.length === 0) {
    toast.error('Pilih minimal 1 siswa.')
    return
  }
  if (!formItem.value.tanggalAwal || !formItem.value.tanggalAkhir || !formItem.value.perihal) {
    toast.error('Mohon lengkapi Tanggal Izin dan Perihal.')
    return
  }

  const payload = {
    tanggalAwal: formItem.value.tanggalAwal,
    tanggalAkhir: formItem.value.tanggalAkhir,
    perihal: formItem.value.perihal,
    siswa: validSiswa.map(s => ({
      student_id: s.siswaId,
      nama: s.nama,
      nisn: s.nisn,
      kelas: s.kelas
    }))
  }

  try {
    if (isEditMode.value) {
      await store.update(formItem.value.id, payload)
      toast.success('Surat dispensasi berhasil diperbarui!')
    } else {
      await store.add(payload)
      toast.success('Surat dispensasi berhasil dibuat!')
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
      title="Daftar Surat Dispensasi Siswa"
      description="Kelola Surat Dispensasi"
      :actions="[
        {
          label: 'Buat Surat Dispensasi Siswa',
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
        label="Total Surat Dispensasi Siswa Dibuat"
        :value="store.items.length"
        :icon="Mail"
        illustration="paper_plane"
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
        illustration="paper_plane"
        v-model:perPage="itemsPerPage"
        :from="from"
        :to="to"
        :total="total"
        :page="currentPage"
        @update:page="currentPage = $event"
        @view="viewSurat"
        @edit="editSurat"
        @delete="deleteSurat"
        :delete-label="(item) => formatNamaSiswa(item.siswa)"
      >
        <template #cell-siswa="{ item }">
          <div class="flex flex-col text-left">
            <span class="font-medium text-foreground">{{ formatNamaSiswa(item.siswa) }}</span>
            <span class="text-[10px] text-muted-foreground">{{ formatNisnSiswa(item.siswa) }}</span>
          </div>
        </template>
      </DataTableCard>
    </div>
    
    <!-- Form Sheet (Inline Create/Edit) -->
    <Sheet v-model:open="isFormSheetOpen">
      <SheetContent :show-close-button="false" class="sm:max-w-[500px] flex flex-col h-full gap-2">
        <SheetHeader class="border-b border-border pb-3 text-left">
          <SheetTitle class="text-base font-bold text-foreground">
            {{ isEditMode ? 'Edit Surat Dispensasi' : 'Buat Surat Dispensasi' }}
          </SheetTitle>
          <SheetDescription class="text-xs text-muted-foreground">
            {{ isEditMode ? 'Perbarui informasi surat dispensasi siswa.' : 'Lengkapi formulir di bawah ini untuk menerbitkan surat dispensasi baru.' }}
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
                  <!-- Siswa List Selection -->
                  <div class="space-y-3">
                    <label class="text-xs font-semibold text-muted-foreground">Siswa Terpilih</label>
                    <div class="space-y-3">
                      <div v-for="(item, index) in selectedSiswa" :key="item.id" class="flex items-center gap-2 w-full">
                        <div class="flex-1">
                          <Popover v-model:open="item.isPopoverOpen">
                            <PopoverTrigger as-child>
                              <Button
                                variant="outline"
                                role="combobox"
                                :aria-expanded="item.isPopoverOpen"
                                class="h-10 w-full justify-between rounded-xl px-3 font-normal text-muted-foreground hover:bg-background/50 border-input shadow-none"
                                :disabled="isLoadingSiswa"
                              >
                                <span v-if="item.nama" class="text-foreground font-medium">
                                  {{ item.nama }} - {{ item.nisn }}
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
                                      v-for="siswa in siswaOptions"
                                      :key="siswa.id"
                                      :value="`${siswa.name} ${siswa.nisn} ${siswa.kelas}`"
                                      @select="() => {
                                        onSiswaSelected(index, siswa.id.toString())
                                        item.isPopoverOpen = false
                                      }"
                                      class="flex items-center justify-between cursor-pointer py-2 px-3 text-xs"
                                    >
                                      <div class="flex flex-col text-left">
                                        <span class="font-semibold text-foreground text-sm">{{ siswa.name }}</span>
                                        <span class="text-[10px] text-muted-foreground">NISN: {{ siswa.nisn }} | Kelas: {{ siswa.kelas || '-' }}</span>
                                      </div>
                                      <Check
                                        v-if="item.siswaId === siswa.id"
                                        class="h-4 w-4 text-primary shrink-0 ml-2"
                                      />
                                    </CommandItem>
                                  </CommandGroup>
                                </CommandList>
                              </Command>
                            </PopoverContent>
                          </Popover>
                        </div>
                        
                        <Button 
                          v-if="selectedSiswa.length > 1" 
                          variant="ghost" 
                          size="xs" 
                          class="h-10 text-destructive hover:bg-destructive/10 rounded-xl px-2.5"
                          @click="removeSiswaField(index)"
                        >
                          <Trash2 class="size-4" />
                        </Button>
                      </div>

                      <Button 
                        type="button" 
                        variant="outline" 
                        size="xs"
                        class="h-9 w-full flex items-center gap-1.5 bg-muted/20 border-border rounded-xl text-[11px]"
                        @click="addSiswaField"
                      >
                        <Plus class="size-3.5" />
                        Tambah Siswa Lainnya
                      </Button>
                    </div>
                  </div>

                  <!-- Tanggal Awal & Akhir -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-muted-foreground">Tanggal Izin Awal</label>
                      <Input type="date" v-model="formItem.tanggalAwal" class="h-10 rounded-xl" />
                    </div>

                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-muted-foreground">Tanggal Izin Akhir</label>
                      <Input type="date" v-model="formItem.tanggalAkhir" class="h-10 rounded-xl" />
                    </div>
                  </div>

                  <!-- Perihal -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Perihal Izin</label>
                    <Textarea v-model="formItem.perihal" placeholder="Masukkan keterangan perihal izin dispensasi..." class="min-h-24 rounded-xl resize-none" />
                  </div>
                </div>
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </div>

        <FormFooter
          :is-edit="isEditMode"
          entity-name="Surat Dispensasi"
          @save="handleSave"
        />
      </SheetContent>
    </Sheet>

    <!-- Print Preview Modal -->
    <SuratDispensasiPrintModal v-model:open="isPrintModalOpen" :data="selectedSurat" />
  </div>
</template>
