<script setup>
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { Building2, School, Settings, Users2, VenusAndMars } from 'lucide-vue-next'
import { actions, columns, filters } from './data/siswaKelas'
import { usePagination } from '@/composables/usePagination'
import { ref, computed, watch } from 'vue'
import { toast } from 'vue-sonner'
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
import { useRoute, useRouter } from 'vue-router'
import { getClassroom, getTeacher } from '@/services/managementService'
import { fetchAllSiswa } from '@/services/siswaService'
import { getPhotoUrl } from '@/utils/getPhotoUrl'

const route = useRoute()
const router = useRouter()

const perPage = ref(10)
const tableItems = ref([])
const classroom = ref(null)
const teacherDetail = ref(null)
const isLoading = ref(false)

const goToManageStudent = () => {
  const classId = route.params.id
  if (classId) {
    router.push(`/manajemen-data/kelas/detail/${classId}/manage`)
  } else {
    router.push('/manajemen-data/kelas')
  }
}

const headerAction = computed(() => [
  {
    label: 'Kelola Anggota Kelas',
    icon: Settings,
    click: goToManageStudent
  }
])

const filterValues = ref({
  search: '',
  status: 'all'
})

const deleteItem = (id, item) => {
  tableItems.value = tableItems.value.filter(s => s.id !== id)
  toast.success('Berhasil dihapus', {
    description: `${item.nama} telah dihapus dari tampilan.`
  })
}

const filteredItems = computed(() => {
  return tableItems.value.filter(item => {
    const searchVal = filterValues.value.search?.trim().toLowerCase() || ''
    const searchMatch =
      !searchVal ||
      item.nama?.toLowerCase().includes(searchVal) ||
      item.nisn?.toLowerCase().includes(searchVal)

    const statusVal = filterValues.value.status
    const statusMatch = !statusVal || statusVal === 'all' || item.status === statusVal

    return searchMatch && statusMatch
  })
})

const { currentPage, total, from, to, paginatedItems } = usePagination(filteredItems, perPage)

// Computed for Class info
const totalStudents = computed(() => {
  return tableItems.value.length || classroom.value?.students_count || 0
})

const maleCount = computed(() => {
  return tableItems.value.filter(item => item.jenisKelamin === 'Laki-laki').length
})

const femaleCount = computed(() => {
  return tableItems.value.filter(item => item.jenisKelamin === 'Perempuan').length
})

// Computed for Wali Kelas
const teacherName = computed(() => {
  return teacherDetail.value?.nama || classroom.value?.homeroom_teacher || 'Belum Ada Wali Kelas'
})

const teacherFullName = computed(() => {
  if (teacherDetail.value) {
    const front = teacherDetail.value.gelar_depan ? `${teacherDetail.value.gelar_depan} ` : ''
    const back = teacherDetail.value.gelar_belakang ? `, ${teacherDetail.value.gelar_belakang}` : ''
    return `${front}${teacherDetail.value.nama}${back}`
  }
  return classroom.value?.homeroom_teacher || 'Belum Ada Wali Kelas'
})

const teacherPosition = computed(() => {
  if (teacherDetail.value?.jabatan) return teacherDetail.value.jabatan
  if (classroom.value?.homeroom_teacher) return 'WALI KELAS'
  return 'Belum Ditentukan'
})

const teacherNip = computed(() => {
  const nip = teacherDetail.value?.nip_nuptk || teacherDetail.value?.nip
  return nip ? `NIP: ${nip}` : 'NIP: -'
})

const teacherPhoto = computed(() => {
  if (teacherDetail.value?.foto) {
    return getPhotoUrl(teacherDetail.value.foto)
  }
  return null
})

const teacherInitials = computed(() => {
  const name = teacherName.value
  if (!name || name === 'Belum Ada Wali Kelas') return '?'
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return parts[0].slice(0, 2).toUpperCase()
})

const hasTeacherContact = computed(() => {
  return !!(teacherDetail.value?.no_hp || teacherDetail.value?.email)
})

const handleContactTeacher = () => {
  if (teacherDetail.value?.no_hp) {
    const cleanPhone = teacherDetail.value.no_hp.replace(/\D/g, '')
    const waPhone = cleanPhone.startsWith('0') ? '62' + cleanPhone.slice(1) : cleanPhone
    window.open(`https://wa.me/${waPhone}`, '_blank')
  } else if (teacherDetail.value?.email) {
    window.location.href = `mailto:${teacherDetail.value.email}`
  } else {
    toast.info('Kontak wali kelas belum tersedia.')
  }
}

// Fetch data logic
const fetchDetailData = async (classId) => {
  if (!classId) return
  isLoading.value = true
  classroom.value = null
  teacherDetail.value = null
  tableItems.value = []

  try {
    const classRes = await getClassroom(classId)
    classroom.value = classRes.data || classRes

    // Load teacher details if homeroom_teacher_id is available
    if (classroom.value?.homeroom_teacher_id) {
      try {
        const teacherRes = await getTeacher(classroom.value.homeroom_teacher_id)
        teacherDetail.value = teacherRes.data || teacherRes
      } catch (err) {
        console.warn('Gagal memuat profil detail guru:', err)
      }
    }

    // Load student list
    try {
      const studentRes = await fetchAllSiswa({ kelasId: classId })
      const students = studentRes.data || []
      tableItems.value = students.map(s => ({
        ...s,
        foto: s.foto ? getPhotoUrl(s.foto) : null
      }))
    } catch (err) {
      console.warn('Gagal memuat siswa kelas:', err)
      toast.error('Gagal memuat daftar siswa')
    }
  } catch (err) {
    console.error('Gagal memuat data kelas:', err)
    toast.error('Gagal memuat data kelas')
  } finally {
    isLoading.value = false
  }
}

