<script setup>
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger
} from '@/components/ui/accordion'
import FormInput from '@/components/forms/FormInput.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import FormSelect from '@/components/forms/FormSelect.vue'

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
  }
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
        <AccordionTrigger class="text-sm font-semibold">
          Informasi Ekstrakulikuler
        </AccordionTrigger>
        <AccordionContent class="space-y-4 pt-3">
          <div class="grid gap-4 grid-cols-1">
            <FormInput
              v-model="form.nama"
              label="Nama Ekstrakulikuler"
              placeholder="Contoh: Pramuka, Futsal, Paduan Suara"
              :error="errors.nama"
              required
            />
            <FormSelect
              v-model="form.advisor_id"
              label="Guru Pembina"
              placeholder="Pilih guru pembina dari sekolah (opsional)"
              searchable
              search-placeholder="Cari nama guru atau NIP..."
              :error="errors.advisor_id"
              :options="teacherOptions"
              action-label="Tambah Guru Baru"
              action-to="/manajemen-data/guru-staff/tambah"
            />
            <FormInput
              v-model="form.coach_name"
              label="Pelatih / Instruktur Luar"
              placeholder="Contoh: Coach Hendra (opsional jika pembina dari luar)"
              :error="errors.coach_name"
            />
            <FormTextArea
              v-model="form.deskripsi"
              label="Deskripsi"
              placeholder="Tuliskan penjelasan singkat mengenai kegiatan ekstrakulikuler ini (opsional)..."
              :error="errors.deskripsi"
              :rows="4"
            />
            <FormSelect
              v-model="form.status"
              label="Status"
              placeholder="Pilih status"
              :error="errors.status"
              :options="[
                { label: 'Aktif', value: 'aktif' },
                { label: 'Nonaktif', value: 'nonaktif' }
              ]"
              required
            />
          </div>
        </AccordionContent>
      </AccordionItem>
    </Accordion>
  </div>
</template>
