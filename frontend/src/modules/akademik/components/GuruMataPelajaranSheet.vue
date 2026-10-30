<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { toast } from 'vue-sonner'

// UI Components
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import FormSheet from '@/components/data-sheet/FormSheet.vue'

// API Services
import * as akademikService from '@/services/akademikService'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  mode: {
    type: String,
    default: 'add' // 'add', 'edit', 'view'
  },
  activeTab: {
    type: String,
    required: true // 'materi', 'tugas', 'ujian'
  },
  activeTabLabel: {
    type: String,
    required: true
  },
  initialForm: {
    type: Object,
    required: true
  },
  selectedSubjectId: {
    type: [String, Number],
    required: true
  },
  draft: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:open', 'save', 'save-draft', 'cancel'])

const isOpen = computed({
  get: () => props.open,
  set: (val) => emit('update:open', val)
})

const skipDraftSave = ref(false)

const form = ref({
  classroom_id: '',
  type: '',
  title: '',
  material_id: '',
  file: null,
  scores: {}
})

// Lookup lists
const classrooms = ref([])
const students = ref([])
const materials = ref([])
const activeAcademicYear = ref(null)

const materialOptions = computed(() => {
  return materials.value.map(m => ({
    value: String(m.id),
    label: m.title
  }))
})

// Load active academic year on mount
onMounted(async () => {
  try {
    const res = await akademikService.getActiveAcademicYear()
    activeAcademicYear.value = res.data
  } catch (err) {
    console.error('Gagal mengambil tahun ajaran aktif:', err)
  }
})

// Load classrooms taught by the teacher for this subject when opened
watch(
  () => props.open,
  async (newOpen) => {
    if (newOpen) {
      skipDraftSave.value = false
      // Sync form values with parent
      if (props.mode === 'add' && props.draft) {
        form.value = JSON.parse(JSON.stringify(props.draft))
      } else {
        form.value = JSON.parse(JSON.stringify(props.initialForm))
      }

      if (props.selectedSubjectId) {
        try {
          const res = await akademikService.getMyClassrooms(props.selectedSubjectId)
          classrooms.value = res.data
        } catch (err) {
          toast.error('Gagal memuat daftar kelas.')
        }
      }
    } else {
      // If closing sheet without submit, save draft
      if (props.mode === 'add' && !skipDraftSave.value) {
        emit('save-draft', { tab: props.activeTab, data: { ...form.value } })
      }
    }
  },
  { immediate: true }
)

// Load students and materials list when classroom selection changes
watch(
  () => form.value.classroom_id,
  async (newClassroomId) => {
    if (newClassroomId) {
      try {
        const res = await akademikService.getStudentsByClassroom(newClassroomId)
        students.value = res.data

        // Initialize grades array for input if adding new assessment
        if (props.mode === 'add') {
          const newScores = {}
          students.value.forEach(st => {
            // Keep old draft score if exists, else empty
            newScores[st.id] = form.value.scores?.[st.id] !== undefined ? form.value.scores[st.id] : ''
          })
          form.value.scores = newScores
        }
      } catch (err) {
        toast.error('Gagal memuat siswa kelas.')
      }

      try {
        const res = await akademikService.getMaterialsBySubjectClassroom(props.selectedSubjectId, newClassroomId)
        materials.value = res.data
      } catch (err) {
        console.error('Gagal memuat materi kelas:', err)
      }
    } else {
      students.value = []
      materials.value = []
    }
  }
)

// Auto-handle type UTS/UAS title & material_id reset
watch(
  () => form.value.type,
  (newType) => {
    if (newType === 'uts' || newType === 'uas') {
      form.value.material_id = ''
      form.value.title = newType.toUpperCase()
    }
  }
)

const semesterLabel = computed(() => {
  if (!activeAcademicYear.value) return 'Memuat...'
  const term = activeAcademicYear.value.semester === 'odd' ? 'Ganjil' : 'Genap'
  return `${activeAcademicYear.value.name} (${term})`
})

