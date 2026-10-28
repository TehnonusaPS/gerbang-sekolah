<script setup>
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { Plus } from 'lucide-vue-next'
import { columns, filters, guruOptions } from './data/siswaTersedia'
import { usePagination } from '@/composables/usePagination'
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { toast } from 'vue-sonner'
import { getClassroom, assignClassroomStudents, getTeacher } from '@/services/managementService'
import { fetchAllSiswa } from '@/services/siswaService'
import { getPhotoUrl } from '@/utils/getPhotoUrl'
import Card from '@/components/ui/card/Card.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardFooter from '@/components/ui/card/CardFooter.vue'
import Button from '@/components/ui/button/Button.vue'
import Separator from '@/components/ui/separator/Separator.vue'
import Avatar from '@/components/ui/avatar/Avatar.vue'
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue'
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue'
import FormSelect from '@/components/forms/FormSelect.vue'
import Progress from '@/components/ui/progress/Progress.vue'
import FormSheet from '@/components/data-sheet/FormSheet.vue'
import AlertDialog from '@/components/ui/alert-dialog/AlertDialog.vue'
import AlertDialogContent from '@/components/ui/alert-dialog/AlertDialogContent.vue'
import AlertDialogHeader from '@/components/ui/alert-dialog/AlertDialogHeader.vue'
import AlertDialogTitle from '@/components/ui/alert-dialog/AlertDialogTitle.vue'
import AlertDialogDescription from '@/components/ui/alert-dialog/AlertDialogDescription.vue'
import AlertDialogFooter from '@/components/ui/alert-dialog/AlertDialogFooter.vue'
import AlertDialogCancel from '@/components/ui/alert-dialog/AlertDialogCancel.vue'
import AlertDialogAction from '@/components/ui/alert-dialog/AlertDialogAction.vue'

const route = useRoute()
const classId = computed(() => route.params.id)

const classroom = ref(null)
const isLoading = ref(false)
const isPlacing = ref(false)

const perPage = ref(10)
const tableItems = ref([])
const selectedStudents = ref([])
const selectedClass = ref('')

const filterValues = ref({
  search: '',
  status: 'all'
})

const isOpenWaliKelas = ref(false)
const selectedItem = ref(null)
const isSaving = ref(false)

const openChangeWaliKelas = () => {
  selectedItem.value = {
    kelasId: classId.value,
    kelas: classroom.value?.name || '',
    waliKelasId: classroom.value?.homeroom_teacher_id || ''
  }

  isOpenWaliKelas.value = true
}

const filteredItems = computed(() => {
  return tableItems.value.filter(item => {
    const searchVal = filterValues.value.search?.trim().toLowerCase() || ''
    const searchMatch = !searchVal || item.nama.toLowerCase().includes(searchVal)

    const statusVal = filterValues.value.status
    const statusMatch = !statusVal || statusVal === 'all' || item.status === statusVal

    return searchMatch && statusMatch
  })
})

const { currentPage, total, from, to, paginatedItems } = usePagination(filteredItems, perPage)

const actions = computed(() => [
  {
    label: `Tempatkan ke Kelas (${selectedStudents.value.length})`,
    icon: Plus,
    disabled: selectedStudents.value.length === 0 || isPlacing.value,
    click: openPlacementConfirmation
  }
])

const waliKelasSections = [
  {
    title: 'Informasi Wali Kelas',
    fields: [
      {
        name: 'waliKelasId',
        label: 'Wali Kelas',
        type: 'select',
        options: guruOptions,
        required: true
      }
    ]
  }
]

const handleSubmitWaliKelas = () => {
  isSaving.value = true
  isSaving.value = false
  isOpenWaliKelas.value = false
  toast.success('Wali Kelas Berhasil Diperbarui')
}

const isConfirmOpen = ref(false)

const openPlacementConfirmation = () => {
  if (selectedStudents.value.length === 0) return
  isConfirmOpen.value = true
}

const isCurrentClassStudent = (item) => {
  if (!item?.kelasId || !classId.value) return false
  return Number(item.kelasId) === Number(classId.value)
}

const isRowDisabled = (item) => {
  return isCurrentClassStudent(item) || item?.status !== 'Aktif'
}

// Kapasitas & Statistik Siswa
const capacityLimit = computed(() => classroom.value?.capacity || 0)
const currentStudentsCount = computed(() => {
  return tableItems.value.filter(s => isCurrentClassStudent(s) && s.status === 'Aktif').length
})
const capacityPercentage = computed(() => {
  if (!capacityLimit.value) return 0
  return Math.min(Math.round((currentStudentsCount.value / capacityLimit.value) * 100), 100)
})

