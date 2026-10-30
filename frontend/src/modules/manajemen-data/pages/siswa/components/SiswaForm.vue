<script setup>
import { computed } from 'vue'
import { Phone, Image, UserCog, SquareUserRound, GraduationCap, ShieldUser, Lock } from 'lucide-vue-next'
import { Separator } from '@/components/ui/separator'
import ImageUpload from '@/components/forms/ImageUpload.vue'
import FormInput from '@/components/forms/FormInput.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import FormDate from '@/components/forms/FormDate.vue'
import FormSection from '@/components/forms/FormSection.vue'
import FormSelect from '@/components/forms/FormSelect.vue'
import BentoFormLayout from '@/components/forms/BentoFormLayout.vue'
import { getPhotoUrl } from '@/utils/getPhotoUrl'

const props = defineProps({
  form: { type: Object, required: true },
  imagePreview: { type: String, default: '' },
  statusOptions: { type: Array, default: () => [] },
  kelasOptions: { type: Array, default: () => [] },
  pekerjaanOptions: { type: Array, default: () => [] },
  agamaOptions: { type: Array, default: () => [] },
  kelaminOptions: { type: Array, default: () => [] },
  hubunganOptions: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  isReadOnlyAcademic: { type: Boolean, default: false },
  showPasswordFields: { type: Boolean, default: false },
  requireOldPassword: { type: Boolean, default: false }
})

const emit = defineEmits(['image-change'])

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
</script>

