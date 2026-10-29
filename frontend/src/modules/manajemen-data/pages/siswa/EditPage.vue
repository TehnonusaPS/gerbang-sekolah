<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { statusOptions, agamaOptions, kelaminOptions, pekerjaanOptions, hubunganOptions } from './data/siswa'
import { useAuthStore } from '@/stores/authStore'
import SiswaForm from './components/SiswaForm.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { defaultForm } from './data/defaultForm'
import { toast } from 'vue-sonner'
import { getClassrooms } from '@/services/managementService'
import { getSiswaDetail, updateSiswa } from '@/services/siswaService'
import { getPhotoUrl } from '@/utils/getPhotoUrl'

const auth = useAuthStore()
const isWaliKelas = computed(() => auth.user?.role === 'wali_kelas')

const route = useRoute()
const studentId = route.query.id

const activeClassrooms = ref([])
const form = ref({ ...defaultForm })

onMounted(async () => {
  try {
    const resClass = await getClassrooms()
    activeClassrooms.value = resClass.data
  } catch (err) {
    console.error('Gagal mengambil data kelas', err)
  }

  if (studentId) {
    try {
      const res = await getSiswaDetail(studentId)
      form.value = {
        ...res.data,
        tanggal_lahir: res.data.tanggal_lahir || '',
        tahun_masuk: res.data.tahun_masuk || ''
      }
      if (res.data.foto) {
        imagePreview.value = getPhotoUrl(res.data.foto)
      }
    } catch (err) {
      toast.error('Gagal memuat data detail siswa')
    }
  }
})

const classes = computed(() => {
  let list = activeClassrooms.value.map(c => ({ label: c.name, value: String(c.id) }))
  if (isWaliKelas.value) {
    return list.filter(item => item.value === String(auth.user?.kelasId))
  }
  return list
})

const router = useRouter()
const isLoading = ref(false)

const imagePreview = ref('')

const handleImage = (file) => {
  form.value.foto = file
  imagePreview.value = URL.createObjectURL(file)
  form.value.foto = file
}

const handleSubmit = async () => {
  isLoading.value = true

  let payload = {
    ...form.value,
    tanggal_lahir: form.value.tanggal_lahir || null,
    tahun_masuk: form.value.tahun_masuk || null
  }

  if (form.value.foto instanceof File) {
    const formData = new FormData()
    Object.keys(payload).forEach(key => {
      if (payload[key] !== null && payload[key] !== undefined && key !== 'foto') {
        formData.append(key, payload[key])
      }
    })
    formData.append('foto', form.value.foto)
    payload = formData
  }

  try {
    await updateSiswa(studentId, payload)
    toast.success('Data siswa berhasil diperbarui', {
      description: 'Perubahan data siswa telah berhasil disimpan.'
    })
    router.push('/manajemen-data/siswa')
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal memperbarui data siswa.'
    toast.error('Gagal Menyimpan', { description: errorMsg })
  } finally {
    isLoading.value = false
  }
}

const goToList = () => {
  router.push('/manajemen-data/siswa')
}
</script>

<template>
  <div class="space-y-6 p-1 pb-10">
    <!-- Header dengan Tombol Kembali -->
    <PageHeader
      back
      title="Edit Siswa"
      description="Lengkapi formulir berikut untuk mengedit data siswa"
      @back="goToList"
    /> 

    <!-- Form Siswa -->
    <SiswaForm
      v-model:form="form"
      :image-preview="imagePreview"
      :status-options="statusOptions"
      :kelas-options="classes"
      :pekerjaan-options="pekerjaanOptions"
      :agama-options="agamaOptions"
      :kelamin-options="kelaminOptions"
      :hubungan-options="hubunganOptions"
      @image-change="handleImage"
    />

    <FormFooter
      :loading="isLoading"
      submit-label="Simpan Perubahan"
      @submit="handleSubmit"
    />
  </div>
</template>
