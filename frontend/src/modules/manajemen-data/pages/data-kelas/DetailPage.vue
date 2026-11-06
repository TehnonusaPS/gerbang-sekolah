<script setup>
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { Building2, School, Settings, Users2, VenusAndMars, ArrowUpRight, MessageSquare } from 'lucide-vue-next'
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
import FormSelect from '@/components/forms/FormSelect.vue'
import Dialog from '@/components/ui/dialog/Dialog.vue'
import DialogContent from '@/components/ui/dialog/DialogContent.vue'
import DialogHeader from '@/components/ui/dialog/DialogHeader.vue'
import DialogTitle from '@/components/ui/dialog/DialogTitle.vue'
import DialogDescription from '@/components/ui/dialog/DialogDescription.vue'
import DialogFooter from '@/components/ui/dialog/DialogFooter.vue'
import { useRoute, useRouter } from 'vue-router'
import {
  getClassroom,
  getClassrooms,
  getTeacher,
  getClassroomHistoryStudents,
  promoteClassroomStudents
} from '@/services/managementService'
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

const isPromoteOpen = ref(false)
const targetClassroomId = ref('')
const retainedIds = ref([])
const availableClasses = ref([])
const isPromoting = ref(false)

const openPromoteDialog = async () => {
  if (!classroom.value || tableItems.value.length === 0) {
    toast.error('Tidak ada siswa di kelas ini untuk dinaikkan.')
    return
  }
  try {
    const params = classroom.value?.school_id ? { school_id: classroom.value.school_id } : {}
    const res = await getClassrooms(params)
    const allClasses = res.data || []
    availableClasses.value = allClasses
      .filter(c => String(c.id) !== String(classroom.value.id))
      .map(c => ({
        label: `Kelas ${c.name} (Tingkat ${c.grade})`,
        value: String(c.id)
      }))

    targetClassroomId.value = ''
    retainedIds.value = []
    isPromoteOpen.value = true
  } catch (err) {
    toast.error('Gagal memuat daftar kelas tujuan')
  }
}

const toggleRetained = (studentId) => {
  const strId = String(studentId)
  if (retainedIds.value.includes(strId)) {
    retainedIds.value = retainedIds.value.filter(id => id !== strId)
  } else {
    retainedIds.value.push(strId)
  }
}

const executePromote = async () => {
  if (!targetClassroomId.value) {
    toast.error('Silakan pilih kelas tujuan terlebih dahulu.')
    return
  }

  isPromoting.value = true
  try {
    const res = await promoteClassroomStudents(classroom.value.id, {
      target_classroom_id: targetClassroomId.value,
      retained_student_ids: retainedIds.value
    })
    toast.success('Kenaikan Kelas Berhasil!', {
      description: res.message || 'Siswa berhasil dinaikkan ke kelas tujuan.'
    })
    isPromoteOpen.value = false
    await fetchDetailData(route.params.id)
  } catch (err) {
    toast.error('Gagal memproses kenaikan kelas', {
      description: err.response?.data?.message || err.message
    })
  } finally {
    isPromoting.value = false
  }
}