<template>
  <BentoFormLayout>
    <!-- ================= KOLOM KIRI (35% LEBAR) ================= -->
    <template #left>
      <!-- 1. Foto Siswa -->
      <FormSection title="Foto Profil" description="Upload foto profil siswa disini" :icon="Image">
        <ImageUpload
          :preview="computedPreview"
          @change="handleImageChange"
          note="Format: JPG atau PNG. Maksimal 10MB. Dimensi rasio 1:1"
          allow-camera
        />
      </FormSection>

      <!-- 2. Kontak Siswa -->
      <FormSection
        title="Kontak Siswa"
        description="Informasi kontak resmi siswa"
        :icon="Phone"
      >
        <div class="space-y-4">
          <FormInput
            v-model="form.email"
            label="E-mail"
            placeholder="Contoh: nama@sekolah.com"
            :error="errors.email"
          />
          <FormInput
            v-model="form.no_hp"
            label="No. Telepon / WhatsApp"
            placeholder="Contoh: 081234567890"
            numeric-only
            :maxlength="15"
            :error="errors.no_hp"
          />
        </div>
      </FormSection>

      <!-- 3. Informasi Akademik -->
      <FormSection
        title="Informasi Akademik"
        :description="isReadOnlyAcademic ? 'Informasi penempatan kelas dan status siswa (Read-Only)' : 'Informasi penempatan kelas dan status siswa'"
        :icon="GraduationCap"
      >
        <div class="space-y-4">
          <FormDate v-model="form.tahun_masuk" label="Tahun Masuk" :disabled="isReadOnlyAcademic" :error="errors.tahun_masuk"/>
          <FormSelect v-model="form.kelas" label="Kelas" placeholder="Pilih kelas" :options="kelasOptions" :disabled="isReadOnlyAcademic" :error="errors.kelas"/>
          <FormSelect v-model="form.status" label="Status" placeholder="Pilih status" :options="statusOptions" :disabled="isReadOnlyAcademic" :error="errors.status"/>
        </div>
      </FormSection>
    </template>

    <!-- ================= KOLOM KANAN (65% LEBAR) ================= -->
    <template #right>
      <!-- 1. Informasi Siswa -->
      <FormSection title="Informasi Siswa" description="Informasi identitas lengkap siswa" :icon="SquareUserRound">
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput v-model="form.nama_depan" label="Nama Depan" placeholder="Contoh: John" :error="errors.first_name"/>
          <FormInput v-model="form.nama_belakang" label="Nama Belakang" placeholder="Contoh: Doe"/>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput v-model="form.nik" label="NIK" placeholder="Contoh: 1234567890" numeric-only :maxlength="16" :error="errors.nik"/>
          <FormInput v-model="form.nisn" label="NISN" placeholder="Contoh: 1234567890" numeric-only :maxlength="15" :error="errors.nisn"/>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput v-model="form.tempat_lahir" label="Tempat Lahir" placeholder="Contoh: Jakarta" :error="errors.tempat_lahir"/>
          <FormDate v-model="form.tanggal_lahir" label="Tanggal Lahir" :error="errors.tanggal_lahir"/>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <FormSelect v-model="form.jenis_kelamin" label="Jenis Kelamin" placeholder="Pilih jenis kelamin" :options="kelaminOptions" :error="errors.jenis_kelamin"/>
          <FormSelect v-model="form.agama" label="Agama" placeholder="Pilih agama" :options="agamaOptions" :error="errors.agama"/>
        </div>  
        <Separator class="my-4" />
        <FormTextArea v-model="form.alamat" :rows="4" label="Alamat Lengkap" placeholder="Contoh: Nama Jalan, RT/RW, Kelurahan, Kecamatan, Kota" :error="errors.address"/>
      </FormSection>

      <!-- 2. Informasi Orang Tua / Wali -->
      <FormSection title="Informasi Orang Tua / Wali" description="Informasi kontak dan data orang tua / wali siswa" :icon="ShieldUser">
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput v-model="form.nama_wali" label="Nama Wali" placeholder="Contoh: John Doe" :error="errors.nama_wali"/>
          <FormSelect v-model="form.hubungan_siswa" label="Hubungan dengan Siswa" placeholder="Pilih hubungan" :options="hubunganOptions" :error="errors.hubungan_siswa"/>
        </div> 
        <div class="grid gap-4 md:grid-cols-2">
          <FormSelect v-model="form.kelamin_wali" label="Jenis Kelamin Wali" placeholder="Pilih jenis kelamin" :options="kelaminOptions" :error="errors.kelamin_wali"/>
          <FormSelect v-model="form.pekerjaan_wali" label="Pekerjaan Wali" placeholder="Pilih pekerjaan" :options="pekerjaanOptions"/>
        </div> 
        <Separator class="my-4" />
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput v-model="form.email_wali" label="E-mail Wali" placeholder="Contoh: john.doe@sekolah.com" :error="errors.email_wali"/>
          <FormInput v-model="form.no_hp_wali" label="No. HP Wali" placeholder="Contoh: 081234567890" numeric-only :maxlength="15" :error="errors.no_hp_wali"/>
        </div> 
      </FormSection>

      <!-- 3. Akun Pengguna Orang Tua/Wali -->
      <FormSection title="Akun Pengguna Orang Tua/Wali" description="Kredensial login orang tua/wali siswa ke sistem." :icon="UserCog">
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput v-model="form.emailLogin" label="E-mail Login" placeholder="Contoh: nama_ortu@gmail.com" :error="errors.emailLogin"/>
          <FormInput v-model="form.noHpLogin" label="No. HP Login" placeholder="Contoh: 081234567890" numeric-only :maxlength="15" :error="errors.noHpLogin"/>
        </div>

        <template v-if="showPasswordFields">
          <Separator class="my-3" />
          <div class="space-y-1 mb-3">
            <div class="flex items-center gap-1.5 text-xs font-semibold text-foreground tracking-tight">
              <Lock class="w-3.5 h-3.5 text-primary" />
              <span>Keamanan Kata Sandi Orang Tua/Wali</span>
            </div>
            <p class="text-[11px] text-muted-foreground leading-relaxed">
              Kosongkan bagian ini jika Anda tidak ingin mengganti kata sandi akun orang tua/wali.
            </p>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
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
          </div>
        </template>
      </FormSection>
    </template>
  </BentoFormLayout>
</template>
