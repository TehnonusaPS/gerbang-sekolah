<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import { Plus, Mail } from 'lucide-vue-next'
import { useSuratLulusStore } from '@/stores/suratLulusStore'
import { usePagination } from '@/composables/usePagination'
import { toast } from 'vue-sonner'
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import SuratLulusPrintModal from '../../../components/SuratLulusPrintModal.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import StatCard from '@/components/stat-card/StatCard.vue'
import { glassSlide, glassFade } from '@/config/motion'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription
} from '@/components/ui/sheet'
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger
} from '@/components/ui/accordion'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { fetchAllSiswa } from '@/services/siswaService'

const store = useSuratLulusStore()

const isPrintModalOpen = ref(false)
const selectedSurat = ref(null)

// --- Inline Form State ---
const isFormSheetOpen = ref(false)
const isEditMode = ref(false)
const isLoadingSiswa = ref(false)
const siswaList = ref([])

const formItem = ref({
  id: '',
  nama: '',
  nisn: '',
  nis: '',
  tempatLahir: '',
  tanggalLahir: '',
  namaOrangTua: ''
})

onMounted(async () => {
  isLoadingSiswa.value = true
  try {
    const data = await fetchAllSiswa()
    siswaList.value = data.map(user => {
      const randomCities = [
        'Jakarta',
        'Bandung',
        'Surabaya',
        'Medan',
        'Semarang',
        'Yogyakarta',
        'Makassar',
        'Denpasar'
      ]
      const city = randomCities[user.id % randomCities.length]

      const year = 2013 + (user.id % 3)
      const month = String(1 + (user.id % 12)).padStart(2, '0')
      const day = String(10 + (user.id % 18)).padStart(2, '0')

      return {
        ...user,
        nisn:
          '00' + user.id.toString().padStart(3, '0') + Math.floor(10000 + Math.random() * 90000),
        nis: '20230' + user.id.toString().padStart(3, '0'),
        tempatLahir: city,
        tanggalLahir: `${year}-${month}-${day}`
      }
    })
  } catch (error) {
    toast.error('Gagal memuat data siswa.')
  } finally {
    isLoadingSiswa.value = false
  }
})

