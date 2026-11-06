<script setup>
import { ref, computed, watch } from 'vue'
import { toast } from 'vue-sonner'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription
} from '@/components/ui/sheet'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import Avatar from '@/components/ui/avatar/Avatar.vue'
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue'
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue'
import {
  UserPlus,
  UserMinus,
  Search,
  Users,
  ShieldAlert,
  Loader2,
  Calendar,
  GraduationCap
} from 'lucide-vue-next'
import { getPhotoUrl } from '@/utils/getPhotoUrl'
import {
  getEkskulStudents,
  addEkskulStudents,
  removeEkskulStudent,
  getAvailableEkskulStudents
} from '@/services/ekskulService'

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  ekskul: {
    type: Object,
    default: null
  },
  canManage: {
    type: Boolean,
    default: true
  }
})

const emit = defineEmits(['update:open', 'updated'])

// --- Member List State ---
const members = ref([])
const isLoadingMembers = ref(false)
const searchMemberQuery = ref('')

const loadMembers = async () => {
  if (!props.ekskul?.id) return
  isLoadingMembers.value = true
  try {
    const res = await getEkskulStudents(props.ekskul.id)
    members.value = res.data || []
  } catch (err) {
    toast.error('Gagal memuat anggota ekstrakulikuler', {
      description: err.response?.data?.message || err.message
    })
  } finally {
    isLoadingMembers.value = false
  }
}

watch(
  () => [props.open, props.ekskul?.id],
  ([isOpen, ekskulId]) => {
    if (isOpen && ekskulId) {
      searchMemberQuery.value = ''
      loadMembers()
    }
  },
  { immediate: true }
)

const filteredMembers = computed(() => {
  const q = searchMemberQuery.value.trim().toLowerCase()
  if (!q) return members.value
  return members.value.filter(m =>
    m.nama?.toLowerCase().includes(q) ||
    m.nisn?.toLowerCase().includes(q) ||
    m.kelas?.toLowerCase().includes(q)
  )
})

// --- Add Student Dialog State ---
const isAddDialogOpen = ref(false)
const availableStudents = ref([])
const isLoadingAvailable = ref(false)
const searchAvailableQuery = ref('')
const selectedStudentIds = ref([])
const isAdding = ref(false)

const openAddDialog = async () => {
  selectedStudentIds.value = []
  searchAvailableQuery.value = ''
  isAddDialogOpen.value = true
  await loadAvailableStudents()
}

const loadAvailableStudents = async () => {
  if (!props.ekskul?.id) return
  isLoadingAvailable.value = true
  try {
    const res = await getAvailableEkskulStudents(props.ekskul.id, {
      search: searchAvailableQuery.value.trim() || undefined
    })
    availableStudents.value = res.data || []
  } catch (err) {
    toast.error('Gagal memuat daftar siswa', {
      description: err.response?.data?.message || err.message
    })
  } finally {
    isLoadingAvailable.value = false
  }
}

const isAllSelected = computed(() => {
  return (
    availableStudents.value.length > 0 &&
    availableStudents.value.every(s => selectedStudentIds.value.includes(s.id))
  )
})

const toggleSelectAll = (checked) => {
  if (checked) {
    selectedStudentIds.value = availableStudents.value.map(s => s.id)
  } else {
    selectedStudentIds.value = []
  }
}

const toggleSelectStudent = (id) => {
  const idx = selectedStudentIds.value.indexOf(id)
  if (idx > -1) {
    selectedStudentIds.value.splice(idx, 1)
  } else {
    selectedStudentIds.value.push(id)
  }
}

const handleAddStudents = async () => {
  if (!selectedStudentIds.value.length || !props.ekskul?.id) return
  isAdding.value = true
  try {
    const res = await addEkskulStudents(props.ekskul.id, selectedStudentIds.value)
    toast.success('Berhasil Menambahkan Siswa', {
      description: res.message || `${selectedStudentIds.value.length} siswa berhasil ditambahkan.`
    })
    isAddDialogOpen.value = false
    selectedStudentIds.value = []
    await loadMembers()
    emit('updated')
  } catch (err) {
    toast.error('Gagal menambahkan siswa', {
      description: err.response?.data?.message || err.message
    })
  } finally {
    isAdding.value = false
  }
}