watch(
  () => route.params.id,
  (newId) => {
    if (newId) {
      fetchDetailData(newId)
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="space-y-6 w-full mx-auto px-0 mb-6">
    <PageHeader
      back
      :title="classroom?.name ? `Kelas ${classroom.name}` : 'Detail Kelas'"
      :description="classroom?.name ? `Lihat informasi lengkap tentang kelas ${classroom.name}, termasuk profil kelas, wali kelas, daftar siswa, dan data lainnya.` : 'Lihat informasi lengkap tentang kelas, profil kelas, wali kelas, dan daftar siswa.'"
      :actions="headerAction"
    />
  </div>

  <div v-if="isLoading" class="flex flex-col items-center justify-center min-h-[350px] gap-3">
    <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
    <p class="text-xs text-muted-foreground font-medium">Memuat data kelas...</p>
  </div>

  <div v-else-if="!classroom" class="text-center py-16 border rounded-2xl bg-card/60 backdrop-blur-sm p-8">
    <School class="w-12 h-12 mx-auto text-muted-foreground mb-3 opacity-40" />
    <h3 class="text-base font-semibold text-foreground">Kelas Tidak Ditemukan</h3>
    <p class="text-xs text-muted-foreground mt-1 max-w-sm mx-auto">
      Data kelas dengan ID ini tidak ditemukan atau Anda tidak memiliki hak akses.
    </p>
    <Button variant="outline" size="sm" class="mt-4 text-xs font-semibold" @click="router.push('/manajemen-data/kelas')">
      Kembali ke Data Kelas
    </Button>
  </div>

  <div v-else class="grid gap-6 md:grid-cols-4">
    <!-- Kolom Kiri: Overview Kelas -->
    <div class="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle class="text-lg">Informasi Kelas</CardTitle>
          <CardDescription>Informasi mengenai kelas {{ classroom.name || '-' }}</CardDescription>
        </CardHeader>
        <Separator />
        <CardContent>
          <div class="flex items-center gap-4 border px-4 py-3 rounded-lg bg-background mb-4">
            <School class="w-6 h-6 text-primary" />
            <div>
              <p class="text-xs text-muted-foreground">KELAS</p>
              <p class="font-semibold text-sm">{{ classroom.name || '-' }}</p>
            </div>
          </div>

          <div class="flex items-center gap-4 border px-4 py-3 rounded-lg bg-background mb-4">
            <Users2 class="w-6 h-6 text-primary" />
            <div>
              <p class="text-xs text-muted-foreground">TOTAL SISWA</p>
              <p class="font-semibold text-sm">{{ totalStudents }}</p>
            </div>
          </div>

          <div class="flex items-center gap-4 border px-4 py-3 rounded-lg bg-background mb-4">
            <VenusAndMars class="w-6 h-6 text-primary" />
            <div>
              <p class="text-xs text-muted-foreground">LAKI-LAKI/PEREMPUAN</p>
              <p class="font-semibold text-sm">{{ maleCount }}/{{ femaleCount }}</p>
            </div>
          </div>

          <div class="flex items-center gap-4 border px-4 py-3 rounded-lg bg-background mb-1">
            <Building2 class="w-6 h-6 text-primary" />
            <div>
              <p class="text-xs text-muted-foreground">RUANGAN</p>
              <p class="font-semibold text-sm">{{ classroom.room || '-' }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="text-lg">Wali Kelas</CardTitle>
          <CardDescription>Informasi wali kelas {{ classroom.name || '-' }}</CardDescription>
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
              <p class="text-xs text-muted-foreground mb-1 uppercase font-medium">{{ teacherPosition }}</p>
              <p class="font-medium text-sm text-primary mb-1">{{ teacherNip }}</p>
            </div>
          </div>
        </CardContent>
        <CardFooter class="flex justify-end gap-2">
          <Button
            :disabled="!hasTeacherContact"
            variant="outline"
            size="sm"
            class="text-xs font-semibold cursor-pointer"
            @click="handleContactTeacher"
          >
            Hubungi Wali Kelas
          </Button>
        </CardFooter>
      </Card>
    </div>

    <!-- Kolom Kanan: Tabel daftar siswa -->
    <div class="md:col-span-3 space-y-6">
      <DataTableCard
        :columns="columns"
        :items="paginatedItems"
        :filters="filters"
        :actions="actions"
        v-model:filterValues="filterValues"
        v-model:perPage="perPage"
        illustration="globe"
        :from="from"
        :to="to"
        :total="total"
        :page="currentPage"
        @update:page="currentPage = $event"
        @edit="$router.push('/manajemen-data/manajemen-kelas/edit')"
        @delete="deleteItem"
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
      </DataTableCard>
    </div>
  </div>
</template>
