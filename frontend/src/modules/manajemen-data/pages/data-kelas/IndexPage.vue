<script setup>
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'

import PageHeader from '@/components/page-header/PageHeader.vue'
import DataSheet from '@/components/data-sheet/DataSheet.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'

import KelasStatCards from './components/KelasStatCards.vue'
import KelasTable from './components/KelasTable.vue'
import KelasForm from './components/KelasForm.vue'
import router from '@/router/index.js'
import { getClassrooms, createClassroom, updateClassroom, deleteClassroom, getTeachers } from '@/services/managementService'

import {
  Eye,
  Pencil,
  Trash2,
  Users
} from 'lucide-vue-next'

const kelasList = ref([])
const isLoading = ref(false)

const fetchClassrooms = async () => {
  isLoading.value = true
  try {
    const res = await getClassrooms()
    kelasList.value = res.data
  } catch (err) {
    toast.error('Gagal memuat data kelas')
  } finally {
    isLoading.value = false
  }
}

const teacherOptions = ref([])

const fetchTeachers = async () => {
  try {
    const res = await getTeachers()
    const teachers = res.data?.data || res.data || []
    
    teacherOptions.value = teachers.map((teacher) => {
      const teacherId =
        teacher.id

      const teacherName =
        teacher.nama

      return {
        label: teacherName,
        value: String(teacherId)
      }
    })
  } catch (err) {
    console.error('Gagal memuat guru:', err)
    teacherOptions.value = []
    toast.error('Gagal memuat pilihan wali kelas')
  }
}

onMounted(() => {
  fetchClassrooms()
  fetchTeachers()
})

// --- Form Sheet State & Methods ---
const isFormSheetOpen = ref(false)
const isEditMode = ref(false)
const formErrors = ref({})
const formItem = ref({
  id: '',
  name: '',
  grade: '',
  major: '',
  homeroom_teacher: '',
  homeroom_teacher_id: '',
  room: '',
  capacity: 36,
  students_count: 0,
  status: 'active'
})

const handleCreate = () => {
  isEditMode.value = false
  formErrors.value = {}
  formItem.value = {
    id: '',
    name: '',
    grade: '',
    major: '',
    homeroom_teacher: '',
    homeroom_teacher_id: '',
    room: '',
    capacity: 36,
    students_count: 0,
    status: 'active'
  }
  isFormSheetOpen.value = true
}

const handleEdit = (item) => {
  isEditMode.value = true
  formErrors.value = {}
  formItem.value = {
    ...item,
    grade: item.grade != null ? String(item.grade) : '',
    homeroom_teacher_id: item.homeroom_teacher_id != null
      ? String(item.homeroom_teacher_id)
      : ''
  }
  isFormSheetOpen.value = true
}

const validateForm = () => {
  const errors = {}
  if (!formItem.value.name?.trim()) errors.name = 'Nama kelas wajib diisi.'
  if (!formItem.value.grade) errors.grade = 'Tingkat wajib dipilih.'
  if (!formItem.value.capacity) errors.capacity = 'Kapasitas wajib diisi.'
  if (!formItem.value.status) errors.status = 'Status wajib dipilih.'
  
  formErrors.value = errors
  if (Object.keys(errors).length > 0) {
    toast.error('Gagal Menyimpan', { description: 'Harap lengkapi semua field yang wajib diisi.' })
    return false
  }
  return true
}

const isSaving = ref(false)
const saveSummary = computed(() => [
  { label: 'Nama Kelas', value: formItem.value.name },
  { label: 'Tingkat', value: formItem.value.grade ? `Kelas ${formItem.value.grade}` : '-' },
  { label: 'Kapasitas', value: formItem.value.capacity ? `${formItem.value.capacity} Siswa` : '-' }
])

const handleSave = async () => {
  const data = { ...formItem.value }
  isSaving.value = true

  try {
    if (isEditMode.value) {
      await updateClassroom(data.id, data)
      toast.success('Berhasil Diperbarui', { description: `Data kelas "${data.name}" telah diperbarui.` })
    } else {
      await createClassroom(data)
      toast.success('Berhasil Ditambahkan', { description: `Kelas "${data.name}" telah ditambahkan.` })
    }
    fetchClassrooms()
    isFormSheetOpen.value = false
  } catch (err) {
    toast.error('Gagal menyimpan kelas')
  } finally {
    isSaving.value = false
  }
}

