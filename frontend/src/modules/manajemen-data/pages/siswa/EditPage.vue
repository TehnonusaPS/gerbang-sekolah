<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { statusOptions, agamaOptions, kelaminOptions, pekerjaanOptions, hubunganOptions } from './data/siswa'
import { useAuthStore } from '@/stores/authStore'
import SiswaForm from './components/SiswaForm.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { defaultForm } from './data/defaultForm'
import { createSiswaSchema, fieldErrorMap } from './data/siswaSchema'
import { useZodForm } from '@/composables/useZodForm'
import { toast } from 'vue-sonner'
import { getClassrooms, getFoundations, getSchools } from '@/services/managementService'
import { getSiswaDetail, updateSiswa } from '@/services/siswaService'
import { getPhotoUrl } from '@/utils/getPhotoUrl'

const auth = useAuthStore()
const isSuperAdmin = computed(() => auth.user?.role === 'superadmin')
const isWaliKelas = computed(() => auth.user?.role === 'wali_kelas')

const route = useRoute()
const studentId = route.query.id

const yayasanOptions = ref([])
const sekolahOptions = ref([])
const activeClassrooms = ref([])
const form = ref({ ...defaultForm })

const {
  errors: formErrors,
  validate,
  clearErrors,
  setServerErrors
} = useZodForm(() => createSiswaSchema({ isSuperAdmin: isSuperAdmin.value }), form, {
  fieldMap: fieldErrorMap
})

onMounted(async () => {
  try {
    const requests = [getClassrooms()]
    if (isSuperAdmin.value) {
      requests.push(getFoundations({ per_page: 1000 }), getSchools({ per_page: 1000 }))
    }
    const [resClass, resFoundations, resSchools] = await Promise.all(requests)
    activeClassrooms.value = resClass.data || []

    if (isSuperAdmin.value) {
      const fList = Array.isArray(resFoundations?.data) ? resFoundations.data : (resFoundations?.data?.data || [])
      yayasanOptions.value = fList.map(f => ({
        label: f.name,
        value: String(f.id)
      }))

      const sList = Array.isArray(resSchools?.data) ? resSchools.data : (resSchools?.data?.data || [])
      sekolahOptions.value = sList.map(s => ({
        label: s.name,
        value: String(s.id),
        foundation_id: String(s.foundation_id || '')
      }))
    }
  } catch (err) {
    console.error('Gagal mengambil data referensi siswa', err)
  }

  if (studentId) {
    try {
      const res = await getSiswaDetail(studentId)
      const schoolIdStr = res.data.sekolahId ? String(res.data.sekolahId) : ''
      const matchedSchool = schoolIdStr
        ? sekolahOptions.value.find(s => s.value === schoolIdStr)
        : null
      const foundationIdStr = res.data.foundation_id
        ? String(res.data.foundation_id)
        : (matchedSchool?.foundation_id || '')

      form.value = {
        ...defaultForm,
        ...res.data,
        foundation_id: foundationIdStr,
        sekolahId: schoolIdStr,
        tanggal_lahir: res.data.tanggal_lahir || '',
        tahun_masuk: res.data.tahun_masuk || ''
      }
      clearErrors()
      if (res.data.foto) {
        imagePreview.value = getPhotoUrl(res.data.foto)
      }
    } catch (err) {
      toast.error('Gagal memuat data detail siswa')
    }
  }
})

const classes = computed(() => {
  let list = activeClassrooms.value.map(c => ({
    label: c.name,
    value: String(c.id),
    school_id: c.school_id ? String(c.school_id) : ''
  }))
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

const onBeforeConfirm = () => {
  if (!validate()) {
    toast.error('Gagal Menyimpan', {
      description: 'Harap periksa kembali kolom yang ditandai merah pada formulir.'
    })
    return false
  }
  return true
}

const handleSubmit = async () => {
  if (isLoading.value || !onBeforeConfirm()) return
  isLoading.value = true

  let payload = {
    ...form.value,
    tanggal_lahir: form.value.tanggal_lahir || null,
    tahun_masuk: form.value.tahun_masuk || null,
    password: form.value.password_baru || form.value.password || undefined
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
    if (setServerErrors(err)) {
      toast.error('Gagal Menyimpan', {
        description: 'Terdapat kesalahan validasi pada data siswa.'
      })
    } else {
      const errorMsg = err.response?.data?.message || 'Gagal memperbarui data siswa.'
      toast.error('Gagal Menyimpan', { description: errorMsg })
    }
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
      :form="form"
      :image-preview="imagePreview"
      :yayasan-options="yayasanOptions"
      :sekolah-options="sekolahOptions"
      :status-options="statusOptions"
      :kelas-options="classes"
      :pekerjaan-options="pekerjaanOptions"
      :agama-options="agamaOptions"
      :kelamin-options="kelaminOptions"
      :hubungan-options="hubunganOptions"
      :errors="formErrors"
      :show-password-fields="true"
      @image-change="handleImage"
    />

    <FormFooter
      :loading="isLoading"
      submit-label="Simpan Perubahan"
      confirm-title="Konfirmasi Perubahan Data Siswa"
      confirm-button-label="Ya, Simpan Perubahan"
      :entity-name="`${form.nama_depan || ''} ${form.nama_belakang || ''}`.trim()"
      :before-confirm="onBeforeConfirm"
      @submit="handleSubmit"
    />
  </div>
</template>
