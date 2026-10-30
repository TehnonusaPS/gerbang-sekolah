<script setup>
import { ref, watch, computed } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import FormFooter from '@/components/forms/FormFooter.vue'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { AlertCircle, Calendar, Clock, Trash2, BookOpen, UserCheck, Sparkles } from 'lucide-vue-next'
import { fetchAllSubjects } from '@/services/subjectService'
import { getTeachers } from '@/services/managementService'
import { createSchedule, updateSchedule, deleteSchedule, checkTeacherConflicts } from '@/services/scheduleService'

const props = defineProps({
  open: Boolean,
  isEditMode: Boolean,
  academicYearId: [String, Number],
  classroomId: [String, Number],
  selectedSlot: Object, // { time_slot_id, day_of_week, slot_label, day_name }
  editItem: Object // full Schedule object if editing
})

const emit = defineEmits(['update:open', 'saved', 'deleted'])

const subjects = ref([])
const teachers = ref([])
const formItem = ref({
  subject_id: '',
  teacher_id: ''
})
const conflictWarning = ref('')
const isCheckingConflict = ref(false)

async function loadFormOptions() {
  try {
    const [subRes, teachRes] = await Promise.all([
      fetchAllSubjects(),
      getTeachers()
    ])
    subjects.value = (subRes.data || []).filter(s => s.is_active)
    teachers.value = (teachRes.data || []).filter(t => 
      t.status?.toLowerCase() === 'aktif' || 
      t.status_aktif?.toLowerCase() === 'aktif' || 
      t.status_aktif === true
    )
  } catch (err) {
    console.error('Failed to load options', err)
  }
}

watch(() => props.open, (newVal) => {
  if (newVal) {
    loadFormOptions()
    if (props.isEditMode && props.editItem) {
      formItem.value = {
        subject_id: String(props.editItem.subject_id),
        teacher_id: String(props.editItem.teacher_id)
      }
    } else {
      formItem.value = {
        subject_id: '',
        teacher_id: ''
      }
    }
    conflictWarning.value = ''
  }
})

// Check teacher conflict when teacher or slot changes
async function checkConflict() {
  if (!formItem.value.teacher_id || !props.academicYearId || !props.selectedSlot) {
    conflictWarning.value = ''
    return
  }
  isCheckingConflict.value = true
  try {
    const res = await checkTeacherConflicts({
      academic_year_id: props.academicYearId,
      teacher_id: formItem.value.teacher_id,
      time_slot_id: props.selectedSlot.time_slot_id,
      day_of_week: props.selectedSlot.day_of_week,
      exclude_id: props.isEditMode ? props.editItem?.id : undefined
    })
    
    if (res.has_conflict) {
      const class_name = res.conflict?.classroom?.name || 'lain'
      const teacher_name = teachers.value.find(t => String(t.id) === String(formItem.value.teacher_id))?.nama || 'Guru'
      conflictWarning.value = `${teacher_name} sudah mengajar di Kelas ${class_name} pada hari dan jam yang sama.`
    } else {
      conflictWarning.value = ''
    }
  } catch (err) {
    console.error(err)
  } finally {
    isCheckingConflict.value = false
  }
}

watch(() => formItem.value.teacher_id, () => {
  checkConflict()
})

const isSaving = ref(false)
const saveSummary = computed(() => {
  const subjectName = subjects.value.find(s => String(s.id) === String(formItem.value.subject_id))?.name
  const teacherName = teachers.value.find(t => String(t.id) === String(formItem.value.teacher_id))?.nama
  return [
    { label: 'Hari & Sesi', value: `${props.selectedSlot?.day_name || ''} • ${props.selectedSlot?.slot_label || ''}` },
    { label: 'Mata Pelajaran', value: subjectName },
    { label: 'Guru Pengajar', value: teacherName }
  ]
})