// --- Detail Sheet State & Methods ---
const isDetailSheetOpen = ref(false)
const selectedItemForDetail = ref(null)

const handleView = (id) => {
  const item = kelasList.value.find(k => k.id === id)
  if (item) {
    selectedItemForDetail.value = item
    isDetailSheetOpen.value = true
  }
}

const rawDetailItem = computed(() => {
  if (!selectedItemForDetail.value) return {}
  return {
    name: selectedItemForDetail.value.name,
    status: selectedItemForDetail.value.status
  }
})

const getStatusLabel = (status) => {
  if (status === 'active') return 'Aktif'
  if (status === 'full') return 'Penuh'
  if (status === 'no_teacher') return 'Tanpa Wali'
  return status
}

const detailSections = computed(() => {
  if (!selectedItemForDetail.value) return []
  const item = selectedItemForDetail.value
  return [
    {
      id: 'info',
      title: 'Informasi Kelas',
      fields: [
        { label: 'Nama Kelas', value: item.name },
        { label: 'Tingkat', value: `Kelas ${item.grade}` },
        { label: 'Jurusan / Program', value: item.major || '-' },
        { label: 'Wali Kelas', value: item.homeroom_teacher || 'Belum ada' },
        { label: 'Ruangan', value: item.room || '-' },
        { label: 'Kapasitas', value: `${item.capacity} Siswa` },
        { label: 'Terisi', value: `${item.students_count} Siswa` },
        { label: 'Status', value: getStatusLabel(item.status) }
      ]
    }
  ]
})

// --- Delete ---
async function handleDelete(id) {
  try {
    await deleteClassroom(id)
    toast.success('Data kelas berhasil dihapus!')
    fetchClassrooms()
  } catch (err) {
    toast.error('Gagal menghapus kelas')
  }
}

const rowActions = [
  {
    label: 'Detail',
    icon: Eye,
    click: item => handleView(item.id)
  },
  {
    label: 'Edit',
    icon: Pencil,
    click: item => handleEdit(item)
  },
  {
    label: 'Kelola',
    icon: Users,
    click: item => router.push(`/manajemen-data/kelas/detail/${item.id}`)
  },
  {
    label: 'Hapus',
    icon: Trash2
  }
]
</script>

<template>
  <div class="space-y-6">
    <PageHeader 
      title="Data Kelas" 
      description="Kelola informasi kelas, wali kelas, dan kapasitas siswa" 
    />

    <!-- Stats Cards -->
    <KelasStatCards :items="kelasList" />

    <!-- Data Table & Filters -->
    <KelasTable 
      :items="kelasList"
      :loading="isLoading"
      @create="handleCreate"
      @delete="handleDelete"
      :rowActions="rowActions"
    />

    <!-- Detail Sheet -->
    <DataSheet
      v-model:open="isDetailSheetOpen"
      :item="rawDetailItem"
      title-key="name"
      :badge="getStatusLabel(rawDetailItem.status)"
      :badge-variant="rawDetailItem.status === 'active' ? 'green' : (rawDetailItem.status === 'full' ? 'amber' : 'gray')"
      :sections="detailSections"
    />

    <!-- Form Sheet (Create / Edit) -->
    <Sheet v-model:open="isFormSheetOpen">
      <SheetContent class="sm:max-w-[500px] flex flex-col h-full gap-2">
        <SheetHeader class="border-b border-border pb-3 text-left">
          <SheetTitle class="text-base font-bold text-foreground">
            {{ isEditMode ? 'Edit Kelas' : 'Tambah Kelas' }}
          </SheetTitle>
          <SheetDescription class="text-xs text-muted-foreground">
            {{ isEditMode ? 'Perbarui informasi kelas yang sudah ada.' : 'Tambahkan kelas baru ke dalam sistem.' }}
          </SheetDescription>
        </SheetHeader>

        <div class="flex-1 overflow-y-auto py-6 pr-1 space-y-6 no-scrollbar">
          <KelasForm :form="formItem" :errors="formErrors" :teacher-options="teacherOptions"/>
        </div>

        <FormFooter
          :loading="isSaving"
          :is-edit="isEditMode"
          entity-name="Kelas"
          :summary-items="saveSummary"
          :before-confirm="validateForm"
          @save="handleSave"
        />
      </SheetContent>
    </Sheet>

  </div>
</template>