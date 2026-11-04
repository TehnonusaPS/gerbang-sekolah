<script setup>
import { ref, watch, computed } from 'vue'
import { toast } from 'vue-sonner'
import FormFooter from '@/components/forms/FormFooter.vue'
import { Input } from '@/components/ui/input'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Calendar, Sparkles } from 'lucide-vue-next'
import { createAcademicYear, updateAcademicYear } from '@/services/academicYearService'

const props = defineProps({
  open: Boolean,
  isEditMode: Boolean,
  editItem: Object
})

const emit = defineEmits(['update:open', 'saved'])

const formItem = ref({
  oddId: null,
  evenId: null,
  tahun: '',
  oddTanggalMulai: '',
  oddTanggalSelesai: '',
  evenTanggalMulai: '',
  evenTanggalSelesai: '',
  activeSemester: 'odd',
  status: 'nonaktif'
})

watch(() => props.open, (newVal) => {
  if (newVal) {
    formErrors.value = {}
    if (props.isEditMode && props.editItem) {
      formItem.value = {
        oddId: props.editItem.odd?.id || null,
        evenId: props.editItem.even?.id || null,
        tahun: props.editItem.tahun || '',
        oddTanggalMulai: props.editItem.odd?.tanggalMulai || '',
        oddTanggalSelesai: props.editItem.odd?.tanggalSelesai || '',
        evenTanggalMulai: props.editItem.even?.tanggalMulai || '',
        evenTanggalSelesai: props.editItem.even?.tanggalSelesai || '',
        activeSemester: 'odd',
        status: props.editItem.status || 'nonaktif'
      }
    } else {
      formItem.value = {
        oddId: null,
        evenId: null,
        tahun: '',
        oddTanggalMulai: '',
        oddTanggalSelesai: '',
        evenTanggalMulai: '',
        evenTanggalSelesai: '',
        activeSemester: 'odd',
        status: 'nonaktif'
      }
    }
  }
})

function validateForm() {
  const errors = {}
  if (!formItem.value.tahun?.trim()) {
    errors.tahun = 'Tahun ajaran wajib diisi.'
  } else if (!/^\d{4}\/\d{4}$/.test(formItem.value.tahun.trim())) {
    errors.tahun = 'Format harus YYYY/YYYY (contoh: 2025/2026).'
  }

  if (!formItem.value.oddTanggalMulai) errors.oddTanggalMulai = 'Tanggal mulai ganjil wajib diisi.'
  if (!formItem.value.oddTanggalSelesai) errors.oddTanggalSelesai = 'Tanggal selesai ganjil wajib diisi.'
  if (formItem.value.oddTanggalMulai && formItem.value.oddTanggalSelesai) {
    if (new Date(formItem.value.oddTanggalMulai) >= new Date(formItem.value.oddTanggalSelesai)) {
      errors.oddTanggalSelesai = 'Tanggal selesai ganjil harus setelah tanggal mulai.'
    }
  }

  if (!formItem.value.evenTanggalMulai) errors.evenTanggalMulai = 'Tanggal mulai genap wajib diisi.'
  if (!formItem.value.evenTanggalSelesai) errors.evenTanggalSelesai = 'Tanggal selesai genap wajib diisi.'
  if (formItem.value.evenTanggalMulai && formItem.value.evenTanggalSelesai) {
    if (new Date(formItem.value.evenTanggalMulai) >= new Date(formItem.value.evenTanggalSelesai)) {
      errors.evenTanggalSelesai = 'Tanggal selesai genap harus setelah tanggal mulai.'
    }
  }

  formErrors.value = errors
  if (Object.keys(errors).length > 0) {
    toast.error('Gagal Menyimpan', { description: 'Harap periksa kembali isian formulir Anda.' })
    return false
  }
  return true
}

const isSaving = ref(false)
const saveSummary = computed(() => [
  { label: 'Tahun Ajaran', value: formItem.value.tahun },
  { label: 'Semester Ganjil', value: `${formItem.value.oddTanggalMulai || '-'} s/d ${formItem.value.oddTanggalSelesai || '-'}` },
  { label: 'Semester Genap', value: `${formItem.value.evenTanggalMulai || '-'} s/d ${formItem.value.evenTanggalSelesai || '-'}` }
])

