<script setup>
import { computed } from 'vue'
import { 
  Phone, 
  Image,
  School,
  Scale,
  Globe,
  UserCog,
  Lock
} from 'lucide-vue-next'
import { Separator } from '@/components/ui/separator'
import ImageUpload from '@/components/forms/ImageUpload.vue'
import FormInput from '@/components/forms/FormInput.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import FormDate from '@/components/forms/FormDate.vue'
import FormSection from '@/components/forms/FormSection.vue'
import FormSelect from '@/components/forms/FormSelect.vue'
import CurriculumSelect from '@/components/curriculum/CurriculumSelect.vue'
import BentoFormLayout from '@/components/forms/BentoFormLayout.vue'
import { getPhotoUrl } from '@/utils/getPhotoUrl'

const props = defineProps({
  form: { type: Object, required: true },
  imagePreview: { type: String, default: '' },
  foundationOptions: { type: Array, default: () => [] },
  jenjangOptions: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
  akreditasi: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  isReadOnlyInstitutional: { type: Boolean, default: false },
  showPasswordFields: { type: Boolean, default: false },
  requireOldPassword: { type: Boolean, default: false }
})

const emit = defineEmits(['image-change'])

const computedPreview = computed(() => {
  if (props.imagePreview) return props.imagePreview
  if (props.form?.logo && typeof props.form.logo === 'string') {
    return getPhotoUrl(props.form.logo)
  }
  return ''
})

const handleImageChange = (file) => {
  emit('image-change', file)
}
</script>