import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
const computedSiswaList = computed(() => {
  const list = [...siswaList.value]
  if (formItem.value.nama && !list.some(s => s.name === formItem.value.nama)) {
    list.push({
      id: -1,
      name: formItem.value.nama,
      nisn: formItem.value.nisn || '',
      nis: formItem.value.nis || '',
      tempatLahir: formItem.value.tempatLahir || '',
      tanggalLahir: formItem.value.tanggalLahir || ''
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
    formItem.value.nis = siswa.nis
    formItem.value.tempatLahir = siswa.tempatLahir
    formItem.value.tanggalLahir = siswa.tanggalLahir
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

const {
  currentPage,
  total,
  from,
  to,
  paginatedItems: paginatedSuratList
} = usePagination(suratList, itemsPerPage)

watch(suratList, () => {
  currentPage.value = 1
})

function openCreateForm() {
  isEditMode.value = false
  formItem.value = {
    id: '',
    nama: '',
    nisn: '',
    nis: '',
    tempatLahir: '',
    tanggalLahir: '',
    namaOrangTua: ''
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
  formItem.value = {
    id: resolvedItem.id,
    nama: resolvedItem.nama,
    nisn: resolvedItem.nisn,
    nis: resolvedItem.nis,
    tempatLahir: resolvedItem.tempatLahir,
    tanggalLahir: resolvedItem.tanggalLahir,
    namaOrangTua: resolvedItem.namaOrangTua
  }
  isFormSheetOpen.value = true
}

function deleteSurat(id, item) {
  store.remove(id)
  toast.success('Surat keterangan lulus berhasil dihapus!')
}

function handleSave() {
  if (
    !formItem.value.nama ||
    !formItem.value.namaOrangTua ||
    !formItem.value.tempatLahir ||
    !formItem.value.tanggalLahir
  ) {
    toast.error('Mohon lengkapi seluruh kolom formulir.')
    return
  }

  try {
    if (isEditMode.value) {
      store.update(formItem.value.id, { ...formItem.value })
      toast.success('Surat Keterangan Lulus berhasil diperbarui!')
    } else {
      store.add({ ...formItem.value })
      toast.success('Surat Keterangan Lulus berhasil dibuat!')
    }
    isFormSheetOpen.value = false
  } catch (error) {
    toast.error('Gagal menyimpan data surat.')
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
      title="Daftar Surat Keterangan Lulus Siswa"
      description="Kelola Surat Keterangan Lulus Siswa"
      :actions="[
        {
          label: 'Buat Surat Keterangan Lulus',
          icon: Plus,
          variant: 'default',
          click: openCreateForm
        }
      ]"
    />

    <div
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{
        ...glassSlide.visible,
        transition: { ...glassSlide.visible.transition, delay: 100 }
      }"
    >
      <StatCard
        label="Total Surat Keterangan Lulus Siswa Dibuat"
        :value="store.items.length"
        :icon="Mail"
        illustration="graduation_cap"
        variant="primary"
      />
    </div>

    <!-- Filters & Table using DataTableCard -->
    <div
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{
        ...glassSlide.visible,
        transition: { ...glassSlide.visible.transition, delay: 200 }
      }"
    >
      <DataTableCard
        :columns="columns"
        :items="paginatedSuratList"
        :filters="filters"
        v-model:filterValues="filterValues"
        illustration="graduation_cap"
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
      <SheetContent
        :show-close-button="false"
        class="sm:max-w-[500px] flex flex-col h-full gap-2"
      >
        <SheetHeader class="border-b border-border pb-3 text-left">
          <SheetTitle class="text-base font-bold text-foreground">
            {{ isEditMode ? 'Edit Surat Keterangan Lulus' : 'Buat Surat Keterangan Lulus' }}
          </SheetTitle>
          <SheetDescription class="text-xs text-muted-foreground">
            {{
              isEditMode
                ? 'Perbarui informasi surat keterangan lulus siswa.'
                : 'Lengkapi formulir di bawah ini untuk menerbitkan surat keterangan lulus baru.'
            }}
          </SheetDescription>
        </SheetHeader>

        <div class="flex-1 overflow-y-auto py-6 pr-1 space-y-6 no-scrollbar">
          <Accordion
            type="multiple"
            class="w-full"
            :default-value="['info']"
          >
            <AccordionItem value="info">
              <AccordionTrigger class="text-sm font-semibold"> Informasi Umum </AccordionTrigger>
              <AccordionContent class="space-y-4 pt-3 text-left">
                <div class="space-y-4">
                  <!-- Nama Siswa -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground">Nama Siswa</label>
                    <Select
                      :modelValue="selectedSiswaId"
                      @update:modelValue="onSiswaSelected"
                      :disabled="isLoadingSiswa"
                    >
                      <SelectTrigger class="h-10 rounded-xl">
                        <SelectValue
                          :placeholder="isLoadingSiswa ? 'Memuat data siswa...' : 'Pilih Siswa...'"
                        />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="siswa in computedSiswaList"
                          :key="siswa.id"
                          :value="siswa.id.toString()"
                        >
                          {{ siswa.name }} - {{ siswa.nisn }}
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>

                  <!-- Nama Orang Tua -->
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-muted-foreground"
                      >Nama Orang Tua</label
                    >
                    <Input
                      v-model="formItem.namaOrangTua"
                      placeholder="Masukkan nama orang tua / wali..."
                      class="h-10 rounded-xl"
                    />
                  </div>

                  <!-- Tempat & Tanggal Lahir -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-muted-foreground"
                        >Tempat Lahir Siswa</label
                      >
                      <Input
                        v-model="formItem.tempatLahir"
                        placeholder="Masukkan tempat lahir..."
                        class="h-10 rounded-xl"
                      />
                    </div>

                    <div class="space-y-1.5">
                      <label class="text-xs font-semibold text-muted-foreground"
                        >Tanggal Lahir Siswa</label
                      >
                      <Input
                        type="date"
                        v-model="formItem.tanggalLahir"
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
          entity-name="Surat Keterangan Lulus"
          @save="handleSave"
        />
      </SheetContent>
    </Sheet>

    <!-- Print Preview Modal -->
    <SuratLulusPrintModal
      v-model:open="isPrintModalOpen"
      :data="selectedSurat"
    />
  </div>
</template>