function validateForm() {
  if (!formItem.value.subject_id || !formItem.value.teacher_id) {
    toast.error('Gagal Menyimpan', { description: 'Harap lengkapi mata pelajaran dan guru pengajar.' })
    return false
  }

  if (conflictWarning.value) {
    toast.error('Gagal Menyimpan', { description: 'Ada konflik jadwal untuk guru terpilih.' })
    return false
  }
  return true
}

async function handleSave() {
  const payload = {
    academic_year_id: props.academicYearId,
    classroom_id: props.classroomId,
    subject_id: formItem.value.subject_id,
    teacher_id: formItem.value.teacher_id,
    time_slot_id: props.selectedSlot.time_slot_id,
    day_of_week: props.selectedSlot.day_of_week
  }

  isSaving.value = true
  try {
    if (props.isEditMode && props.editItem) {
      await updateSchedule(props.editItem.id, payload)
      toast.success('Berhasil Diperbarui', { description: 'Jadwal pelajaran telah diperbarui.' })
    } else {
      await createSchedule(payload)
      toast.success('Berhasil Ditambahkan', { description: 'Jadwal pelajaran telah ditambahkan.' })
    }
    emit('update:open', false)
    emit('saved')
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal menyimpan jadwal pelajaran.'
    toast.error('Gagal Menyimpan', { description: errorMsg })
  } finally {
    isSaving.value = false
  }
}

async function handleDelete() {
  if (!props.editItem) return
  try {
    await deleteSchedule(props.editItem.id)
    toast.success('Berhasil Dihapus', { description: 'Jadwal pelajaran telah dihapus.' })
    emit('update:open', false)
    emit('deleted')
  } catch (err) {
    toast.error('Gagal menghapus jadwal pelajaran')
  }
}
</script>