const classroomOptions = computed(() => {
  return classrooms.value.map(c => ({
    value: String(c.id),
    label: c.name
  }))
})

const typeOptions = computed(() => {
  if (props.activeTab === 'tugas') {
    return [
      { value: 'tugas_sekolah', label: 'Tugas Sekolah' },
      { value: 'tugas_rumah', label: 'Tugas Rumah' }
    ]
  } else {
    return [
      { value: 'ujian_harian', label: 'Ujian Harian' },
      { value: 'uts', label: 'UTS' },
      { value: 'uas', label: 'UAS' }
    ]
  }
})

const schemaSections = computed(() => {
  const sections = []
  
  if (props.activeTab === 'materi') {
    sections.push({
      id: 'materi-info',
      title: 'Materi Pelajaran',
      fields: [
        {
          label: 'Pilih Kelas',
          key: 'classroom_id',
          select: true,
          options: classroomOptions.value
        },
        {
          label: 'Semester',
          key: 'semester_text',
          type: 'text',
          disabled: true,
          placeholder: semesterLabel.value
        },
        {
          label: 'Nama Materi',
          key: 'title',
          placeholder: 'Masukkan Nama Materi'
        },
        {
          label: 'Unggah Berkas',
          key: 'file',
          file: true,
          accept: '.pdf,.ppt,.pptx'
        }
      ]
    })
  } else {
    const fields = [
      {
        label: 'Pilih Kelas',
        key: 'classroom_id',
        select: true,
        options: classroomOptions.value
      },
      {
        label: 'Semester',
        key: 'semester_text',
        type: 'text',
        disabled: true,
        placeholder: semesterLabel.value
      },
      {
        label: 'Tipe Penilaian',
        key: 'type',
        select: true,
        options: typeOptions.value
      }
    ]

    const type = form.value.type
    const isUtsUas = type === 'uts' || type === 'uas'

    if (!isUtsUas) {
      fields.push({
        label: 'Materi Pelajaran',
        key: 'material_id',
        select: true,
        options: materialOptions.value
      })

      fields.push({
        label: 'Judul Penilaian',
        key: 'title',
        placeholder: 'Masukkan Judul ' + props.activeTabLabel
      })
    }

    fields.push({
      label: 'Daftar Siswa & Nilai',
      key: 'scores'
    })

    sections.push({
      id: 'assessment-info',
      title: props.activeTabLabel,
      fields
    })
  }
  
  return sections
})

const getInitials = (name) => {
  if (!name) return '?'
  const parts = String(name).trim().split(/\s+/)
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
}

// File Upload Pre-validation
const validateFile = (file) => {
  const allowedExtensions = ['pdf', 'ppt', 'pptx']
  const fileExt = file.name.split('.').pop().toLowerCase()
  const maxSize = 15 * 1024 * 1024 // 15MB

  if (!allowedExtensions.includes(fileExt)) {
    toast.error('Format berkas ditolak.', {
      description: 'Hanya dokumen berformat PDF, PPT, atau PPTX yang diperbolehkan.'
    })
    return false
  }

  if (file.size > maxSize) {
    toast.error('Berkas terlalu besar.', {
      description: `Ukuran berkas ${(file.size / (1024 * 1024)).toFixed(1)}MB melebihi batas maksimal 15MB.`
    })
    return false
  }

  return true
}

// Watch custom change from FormSheet file input component
const handleFormChange = (newVal) => {
  if (newVal.file && newVal.file !== form.value.file) {
    if (!validateFile(newVal.file)) {
      newVal.file = null
      newVal.file_fileName = ''
    }
  }
  form.value = { ...form.value, ...newVal }
}

