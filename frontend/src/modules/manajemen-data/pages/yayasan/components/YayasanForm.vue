<script setup>
import { computed } from 'vue'
import { Phone, Image, Building2, FileText, UserCog, Lock } from 'lucide-vue-next'
import ImageUpload from '@/components/forms/ImageUpload.vue'
import FormInput from '@/components/forms/FormInput.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import FormDate from '@/components/forms/FormDate.vue'
import FormSection from '@/components/forms/FormSection.vue'
import FormSelect from '@/components/forms/FormSelect.vue'
import BentoFormLayout from '@/components/forms/BentoFormLayout.vue'
import { Separator } from '@/components/ui/separator'
import { getPhotoUrl } from '@/utils/getPhotoUrl'

const props = defineProps({
  form: { type: Object, required: true },
  statusOptions: { type: Array, default: () => [] },
  imagePreview: { type: String, default: '' },
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
      <!-- 1. Logo Yayasan -->
      <FormSection
        title="Logo Yayasan"
        :description="isReadOnlyInstitutional ? 'Identitas visual resmi logo yayasan (Read-Only)' : 'Unggah identitas visual resmi logo yayasan'"
        :icon="Image"
        :required="!isReadOnlyInstitutional"
      >
        <ImageUpload
          :preview="computedPreview"
          fit="contain"
          :disabled="isReadOnlyInstitutional"
          :note="isReadOnlyInstitutional ? 'Logo resmi yayasan induk dikelola melalui menu Manajemen Data' : 'Format berkas: JPG, JPEG, PNG, atau WEBP. Maksimal 2 MB. Dimensi rekomendasi 1:1.'"
          :error="errors.logo"
          @change="handleImageChange"
        />
      </FormSection>

      <!-- 2. Akun Administrator (Login) -->
      <FormSection
        title="Akun Administrator Login"
        description="Data kredensial administrator yayasan untuk login ke sistem"
        :icon="UserCog"
      >
        <div class="space-y-4">
          <FormInput
            v-model="form.emailLogin"
            type="email"
            label="E-mail Login Administrator"
            placeholder="Contoh: admin@yayasan.or.id"
            :disabled="isReadOnlyInstitutional"
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
      <!-- 1. Informasi Dasar Yayasan -->
      <FormSection
        title="Informasi Dasar Yayasan"
        :description="isReadOnlyInstitutional ? 'Data profil, status operasional, dan domisili kantor yayasan (Read-Only)' : 'Rincian identitas yayasan, legalitas pendirian, dan status operasional'"
        :icon="Building2"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.nama"
            label="Nama Yayasan"
            placeholder="Contoh: Yayasan Pendidikan Indonesia Maju"
            :disabled="isReadOnlyInstitutional"
            :error="errors.nama"
            required
          />

          <FormSelect
            v-model="form.status"
            label="Status Operasional"
            placeholder="Pilih status"
            :options="statusOptions"
            :disabled="isReadOnlyInstitutional"
            :error="errors.status"
            required
          />

          <FormDate
            v-model="form.tanggal_berdiri"
            label="Tanggal Berdiri Yayasan"
            :disabled="isReadOnlyInstitutional"
            :error="errors.tanggal_berdiri"
            required
            class="md:col-span-2"
          />
        </div>

        <Separator class="my-2" />

        <FormTextArea
          v-model="form.alamat"
          label="Alamat Lengkap Kantor Yayasan"
          placeholder="Contoh: Jl. Sudirman No. 45, Kebayoran Baru, Jakarta Selatan"
          :rows="3"
          :disabled="isReadOnlyInstitutional"
          :error="errors.alamat"
          required
        />
      </FormSection>

      <!-- 2. Dokumen Legalitas Yayasan -->
      <FormSection
        title="Dokumen Legalitas Yayasan"
        :description="isReadOnlyInstitutional ? 'Nomor Akta Pendirian dan SK Kemenkumham resmi yayasan (Read-Only)' : 'Rincian Nomor Akta Pendirian dan SK Kemenkumham resmi yayasan'"
        :icon="FileText"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.no_akta"
            label="No. Akta Notaris / Pendirian"
            placeholder="Contoh: 12 Tanggal 15 Januari 2020"
            :disabled="isReadOnlyInstitutional"
            :error="errors.no_akta"
            required
          />

          <FormDate
            v-model="form.tanggal_akta"
            label="Tanggal Akta Notaris"
            :disabled="isReadOnlyInstitutional"
            :error="errors.tanggal_akta"
            required
          />

          <FormInput
            v-model="form.no_sk"
            label="No. SK Kemenkumham"
            placeholder="Contoh: AHU-0023456.AH.01.04.Tahun 2021"
            :disabled="isReadOnlyInstitutional"
            :error="errors.no_sk"
            required
          />

          <FormDate
            v-model="form.tanggal_sk"
            label="Tanggal SK Kemenkumham"
            :disabled="isReadOnlyInstitutional"
            :error="errors.tanggal_sk"
            required
          />
        </div>
      </FormSection>

      <!-- 3. Informasi Kontak Resmi Yayasan -->
      <FormSection
        title="Kontak Resmi Yayasan"
        :description="isReadOnlyInstitutional ? 'Informasi email, telepon kantor, dan website resmi yayasan (Read-Only)' : 'Informasi email, telepon kantor, dan website resmi yayasan'"
        :icon="Phone"
      >
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput
            v-model="form.email"
            type="email"
            label="E-mail Resmi Yayasan"
            placeholder="Contoh: info@yayasan.or.id"
            :disabled="isReadOnlyInstitutional"
            :error="errors.email"
          />

          <FormInput
            v-model="form.no_hp"
            label="No. Telepon Kantor"
            placeholder="Contoh: 021-12345678"
            numeric-only
            :maxlength="15"
            :disabled="isReadOnlyInstitutional"
            :error="errors.no_hp"
          />

          <FormInput
            v-model="form.website"
            label="Website Resmi"
            placeholder="Contoh: https://yayasan.or.id"
            :disabled="isReadOnlyInstitutional"
            :error="errors.website"
            class="md:col-span-2"
          />
        </div>
      </FormSection>
    </template>
  </BentoFormLayout>
</template>
