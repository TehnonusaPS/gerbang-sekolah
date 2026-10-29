<script setup>
import { Phone, Image, Building2, FileText, UserCog } from 'lucide-vue-next'
import ImageUpload from '@/components/forms/ImageUpload.vue'
import FormInput from '@/components/forms/FormInput.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import FormDate from '@/components/forms/FormDate.vue'
import FormSection from '@/components/forms/FormSection.vue'
import FormSelect from '@/components/forms/FormSelect.vue'

const props = defineProps({
  form: { type: Object, required: true },
  statusOptions: { type: Array, default: () => [] },
  imagePreview: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['image-change'])

const handleImageChange = (file) => {
  emit('image-change', file)
}
</script>

<template>
  <div class="grid gap-6 md:grid-cols-3 text-left">
    <!-- Kolom Kiri: Logo, Kontak & Akun Login (Lebar 1 Kolom) -->
    <div class="space-y-6">
      <!-- Logo Yayasan -->
      <FormSection
        title="Logo Yayasan"
        description="Unggah identitas visual logo yayasan"
        :icon="Image"
        required
      >
        <ImageUpload
          :preview="imagePreview"
          @change="handleImageChange"
          note="Format berkas: JPG, JPEG, PNG, atau WEBP. Ukuran maksimal 2 MB. Dimensi rekomendasi 1:1."
          :error="errors.logo"
        />
      </FormSection>

      <!-- Informasi Kontak Resmi -->
      <FormSection
        title="Kontak Resmi Yayasan"
        description="Informasi email, telepon kantor, dan website resmi yayasan"
        :icon="Phone"
      >
        <FormInput
          v-model="form.email"
          label="E-mail Resmi Yayasan"
          placeholder="Contoh: info@yayasan.or.id"
          :error="errors.email"
        />

        <FormInput
          v-model="form.no_hp"
          label="No. Telepon Kantor"
          placeholder="Contoh: (021) 1234567"
          :error="errors.phone"
        />

        <FormInput
          v-model="form.website"
          label="Website Resmi"
          placeholder="Contoh: https://yayasan.or.id"
          :error="errors.website"
        />
      </FormSection>

      <!-- Akun Administrator (Login) -->
    </div>

    <!-- Kolom Kanan: Informasi Umum & Legalitas Yayasan (Lebar 2 Kolom) -->
    <div class="md:col-span-2 space-y-6">
      <!-- Informasi Dasar -->
      <FormSection
        title="Informasi Dasar Yayasan"
        description="Data utama profil, status operasional, dan alamat domisili yayasan"
        :icon="Building2"
      >
        <FormInput
          v-model="form.nama"
          label="Nama Lengkap Yayasan"
          placeholder="Contoh: Yayasan Pendidikan Nusantara"
          :error="errors.name"
          required
        />

        <div class="grid gap-4 md:grid-cols-2">
          <FormDate
            v-model="form.tanggal_berdiri"
            label="Tanggal Berdiri"
            :error="errors.established_date"
            required
          />
          <FormSelect
            v-model="form.status"
            label="Status Operasional"
            placeholder="Pilih status yayasan"
            :options="statusOptions"
            :error="errors.status"
            required
          />
        </div>

        <FormTextArea
          v-model="form.alamat"
          label="Alamat Lengkap Yayasan"
          placeholder="Contoh: Jl. Pendidikan No. 123, RT 01/RW 05, Kel. Kayu Manis, Kec. Matraman, Kota Jakarta Timur"
          :rows="3"
          :error="errors.address"
          required
        />
      </FormSection>

      <!-- Dokumen Legalitas -->
      <FormSection
        title="Dokumen Legalitas Yayasan"
        description="Rincian Nomor Akta Pendirian dan SK Kemenkumham yayasan"
        :icon="FileText"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.no_akta"
            label="No. Akta Pendirian"
            placeholder="Contoh: AHU-0012345.AH.01.04"
            :error="errors.deed_number"
            required
          />
          <FormDate
            v-model="form.tanggal_akta"
            label="Tanggal Akta Pendirian"
            :error="errors.deed_date"
            required
          />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.no_sk"
            label="No. SK Kemenkumham"
            placeholder="Contoh: SK-001/YPN/2010"
            :error="errors.decree_number"
            required
          />
          <FormDate
            v-model="form.tanggal_sk"
            label="Tanggal SK Kemenkumham"
            :error="errors.decree_date"
            required
          />
        </div>
      </FormSection>

      <FormSection
        title="Akun Administrator Login"
        description="Data E-mail yang digunakan administrator yayasan untuk login ke sistem"
        :icon="UserCog"
      >
        <FormInput
          v-model="form.emailLogin"
          label="E-mail Login Administrator"
          placeholder="Contoh: admin@yayasan.or.id"
          :error="errors.emailLogin"
          required
        />
      </FormSection>
    </div>
  </div>
</template>
