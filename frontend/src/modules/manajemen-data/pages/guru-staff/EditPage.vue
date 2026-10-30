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
import { getTeacher, updateTeacher, getFoundation, getSchools } from '@/services/managementService'
import { fetchAllSubjects } from '@/services/subjectService'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const isLoading = ref(false)
const teacherId = route.params.id || route.query.id
const unitOptions = ref([])
const subjectOptions = ref([])

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
} = useZodForm(() => createGuruStaffSchema({ isAdminSekolah: isAdminSekolah.value }), form)

const loadUnitOptions = async () => {
  if (isAdminSekolah.value) return
  try {
    const resSchools = await getSchools()
    const options = resSchools.data.data.map(s => ({
      label: s.name,
      value: 'S' + String(s.id).padStart(4, '0')
    }))

    if (auth.user?.foundation_id) {
      try {
        const resFd = await getFoundation(auth.user.foundation_id)
        options.unshift({
          label: resFd.data.name,
          value: 'Y' + String(auth.user.foundation_id).padStart(4, '0')
        })
      } catch (err) {
        options.unshift({
          label: 'Yayasan',
          value: 'Y' + String(auth.user.foundation_id).padStart(4, '0')
        })
      }
    }
    unitOptions.value = options
  } catch (err) {
    console.error('Failed to load schools', err)
  }
}

const mapAgamaToValue = (label) => {
  if (!label) return ''
  const map = {
    'Islam': 'A01',
    'Kristen': 'A02',
    'Katolik': 'A03',
    'Buddha': 'A04',
    'Hindu': 'A05',
    'Konghucu': 'A06',
    'A01': 'A01',
    'A02': 'A02',
    'A03': 'A03',
    'A04': 'A04',
    'A05': 'A05',
    'A06': 'A06',
  }
  return map[label] || ''
}

const mapPernikahanToValue = (label) => {
  if (!label) return ''
  const map = {
    'Belum Menikah': 'SP01',
    'Menikah': 'SP02',
    'Janda': 'SP03',
    'Duda': 'SP04',
    'SP01': 'SP01',
    'SP02': 'SP02',
    'SP03': 'SP03',
    'SP04': 'SP04',
  }
  return map[label] || ''
}

