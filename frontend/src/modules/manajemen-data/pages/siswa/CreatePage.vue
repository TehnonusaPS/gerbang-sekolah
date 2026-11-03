<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { statusOptions, agamaOptions, kelaminOptions, pekerjaanOptions, hubunganOptions } from './data/siswa'
import { useAuthStore } from '@/stores/authStore'
import SuccessAccountDialog from '@/components/dialogs/SuccessAccountDialog.vue'
import SiswaForm from './components/SiswaForm.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { defaultForm } from './data/defaultForm'
import { createSiswaSchema, fieldErrorMap } from './data/siswaSchema'
import { useZodForm } from '@/composables/useZodForm'
import { toast } from 'vue-sonner'
import { getClassrooms, getFoundations, getSchools } from '@/services/managementService'
import { fetchAllEkskul } from '@/services/ekskulService'
import { createSiswa } from '@/services/siswaService'

const auth = useAuthStore()
const isSuperAdmin = computed(() => auth.user?.role === 'superadmin')
const isWaliKelas = computed(() => auth.user?.role === 'wali_kelas')

const yayasanOptions = ref([])
const sekolahOptions = ref([])
const activeClassrooms = ref([])
const activeEkskuls = ref([])

onMounted(async () => {
  try {
    const requests = [getClassrooms(), fetchAllEkskul()]
    if (isSuperAdmin.value) {
      requests.push(getFoundations({ per_page: 1000 }), getSchools({ per_page: 1000 }))
    }
    const [resClass, resEkskul, resFoundations, resSchools] = await Promise.all(requests)
    activeClassrooms.value = resClass.data || []
    activeEkskuls.value = resEkskul.data || []

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

const ekskulOptions = computed(() => {
  return activeEkskuls.value.map(e => ({
    label: e.nama,
    value: String(e.id),
    school_id: e.school_id ? String(e.school_id) : ''
  }))
})

const form = ref({ ...defaultForm })

const router = useRouter()
const isLoading = ref(false)

const imagePreview = ref('')

const handleImage = (file) => {
  form.value.foto = file
  imagePreview.value = URL.createObjectURL(file)
  form.value.foto = file
}

const showSuccessModal = ref(false)

const generatedAccount = ref({
  email: '',
  phone: '',
  password: '',
  accounts: []
})

const {
  errors: formErrors,
  validate,
  setServerErrors
} = useZodForm(() => createSiswaSchema({ isSuperAdmin: isSuperAdmin.value }), form, {
  fieldMap: fieldErrorMap
})

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
    tahun_masuk: form.value.tahun_masuk || null
  }

  if (form.value.foto instanceof File) {
    const formData = new FormData()
    Object.keys(payload).forEach(key => {
      if (payload[key] !== null && payload[key] !== undefined) {
        formData.append(key, payload[key])
      }
    })
    payload = formData
  }

  try {
    const res = await createSiswa(payload)
    generatedAccount.value = {
      email: res.data.email,
      phone: res.data.phone || '-',
      password: res.data.password,
      accounts: res.data.accounts || [
        {
          title: 'Akun Login Siswa',
          loginLabel: 'NISN Login',
          loginValue: form.value.nisn,
          password: res.data.password || '123456'
        },
        {
          title: 'Akun Login Orang Tua / Wali',
          loginLabel: 'E-mail Login',
          loginValue: res.data.email || form.value.emailLogin,
          password: res.data.password || '123456'
        }
      ]
    }
    showSuccessModal.value = true
  } catch (err) {
    if (setServerErrors(err)) {
      toast.error('Gagal Menyimpan', {
        description: 'Terdapat kesalahan validasi pada data siswa.'
      })
    } else {
      const errorMsg = err.response?.data?.message || 'Gagal menambahkan siswa baru.'
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
      title="Tambah Siswa"
      description="Lengkapi formulir berikut untuk menambahkan data siswa baru"
    /> 

    <!-- Form Siswa -->
    <SiswaForm
      :form="form"
      :image-preview="imagePreview"
      :yayasan-options="yayasanOptions"
      :sekolah-options="sekolahOptions"
      :status-options="statusOptions"
      :kelas-options="classes"
      :ekskul-options="ekskulOptions"
      :pekerjaan-options="pekerjaanOptions"
      :agama-options="agamaOptions"
      :kelamin-options="kelaminOptions"
      :hubungan-options="hubunganOptions"
      @image-change="handleImage"
      :errors="formErrors"
    />

    <FormFooter
      :loading="isLoading"
      submit-label="Simpan Data Siswa"
      confirm-title="Konfirmasi Simpan Data Siswa"
      :entity-name="`${form.nama_depan || ''} ${form.nama_belakang || ''}`.trim()"
      :summary-items="[
        { label: 'NISN', value: form.nisn },
        { label: 'Email Login Wali', value: form.emailLogin }
      ]"
      :before-confirm="onBeforeConfirm"
      @submit="handleSubmit"
    />
  </div>

  <SuccessAccountDialog
    v-model:open="showSuccessModal"
    title="Siswa Berhasil Ditambahkan"
    description="Data siswa berhasil disimpan. Berikut kredensial login untuk akun Siswa dan Orang Tua/Wali."
    :email="generatedAccount.email"
    :phone="generatedAccount.phone"
    :password="generatedAccount.password"
    :accounts="generatedAccount.accounts"
    @close="goToList"
  />
</template>
