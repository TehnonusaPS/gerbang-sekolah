<script setup>
import { ref, computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'

import PageHeader from '@/components/page-header/PageHeader.vue'
import DataSheet from '@/components/data-sheet/DataSheet.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogCancel,
  AlertDialogAction
} from '@/components/ui/alert-dialog'

import KelasStatCards from './components/KelasStatCards.vue'
import KelasTable from './components/KelasTable.vue'
import KelasForm from './components/KelasForm.vue'
import router from '@/router/index.js'
import { getClassrooms, createClassroom, updateClassroom, deleteClassroom, getTeachers } from '@/services/managementService'
import { fetchAllAcademicYears } from '@/services/academicYearService'
import { useZodForm } from '@/composables/useZodForm'
import { kelasSchema } from './data/kelasSchema'

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

    teacherOptions.value = teachers.map(teacher => ({
      label: teacher.nama,
      value: String(teacher.id)
    }))
  } catch (err) {
    console.error('Gagal memuat guru:', err)
    teacherOptions.value = []
    toast.error('Gagal memuat pilihan wali kelas')
  }
}

// --- Academic Years ---
const rawAcademicYears = ref([])

const fetchAcademicYears = async () => {
  try {
    const res = await fetchAllAcademicYears()
    rawAcademicYears.value = res.data || []
  } catch (err) {
    console.error('Gagal memuat tahun ajaran:', err)
  }
}

const academicYearOptions = computed(() => {
  const map = new Map()
  rawAcademicYears.value.forEach(item => {
    if (!map.has(item.name) || item.is_active) {
      map.set(item.name, {
        name: item.name,
        label: `${item.name}${item.is_active ? ' (Aktif)' : ''}`,
        value: String(item.id),
        isActive: Boolean(item.is_active)
      })
    }
  })
  return Array.from(map.values())
})

const defaultAcademicYearId = computed(() => {
  const active = academicYearOptions.value.find(o => o.isActive)
  if (active) return active.value
  return academicYearOptions.value[0]?.value || ''
})

onMounted(() => {
  fetchClassrooms()
  fetchTeachers()
  fetchAcademicYears()
})

// --- Form Sheet State & Methods ---
const isFormSheetOpen = ref(false)
const isEditMode = ref(false)
const formItem = ref({
  id: '',
  academic_year_id: '',
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

const {
  errors: formErrors,
  validate: validateForm,
  clearErrors
} = useZodForm(kelasSchema, formItem)

const handleCreate = () => {
  isEditMode.value = false
  clearErrors()
  formItem.value = {
    id: '',
    academic_year_id: defaultAcademicYearId.value,
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
  clearErrors()
  formItem.value = {
    ...item,
    academic_year_id: item.academic_year_id ? String(item.academic_year_id) : (defaultAcademicYearId.value || ''),
    grade: item.grade != null ? String(item.grade) : '',
    homeroom_teacher_id: item.homeroom_teacher_id != null
      ? String(item.homeroom_teacher_id)
      : ''
  }
  isFormSheetOpen.value = true
}

const isSaving = ref(false)
const selectedYearLabel = computed(() => {
  const found = academicYearOptions.value.find(o => String(o.value) === String(formItem.value.academic_year_id))
  return found ? found.label : '-'
})

const saveSummary = computed(() => [
  { label: 'Tahun Ajaran', value: selectedYearLabel.value },
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
    toast.error(err.response?.data?.message || 'Gagal menyimpan kelas')
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
        { label: 'Tahun Ajaran', value: item.academic_year_name || '-' },
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

// --- Delete State & Methods ---
const isDeleteDialogOpen = ref(false)
const itemToDelete = ref(null)
const isDeleting = ref(false)

const requestDelete = (item) => {
  itemToDelete.value = item
  isDeleteDialogOpen.value = true
}

const confirmDelete = async () => {
  if (!itemToDelete.value) return
  isDeleting.value = true
  try {
    await deleteClassroom(itemToDelete.value.id)
    toast.success('Berhasil Dihapus', {
      description: `Data kelas "${itemToDelete.value.name}" berhasil dihapus.`
    })
    isDeleteDialogOpen.value = false
    itemToDelete.value = null
    await fetchClassrooms()
  } catch (err) {
    toast.error('Gagal menghapus kelas', {
      description: err.response?.data?.message || err.message
    })
  } finally {
    isDeleting.value = false
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
    icon: Trash2,
    variant: 'destructive',
    click: item => requestDelete(item)
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
      :academic-year-options="academicYearOptions"
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
          <KelasForm
            :form="formItem"
            :errors="formErrors"
            :teacher-options="teacherOptions"
            :academic-year-options="academicYearOptions"
          />
        </div>

        <FormFooter
          :loading="isSaving"
          :is-edit="isEditMode"
          entity-name="Kelas"
          :summary-items="saveSummary"
          :before-confirm="validateForm"
          @submit="handleSave"
        />
      </SheetContent>
    </Sheet>

    <!-- Delete Confirmation Dialog -->
    <AlertDialog :open="isDeleteDialogOpen" @update:open="isDeleteDialogOpen = $event">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Hapus Data Kelas?</AlertDialogTitle>
          <AlertDialogDescription>
            Apakah Anda yakin ingin menghapus kelas <span class="font-bold text-foreground">"{{ itemToDelete?.name }}"</span>? Tindakan ini tidak dapat dibatalkan.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel :disabled="isDeleting" @click="isDeleteDialogOpen = false">
            Batal
          </AlertDialogCancel>
          <AlertDialogAction
            :disabled="isDeleting"
            class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            @click="confirmDelete"
          >
            {{ isDeleting ? 'Menghapus...' : 'Ya, Hapus Kelas' }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

  </div>
</template>