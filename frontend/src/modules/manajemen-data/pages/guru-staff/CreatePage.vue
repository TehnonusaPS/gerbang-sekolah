<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { defaultForm } from './data/defaultForm'
import { useAuthStore } from '@/stores/authStore'
import GuruStaffForm from './components/GuruStaffForm.vue'
import { agamaOptions, jabatanOptions, kelaminOptions, pendidikanOptions, statusKepegawaianOptions, statusOptions, statusPernikahanOptions } from './data/guruStaff'
import { createGuruStaffSchema } from './data/guruStaffSchema'
import { useZodForm } from '@/composables/useZodForm'
import SuccessAccountDialog from '@/components/dialogs/SuccessAccountDialog.vue'
import { createTeacher, getFoundations, getSchools } from '@/services/managementService'
import { toast } from 'vue-sonner'
import { fetchAllSubjects } from '@/services/subjectService'

const auth = useAuthStore()
const router = useRouter()
const isLoading = ref(false)
const yayasanOptions = ref([])
const unitOptions = ref([])
const subjectOptions = ref([])

const isSuperAdmin = computed(() => auth.user?.role === 'superadmin')
const isAdminSekolah = computed(() => auth.user?.role === 'admin_sekolah')

const loadYayasanOptions = async () => {
  if (!isSuperAdmin.value) return
  try {
    const res = await getFoundations({ per_page: 1000 })
    const list = Array.isArray(res.data) ? res.data : (res.data?.data || [])
    yayasanOptions.value = list.map(f => ({
      label: f.name,
      value: String(f.id)
    }))
  } catch (err) {
    console.error('Failed to load foundations', err)
  }
}

const loadUnitOptions = async () => {
  if (isAdminSekolah.value) return
  try {
    const resSchools = await getSchools({ per_page: 1000 })
    const list = Array.isArray(resSchools.data) ? resSchools.data : (resSchools.data?.data || [])
    unitOptions.value = list.map(s => ({
      label: s.name,
      value: 'S' + String(s.id).padStart(4, '0'),
      foundation_id: String(s.foundation_id || '')
    }))
  } catch (err) {
    console.error('Failed to load schools', err)
  }
}

onMounted(() => {
  loadYayasanOptions()
  loadUnitOptions()
  loadSubjectOptions()
})

const form = ref({
  ...defaultForm,
  unit_kerja: [],
  subject_ids: []
})

const imagePreview = ref('')

const handleImage = (file) => {
  form.value.foto = file
  if (file instanceof File) {
    imagePreview.value = URL.createObjectURL(file)
  } else {
    imagePreview.value = ''
  }
}

const showSuccessModal = ref(false)

const generatedAccount = ref({
  email: '',
  password: ''
})

