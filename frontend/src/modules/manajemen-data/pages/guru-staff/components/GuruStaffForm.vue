<script setup>
import { computed, watch } from 'vue'
import { Image, SquareUserRound, BriefcaseBusiness, UserCog, Lock } from 'lucide-vue-next'
import { Separator } from '@/components/ui/separator'
import ImageUpload from '@/components/forms/ImageUpload.vue'
import FormInput from '@/components/forms/FormInput.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import FormDate from '@/components/forms/FormDate.vue'
import FormSection from '@/components/forms/FormSection.vue'
import FormSelect from '@/components/forms/FormSelect.vue'
import FormMultiSelect from '@/components/forms/FormMultiSelect.vue'
import BentoFormLayout from '@/components/forms/BentoFormLayout.vue'
import { getPhotoUrl } from '@/utils/getPhotoUrl'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
  form: { type: Object, required: true },
  imagePreview: { type: String, default: '' },
  kelaminOptions: { type: Array, default: () => [] },
  agamaOptions: { type: Array, default: () => [] },
  statusPernikahanOptions: { type: Array, default: () => [] },
  pendidikanOptions: { type: Array, default: () => [] },
  jabatanOptions: { type: Array, default: () => [] },
  statusKepegawaianOptions: { type: Array, default: () => [] },
  unitKerjaOptions: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
  subjectOptions: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  isReadOnlyEmployment: { type: Boolean, default: false },
  isReadOnlyEmail: { type: Boolean, default: false },
  showPasswordFields: { type: Boolean, default: false },
  requireOldPassword: { type: Boolean, default: false }
})

const emit = defineEmits(['image-change'])
const auth = useAuthStore()

const isAdminSekolah = computed(() => auth.user?.role === 'admin_sekolah')

const filteredJabatanOptions = computed(() => {
  if (isAdminSekolah.value) {
    return props.jabatanOptions.filter(opt => !['J001', 'J002'].includes(opt.value))
  }
  return props.jabatanOptions
})

const computedPreview = computed(() => {
  if (props.imagePreview) return props.imagePreview
  const raw = props.form?.foto || props.form?.photo
  if (raw && typeof raw === 'string') {
    return getPhotoUrl(raw)
  }
  return ''
})

const handleImageChange = file => {
  emit('image-change', file)
}

const isTeacher = computed(() => {
  const jabatan = String(props.form.jabatan || '').toUpperCase()
  return jabatan === 'GURU' || jabatan === 'J004'
})

const selectedSchoolUnitIds = computed(() => {
  const units = Array.isArray(props.form.unit_kerja)
    ? props.form.unit_kerja
    : props.form.unit_kerja
      ? [props.form.unit_kerja]
      : []
  return units.map(u => String(u).toUpperCase()).filter(u => u.startsWith('S'))
})

const unitNameMap = computed(() => {
  const map = {}
  for (const opt of props.unitKerjaOptions) {
    map[String(opt.value).toUpperCase()] = opt.label
  }
  return map
})

const filteredSubjectOptions = computed(() => {
  if (isAdminSekolah.value) return props.subjectOptions
  const schoolUnits = selectedSchoolUnitIds.value
  if (schoolUnits.length === 0) return []
  const showSchoolLabel = schoolUnits.length > 1
  return props.subjectOptions
    .filter(opt => !opt.unit_id || schoolUnits.includes(String(opt.unit_id).toUpperCase()))
    .map(opt => {
      const unitLabel = opt.unit_id ? unitNameMap.value[String(opt.unit_id).toUpperCase()] : ''
      return {
        ...opt,
        label: showSchoolLabel && unitLabel ? `${opt.label} (${unitLabel})` : opt.label
      }
    })
})

watch(
  () => props.form.unit_kerja,
  () => {
    if (isAdminSekolah.value) return
    const schoolUnits = selectedSchoolUnitIds.value
    if (schoolUnits.length === 0) {
      props.form.subject_ids = []
    }
  },
  { deep: true }
)

watch(
  [selectedSchoolUnitIds, () => props.subjectOptions],
  ([, subjects]) => {
    if (isAdminSekolah.value || subjects.length === 0) return
    const validIds = new Set(filteredSubjectOptions.value.map(o => String(o.value)))
    const current = Array.isArray(props.form.subject_ids) ? props.form.subject_ids : []
    const next = current.filter(id => validIds.has(String(id)))
    if (next.length !== current.length) {
      props.form.subject_ids = next
    }
  }
)
</script>

