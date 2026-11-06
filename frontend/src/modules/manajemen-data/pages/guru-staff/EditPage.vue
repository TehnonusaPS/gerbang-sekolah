<script setup>
import { getPhotoUrl } from '@/utils/getPhotoUrl'
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { defaultForm } from './data/defaultForm'
import { useAuthStore } from '@/stores/authStore'
import GuruStaffForm from './components/GuruStaffForm.vue'
import { agamaOptions, jabatanOptions, kelaminOptions, pendidikanOptions, statusKepegawaianOptions, statusOptions, statusPernikahanOptions } from './data/guruStaff'
import { createGuruStaffSchema } from './data/guruStaffSchema'
import { useZodForm } from '@/composables/useZodForm'
import { toast } from 'vue-sonner'
import { getTeacher, updateTeacher, getFoundations, getSchools } from '@/services/managementService'
import { fetchAllSubjects } from '@/services/subjectService'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const isLoading = ref(false)
const teacherId = route.params.id || route.query.id
const yayasanOptions = ref([])
const unitOptions = ref([])
const subjectOptions = ref([])

const isSuperAdmin = computed(() => auth.user?.role === 'superadmin')
const isAdminSekolah = computed(() => auth.user?.role === 'admin_sekolah')

const form = ref({
  ...defaultForm,
  unit_kerja: [],
  subject_ids: []
})
const imagePreview = ref('')
const {
  errors: formErrors,
  validate,
  clearErrors,
  setServerErrors
} = useZodForm(() => createGuruStaffSchema({ isSuperAdmin: isSuperAdmin.value, isAdminSekolah: isAdminSekolah.value }), form)

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

const formatDateString = (val) => {
  if (!val) return ''
  if (typeof val === 'string') {
    return val.split('T')[0]
  }
  if (val instanceof Date) {
    return val.toISOString().split('T')[0]
  }
  return val
}

const loadTeacher = async () => {
  const currentId = route.params.id || route.query.id
  if (!currentId) return

  isLoading.value = true
  try {
    const res = await getTeacher(currentId)
    const data = (res && res.data) ? res.data : (res || {})

    const user = data.user || data || {}
    const profile = data.teacher_profile || data.profile || data || {}

    const isActiveVal = user.is_active !== undefined ? user.is_active : (data.status_aktif !== undefined ? data.status_aktif : true)
    const statusAktifVal = (isActiveVal === true || isActiveVal === 1 || isActiveVal === 'aktif' || isActiveVal === 'Aktif') ? 'Aktif' : 'Nonaktif'

    let loadedUnitIds = []
    if (Array.isArray(data.unit_ids) && data.unit_ids.length > 0) {
      loadedUnitIds = data.unit_ids.map(String)
    } else if (data.unit_id) {
      loadedUnitIds = [String(data.unit_id)]
    } else if (user.school_id) {
      loadedUnitIds = ['S' + String(user.school_id).padStart(4, '0')]
    } else if (user.foundation_id) {
      loadedUnitIds = ['Y' + String(user.foundation_id).padStart(4, '0')]
    }

    const schoolUnitIds = loadedUnitIds.filter(u => String(u).toUpperCase().startsWith('S'))
    const firstSchoolOpt = schoolUnitIds.length > 0
      ? unitOptions.value.find(o => o.value === schoolUnitIds[0])
      : null
    const resolvedFoundationId =
      data.foundation_id ||
      user.foundation_id ||
      firstSchoolOpt?.foundation_id ||
      ''

    form.value = {
      ...defaultForm,
      nama_depan: user.nama_depan || (user.name ? user.name.split(' ')[0] : ''),
      nama_belakang: user.nama_belakang || (user.name ? user.name.split(' ').slice(1).join(' ') : ''),
      nik: profile.nik || '',
      nip_nuptk: profile.nip_nuptk || profile.nip || '',
      tempat_lahir: profile.birth_place || profile.tempat_lahir || '',
      tanggal_lahir: formatDateString(profile.birth_date || profile.tanggal_lahir || ''),
      jenis_kelamin: profile.jenis_kelamin || profile.gender || '',
      agama: profile.agama || profile.religion || '',
      status_pernikahan: profile.status_pernikahan || profile.marital_status || '',
      pendidikan_terakhir: profile.pendidikan_terakhir || profile.last_education || '',
      gelar_depan: profile.front_title || profile.gelar_depan || '',
      gelar_belakang: profile.back_title || profile.gelar_belakang || '',
      alamat: profile.address || profile.alamat || '',
      jabatan: profile.jabatan || profile.position || '',
      status_kepegawaian: profile.status_kepegawaian || profile.employment_status || '',
      foundation_id: resolvedFoundationId ? String(resolvedFoundationId) : '',
      unit_kerja: schoolUnitIds,
      status_aktif: statusAktifVal,
      emailLogin: user.email || user.emailLogin || data.emailLogin || '',
      noHpLogin: user.phone || user.noHpLogin || data.noHpLogin || '',
      join_date: formatDateString(profile.join_date || ''),
      subject_ids: Array.isArray(data.subject_ids) ? data.subject_ids.map(id => String(id)) : []
    }
    clearErrors()

    const photoPath = user.photo || data.foto
    if (photoPath) {
      imagePreview.value = getPhotoUrl(photoPath)
    }
  } catch (err) {
    console.error('Error loading teacher details:', err)
    toast.error('Gagal memuat data guru/staff')
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await Promise.all([loadYayasanOptions(), loadUnitOptions(), loadSubjectOptions()])
  await loadTeacher()
})