const {
  errors: formErrors,
  validate,
  setServerErrors
} = useZodForm(() => createGuruStaffSchema({ isSuperAdmin: isSuperAdmin.value, isAdminSekolah: isAdminSekolah.value }), form)

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
  if (!onBeforeConfirm()) return

  const unitKerjaList = Array.isArray(form.value.unit_kerja)
    ? form.value.unit_kerja
    : (form.value.unit_kerja ? [form.value.unit_kerja] : [])

  isLoading.value = true

  const plainPassword = form.value.password || Math.random().toString(36).substring(2, 10) + 'A1!'

  const postData = {
    nama_depan: form.value.nama_depan,
    nama_belakang: form.value.nama_belakang,
    emailLogin: form.value.emailLogin,
    noHpLogin: form.value.noHpLogin,
    password: plainPassword,
    nik: form.value.nik,
    nip_nuptk: form.value.nip_nuptk,
    tempat_lahir: form.value.tempat_lahir,
    tanggal_lahir: form.value.tanggal_lahir,
    jenis_kelamin: form.value.jenis_kelamin,
    agama: form.value.agama,
    status_pernikahan: form.value.status_pernikahan,
    pendidikan_terakhir: form.value.pendidikan_terakhir,
    gelar_depan: form.value.gelar_depan,
    gelar_belakang: form.value.gelar_belakang,
    alamat: form.value.alamat,
    jabatan: form.value.jabatan,
    status_kepegawaian: form.value.status_kepegawaian,
    foundation_id: form.value.foundation_id,
    unit_kerja: unitKerjaList,
    status_aktif: form.value.status_aktif === 'Aktif' ? 'aktif' : 'nonaktif',
    join_date: form.value.join_date,
    subject_ids: form.value.subject_ids || []
  }

  try {
    const formData = new FormData()
    Object.keys(postData).forEach(key => {
      const value = postData[key]
      if (Array.isArray(value)) {
        value.forEach(item => {
          formData.append(`${key}[]`, item)
        })
        return
      }

      if (value !== null && value !== undefined && value !== '') {
        formData.append(key, value)
      }
    })
    if (form.value.foto instanceof File) {
      formData.append('foto', form.value.foto)
    }
    const res = await createTeacher(formData)
    if (res.status === 'success') {
      generatedAccount.value = {
        email: res.data.email,
        password: res.data.password
      }
      showSuccessModal.value = true
    } else {
      toast.error(res.message || 'Gagal menyimpan data')
    }
  } catch (err) {
    if (setServerErrors(err)) {
      toast.error('Gagal', { description: 'Terdapat kesalahan validasi pada data guru/staff.' })
    } else {
      const errorMsg = err.response?.data?.message || 'Gagal menyimpan data guru/staff.'
      toast.error('Gagal', { description: errorMsg })
    }
  } finally {
    isLoading.value = false
  }
}

const goToList = () => {
  router.push('/manajemen-data/guru-staff')
}

const loadSubjectOptions = async () => {
  try {
    const res = await fetchAllSubjects()

    const subjects = Array.isArray(res.data) ? res.data : []

    subjectOptions.value = subjects
      .filter(subject => subject.is_active === true)
      .map(subject => ({
        label: subject.name,
        value: String(subject.id),
        unit_id: subject.school_id ? 'S' + String(subject.school_id).padStart(4, '0') : null
      }))
  } catch (error) {
    console.error('Gagal memuat mata pelajaran:', error)
    subjectOptions.value = []
  }
}
</script>

<template>
  <div class="space-y-6 p-1 pb-10">
    <!-- Header dengan Tombol Kembali -->
    <PageHeader
      back
      title="Tambah Guru/Staff"
      description="Lengkapi formulir berikut untuk menambahkan data guru atau staff baru"
    /> 

    <GuruStaffForm
      :form="form"
      :image-preview="imagePreview"
      :agama-options="agamaOptions"
      :kelamin-options="kelaminOptions"
      :pendidikan-options="pendidikanOptions"
      :status-pernikahan-options="statusPernikahanOptions"
      :jabatan-options="jabatanOptions"
      :status-kepegawaian-options="statusKepegawaianOptions"
      :yayasan-options="yayasanOptions"
      :unit-kerja-options="unitOptions"
      :status-options="statusOptions"
      :subject-options="subjectOptions"
      :errors="formErrors"
      @image-change="handleImage"
    />

    <FormFooter
      :loading="isLoading"
      submit-label="Simpan Data Guru/Staff"
      confirm-title="Konfirmasi Simpan Data Guru/Staff"
      :entity-name="`${form.nama_depan || ''} ${form.nama_belakang || ''}`.trim()"
      :summary-items="[
        { label: 'Email Login', value: form.emailLogin }
      ]"
      :before-confirm="onBeforeConfirm"
      @submit="handleSubmit"
    />
  </div>

  <SuccessAccountDialog
    v-model:open="showSuccessModal"
    title="Guru/Staff Berhasil Ditambahkan"
    description="Data guru/staff berhasil disimpan dan akun pengguna telah dibuat."
    :email="generatedAccount.email"
    :password="generatedAccount.password"
    @close="goToList"
  />
</template>
