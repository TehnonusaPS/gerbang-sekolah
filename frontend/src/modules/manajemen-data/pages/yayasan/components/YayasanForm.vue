<script setup>
import { computed } from 'vue'
import { Phone, Image, Building2, FileText, UserCog } from 'lucide-vue-next'
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
  statusOptions: { type: Array, default: () => [] },
  imagePreview: { type: String, default: '' },
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
      <!-- 1. Logo Yayasan -->
      <FormSection
        title="Logo Yayasan"
        description="Unggah identitas visual resmi logo yayasan"
        :icon="Image"
        required
      >
        <ImageUpload
          :preview="computedPreview"
          @change="handleImageChange"
          note="Format berkas: JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB. Dimensi rekomendasi 1:1."
          :error="errors.logo"
        />
      </FormSection>

      <!-- 2. Informasi Kontak Resmi Yayasan -->
      <FormSection
        title="Kontak Resmi Yayasan"
        description="Informasi email, telepon kantor, dan website resmi yayasan"
        :icon="Phone"
      >
        <div class="space-y-4">
          <FormInput
            v-model="form.email"
            type="email"
            label="E-mail Resmi Yayasan"
            placeholder="Contoh: info@yayasan.or.id"
            :error="errors.email"
          />

          <FormInput
            v-model="form.telepon"
            label="No. Telepon Kantor"
            placeholder="Contoh: 021-12345678"
            numeric-only
            :maxlength="15"
            :error="errors.telepon"
          />

          <FormInput
            v-model="form.website"
            label="Website Resmi"
            placeholder="Contoh: https://yayasan.or.id"
            :error="errors.website"
          />
        </div>
      </FormSection>
    </template>

    <!-- ================= KOLOM KANAN (65% LEBAR) ================= -->
    <template #right>
      <!-- 1. Informasi Dasar Yayasan -->
      <FormSection
        title="Informasi Dasar Yayasan"
        description="Rincian identitas yayasan, legalitas pendirian, dan status operasional"
        :icon="Building2"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.nama"
            label="Nama Yayasan"
            placeholder="Contoh: Yayasan Pendidikan Indonesia Maju"
            :error="errors.nama"
            required
          />

          <FormSelect
            v-model="form.status"
            label="Status Operasional"
            placeholder="Pilih status"
            :options="statusOptions"
            :error="errors.status"
            required
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <FormDate
            v-model="form.tanggal_berdiri"
            label="Tanggal Berdiri Yayasan"
            :error="errors.tanggal_berdiri"
            required
          />

          <FormInput
            v-model="form.ketua_yayasan"
            label="Nama Ketua / Pimpinan Yayasan"
            placeholder="Contoh: Prof. Dr. Budi Santoso"
            :error="errors.ketua_yayasan"
            required
          />
        </div>

        <FormTextArea
          v-model="form.alamat"
          label="Alamat Lengkap Kantor Yayasan"
          placeholder="Contoh: Jl. Sudirman No. 45, Kebayoran Baru, Jakarta Selatan"
          :rows="3"
          :error="errors.alamat"
          required
        />
      </FormSection>

      <!-- 2. Dokumen Legalitas Yayasan -->
      <FormSection
        title="Dokumen Legalitas Yayasan"
        description="Nomor SK Kemenkumham, NPWP, dan berkas legalitas resmi yayasan"
        :icon="FileText"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.nomor_akta"
            label="Nomor Akta Notaris"
            placeholder="Contoh: AHU-0012345.AH.01.04.Tahun 2020"
            :error="errors.nomor_akta"
            required
          />

          <FormDate
            v-model="form.tanggal_akta"
            label="Tanggal Akta Notaris"
            :error="errors.tanggal_akta"
            required
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.nomor_sk_kemenkumham"
            label="Nomor SK Kemenkumham"
            placeholder="Contoh: AHU-0023456.AH.01.04.Tahun 2021"
            :error="errors.nomor_sk_kemenkumham"
            required
          />

          <FormInput
            v-model="form.npwp"
            label="NPWP Yayasan"
            placeholder="16 Digit NPWP Resmi Yayasan"
            numeric-only
            :maxlength="16"
            :error="errors.npwp"
            required
          />
        </div>
      </FormSection>

      <!-- 3. Akun Administrator (Login) -->
      <FormSection
        title="Akun Administrator Login"
        description="Data kredensial administrator yayasan untuk login ke sistem"
        :icon="UserCog"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.emailLogin"
            type="email"
            label="E-mail Login Administrator"
            placeholder="Contoh: admin@yayasan.or.id"
            :error="errors.emailLogin"
            required
          />

          <FormInput
            v-model="form.noHpLogin"
            label="No. HP / WhatsApp (Opsional)"
            placeholder="Contoh: 081234567890"
            numeric-only
            :maxlength="15"
            :error="errors.noHpLogin"
          />
        </div>
      </FormSection>
    </template>
  </BentoFormLayout>
</template>