// --- Remove Student Dialog State ---
const isRemoveConfirmOpen = ref(false)
const studentToRemove = ref(null)
const isRemoving = ref(false)

const confirmRemove = (student) => {
  studentToRemove.value = student
  isRemoveConfirmOpen.value = true
}

const handleRemoveStudent = async () => {
  if (!studentToRemove.value || !props.ekskul?.id) return
  const studentId = studentToRemove.value.student_profile_id || studentToRemove.value.id
  isRemoving.value = true
  try {
    const res = await removeEkskulStudent(props.ekskul.id, studentId)
    toast.success('Siswa Berhasil Dikeluarkan', {
      description: res.message || `${studentToRemove.value.nama} telah dikeluarkan dari ekstrakulikuler.`
    })
    isRemoveConfirmOpen.value = false
    studentToRemove.value = null
    await loadMembers()
    emit('updated')
  } catch (err) {
    toast.error('Gagal mengeluarkan siswa', {
      description: err.response?.data?.message || err.message
    })
  } finally {
    isRemoving.value = false
  }
}
</script>

<template>
  <Sheet :open="open" @update:open="emit('update:open', $event)">
    <SheetContent class="sm:max-w-[720px] w-full flex flex-col h-full p-0">
      <!-- Header -->
      <SheetHeader class="p-6 border-b border-border text-left">
        <div class="flex items-center justify-between gap-3">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <SheetTitle class="text-lg font-bold text-foreground">
                Anggota: {{ ekskul?.nama }}
              </SheetTitle>
              <Badge :variant="ekskul?.status === 'aktif' ? 'green' : 'gray'" class="text-xs">
                {{ ekskul?.status === 'aktif' ? 'Aktif' : 'Nonaktif' }}
              </Badge>
            </div>
            <SheetDescription class="text-xs text-muted-foreground">
              Pembina: <strong class="text-foreground">{{ ekskul?.pembina_nama || '-' }}</strong>
              <span class="mx-1.5">•</span>
              Total Anggota: <strong class="text-foreground">{{ members.length }} Siswa</strong>
            </SheetDescription>
          </div>

          <Button
            v-if="canManage"
            size="sm"
            class="gap-1.5 rounded-xl cursor-pointer"
            @click="openAddDialog"
          >
            <UserPlus class="size-4" />
            <span>Tambah Siswa</span>
          </Button>
        </div>

        <!-- Search Bar -->
        <div class="pt-3">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <Input
              v-model="searchMemberQuery"
              placeholder="Cari anggota berdasarkan nama, NISN, atau kelas..."
              class="pl-9 text-xs h-9"
            />
          </div>
        </div>
      </SheetHeader>

      <!-- Content / Student List -->
      <div class="flex-1 overflow-y-auto p-6 space-y-3">
        <!-- Loading State -->
        <div v-if="isLoadingMembers" class="flex flex-col items-center justify-center py-16 gap-3 text-muted-foreground">
          <Loader2 class="size-8 animate-spin text-primary" />
          <p class="text-xs">Memuat data anggota...</p>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="filteredMembers.length === 0"
          class="flex flex-col items-center justify-center py-16 px-4 text-center border border-dashed rounded-2xl bg-muted/20"
        >
          <div class="size-12 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-3">
            <Users class="size-6" />
          </div>
          <h3 class="text-sm font-semibold text-foreground">
            {{ searchMemberQuery ? 'Siswa tidak ditemukan' : 'Belum ada anggota' }}
          </h3>
          <p class="text-xs text-muted-foreground max-w-sm mt-1">
            {{
              searchMemberQuery
                ? 'Coba gunakan kata kunci pencarian yang lain.'
                : 'Ekstrakulikuler ini belum memiliki anggota terdaftar. Klik tombol di bawah untuk menambahkan siswa.'
            }}
          </p>
          <Button
            v-if="!searchMemberQuery && canManage"
            size="sm"
            class="mt-4 gap-1.5 rounded-xl cursor-pointer"
            @click="openAddDialog"
          >
            <UserPlus class="size-4" />
            <span>Tambah Siswa Sekarang</span>
          </Button>
        </div>

        <!-- Members Table / Cards -->
        <div v-else class="space-y-2">
          <div
            v-for="member in filteredMembers"
            :key="member.id"
            class="flex items-center justify-between p-3.5 rounded-xl border border-border bg-card hover:bg-muted/40 transition-colors"
          >
            <div class="flex items-center gap-3 min-w-0">
              <Avatar class="size-10 border shrink-0">
                <AvatarImage v-if="getPhotoUrl(member.avatar)" :src="getPhotoUrl(member.avatar)" :alt="member.nama" />
                <AvatarFallback class="bg-primary/10 text-primary text-xs font-semibold">
                  {{ member.nama?.charAt(0)?.toUpperCase() || 'S' }}
                </AvatarFallback>
              </Avatar>

              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-semibold text-foreground truncate">
                    {{ member.nama }}
                  </p>
                  <span class="text-[10px] px-1.5 py-0.5 rounded font-medium bg-muted text-muted-foreground">
                    {{ member.jenis_kelamin }}
                  </span>
                </div>
                <div class="flex items-center gap-3 text-xs text-muted-foreground mt-0.5">
                  <span class="flex items-center gap-1">
                    <GraduationCap class="size-3 text-muted-foreground" />
                    {{ member.kelas }}
                  </span>
                  <span>•</span>
                  <span>NISN: {{ member.nisn || '-' }}</span>
                  <span>•</span>
                  <span class="flex items-center gap-1">
                    <Calendar class="size-3 text-muted-foreground" />
                    {{ member.tanggal_bergabung }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Remove Button -->
            <Button
              v-if="canManage"
              variant="ghost"
              size="sm"
              class="text-rose-500 hover:text-rose-600 hover:bg-rose-50 shrink-0 gap-1 text-xs cursor-pointer"
              title="Keluarkan dari ekskul"
              @click="confirmRemove(member)"
            >
              <UserMinus class="size-4" />
              <span class="hidden sm:inline">Keluarkan</span>
            </Button>
          </div>
        </div>
      </div>
    </SheetContent>
  </Sheet>

  <!-- Add Students Dialog -->
  <Dialog v-model:open="isAddDialogOpen">
    <DialogContent class="sm:max-w-[620px] rounded-2xl p-6">
      <DialogHeader class="text-left">
        <DialogTitle class="text-base font-bold text-foreground flex items-center gap-2">
          <UserPlus class="size-5 text-primary" />
          Tambah Siswa ke {{ ekskul?.nama }}
        </DialogTitle>
        <DialogDescription class="text-xs text-muted-foreground">
          Pilih siswa yang ingin didaftarkan ke kegiatan ekstrakurikuler ini.
        </DialogDescription>
      </DialogHeader>

      <!-- Search Input -->
      <div class="pt-2">
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
          <Input
            v-model="searchAvailableQuery"
            placeholder="Cari nama, NISN, atau kelas..."
            class="pl-9 text-xs h-9"
            @input="loadAvailableStudents"
          />
        </div>
      </div>

      <!-- Table / Select List -->
      <div class="border rounded-xl mt-3 max-h-[340px] overflow-y-auto">
        <!-- Header Checkbox -->
        <div
          v-if="availableStudents.length > 0"
          class="flex items-center justify-between p-3 border-b bg-muted/40 text-xs font-semibold text-muted-foreground"
        >
          <div class="flex items-center gap-2">
            <Checkbox
              :checked="isAllSelected"
              @update:checked="toggleSelectAll"
            />
            <span>Pilih Semua Siswa</span>
          </div>
          <span>{{ selectedStudentIds.length }} dipilih</span>
        </div>

        <!-- Loading -->
        <div v-if="isLoadingAvailable" class="flex flex-col items-center justify-center py-12 gap-2 text-muted-foreground">
          <Loader2 class="size-6 animate-spin text-primary" />
          <p class="text-xs">Memuat daftar siswa yang tersedia...</p>
        </div>

        <!-- Empty Available -->
        <div v-else-if="availableStudents.length === 0" class="py-12 text-center text-xs text-muted-foreground">
          Tidak ada siswa tersedia untuk ditambahkan.
        </div>

        <!-- Student Items -->
        <div v-else class="divide-y divide-border">
          <div
            v-for="st in availableStudents"
            :key="st.id"
            class="flex items-center gap-3 p-3 hover:bg-muted/30 cursor-pointer transition-colors"
            @click="toggleSelectStudent(st.id)"
          >
            <Checkbox
              :checked="selectedStudentIds.includes(st.id)"
              @click.stop
              @update:checked="toggleSelectStudent(st.id)"
            />
            <Avatar class="size-8 border shrink-0">
              <AvatarImage v-if="getPhotoUrl(st.avatar)" :src="getPhotoUrl(st.avatar)" :alt="st.nama" />
              <AvatarFallback class="bg-primary/10 text-primary text-[10px] font-semibold">
                {{ st.nama?.charAt(0)?.toUpperCase() || 'S' }}
              </AvatarFallback>
            </Avatar>
            <div class="min-w-0 flex-1">
              <p class="text-xs font-semibold text-foreground truncate">
                {{ st.nama }}
              </p>
              <p class="text-[11px] text-muted-foreground">
                {{ st.kelas }} • NISN: {{ st.nisn || '-' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <DialogFooter class="flex flex-row items-center justify-end gap-2 pt-4 border-t mt-4">
        <Button
          type="button"
          variant="ghost"
          class="text-xs font-semibold rounded-xl cursor-pointer"
          :disabled="isAdding"
          @click="isAddDialogOpen = false"
        >
          Batal
        </Button>
        <Button
          type="button"
          class="text-xs font-semibold rounded-xl cursor-pointer"
          :disabled="isAdding || selectedStudentIds.length === 0"
          @click="handleAddStudents"
        >
          <Loader2 v-if="isAdding" class="size-4 animate-spin mr-1.5" />
          {{ isAdding ? 'Menyimpan...' : `Tambahkan (${selectedStudentIds.length}) Siswa` }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- Remove Confirm Dialog -->
  <Dialog v-model:open="isRemoveConfirmOpen">
    <DialogContent class="sm:max-w-[420px] rounded-2xl p-6">
      <DialogHeader class="text-left">
        <DialogTitle class="text-sm font-bold text-foreground flex items-center gap-2">
          <ShieldAlert class="size-5 text-rose-500 shrink-0" />
          Keluarkan Siswa dari Ekskul
        </DialogTitle>
        <DialogDescription class="text-xs text-muted-foreground leading-relaxed mt-2">
          Apakah Anda yakin ingin mengeluarkan
          <strong class="text-foreground">"{{ studentToRemove?.nama }}"</strong>
          dari ekstrakulikuler
          <strong class="text-foreground">"{{ ekskul?.nama }}"</strong>?
        </DialogDescription>
      </DialogHeader>

      <DialogFooter class="flex flex-row items-center justify-end gap-2 pt-4 border-t mt-4">
        <Button
          type="button"
          variant="ghost"
          class="text-xs font-semibold rounded-xl cursor-pointer"
          :disabled="isRemoving"
          @click="isRemoveConfirmOpen = false"
        >
          Batal
        </Button>
        <Button
          type="button"
          class="text-xs font-semibold rounded-xl cursor-pointer bg-rose-500 text-white hover:bg-rose-600 border-none shadow-none"
          :disabled="isRemoving"
          @click="handleRemoveStudent"
        >
          {{ isRemoving ? 'Mengeluarkan...' : 'Ya, Keluarkan' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

