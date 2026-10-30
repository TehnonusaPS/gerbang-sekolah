<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from 'vue-sonner'

import PageHeader from '@/components/page-header/PageHeader.vue'
import { Button } from '@/components/ui/button'
import { Save, User, Users } from 'lucide-vue-next'

import FormSection from '@/components/forms/FormSection.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import FormInput from '@/components/forms/FormInput.vue'
import FormSelect from '@/components/forms/FormSelect.vue'
import FormDate from '@/components/forms/FormDate.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import ImageUpload from '@/components/forms/ImageUpload.vue'

const route = useRoute()
const router = useRouter()

const dataList = [
  { id: 1, nama: 'Andi Pratama',  nisn: '0123456789', nik: '3174012345678901', noKk: '3174098765432101' },
  { id: 2, nama: 'Siti Aminah',   nisn: '0123456790', nik: '3174012345678902', noKk: '3174098765432102' },
  { id: 3, nama: 'Budi Santoso',  nisn: '0123456791', nik: '3174012345678903', noKk: '3174098765432103' },
]

const studentId = Number(route.query.id) || 1
const studentData = dataList.find(s => s.id === studentId) || dataList[0]

const form = ref({
  namaLengkap: studentData.nama,
  nisn: studentData.nisn,
  nik: studentData.nik,
  noKk: studentData.noKk,
  jenjang: 'SMA',
  agama: 'Islam',
  tempatLahir: 'Jakarta',
  tanggalLahir: '2008-05-14',
  jenisKelamin: 'L',
  alamat: 'Jl. Merpati No. 42, Kebayoran Baru, Jakarta Selatan, 12110',
  namaAyah: 'Budi Sulianto',
  pekerjaanAyah: 'Pegawai Swasta',
  namaIbu: 'Siti Aminah',
  pekerjaanIbu: 'Ibu Rumah Tangga',
  noHp: '81234567890',
  email: 'fajar.sulianto@gmail.com',
  foto: null
})

const previewUrl = ref('https://lh3.googleusercontent.com/aida/AP1WRLsvU1cMyl-boCd7O9tCEp0h8QLxhj02U4pmVKGG6AFia7mCMkwD2zO56Mum1C0VBM9EQT1TkwOaGn0JwUyzLbnVMJ31B1X0gvxJHk6rc2LuwiLvAiBgwyYxV8bHSWyFsr31vxxn_jlN2Bxb2r4qSWb-qI5vuLGUoHsw9sMLOZtf0JkyWOhjnvwAOUrfs53_PA69WgopaCIFu1Oimoq0rdGQ4bJnplPcJXRAbpeBYYdV-NWKA7eAolUKmA')

const jenjangOptions = [
  { value: 'SD', label: 'SD' },
  { value: 'SMP', label: 'SMP' },
  { value: 'SMA', label: 'SMA' },
  { value: 'SMK', label: 'SMK' }
]

const agamaOptions = [
  { value: 'Islam', label: 'Islam' },
  { value: 'Kristen', label: 'Kristen' },
  { value: 'Katolik', label: 'Katolik' },
  { value: 'Hindu', label: 'Hindu' },
  { value: 'Buddha', label: 'Buddha' }
]

const jkOptions = [
  { value: 'L', label: 'Laki-laki' },
  { value: 'P', label: 'Perempuan' }
]

const isLoading = ref(false)

const batal = () => {
  router.push('/administrasi/loket')
}

const simpanPerubahan = () => {
  isLoading.value = true
  setTimeout(() => {
    isLoading.value = false
    toast.success('Data pendaftaran berhasil diperbarui')
    router.push('/administrasi/loket')
  }, 1500)
}
</script>

<template>
  <div class="flex-1 space-y-8 relative z-10 w-full p-2 sm:p-4">
    <PageHeader
      title="Edit Data Siswa"
      description="Perbarui informasi registrasi calon peserta didik"
      :back="true"
      @back="batal"
      class="mb-8"
    />

    <div class="grid grid-cols-1 gap-6 pb-20 md:pb-0">
      
      <FormSection title="Data Pribadi Siswa" :icon="User">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
          <div class="md:col-span-4">
            <ImageUpload 
              label="Foto Calon Siswa"
              note="JPG/PNG (Maks. 1MB). Latar merah/biru."
              :preview="previewUrl"
              @change="form.foto = $event"
            />
          </div>
          
          <div class="md:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <FormInput class="md:col-span-3" v-model="form.namaLengkap" label="Nama Lengkap Sesuai Akta" />
            <FormInput v-model="form.nisn" label="NISN" />
            <FormInput v-model="form.nik" label="NIK" />
            <FormInput v-model="form.noKk" label="Nomor KK" />
            <FormSelect v-model="form.jenjang" label="Jenjang Pendidikan" :options="jenjangOptions" />
          </div>
          
          <FormSelect class="md:col-span-3" v-model="form.agama" label="Agama" :options="agamaOptions" />
          <FormInput class="md:col-span-3" v-model="form.tempatLahir" label="Tempat Lahir" />
          <FormDate class="md:col-span-3" v-model="form.tanggalLahir" label="Tanggal Lahir" />
          <FormSelect class="md:col-span-3" v-model="form.jenisKelamin" label="Jenis Kelamin" :options="jkOptions" />
          
          <FormTextArea class="md:col-span-12" v-model="form.alamat" label="Alamat Domisili Lengkap" :rows="2" />
        </div>
      </FormSection>

      <FormSection title="Data Orang Tua / Wali" :icon="Users">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <FormInput v-model="form.namaAyah" label="Nama Ayah Kandung" />
          <FormInput v-model="form.pekerjaanAyah" label="Pekerjaan Ayah" />
          <FormInput v-model="form.noHp" label="Nomor Telepon/WA" type="tel" />
          
          <FormInput v-model="form.namaIbu" label="Nama Ibu Kandung" />
          <FormInput v-model="form.pekerjaanIbu" label="Pekerjaan Ibu" />
          <FormInput v-model="form.email" label="Email Aktif" type="email" />
        </div>
      </FormSection>

      <FormFooter
        :loading="isLoading"
        is-edit
        entity-name="Pendaftaran Siswa"
        @save="simpanPerubahan"
      />
    </div>
  </div>
</template>