const maleCount = computed(() => {
  return tableItems.value.filter(s => isCurrentClassStudent(s) && s.status === 'Aktif' && s.jenisKelamin === 'Laki-laki').length
})
const femaleCount = computed(() => {
  return tableItems.value.filter(s => isCurrentClassStudent(s) && s.status === 'Aktif' && s.jenisKelamin === 'Perempuan').length
})

const classes = computed(() => {
  if (!classroom.value) return []
  return [{ label: `Kelas ${classroom.value.name}`, value: classroom.value.name }]
})

// Profil Wali Kelas
const teacherDetail = ref(null)

const teacherName = computed(() => {
  return teacherDetail.value?.nama || classroom.value?.homeroom_teacher || 'Belum Ada Wali'
})

const teacherFullName = computed(() => {
  if (teacherDetail.value) {
    const front = teacherDetail.value.gelar_depan ? `${teacherDetail.value.gelar_depan} ` : ''
    const back = teacherDetail.value.gelar_belakang ? `, ${teacherDetail.value.gelar_belakang}` : ''
    return `${front}${teacherDetail.value.nama}${back}`
  }
  return classroom.value?.homeroom_teacher || 'Belum Ada Wali'
})

const teacherPhoto = computed(() => {
  if (teacherDetail.value?.foto) {
    return getPhotoUrl(teacherDetail.value.foto)
  }
  return null
})

const teacherInitials = computed(() => {
  const name = teacherName.value
  if (!name || name === 'Belum Ada Wali') return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
  return parts[0].slice(0, 2).toUpperCase()
})

const loadData = async () => {
  if (!classId.value) return
  isLoading.value = true
  teacherDetail.value = null
  try {
    const classRes = await getClassroom(classId.value)
    classroom.value = classRes.data
    selectedClass.value = classRes.data?.name || ''

    // Muat detail wali kelas jika ada
    if (classRes.data?.homeroom_teacher_id) {
      try {
        const teacherRes = await getTeacher(classRes.data.homeroom_teacher_id)
        teacherDetail.value = teacherRes.data || teacherRes
      } catch (err) {
        console.warn('Gagal memuat profil detail guru:', err)
      }
    }

    const studentParams = classRes.data?.school_id ? { school_id: classRes.data.school_id } : {}
    const studentRes = await fetchAllSiswa(studentParams)

    tableItems.value = (studentRes.data || []).map(s => ({
      ...s,
      foto: s.foto ? getPhotoUrl(s.foto) : null,
      kelas: s.kelasId ? s.kelas : 'Belum Ada Kelas'
    }))
  } catch (err) {
    console.error('Gagal memuat data kelas atau siswa:', err)
    toast.error('Gagal memuat data kelas atau siswa')
  } finally {
    isLoading.value = false
  }
}

watch(
  () => route.params.id,
  (newId) => {
    if (newId) loadData()
  },
  { immediate: true }
)

const placeStudentsToClass = async () => {
  const selectedIds = selectedStudents.value.map(student => student.id)
  if (!selectedIds.length || !classId.value) return

  // Validasi kapasitas di sisi klien sebelum request
  if (capacityLimit.value && (currentStudentsCount.value + selectedIds.length) > capacityLimit.value) {
    toast.error(`Kapasitas kelas tidak mencukupi! Sisa kuota: ${Math.max(0, capacityLimit.value - currentStudentsCount.value)} siswa.`)
    return
  }

  isPlacing.value = true
  try {
    const res = await assignClassroomStudents(classId.value, selectedIds)
    toast.success('Penempatan Berhasil', {
      description: res.message || `${selectedIds.length} siswa berhasil ditempatkan ke kelas ${selectedClass.value}.`
    })
    selectedStudents.value = []
    isConfirmOpen.value = false
    await loadData()
  } catch (err) {
    const msg = err.response?.data?.message || 'Gagal menempatkan siswa ke kelas'
    toast.error(msg)
  } finally {
    isPlacing.value = false
  }
}
</script>