<template>
  <BentoFormLayout>
    <!-- ================= KOLOM KIRI (35% LEBAR) ================= -->
    <template #left>
      <!-- 1. Logo Sekolah -->
      <FormSection
        title="Logo Sekolah"
        :description="isReadOnlyInstitutional ? 'Identitas visual resmi unit sekolah (Read-Only)' : 'Unggah identitas visual logo unit sekolah'"
        :icon="Image"
      >
        <ImageUpload
          :preview="computedPreview"
          fit="contain"
          :disabled="isReadOnlyInstitutional"
          :note="isReadOnlyInstitutional ? 'Logo resmi unit sekolah dikelola melalui menu Manajemen Data' : 'Format: JPG atau PNG. Maksimal 2MB. Dimensi rekomendasi 1:1'"
          :error="errors.logo"
          @change="handleImageChange"
        />
      </FormSection>

      <!-- 2. Akun Administrator Login -->
      <FormSection
        title="Akun Administrator Login"
        description="Data kredensial administrator sekolah untuk login ke sistem"
        :icon="UserCog"
      >
        <div class="space-y-4">
          <FormInput
            v-model="form.emailLogin"
            type="email"
            label="E-mail Login Administrator"
            placeholder="Contoh: admin@sdnpbekasi.sch.id"
            :disabled="isReadOnlyInstitutional"
            :error="errors.emailLogin || errors.email"
            required
          />

          <FormInput
            v-model="form.noHpLogin"
            label="No. HP / WhatsApp (Opsional)"
            placeholder="Contoh: 081234567890"
            numeric-only
            :maxlength="15"
            :error="errors.noHpLogin || errors.phone"
          />

          <!-- Opsi Ganti Kata Sandi jika diaktifkan (Edit Manajemen Data & Pengaturan Akun) -->
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
      <!-- 1. Informasi Dasar Sekolah -->
      <FormSection
        title="Informasi Dasar Sekolah"
        :description="isReadOnlyInstitutional ? 'Rincian nama sekolah, NPSN, jenjang, dan kurikulum (Read-Only)' : 'Rincian nama sekolah, NPSN, naungan yayasan, jenjang, dan kurikulum'"
        :icon="School"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.nama"
            label="Nama Unit Sekolah"
            placeholder="Contoh: SD Nusantara Pintar Bekasi"
            :disabled="isReadOnlyInstitutional"
            :error="errors.name || errors.nama"
            required
          />
          <FormInput
            v-model="form.npsn"
            label="NPSN"
            placeholder="Contoh: 20100001"
            :disabled="isReadOnlyInstitutional"
            :error="errors.npsn"
            required
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <FormSelect
            v-model="form.yayasan"
            label="Yayasan Induk"
            placeholder="Pilih yayasan naungan"
            :options="foundationOptions"
            :disabled="isReadOnlyInstitutional"
            :error="errors.foundation_id || errors.yayasan"
          />
          <FormSelect
            v-model="form.jenjang"
            label="Jenjang Pendidikan"
            placeholder="Pilih jenjang (SD / SMP)"
            :options="jenjangOptions"
            :disabled="isReadOnlyInstitutional"
            :error="errors.level || errors.jenjang"
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <FormDate
            v-model="form.tanggal_berdiri"
            label="Tanggal Berdiri"
            :disabled="isReadOnlyInstitutional"
            :error="errors.established_date || errors.tanggal_berdiri"
          />
          <FormSelect
            v-model="form.status"
            label="Status Operasional"
            placeholder="Pilih status sekolah"
            :options="statusOptions"
            :disabled="isReadOnlyInstitutional"
            :error="errors.status"
          />
        </div>

        <div class="pt-1">
          <CurriculumSelect
            v-model="form.curriculum_id"
            mode="school"
            :level-filter="form.jenjang"
            label="Jenis Kurikulum Sekolah"
            hint="Pilihan kurikulum disesuaikan dengan Kurikulum Yayasan Induk."
            :disabled="isReadOnlyInstitutional"
            :error="errors.curriculum_id"
          />
        </div>

        <Separator class="my-2 bg-border/60 dark:bg-zinc-800" />

        <FormTextArea
          v-model="form.alamat"
          label="Alamat Lengkap Sekolah"
          placeholder="Contoh: Jl. Raya Bekasi No. 45, Kel. Harapan Mulya, Kec. Medan Satria, Kota Bekasi"
          :rows="3"
          :disabled="isReadOnlyInstitutional"
          :error="errors.address || errors.alamat"
        />
      </FormSection>

      <!-- 2. Dokumen Legalitas & Akreditasi -->
      <FormSection
        title="Dokumen Legalitas & Akreditasi"
        :description="isReadOnlyInstitutional ? 'Informasi SK Pendirian, Izin Operasional, dan Akreditasi (Read-Only)' : 'Informasi SK Pendirian, Izin Operasional, dan Sertifikat Akreditasi'"
        :icon="Scale"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.no_sk"
            label="No. SK Pendirian"
            placeholder="Contoh: SK-002/SDNP/2012"
            :disabled="isReadOnlyInstitutional"
            :error="errors.decree_number || errors.no_sk"
          />
          <FormDate
            v-model="form.tanggal_sk"
            label="Tanggal SK Pendirian"
            :disabled="isReadOnlyInstitutional"
            :error="errors.decree_date || errors.tanggal_sk"
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.no_izin"
            label="No. Izin Operasional"
            placeholder="Contoh: IZIN-001/2012"
            :disabled="isReadOnlyInstitutional"
            :error="errors.permit_number || errors.no_izin"
          />
          <FormDate
            v-model="form.tanggal_izin"
            label="Tanggal Izin Operasional"
            :disabled="isReadOnlyInstitutional"
            :error="errors.permit_date || errors.tanggal_izin"
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <FormSelect
            v-model="form.akreditasi"
            label="Peringkat Akreditasi"
            placeholder="Pilih akreditasi"
            :options="akreditasi"
            :disabled="isReadOnlyInstitutional"
            :error="errors.accreditation || errors.akreditasi"
          />
          <FormDate
            v-model="form.tanggal_akreditasi"
            label="Tanggal Akreditasi"
            :disabled="isReadOnlyInstitutional"
            :error="errors.accreditation_date || errors.tanggal_akreditasi"
          />
        </div>

        <FormInput
          v-model="form.no_akreditasi"
          label="No. SK Akreditasi"
          placeholder="Contoh: AKR-2023-0001"
          :disabled="isReadOnlyInstitutional"
          :error="errors.accreditation_number || errors.no_akreditasi"
        />
      </FormSection>

      <!-- 3. Informasi Kontak Resmi Sekolah -->
      <FormSection
        title="Kontak Resmi Sekolah"
        :description="isReadOnlyInstitutional ? 'Informasi email, telepon, website, dan medsos resmi sekolah (Read-Only)' : 'Informasi email, telepon kantor, website, dan media sosial resmi unit sekolah'"
        :icon="Phone"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.email"
            label="E-mail Resmi Sekolah"
            placeholder="Contoh: info@sdnpbekasi.sch.id"
            :disabled="isReadOnlyInstitutional"
            :error="errors.email"
          />
          <FormInput
            v-model="form.no_hp"
            label="No. Telepon Sekolah"
            placeholder="Contoh: (021) 87654321"
            :disabled="isReadOnlyInstitutional"
            :error="errors.phone || errors.no_hp"
          />
          <FormInput
            v-model="form.website"
            label="Website Resmi Sekolah"
            placeholder="Contoh: https://sdnpbekasi.sch.id"
            :disabled="isReadOnlyInstitutional"
            :error="errors.website"
            class="md:col-span-2"
          />
          <FormInput
            v-model="form.instagram"
            label="Akun Instagram"
            placeholder="Contoh: @sd_nusantara_bekasi"
            :disabled="isReadOnlyInstitutional"
            :error="errors.instagram"
          />
          <FormInput
            v-model="form.facebook"
            label="Halaman Facebook"
            placeholder="Contoh: @sd_nusantara_bekasi"
            :disabled="isReadOnlyInstitutional"
            :error="errors.facebook"
          />
        </div>
      </FormSection>
    </template>
  </BentoFormLayout>
</template>