const headerAction = computed(() => {
  const acts = [
    {
      label: 'Kelola Anggota Kelas',
      icon: Settings,
      click: goToManageStudent
    }
  ]

  if (tableItems.value.length > 0) {
    acts.unshift({
      label: 'Naikkan Kelas',
      icon: ArrowUpRight,
      click: openPromoteDialog
    })
  }

  return acts
})

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
      let students = studentRes.data || []

      // Jika siswa aktif kosong (misal kelas arsip masa lalu), ambil dari history students
      if (students.length === 0) {
        try {
          const histRes = await getClassroomHistoryStudents(classId)
          if (histRes.data && histRes.data.length > 0) {
            students = histRes.data.map(h => ({
              id: h.user_id || h.id,
              nama: h.nama,
              nisn: h.nisn,
              jenisKelamin: h.jenis_kelamin,
              status: h.status_keanggotaan === 'active' ? 'Aktif' : (h.status_keanggotaan === 'promoted' ? 'Naik Kelas' : (h.status_keanggotaan === 'graduated' ? 'Lulus' : 'Pindah')),
              foto: null
            }))
          }
        } catch (hErr) {
          console.warn('Gagal memuat riwayat siswa:', hErr)
        }
      }

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

  <div v-else class="grid gap-6 grid-cols-1 lg:grid-cols-12 items-start">
    <!-- Kolom Kiri: Overview Kelas & Wali Kelas -->
    <div class="lg:col-span-4 xl:col-span-4 space-y-5 lg:sticky lg:top-6 self-start">
      <!-- Card Informasi Kelas -->
      <Card class="overflow-hidden border border-border/70 shadow-xs">
        <CardHeader class="pb-3.5">
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-base font-bold text-foreground">Informasi Kelas</CardTitle>
              <CardDescription class="text-xs">Profil & kapasitas kelas {{ classroom.name || '-' }}</CardDescription>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
              Tingkat {{ classroom.grade || '-' }}
            </span>
          </div>
        </CardHeader>
        <CardContent class="pt-0">
          <div class="grid grid-cols-2 gap-2.5">
            <!-- Card 1: Nama Kelas -->
            <div class="flex flex-col p-3 rounded-xl bg-muted/40 border border-border/50">
              <div class="flex items-center gap-1.5 text-muted-foreground mb-1">
                <School class="size-4 text-primary" />
                <span class="text-[11px] font-semibold tracking-wider uppercase">Kelas</span>
              </div>
              <span class="text-base font-bold text-foreground">{{ classroom.name || '-' }}</span>
            </div>

            <!-- Card 2: Ruangan -->
            <div class="flex flex-col p-3 rounded-xl bg-muted/40 border border-border/50">
              <div class="flex items-center gap-1.5 text-muted-foreground mb-1">
                <Building2 class="size-4 text-primary" />
                <span class="text-[11px] font-semibold tracking-wider uppercase">Ruangan</span>
              </div>
              <span class="text-base font-bold text-foreground truncate" :title="classroom.room || '-'">
                {{ classroom.room || '-' }}
              </span>
            </div>

            <!-- Card 3: Total Siswa -->
            <div class="flex flex-col p-3 rounded-xl bg-muted/40 border border-border/50">
              <div class="flex items-center gap-1.5 text-muted-foreground mb-1">
                <Users2 class="size-4 text-primary" />
                <span class="text-[11px] font-semibold tracking-wider uppercase">Total Siswa</span>
              </div>
              <div class="flex items-baseline gap-1">
                <span class="text-xl font-black text-foreground">{{ totalStudents }}</span>
                <span class="text-xs text-muted-foreground font-medium">siswa</span>
              </div>
            </div>

            <!-- Card 4: Rasio Gender -->
            <div class="flex flex-col p-3 rounded-xl bg-muted/40 border border-border/50">
              <div class="flex items-center gap-1.5 text-muted-foreground mb-1">
                <VenusAndMars class="size-4 text-primary" />
                <span class="text-[11px] font-semibold tracking-wider uppercase">Gender</span>
              </div>
              <div class="flex items-center gap-2 text-xs font-semibold mt-0.5">
                <span class="text-sky-600 dark:text-sky-400 bg-sky-50 dark:bg-sky-950/40 px-2 py-0.5 rounded-md">
                  {{ maleCount }} L
                </span>
                <span class="text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded-md">
                  {{ femaleCount }} P
                </span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Card Wali Kelas -->
      <Card class="overflow-hidden border border-border/70 shadow-xs">
        <CardHeader class="pb-3.5">
          <div class="flex items-center justify-between">
            <div>
              <CardTitle class="text-base font-bold text-foreground">Wali Kelas</CardTitle>
              <CardDescription class="text-xs">Penanggung jawab kelas {{ classroom.name || '-' }}</CardDescription>
            </div>
            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold bg-muted text-muted-foreground uppercase tracking-wide">
              {{ teacherPosition }}
            </span>
          </div>
        </CardHeader>
        <CardContent class="pt-0">
          <div class="flex items-start gap-3.5 p-3.5 rounded-xl bg-muted/30 border border-border/50">
            <Avatar class="size-13 rounded-full border border-border/80 shadow-xs shrink-0 mt-0.5">
              <AvatarImage
                v-if="teacherPhoto"
                :src="teacherPhoto"
                :alt="teacherName"
                class="object-cover"
              />
              <AvatarFallback class="bg-primary/10 text-primary text-sm font-bold">
                {{ teacherInitials }}
              </AvatarFallback>
            </Avatar>
            <div class="min-w-0 flex-1 space-y-1">
              <p class="font-bold text-sm text-foreground leading-snug break-words">
                {{ teacherFullName }}
              </p>
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="inline-flex items-center text-xs font-medium text-muted-foreground bg-background px-2 py-0.5 rounded-md border border-border/60">
                  {{ teacherNip }}
                </span>
              </div>
            </div>
          </div>
        </CardContent>
        <CardFooter class="pt-0">
          <Button
            :disabled="!hasTeacherContact"
            variant="outline"
            size="sm"
            class="w-full text-xs font-semibold gap-2 rounded-xl cursor-pointer hover:bg-primary/5 hover:text-primary transition-colors"
            @click="handleContactTeacher"
          >
            <MessageSquare class="size-3.5" />
            <span>Hubungi Wali Kelas</span>
          </Button>
        </CardFooter>
      </Card>
    </div>

    <!-- Kolom Kanan: Tabel daftar siswa -->
    <div class="lg:col-span-8 xl:col-span-8 space-y-6">
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
        @edit="$router.push(`/manajemen-data/siswa/edit?id=${$event}`)"
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

  <!-- Dialog Kenaikan Kelas Masal -->
  <Dialog v-model:open="isPromoteOpen">
    <DialogContent class="max-w-lg">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <ArrowUpRight class="size-5 text-primary" />
          Kenaikan Kelas Siswa
        </DialogTitle>
        <DialogDescription>
          Pindahkan seluruh siswa dari <strong>Kelas {{ classroom?.name }}</strong> ke kelas tingkat berikutnya secara masal.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4 py-2">
        <FormSelect
          label="Pilih Kelas Tujuan"
          placeholder="Pilih kelas tingkat berikutnya..."
          :options="availableClasses"
          v-model="targetClassroomId"
          required
        />

        <div class="border rounded-lg p-3 bg-muted/30">
          <div class="flex items-center justify-between text-xs">
            <span class="text-muted-foreground font-medium">Total Siswa Aktif:</span>
            <span class="font-bold text-foreground">{{ tableItems.length }} Siswa</span>
          </div>
          <div class="flex items-center justify-between text-xs mt-1">
            <span class="text-muted-foreground font-medium">Siswa Akan Naik Kelas:</span>
            <span class="font-bold text-primary">{{ Math.max(0, tableItems.length - retainedIds.length) }} Siswa</span>
          </div>
          <div v-if="retainedIds.length > 0" class="flex items-center justify-between text-xs mt-1">
            <span class="text-amber-600 font-medium">Siswa Tinggal Kelas:</span>
            <span class="font-bold text-amber-600">{{ retainedIds.length }} Siswa</span>
          </div>
        </div>

        <div v-if="tableItems.length > 0" class="space-y-2">
          <label class="text-xs font-semibold text-muted-foreground">
            Tandai Siswa Yang Tinggal Kelas (Opsional):
          </label>
          <div class="max-h-40 overflow-y-auto border rounded-md divide-y text-xs">
            <div
              v-for="s in tableItems"
              :key="s.id"
              class="flex items-center justify-between p-2 hover:bg-muted/50 cursor-pointer"
              @click="toggleRetained(s.id)"
            >
              <div class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :checked="retainedIds.includes(String(s.id))"
                  class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                  @click.stop="toggleRetained(s.id)"
                />
                <span :class="{ 'line-through text-muted-foreground': retainedIds.includes(String(s.id)) }">
                  {{ s.nama }} ({{ s.nisn }})
                </span>
              </div>
              <span v-if="retainedIds.includes(String(s.id))" class="text-[10px] font-semibold text-amber-600">
                Tinggal Kelas
              </span>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter class="flex justify-end gap-2">
        <Button variant="outline" size="sm" :disabled="isPromoting" @click="isPromoteOpen = false">
          Batal
        </Button>
        <Button
          size="sm"
          :disabled="isPromoting || !targetClassroomId"
          @click="executePromote"
        >
          {{ isPromoting ? 'Memproses...' : 'Proses Kenaikan Kelas' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
