<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'

import PageHeader from '@/components/page-header/PageHeader.vue'
import { Save, Send, User, Users } from 'lucide-vue-next'

import FormSection from '@/components/forms/FormSection.vue'
import FormInput from '@/components/forms/FormInput.vue'
import FormSelect from '@/components/forms/FormSelect.vue'
import FormDate from '@/components/forms/FormDate.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import ImageUpload from '@/components/forms/ImageUpload.vue'

const router = useRouter()

const form = ref({
  namaLengkap: '',
  nisn: '',
  nik: '',
  noKk: '',
  jenjang: '',
  agama: '',
  tempatLahir: '',
  tanggalLahir: '',
  jenisKelamin: '',
  alamat: '',
  namaAyah: '',
  pekerjaanAyah: '',
  noHp: '',
  namaIbu: '',
  pekerjaanIbu: '',
  email: '',
  foto: null
})

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

const simpanDraf = () => {
  toast.success('Draf pendaftaran berhasil disimpan')
}

const submitPendaftaran = () => {
  toast.success('Pendaftaran berhasil disubmit')
  router.push('/administrasi/loket')
}
</script>

<template>
  <div class="flex-1 space-y-8 relative z-10 w-full p-2 sm:p-4">
    <PageHeader
      title="Pendaftaran Baru"
      description="Lengkapi formulir pendaftaran di bawah ini secara akurat. Data yang Anda masukkan akan diproses oleh tim administrasi Gerbang Sekolah."
      :back="true"
      @back="router.push('/administrasi/loket')"
      :actions="[
        { label: 'Simpan Draf', click: simpanDraf, variant: 'outline', icon: Save },
        { label: 'Submit Pendaftaran', click: submitPendaftaran, variant: 'default', icon: Send }
      ]"
      class="mb-8"
    />

    <div class="grid grid-cols-1 gap-6 pb-20 md:pb-0">
      
      <FormSection title="Data Pribadi Siswa" :icon="User">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
          <div class="md:col-span-4">
            <ImageUpload 
              label="Foto Calon Siswa"
              note="JPG/PNG (Maks. 1MB). Latar merah/biru."
              @change="form.foto = $event"
            />
          </div>
          
          <div class="md:col-span-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <FormInput class="md:col-span-3" v-model="form.namaLengkap" label="Nama Lengkap Sesuai Akta" placeholder="Contoh: Muhammad Budi Santoso" />
            <FormInput v-model="form.nisn" label="NISN" placeholder="Masukkan 10 digit NISN" />
            <FormInput v-model="form.nik" label="NIK" placeholder="Masukkan 16 digit NIK" />
            <FormInput v-model="form.noKk" label="Nomor KK" placeholder="Masukkan 16 digit No. KK" />
            <FormSelect v-model="form.jenjang" label="Jenjang Pendidikan" placeholder="Pilih Jenjang" :options="jenjangOptions" />
          </div>
          
          <FormSelect class="md:col-span-3" v-model="form.agama" label="Agama" placeholder="Pilih Agama" :options="agamaOptions" />
          <FormInput class="md:col-span-3" v-model="form.tempatLahir" label="Tempat Lahir" placeholder="Kota" />
          <FormDate class="md:col-span-3" v-model="form.tanggalLahir" label="Tanggal Lahir" />
          <FormSelect class="md:col-span-3" v-model="form.jenisKelamin" label="Jenis Kelamin" placeholder="Pilih" :options="jkOptions" />
          
          <FormTextArea class="md:col-span-12" v-model="form.alamat" label="Alamat Domisili Lengkap" placeholder="Nama Jalan, No. Rumah, RT/RW, Kecamatan, Kota" :rows="2" />
        </div>
      </FormSection>

      <FormSection title="Data Orang Tua / Wali" :icon="Users">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <FormInput v-model="form.namaAyah" label="Nama Ayah Kandung" placeholder="Nama Lengkap Ayah" />
          <FormInput v-model="form.pekerjaanAyah" label="Pekerjaan Ayah" placeholder="Pekerjaan" />
          <FormInput v-model="form.noHp" label="Nomor Telepon/WA" placeholder="0812xxxx" type="tel" />
          
          <FormInput v-model="form.namaIbu" label="Nama Ibu Kandung" placeholder="Nama Lengkap Ibu" />
          <FormInput v-model="form.pekerjaanIbu" label="Pekerjaan Ibu" placeholder="Pekerjaan" />
          <FormInput v-model="form.email" label="Email Aktif" placeholder="example@mail.com" type="email" />
        </div>
      </FormSection>
    </div>

    <!-- Bottom Action Mobile Only -->
    <div class="md:hidden fixed bottom-0 left-0 w-full p-4 bg-surface/90 backdrop-blur-xl border-t border-white/10 flex gap-4 z-50">
      <Button @click="simpanDraf" variant="outline" class="flex-1 h-11 rounded-lg border-white/10 text-on-surface font-bold text-sm">Draft</Button>
      <Button @click="submitPendaftaran" class="flex-[2] h-11 rounded-lg bg-primary text-primary-foreground font-bold text-sm">Submit Now</Button>
    </div>
  </div>
</template>
