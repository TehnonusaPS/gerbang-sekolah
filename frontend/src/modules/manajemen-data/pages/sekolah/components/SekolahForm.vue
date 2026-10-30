<script setup>
import { computed } from 'vue'
import { 
  Phone, 
  Image,
  School,
  Scale,
  Globe,
  MapPin,
  Sparkles,
  UserCog
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
  errors: { type: Object, default: () => ({}) }
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
        description="Unggah identitas visual logo unit sekolah"
        :icon="Image"
      >
        <ImageUpload
          :preview="computedPreview"
          @change="handleImageChange"
          note="Format: JPG atau PNG. Maksimal 2MB. Dimensi rekomendasi 1:1"
          :error="errors.logo"
        />
      </FormSection>

      <!-- 2. Informasi Kontak Resmi Sekolah -->
      <FormSection
        title="Kontak Resmi Sekolah"
        description="Informasi email, telepon kantor, website, dan media sosial resmi unit sekolah"
        :icon="Phone"
      >
        <div class="space-y-4">
          <FormInput
            v-model="form.email"
            label="E-mail Resmi Sekolah"
            placeholder="Contoh: info@sdnpbekasi.sch.id"
            :error="errors.email"
          />
          <FormInput
            v-model="form.no_hp"
            label="No. Telepon Sekolah"
            placeholder="Contoh: (021) 87654321"
            :error="errors.phone"
          />
          <FormInput
            v-model="form.website"
            label="Website Resmi Sekolah"
            placeholder="Contoh: https://sdnpbekasi.sch.id"
            :error="errors.website"
          />
          <FormInput
            v-model="form.instagram"
            label="Akun Instagram"
            placeholder="Contoh: @sd_nusantara_bekasi"
            :error="errors.instagram"
          />
          <FormInput
            v-model="form.facebook"
            label="Halaman Facebook"
            placeholder="Contoh: @sd_nusantara_bekasi"
            :error="errors.facebook"
          />
        </div>
      </FormSection>
    </template>

    <!-- ================= KOLOM KANAN (65% LEBAR) ================= -->
    <template #right>
      <!-- 1. Informasi Dasar Sekolah -->
      <FormSection
        title="Informasi Dasar Sekolah"
        description="Rincian nama sekolah, NPSN, naungan yayasan, jenjang, dan kurikulum"
        :icon="School"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.nama"
            label="Nama Unit Sekolah"
            placeholder="Contoh: SD Nusantara Pintar Bekasi"
            :error="errors.name"
            required
          />
          <FormInput
            v-model="form.npsn"
            label="NPSN"
            placeholder="Contoh: 20100001"
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
            :error="errors.foundation_id"
          />
          <FormSelect
            v-model="form.jenjang"
            label="Jenjang Pendidikan"
            placeholder="Pilih jenjang (SD / SMP)"
            :options="jenjangOptions"
            :error="errors.level"
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <FormDate
            v-model="form.tanggal_berdiri"
            label="Tanggal Berdiri"
            :error="errors.established_date"
          />
          <FormSelect
            v-model="form.status"
            label="Status Operasional"
            placeholder="Pilih status sekolah"
            :options="statusOptions"
            :error="errors.status"
          />
        </div>

        <div class="pt-1">
          <CurriculumSelect
            v-model="form.curriculum_id"
            mode="school"
            :level-filter="form.jenjang"
            label="Jenis Kurikulum Sekolah"
            hint="Pilihan kurikulum spesifik jenjang (SD / SMP) disesuaikan dengan Kurikulum Yayasan Induk."
            :error="errors.curriculum_id"
          />
        </div>

        <Separator class="my-2 bg-border/60 dark:bg-zinc-800" />

        <FormTextArea
          v-model="form.alamat"
          label="Alamat Lengkap Sekolah"
          placeholder="Contoh: Jl. Raya Bekasi No. 45, Kel. Harapan Mulya, Kec. Medan Satria, Kota Bekasi"
          :rows="3"
          :error="errors.address"
        />
      </FormSection>

      <!-- 2. Dokumen Legalitas & Akreditasi -->
      <FormSection
        title="Dokumen Legalitas & Akreditasi"
        description="Informasi SK Pendirian, Izin Operasional, dan Sertifikat Akreditasi"
        :icon="Scale"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.no_sk"
            label="No. SK Pendirian"
            placeholder="Contoh: SK-002/SDNP/2012"
            :error="errors.decree_number"
          />
          <FormDate
            v-model="form.tanggal_sk"
            label="Tanggal SK Pendirian"
            :error="errors.decree_date"
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.no_izin"
            label="No. Izin Operasional"
            placeholder="Contoh: IZIN-001/2012"
            :error="errors.permit_number"
          />
          <FormDate
            v-model="form.tanggal_izin"
            label="Tanggal Izin Operasional"
            :error="errors.permit_date"
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <FormSelect
            v-model="form.akreditasi"
            label="Peringkat Akreditasi"
            placeholder="Pilih akreditasi"
            :options="akreditasi"
            :error="errors.accreditation"
          />
          <FormDate
            v-model="form.tanggal_akreditasi"
            label="Tanggal Akreditasi"
            :error="errors.accreditation_date"
          />
        </div>

        <FormInput
          v-model="form.no_akreditasi"
          label="No. SK Akreditasi"
          placeholder="Contoh: AKR-2023-0001"
          :error="errors.accreditation_number"
        />
      </FormSection>

      <!-- 3. Akun Administrator Login -->
      <FormSection
        title="Akun Administrator Login"
        description="Data E-mail dan No. HP yang digunakan administrator sekolah untuk login ke sistem"
        :icon="UserCog"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.emailLogin"
            label="E-mail Login Administrator"
            placeholder="Contoh: admin@sdnpbekasi.sch.id"
            :error="errors.emailLogin || errors.email"
            required
          />

          <FormInput
            v-model="form.noHpLogin"
            label="No. HP Login Administrator"
            placeholder="Contoh: 081234567890"
            :error="errors.noHpLogin || errors.phone"
            required
          />
        </div>
      </FormSection>
    </template>
  </BentoFormLayout>
</template>