const handleImage = (file) => {
  form.value.foto = file
  if (file instanceof File) {
    imagePreview.value = URL.createObjectURL(file)
  }
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
  if (!onBeforeConfirm()) return

  const unitKerjaList = Array.isArray(form.value.unit_kerja)
    ? form.value.unit_kerja
    : (form.value.unit_kerja ? [form.value.unit_kerja] : [])

  isLoading.value = true

  const postData = {
    nama_depan: form.value.nama_depan,
    nama_belakang: form.value.nama_belakang,
    emailLogin: form.value.emailLogin,
    noHpLogin: form.value.noHpLogin,
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
    status_aktif: form.value.status_aktif === 'Aktif' ? 'aktif' : 'nonaktif',
    join_date: form.value.join_date,
    subject_ids: form.value.subject_ids || []
  }

  if (!isAdminSekolah.value) {
    postData.unit_kerja = unitKerjaList
    if (form.value.foundation_id) {
      postData.foundation_id = form.value.foundation_id
    }
  }

  if (form.value.password_baru || form.value.password) {
    postData.password = form.value.password_baru || form.value.password
  }

  try {
    if (form.value.foto instanceof File) {
      const fd = new FormData()

      Object.entries(postData).forEach(([key, value]) => {
        if (Array.isArray(value)) {
          value.forEach(item => {
            fd.append(`${key}[]`, item)
          })
          return
        }

        if (value !== null && value !== undefined && value !== '') {
          fd.append(key, value)
        }
      })

      fd.append('foto', form.value.foto)

      await updateTeacher(teacherId, fd)
    } else {
      await updateTeacher(teacherId, postData)
    }
    toast.success('Data guru/staff berhasil diperbarui', {
      description: 'Perubahan data guru/staff telah berhasil disimpan.'
    })
    router.push('/manajemen-data/guru-staff')
  } catch (err) {
    if (setServerErrors(err)) {
      toast.error('Gagal', { description: 'Terdapat kesalahan validasi pada data guru/staff.' })
    } else {
      const errorMsg = err.response?.data?.message || 'Gagal memperbarui data guru/staff.'
      toast.error('Gagal', { description: errorMsg })
    }
  } finally {
    isLoading.value = false
  }
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
      title="Edit Guru/Staff"
      description="Lengkapi formulir berikut untuk mengedit data guru atau staff"
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
      :show-password-fields="true"
      @image-change="handleImage"
    />

    <FormFooter
      :loading="isLoading"
      submit-label="Simpan Perubahan"
      confirm-title="Konfirmasi Perubahan Data Guru/Staff"
      confirm-button-label="Ya, Simpan Perubahan"
      :entity-name="`${form.nama_depan || ''} ${form.nama_belakang || ''}`.trim()"
      :summary-items="[
        { label: 'Email Login', value: form.emailLogin }
      ]"
      :before-confirm="onBeforeConfirm"
      @submit="handleSubmit"
    />
  </div>
</template>