async function handleSave() {
  isSaving.value = true
  try {
    const trimmedYear = formItem.value.tahun.trim()

    if (props.isEditMode) {
      const updatePromises = []

      if (formItem.value.oddId) {
        updatePromises.push(
          updateAcademicYear(formItem.value.oddId, {
            name: trimmedYear,
            start_date: formItem.value.oddTanggalMulai,
            end_date: formItem.value.oddTanggalSelesai
          })
        )
      }

      if (formItem.value.evenId) {
        updatePromises.push(
          updateAcademicYear(formItem.value.evenId, {
            name: trimmedYear,
            start_date: formItem.value.evenTanggalMulai,
            end_date: formItem.value.evenTanggalSelesai
          })
        )
      }

      await Promise.all(updatePromises)
      toast.success('Berhasil Diperbarui', { description: `Tahun ajaran "${trimmedYear}" telah diperbarui.` })
    } else {
      const payload = {
        name: trimmedYear,
        odd_start_date: formItem.value.oddTanggalMulai,
        odd_end_date: formItem.value.oddTanggalSelesai,
        even_start_date: formItem.value.evenTanggalMulai,
        even_end_date: formItem.value.evenTanggalSelesai,
        active_semester: formItem.value.activeSemester
      }
      await createAcademicYear(payload)
      toast.success('Berhasil Ditambahkan', { description: `Tahun ajaran "${trimmedYear}" (Ganjil & Genap) telah ditambahkan.` })
    }
    emit('update:open', false)
    emit('saved')
  } catch (err) {
    toast.error('Gagal menyimpan tahun ajaran', { description: err.response?.data?.message || err.message })
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <Sheet :open="open" @update:open="emit('update:open', $event)">
    <SheetContent class="sm:max-w-[480px] flex flex-col h-full gap-2 text-left p-6">
      <!-- Header -->
      <SheetHeader class="border-b border-border/60 pb-3 text-left">
        <div class="flex items-center gap-2.5">
          <div class="p-2 rounded-xl bg-primary/10 text-primary shrink-0">
            <Sparkles class="size-4" />
          </div>
          <div>
            <SheetTitle class="text-base font-bold text-foreground">
              {{ isEditMode ? 'Edit Tahun Ajaran' : 'Tambah Tahun Ajaran' }}
            </SheetTitle>
            <SheetDescription class="text-xs text-muted-foreground mt-0.5">
              {{ isEditMode ? 'Perbarui detail data tahun ajaran sekolah.' : 'Tambahkan periode tahun ajaran baru (Ganjil & Genap) ke sistem.' }}
            </SheetDescription>
          </div>
        </div>
      </SheetHeader>

      <!-- Form Body -->
      <div class="flex-1 overflow-y-auto py-5 pr-1 space-y-4 no-scrollbar">
        <!-- Tahun Ajaran Input -->
        <div class="space-y-1.5 w-full">
          <label class="text-xs font-bold text-foreground flex items-center gap-1.5">
            <Calendar class="size-3.5 text-primary" />
            Tahun Ajaran <span class="text-rose-500">*</span>
          </label>
          <Input
            v-model="formItem.tahun"
            placeholder="Contoh: 2025/2026"
            class="h-10 rounded-xl bg-background border-border/80 text-xs font-semibold px-3.5"
            :class="formErrors.tahun ? 'border-rose-500' : ''"
          />
          <p v-if="formErrors.tahun" class="text-[10px] text-rose-500">{{ formErrors.tahun }}</p>
        </div>

        <!-- Semester Ganjil Section -->
        <div class="p-3.5 rounded-2xl border border-border/70 bg-muted/20 space-y-3">
          <span class="text-xs font-bold text-foreground flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full bg-primary inline-block"></span>
            Semester Ganjil
          </span>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[11px] font-semibold text-muted-foreground">Tanggal Mulai <span class="text-rose-500">*</span></label>
              <Input
                type="date"
                v-model="formItem.oddTanggalMulai"
                class="h-10 text-xs rounded-xl"
                :class="formErrors.oddTanggalMulai ? 'border-rose-500' : ''"
              />
              <p v-if="formErrors.oddTanggalMulai" class="text-[9px] text-rose-500">{{ formErrors.oddTanggalMulai }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-[11px] font-semibold text-muted-foreground">Tanggal Selesai <span class="text-rose-500">*</span></label>
              <Input
                type="date"
                v-model="formItem.oddTanggalSelesai"
                class="h-10 text-xs rounded-xl"
                :class="formErrors.oddTanggalSelesai ? 'border-rose-500' : ''"
              />
              <p v-if="formErrors.oddTanggalSelesai" class="text-[9px] text-rose-500">{{ formErrors.oddTanggalSelesai }}</p>
            </div>
          </div>
        </div>

        <!-- Semester Genap Section -->
        <div class="p-3.5 rounded-2xl border border-border/70 bg-muted/20 space-y-3">
          <span class="text-xs font-bold text-foreground flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 inline-block"></span>
            Semester Genap
          </span>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="text-[11px] font-semibold text-muted-foreground">Tanggal Mulai <span class="text-rose-500">*</span></label>
              <Input
                type="date"
                v-model="formItem.evenTanggalMulai"
                class="h-10 text-xs rounded-xl"
                :class="formErrors.evenTanggalMulai ? 'border-rose-500' : ''"
              />
              <p v-if="formErrors.evenTanggalMulai" class="text-[9px] text-rose-500">{{ formErrors.evenTanggalMulai }}</p>
            </div>
            <div class="space-y-1">
              <label class="text-[11px] font-semibold text-muted-foreground">Tanggal Selesai <span class="text-rose-500">*</span></label>
              <Input
                type="date"
                v-model="formItem.evenTanggalSelesai"
                class="h-10 text-xs rounded-xl"
                :class="formErrors.evenTanggalSelesai ? 'border-rose-500' : ''"
              />
              <p v-if="formErrors.evenTanggalSelesai" class="text-[9px] text-rose-500">{{ formErrors.evenTanggalSelesai }}</p>
            </div>
          </div>
        </div>

        <!-- Active Semester Selector (Hanya saat Tambah Baru) -->
        <div v-if="!isEditMode" class="space-y-1.5 w-full">
          <label class="text-xs font-bold text-foreground">Semester Aktif Pertama <span class="text-rose-500">*</span></label>
          <Select v-model="formItem.activeSemester">
            <SelectTrigger class="w-full h-10 rounded-xl bg-background border-border/80 text-xs font-semibold px-3.5">
              <SelectValue placeholder="Pilih Semester Aktif..." />
            </SelectTrigger>
            <SelectContent class="rounded-xl">
              <SelectItem value="odd" class="text-xs font-semibold">Semester Ganjil (Aktif)</SelectItem>
              <SelectItem value="even" class="text-xs font-semibold">Semester Genap (Aktif)</SelectItem>
              <SelectItem value="none" class="text-xs font-semibold">Nonaktifkan Keduanya</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <!-- Footer -->
      <FormFooter
        :loading="isSaving"
        :is-edit="isEditMode"
        entity-name="Tahun Ajaran"
        :summary-items="saveSummary"
        :before-confirm="validateForm"
        @save="handleSave"
      />
    </SheetContent>
  </Sheet>
</template>