<template>
  <Sheet :open="open" @update:open="emit('update:open', $event)">
    <SheetContent class="sm:max-w-[460px] flex flex-col h-full text-left p-6">
      
      <!-- Modal Header -->
      <SheetHeader class="border-b border-border/60 pb-3.5 text-left">
        <div class="flex items-center gap-2.5">
          <div class="p-2 rounded-xl bg-primary/10 text-primary shrink-0">
            <Sparkles class="size-4" />
          </div>
          <div>
            <SheetTitle class="text-base font-bold text-foreground">
              {{ isEditMode ? 'Edit Jadwal Pelajaran' : 'Tambah Jadwal Pelajaran' }}
            </SheetTitle>
            <SheetDescription class="text-xs text-muted-foreground mt-0.5">
              Atur alokasi mata pelajaran dan guru pengajar untuk slot sesi terpilih.
            </SheetDescription>
          </div>
        </div>
      </SheetHeader>

      <!-- Main Form Body -->
      <div class="flex-1 overflow-y-auto py-5 space-y-5 no-scrollbar">
        
        <!-- Target Info Card (Hari & Jam) -->
        <div class="p-3.5 bg-muted/30 dark:bg-zinc-900/50 rounded-2xl border border-border/70 text-left space-y-2.5 shadow-2xs">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
              <div class="p-1 rounded-lg bg-primary/10 text-primary">
                <Calendar class="size-3.5" />
              </div>
              <span class="font-medium">Hari Pembelajaran:</span>
            </div>
            <span class="text-xs font-extrabold text-foreground bg-background dark:bg-zinc-800 px-2.5 py-1 rounded-lg border border-border/60">
              {{ selectedSlot?.day_name }}
            </span>
          </div>

          <div class="flex items-center justify-between border-t border-border/40 pt-2.5">
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
              <div class="p-1 rounded-lg bg-primary/10 text-primary">
                <Clock class="size-3.5" />
              </div>
              <span class="font-medium">Sesi Waktu / Jam:</span>
            </div>
            <span class="text-xs font-bold text-foreground bg-background dark:bg-zinc-800 px-2.5 py-1 rounded-lg border border-border/60 font-mono">
              {{ selectedSlot?.slot_label }}
            </span>
          </div>
        </div>

        <!-- Form Container -->
        <div class="space-y-4">
          
          <!-- Subject Select Form (Full Width w-full) -->
          <div class="space-y-1.5 text-left w-full">
            <label class="text-xs font-bold text-foreground flex items-center gap-1.5">
              <BookOpen class="size-3.5 text-primary" />
              Mata Pelajaran <span class="text-rose-500">*</span>
            </label>
            
            <Select v-model="formItem.subject_id">
              <SelectTrigger class="w-full h-11 rounded-xl bg-background border-border/80 focus:ring-2 focus:ring-primary/20 text-xs font-semibold px-3.5">
                <SelectValue placeholder="Pilih Mata Pelajaran..." />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem
                  v-for="sub in subjects"
                  :key="sub.id"
                  :value="String(sub.id)"
                  class="text-xs font-semibold cursor-pointer py-2"
                >
                  <span class="font-bold text-foreground">{{ sub.name }}</span>
                  <span class="text-[10px] text-muted-foreground ml-1.5 font-mono">({{ sub.code }})</span>
                </SelectItem>
              </SelectContent>
            </Select>
            <p class="text-[10px] text-muted-foreground">Pilih mata pelajaran yang dialokasikan pada jam ini.</p>
          </div>

          <!-- Teacher Select Form (Full Width w-full) -->
          <div class="space-y-1.5 text-left w-full">
            <label class="text-xs font-bold text-foreground flex items-center gap-1.5">
              <UserCheck class="size-3.5 text-primary" />
              Guru Pengajar <span class="text-rose-500">*</span>
            </label>
            
            <Select v-model="formItem.teacher_id">
              <SelectTrigger class="w-full h-11 rounded-xl bg-background border-border/80 focus:ring-2 focus:ring-primary/20 text-xs font-semibold px-3.5">
                <SelectValue placeholder="Pilih Guru Pengajar..." />
              </SelectTrigger>
              <SelectContent class="rounded-xl">
                <SelectItem
                  v-for="t in teachers"
                  :key="t.id"
                  :value="String(t.id)"
                  class="text-xs font-semibold cursor-pointer py-2"
                >
                  {{ t.nama }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p class="text-[10px] text-muted-foreground">Pilih guru aktif yang bertugas mengajar di kelas ini.</p>
          </div>

        </div>

        <!-- Conflict Warning Alert -->
        <Alert v-if="conflictWarning" variant="destructive" class="rounded-2xl border-rose-500/80 bg-rose-500/10 text-rose-600 dark:text-rose-400 p-3.5">
          <div class="flex items-start gap-2.5">
            <AlertCircle class="h-4 w-4 text-rose-500 shrink-0 mt-0.5" />
            <AlertDescription class="text-xs font-bold leading-relaxed text-left">
              {{ conflictWarning }}
            </AlertDescription>
          </div>
        </Alert>

      </div>

      <!-- Footer Actions -->
      <div class="border-t border-border/60 pt-3.5 flex items-center justify-between shrink-0">
        <div>
          <Button
            v-if="isEditMode"
            type="button"
            variant="ghost"
            class="text-xs font-bold rounded-xl text-rose-500 hover:text-rose-600 hover:bg-rose-500/10 cursor-pointer h-9 px-3"
            @click="handleDelete"
          >
            <Trash2 class="size-3.5 mr-1.5" />
            Hapus Jadwal
          </Button>
        </div>

        <FormFooter
          :loading="isSaving"
          :disabled="isCheckingConflict"
          :is-edit="isEditMode"
          label="Simpan Jadwal"
          entity-name="Jadwal Pelajaran"
          :summary-items="saveSummary"
          :before-confirm="validateForm"
          @save="handleSave"
          class="border-none pt-0"
        />
      </div>

    </SheetContent>
  </Sheet>
</template>