<template>
  <BentoFormLayout>
    <!-- ================= KOLOM KIRI (35% LEBAR) ================= -->
    <template #left>
      <!-- 1. Foto Profil -->
      <FormSection title="Foto Profil (Opsional)" description="Upload foto profil formal guru atau staff" :icon="Image">
        <ImageUpload
          :preview="computedPreview"
          note="Format: JPG atau PNG. Maksimal 2MB. Rasio 1:1"
          :error="errors.foto"
          @change="handleImageChange"
        />
      </FormSection>

      <!-- 2. Akun Pengguna (Kredensial Login) -->
      <FormSection title="Akun Pengguna" description="Kredensial yang digunakan untuk login ke sistem" :icon="UserCog">
        <div class="space-y-4">
          <FormInput
            v-model="form.emailLogin"
            type="email"
            label="E-mail Login"
            placeholder="Contoh: nama@sekolah.sch.id"
            :disabled="isReadOnlyEmail"
            required
            :error="errors.emailLogin"
          />
          <FormInput
            v-model="form.noHpLogin"
            label="No. HP / WhatsApp (Opsional)"
            placeholder="Contoh: 081234567890"
            numeric-only
            :maxlength="15"
            :error="errors.noHpLogin"
          />

          <!-- Opsi Ganti Kata Sandi jika diaktifkan (Edit & Akun Setting) -->
          <template v-if="showPasswordFields">
            <Separator class="my-2" />
            <div class="space-y-1">
              <div class="flex items-center gap-1.5 text-xs font-semibold text-foreground tracking-tight">
                <Lock class="w-3.5 h-3.5 text-primary" />
                <span>Keamanan Kata Sandi</span>
              </div>
              <p class="text-[11px] text-muted-foreground leading-relaxed">
                Kosongkan bagian ini jika Anda tidak ingin mengganti kata sandi akun.
              </p>
            </div>

            <FormInput
              v-if="requireOldPassword"
              v-model="form.password_lama"
              type="password"
              label="Password Lama"
              placeholder="Masukkan password saat ini"
              :error="errors.password_lama"
            />

            <FormInput
              v-model="form.password_baru"
              type="password"
              label="Password Baru"
              placeholder="Minimal 8 karakter"
              :error="errors.password_baru"
            />

            <FormInput
              v-model="form.password_baru_confirmation"
              type="password"
              label="Konfirmasi Password Baru"
              placeholder="Ulangi password baru"
              :error="errors.password_baru_confirmation"
            />
          </template>
        </div>
      </FormSection>
    </template>

    <!-- ================= KOLOM KANAN (65% LEBAR) ================= -->
    <template #right>
      <!-- 1. Informasi Pribadi -->
      <FormSection title="Informasi Pribadi" description="Identitas diri, data kelahiran, kualifikasi pendidikan, dan domisili" :icon="SquareUserRound">
        <!-- Grup A: Nama & Gelar -->
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.nama_depan"
            label="Nama Depan"
            placeholder="Contoh: Ahmad"
            required
            :error="errors.nama_depan"
          />
          <FormInput
            v-model="form.nama_belakang"
            label="Nama Belakang (Opsional)"
            placeholder="Contoh: Fauzi"
            :error="errors.nama_belakang"
          />
          <FormInput
            v-model="form.gelar_depan"
            label="Gelar Depan (Opsional)"
            placeholder="Contoh: Drs."
            :error="errors.gelar_depan"
          />
          <FormInput
            v-model="form.gelar_belakang"
            label="Gelar Belakang (Opsional)"
            placeholder="Contoh: S.Pd., M.Pd."
            :error="errors.gelar_belakang"
          />
        </div>

        <Separator class="my-2" />

        <!-- Grup B: Identitas Kependudukan & Kelahiran -->
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.nik"
            label="NIK (Nomor Induk Kependudukan)"
            placeholder="16 digit NIK sesuai KTP"
            numeric-only
            :maxlength="16"
            required
            :error="errors.nik"
          />
          <FormSelect
            v-model="form.jenis_kelamin"
            label="Jenis Kelamin"
            placeholder="Pilih jenis kelamin"
            :options="kelaminOptions"
            required
            :error="errors.jenis_kelamin"
          />
          <FormInput
            v-model="form.tempat_lahir"
            label="Tempat Lahir"
            placeholder="Contoh: Jakarta"
            required
            :error="errors.tempat_lahir"
          />
          <FormDate
            v-model="form.tanggal_lahir"
            label="Tanggal Lahir"
            required
            :error="errors.tanggal_lahir"
          />
          <FormSelect
            v-model="form.agama"
            label="Agama"
            placeholder="Pilih agama"
            :options="agamaOptions"
            required
            :error="errors.agama"
          />
          <FormSelect
            v-model="form.status_pernikahan"
            label="Status Pernikahan"
            placeholder="Pilih status pernikahan"
            :options="statusPernikahanOptions"
            required
            :error="errors.status_pernikahan"
          />
        </div>

        <Separator class="my-2" />

        <!-- Grup C: Kualifikasi Pendidikan & Domisili -->
        <div class="grid gap-4 md:grid-cols-1">
          <FormSelect
            v-model="form.pendidikan_terakhir"
            label="Pendidikan Terakhir"
            placeholder="Pilih jenjang pendidikan terakhir"
            :options="pendidikanOptions"
            required
            :error="errors.pendidikan_terakhir"
          />
          <FormTextArea
            v-model="form.alamat"
            :rows="3"
            label="Alamat Lengkap"
            placeholder="Contoh: Jl. Pendidikan No. 10, RT 01/RW 02, Kel. Sukamaju, Kec. Cimandiri, Kota Bandung"
            required
            :error="errors.alamat"
          />
        </div>
      </FormSection>

      <!-- 2. Informasi Kepegawaian -->
      <FormSection
        title="Informasi Kepegawaian"
        :description="isReadOnlyEmployment ? 'Penempatan unit kerja, jabatan, dan status ikatan kerja (Read-Only)' : 'Penempatan unit kerja, jabatan, status ikatan kerja, dan nomor induk pegawai'"
        :icon="BriefcaseBusiness"
      >
        <!-- Grup A: Penempatan & Jabatan -->
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-if="isReadOnlyEmployment"
            :model-value="Array.isArray(form.unit_kerja) ? form.unit_kerja.join(', ') : (form.unit_kerja || form.unit_kerja_label || '-')"
            label="Unit Kerja"
            disabled
          />
          <FormMultiSelect
            v-else-if="!isAdminSekolah"
            v-model="form.unit_kerja"
            label="Unit Kerja"
            placeholder="Pilih satu atau lebih unit kerja"
            :options="unitKerjaOptions"
            :error="errors.unit_kerja"
            item-name="unit kerja"
            searchable
            show-select-all
            required
          />
          <FormInput
            v-if="isReadOnlyEmployment"
            :model-value="form.jabatan || '-'"
            label="Jabatan"
            disabled
          />
          <FormSelect
            v-else
            v-model="form.jabatan"
            label="Jabatan"
            placeholder="Pilih jabatan"
            :options="filteredJabatanOptions"
            required
            :error="errors.jabatan"
          />
          <FormInput
            v-if="isReadOnlyEmployment"
            :model-value="form.status_kepegawaian || '-'"
            label="Status Kepegawaian"
            disabled
          />
          <FormSelect
            v-else-if="isAdminSekolah"
            v-model="form.status_kepegawaian"
            label="Status Kepegawaian"
            placeholder="Pilih status kepegawaian"
            :options="statusKepegawaianOptions"
            required
            :error="errors.status_kepegawaian"
          />
        </div>

        <!-- Penugasan Mata Pelajaran (Muncul tepat di bawah Jabatan jika memilih Guru) -->
        <div v-if="isTeacher && !isReadOnlyEmployment" class="grid gap-4 md:grid-cols-1">
          <FormMultiSelect
            v-model="form.subject_ids"
            label="Mata Pelajaran yang Diajarkan"
            :placeholder="!isAdminSekolah && selectedSchoolUnitIds.length === 0 ? 'Pilih unit kerja sekolah terlebih dahulu' : 'Pilih mata pelajaran yang diampu'"
            :disabled="!isAdminSekolah && selectedSchoolUnitIds.length === 0"
            :options="filteredSubjectOptions"
            :error="errors.subject_ids"
            item-name="mata pelajaran"
            searchable
            show-select-all
            required
          />
        </div>

        <Separator class="my-2" />

        <!-- Grup B: Status Kepegawaian, Tanggal Bergabung & Nomor Induk -->
        <div class="grid gap-4 md:grid-cols-2">
          <template v-if="!isReadOnlyEmployment">
            <FormSelect
              v-if="!isAdminSekolah"
              v-model="form.status_kepegawaian"
              label="Status Kepegawaian"
              placeholder="Pilih status kepegawaian"
              :options="statusKepegawaianOptions"
              required
              :error="errors.status_kepegawaian"
            />
            <FormSelect
              v-model="form.status_aktif"
              label="Status Aktif"
              placeholder="Pilih status aktif"
              :options="statusOptions"
              required
              :error="errors.status_aktif"
            />
            <FormDate
              v-model="form.join_date"
              label="Tanggal Bergabung"
              required
              :error="errors.join_date"
            />
          </template>
          <FormInput
            v-model="form.nip_nuptk"
            label="NIP / NUPTK"
            placeholder="Kosongkan jika belum memiliki NIP/NUPTK"
            :disabled="isReadOnlyEmployment"
            numeric-only
            :maxlength="18"
            :error="errors.nip_nuptk"
          />
        </div>
      </FormSection>
    </template>
  </BentoFormLayout>
</template>
