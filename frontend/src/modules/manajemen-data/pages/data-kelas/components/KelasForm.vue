<script setup>
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger
} from '@/components/ui/accordion'
import FormInput from '@/components/forms/FormInput.vue'
import FormSelect from '@/components/forms/FormSelect.vue'
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { Plus } from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()

defineProps({
  form: {
    type: Object,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  teacherOptions: {
    type: Array,
    default: () => []
  },
  academicYearOptions: {
    type: Array,
    default: () => []
  }
})

defineEmits(['create-academic-year'])

const schoolLevel = computed(() => auth.user?.school?.level)

const tingkatOptions = computed(() => {
  const jenjang = schoolLevel.value?.toUpperCase()

  // TK
  if (jenjang === 'JP001' || jenjang === 'TK') {
    return [
      { label: 'TK A', value: 'TK A' },
      { label: 'TK B', value: 'TK B' }
    ]
  }

  // SD
  if (jenjang === 'JP002' || jenjang === 'SD') {
    return Array.from({ length: 6 }, (_, index) => ({
      label: `Kelas ${index + 1}`,
      value: String(index + 1)
    }))
  }

  // SMP
  if (jenjang === 'JP003' || jenjang === 'SMP') {
    return Array.from({ length: 3 }, (_, index) => ({
      label: `Kelas ${index + 7}`,
      value: String(index + 7)
    }))
  }

  return []
})

const isSMA = computed(() => {
  const jenjang = schoolLevel.value?.toUpperCase()

  return jenjang === 'SMA' || jenjang === 'JP004' || jenjang === 'SMK' || jenjang === 'JP005'
})
</script>

<template>
  <div class="w-full space-y-4 text-left">
    <Accordion
      type="multiple"
      class="w-full"
      :default-value="['info']"
    >
      <AccordionItem value="info">
        <AccordionTrigger class="text-sm font-semibold"> Informasi Kelas </AccordionTrigger>
        <AccordionContent class="space-y-4 pt-3">
          <div class="grid gap-4 grid-cols-1">
            <FormSelect
              v-model="form.academic_year_id"
              label="Tahun Ajaran"
              placeholder="Pilih tahun ajaran"
              :options="academicYearOptions"
              :error="errors.academic_year_id"
              required
            />

            <FormInput
              v-model="form.name"
              label="Nama Kelas"
              placeholder="Contoh: X MIPA 1"
              :error="errors.name"
              required
            />

            <div :class="isSMA ? 'grid grid-cols-2 gap-4' : 'grid grid-cols-1 gap-4'">
              <FormSelect
                v-model="form.grade"
                label="Tingkat"
                placeholder="Pilih tingkat"
                :error="errors.grade"
                :options="tingkatOptions"
                required
              />

              <FormInput
                v-if="isSMA"
                v-model="form.major"
                label="Jurusan/Program"
                placeholder="Contoh: MIPA, IPS, dll"
                :error="errors.major"
              />
            </div>

            <div>
              <FormSelect
                v-model="form.homeroom_teacher_id"
                label="Wali Kelas"
                placeholder="Pilih wali kelas"
                :options="teacherOptions"
                :error="errors.homeroom_teacher_id"
              />
              <div class="flex items-center justify-between text-xs text-muted-foreground px-0.5 mt-1.5">
                <span v-if="!form.homeroom_teacher_id || form.homeroom_teacher_id === 'none'">Belum ada wali kelas terpilih</span>
                <span v-else class="text-emerald-600 dark:text-emerald-400 font-medium">Wali kelas terpilih</span>
                <button
                  type="button"
                  class="inline-flex items-center gap-1 font-medium text-primary hover:underline cursor-pointer"
                  @click="router.push('/manajemen-data/guru-staff/tambah')"
                >
                  <Plus class="size-3.5" />
                  <span>Tambah Guru Baru</span>
                </button>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <FormInput
                v-model="form.room"
                label="Ruangan"
                placeholder="Contoh: R. 101"
                :error="errors.room"
              />
              <FormInput
                v-model.number="form.capacity"
                type="number"
                label="Kapasitas Siswa"
                placeholder="Contoh: 36"
                :error="errors.capacity"
                required
              />
            </div>

            <FormSelect
              v-model="form.status"
              label="Status"
              placeholder="Pilih status"
              :error="errors.status"
              :options="[
                { label: 'Aktif', value: 'active' },
                { label: 'Penuh', value: 'full' },
                { label: 'Belum Ada Wali', value: 'no_teacher' }
              ]"
              required
            />
          </div>
        </AccordionContent>
      </AccordionItem>
    </Accordion>
  </div>
</template>