const mapKelaminToValue = (label) => {
  if (!label) return ''
  const map = {
    'Laki-laki': 'JK01',
    'Perempuan': 'JK02',
    'male': 'JK01',
    'female': 'JK02',
    'JK01': 'JK01',
    'JK02': 'JK02',
  }
  return map[label] || ''
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

    const rawGender = profile.gender || profile.jenis_kelamin || ''
    const rawAgama = profile.religion || profile.agama || ''
    const rawPernikahan = profile.marital_status || profile.status_pernikahan || ''
    const rawPendidikan = profile.last_education || profile.pendidikan_terakhir || ''
    const rawJabatan = profile.position || profile.jabatan || ''
    const rawKepegawaian = profile.employment_status || profile.status_kepegawaian || ''

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

    form.value = {
      ...defaultForm,
      nama_depan: user.nama_depan || (user.name ? user.name.split(' ')[0] : ''),
      nama_belakang: user.nama_belakang || (user.name ? user.name.split(' ').slice(1).join(' ') : ''),
      nik: profile.nik || '',
      nip_nuptk: profile.nip_nuptk || profile.nip || '',
      tempat_lahir: profile.birth_place || profile.tempat_lahir || '',
      tanggal_lahir: formatDateString(profile.birth_date || profile.tanggal_lahir || ''),
      jenis_kelamin: mapKelaminToValue(rawGender),
      agama: mapAgamaToValue(rawAgama),
      status_pernikahan: mapPernikahanToValue(rawPernikahan),
      pendidikan_terakhir: mapPendidikanToValue(rawPendidikan),
      gelar_depan: profile.front_title || profile.gelar_depan || '',
      gelar_belakang: profile.back_title || profile.gelar_belakang || '',
      alamat: profile.address || profile.alamat || '',
      jabatan: mapJabatanToValue(rawJabatan),
      status_kepegawaian: mapStatusKepegawaianToValue(rawKepegawaian),
      unit_kerja: loadedUnitIds,
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

const mapPendidikanToValue = (label) => {
  const map = {
    'Sekolah Dasar (SD)': 'P01',
    'SD': 'P01',
    'P01': 'P01',
    'Sekolah Menengah Pertama (SMP)': 'P02',
    'SMP': 'P02',
    'P02': 'P02',
    'Sekolah Menengah Awal/Kejuruan (SMA/SMK)': 'P03',
    'SMA/SMK': 'P03',
    'P03': 'P03',
    'Diploma I (D1)': 'P04',
    'D1': 'P04',
    'P04': 'P04',
    'Diploma III (D3)': 'P05',
    'D3': 'P05',
    'P05': 'P05',
    'Diploma IV (D4)': 'P06',
    'D4': 'P06',
    'P06': 'P06',
    'Sarjana (S1)': 'P07',
    'S1': 'P07',
    'P07': 'P07',
    'Magister (S2)': 'P08',
    'S2': 'P08',
    'P08': 'P08',
    'Doktoral (S3)': 'P09',
    'S3': 'P09',
    'P09': 'P09'
  }
  return map[label] || ''
}

const mapJabatanToValue = (label) => {
  const map = {
    'Kepala Yayasan': 'J001',
    'J001': 'J001',
    'Staff Yayasan': 'J002',
    'J002': 'J002',
    'Kepala Sekolah': 'J003',
    'J003': 'J003',
    'Guru': 'J004',
    'J004': 'J004',
    'Staff Sekolah': 'J005',
    'J005': 'J005',
    'Admin Sekolah': 'J006',
    'J006': 'J006'
  }
  return map[label] || ''
}

const mapStatusKepegawaianToValue = (label) => {
  const map = {
    'Tetap': 'SK01',
    'SK01': 'SK01',
    'Kontrak': 'SK02',
    'SK02': 'SK02',
    'Honorer': 'SK03',
    'SK03': 'SK03'
  }
  return map[label] || ''
}

const mapValueToAgama = (value) => {
  const map = {
    'A01': 'Islam',
    'A02': 'Kristen',
    'A03': 'Katolik',
    'A04': 'Buddha',
    'A05': 'Hindu',
    'A06': 'Konghucu'
  }
  return map[value] || value
}

const mapValueToPernikahan = (value) => {
  const map = {
    'SP01': 'Belum Menikah',
    'SP02': 'Menikah',
    'SP03': 'Janda',
    'SP04': 'Duda'
  }
  return map[value] || value
}

const mapValueToPendidikan = (value) => {
  const map = {
    'P01': 'Sekolah Dasar (SD)',
    'P02': 'Sekolah Menengah Pertama (SMP)',
    'P03': 'Sekolah Menengah Awal/Kejuruan (SMA/SMK)',
    'P04': 'Diploma I (D1)',
    'P05': 'Diploma III (D3)',
    'P06': 'Diploma IV (D4)',
    'P07': 'Sarjana (S1)',
    'P08': 'Magister (S2)',
    'P09': 'Doktoral (S3)'
  }
  return map[value] || value
}

const mapValueToJabatan = (value) => {
  const map = {
    'J001': 'Kepala Yayasan',
    'J002': 'Staff Yayasan',
    'J003': 'Kepala Sekolah',
    'J004': 'Guru',
    'J005': 'Staff Sekolah',
    'J006': 'Admin Sekolah'
  }
  return map[value] || value
}

const mapValueToStatusKepegawaian = (value) => {
  const map = {
    'SK01': 'Tetap',
    'SK02': 'Kontrak',
    'SK03': 'Honorer'
  }
  return map[value] || value
}

const mapValueToKelamin = (value) => {
  const map = {
    'JK01': 'Laki-laki',
    'JK02': 'Perempuan'
  }
  return map[value] || value
}

onMounted(async () => {
  await loadUnitOptions()
  await loadSubjectOptions()
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
    jenis_kelamin: mapValueToKelamin(form.value.jenis_kelamin),
    agama: mapValueToAgama(form.value.agama),
    status_pernikahan: mapValueToPernikahan(form.value.status_pernikahan),
    pendidikan_terakhir: mapValueToPendidikan(form.value.pendidikan_terakhir),
    gelar_depan: form.value.gelar_depan,
    gelar_belakang: form.value.gelar_belakang,
    alamat: form.value.alamat,
    jabatan: mapValueToJabatan(form.value.jabatan),
    status_kepegawaian: mapValueToStatusKepegawaian(form.value.status_kepegawaian),
    status_aktif: form.value.status_aktif === 'Aktif' ? 'aktif' : 'nonaktif',
    join_date: form.value.join_date,
    subject_ids: form.value.subject_ids || []
  }

  if (!isAdminSekolah.value) {
    postData.unit_kerja = unitKerjaList
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
        { label: 'Email Login', value: form.emailLogin },
        { label: 'No. HP Login', value: form.noHpLogin }
      ]"
      :before-confirm="onBeforeConfirm"
      @submit="handleSubmit"
    />
  </div>
</template>