<template>
  <div class="space-y-6 w-full mx-auto px-0 mb-6">
    <PageHeader
      back
      :title="classroom?.name ? `Kelola Anggota Kelas ${classroom.name}` : 'Kelola Anggota Kelas'"
      description="Tambahkan atau pindahkan siswa ke kelas"
    />
  </div>

  <div class="grid gap-6 md:grid-cols-4">
    <!-- Kolom Kiri: Overview Kelas -->
    <div class="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle class="text-lg">Penempatan Kelas</CardTitle>
          <CardDescription> Kelas tujuan penempatan siswa </CardDescription>
        </CardHeader>
        <Separator />
        <CardContent class="space-y-4">
          <FormSelect
            label="Pilih Kelas"
            :options="classes"
            v-model="selectedClass"
            disabled
          />

          <div class="border rounded-lg p-4">
            <div class="flex justify-between mb-2">
              <span class="text-xs text-muted-foreground">Kapasitas</span>
              <span class="text-xs font-semibold">{{ currentStudentsCount }} / {{ capacityLimit || '-' }}</span>
            </div>

            <Progress
              :model-value="capacityPercentage"
              class="h-1 sm:h-2"
            />
          </div>

          <div class="grid grid-cols-2 gap-2">
            <div class="border rounded-lg p-3 text-center">
              <p class="text-xs text-muted-foreground font-medium">LAKI-LAKI</p>
              <p class="font-bold text-lg text-primary">{{ maleCount }}</p>
            </div>

            <div class="border rounded-lg p-3 text-center">
              <p class="text-xs text-muted-foreground font-medium">PEREMPUAN</p>
              <p class="font-bold text-lg text-primary">{{ femaleCount }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="text-lg">Wali Kelas</CardTitle>
          <CardDescription>Informasi wali kelas {{ classroom?.name || '-' }}</CardDescription>
        </CardHeader>
        <Separator />
        <CardContent>
          <div class="flex items-center gap-4 py-1">
            <Avatar class="shrink-0 size-20">
              <AvatarImage
                v-if="teacherPhoto"
                :src="teacherPhoto"
                :alt="teacherName"
              />
              <AvatarFallback class="text-sm font-bold"> {{ teacherInitials }} </AvatarFallback>
            </Avatar>
            <div class="min-w-0 flex-1">
              <p class="font-semibold text-base mb-1 truncate">{{ teacherFullName }}</p>
              <p class="text-xs text-muted-foreground mb-1 uppercase font-medium">Wali Kelas</p>
            </div>
          </div>
        </CardContent>
        <CardFooter class="flex justify-end gap-2">
          <Button variant="outline" size="sm" @click="openChangeWaliKelas">Ubah Wali Kelas</Button>
        </CardFooter>
      </Card>
    </div>

    <!-- Kolom Kanan: Tabel daftar siswa -->
    <div class="md:col-span-3 space-y-6">
      <DataTableCard
        selectable
        v-model:selectedRows="selectedStudents"
        :rowDisabled="isRowDisabled"
        :actions="actions"
        :columns="columns"
        :items="paginatedItems"
        :filters="filters"
        v-model:filterValues="filterValues"
        v-model:perPage="perPage"
        illustration="globe"
        :from="from"
        :to="to"
        :total="total"
        :page="currentPage"
        @update:page="currentPage = $event"
      >
        <template #cell-kehadiran="{ item }">
          <div v-if="item.kehadiran !== undefined && item.kehadiran !== null" class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
              <span
                class="text-sm font-medium"
                :class="item.kehadiran >= 100 ? 'text-primary' : 'text-amber-600'"
              >
                {{ item.kehadiran }}
              </span>
              <span class="text-muted-foreground text-xs">%</span>
            </div>
            <div class="w-full bg-secondary rounded-full h-1.5 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-300"
                :class="item.kehadiran >= 100 ? 'bg-primary' : 'bg-amber-500'"
                :style="{ width: `${Math.min((item.kehadiran / 100) * 100, 100)}%` }"
              ></div>
            </div>
          </div>
          <span v-else class="text-xs text-muted-foreground font-medium">-</span>
        </template>

        <template #cell-kelas="{ item }">
          <div class="flex justify-center">
            <span
              :class="{
                'text-red-500 font-medium': item.kelas === 'Belum Ada Kelas',
                'font-bold text-primary': isCurrentClassStudent(item)
              }"
            >
              {{ item.kelas }}
            </span>
          </div>
        </template>
      </DataTableCard>
    </div>
  </div>

  <FormSheet
    v-model:open="isOpenWaliKelas"
    :item="selectedItem"
    title="Ubah Wali Kelas"
    description="Pilih guru yang akan ditetapkan sebagai wali kelas."
    :sections="waliKelasSections"
    :loading="isSaving"
    @submit="handleSubmitWaliKelas"
    avatarKey="false"
  />

  <AlertDialog v-model:open="isConfirmOpen">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle> Konfirmasi Penempatan Siswa </AlertDialogTitle>

        <AlertDialogDescription>
          <strong>{{ selectedStudents.length }} siswa</strong>
          akan ditempatkan ke kelas
          <strong>{{ selectedClass }}</strong
          >. <br /><br />
          Siswa yang berasal dari kelas lain akan dipindahkan dari kelas sebelumnya.
        </AlertDialogDescription>
      </AlertDialogHeader>

      <AlertDialogFooter>
        <AlertDialogCancel :disabled="isPlacing"> Batal </AlertDialogCancel>

        <AlertDialogAction :disabled="isPlacing" @click="placeStudentsToClass">
          {{ isPlacing ? 'Menempatkan...' : 'Tempatkan' }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