// --- Save Action ---
const handleSave = () => {
  if (!form.value.classroom_id) {
    toast.error('Silakan pilih kelas terlebih dahulu')
    return
  }

  if (props.activeTab === 'materi') {
    if (!form.value.title || !form.value.title.trim()) {
      toast.error('Nama materi wajib diisi.')
      return
    }
    if (props.mode === 'add' && !form.value.file) {
      toast.error('Silakan pilih berkas materi terlebih dahulu.')
      return
    }
  } else {
    if (!form.value.type) {
      toast.error('Silakan tentukan tipe penilaian.')
      return
    }
    const isUtsUas = form.value.type === 'uts' || form.value.type === 'uas'
    if (!isUtsUas) {
      if (!form.value.material_id) {
        toast.error('Silakan pilih materi pelajaran.')
        return
      }
      if (!form.value.title || !form.value.title.trim()) {
        toast.error('Judul penilaian tidak boleh kosong.')
        return
      }
    }
  }

  // Convert empty values to 0 for grade numbers
  const finalScores = {}
  students.value.forEach(student => {
    const sVal = form.value.scores[student.id]
    finalScores[student.id] = (sVal === '' || sVal === undefined) ? 0 : Number(sVal)
  })

  skipDraftSave.value = true
  emit('save', {
    classroom_id: form.value.classroom_id,
    type: form.value.type,
    title: form.value.title,
    material_id: form.value.material_id || null,
    file: form.value.file,
    scores: finalScores
  })
}

const handleCancel = () => {
  skipDraftSave.value = true
  emit('cancel')
  isOpen.value = false
}
</script>

<template>
  <FormSheet
    v-model:open="isOpen"
    :item="form"
    :avatar-key="false"
    :title="mode === 'add' ? 'Tambah ' + activeTabLabel + ' Baru' : mode === 'edit' ? 'Edit ' + activeTabLabel : 'Detail ' + activeTabLabel"
    :description="mode === 'view' ? 'Melihat rincian informasi dan berkas' : 'Isi form berikut untuk mengelola data pelajaran dan berkas'"
    :sections="schemaSections"
    :disabled="mode === 'view'"
    @change="handleFormChange"
  >
    <!-- Override custom field for scores -->
    <template #field-scores="{ form: localForm }">
      <!-- List Siswa dan Input Nilai -->
      <div v-if="localForm.classroom_id" class="space-y-4 pt-2 border-t border-border/60">
        <div class="flex items-center justify-between">
          <label class="text-xs font-bold text-muted-foreground uppercase tracking-wider">Daftar Siswa & Nilai</label>
          <Badge variant="secondary" class="text-[10px] font-semibold">
            {{ students.length }} Siswa
          </Badge>
        </div>
        
        <div class="space-y-2.5 max-h-[350px] overflow-y-auto pr-1">
          <div
            v-for="student in students"
            :key="student.id"
            class="flex items-center justify-between p-3 rounded-xl border border-border/40 bg-background/40 backdrop-blur-sm"
          >
            <div class="flex items-center gap-3">
              <Avatar class="size-8 border border-border/50">
                <AvatarFallback class="text-xs font-bold bg-muted/60 text-muted-foreground">
                  {{ getInitials(student.name) }}
                </AvatarFallback>
              </Avatar>
              <span class="text-sm font-semibold text-foreground truncate max-w-[220px]">
                {{ student.name }}
              </span>
            </div>

            <div class="flex items-center gap-2">
              <span class="text-xs font-medium text-muted-foreground">Nilai:</span>
              <Input
                type="number"
                min="0"
                max="100"
                step="0.01"
                v-model.number="localForm.scores[student.id]"
                :disabled="mode === 'view'"
                class="w-20 h-8 text-center text-sm font-bold bg-background/60"
                placeholder="0"
              />
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-8 text-sm text-muted-foreground italic border border-dashed rounded-xl">
        Pilih kelas terlebih dahulu untuk melihat daftar siswa.
      </div>
    </template>

    <!-- Override actions slot to use custom buttons -->
    <template #actions="{ submit }">
      <Button
        v-if="mode === 'view'"
        variant="outline"
        size="sm"
        class="h-9 px-4 rounded-xl"
        @click="handleCancel"
      >
        Tutup
      </Button>
      <Button
        v-else
        size="sm"
        class="h-9 px-5 rounded-xl bg-primary text-primary-foreground font-semibold"
        @click="submit"
      >
        Simpan
      </Button>
    </template>
  </FormSheet>
</template>
