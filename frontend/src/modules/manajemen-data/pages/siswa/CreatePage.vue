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
import { toast } from 'vue-sonner'
import { getClassrooms } from '@/services/managementService'
import { createSiswa } from '@/services/siswaService'

const auth = useAuthStore()
const isWaliKelas = computed(() => auth.user?.role === 'wali_kelas')

const activeClassrooms = ref([])
onMounted(async () => {
  try {
    const resClass = await getClassrooms()
    activeClassrooms.value = resClass.data
  } catch (err) {
    console.error('Gagal mengambil data kelas', err)
  }
})

const classes = computed(() => {
  let list = activeClassrooms.value.map(c => ({ label: c.name, value: String(c.id) }))
  if (isWaliKelas.value) {
    return list.filter(item => item.value === String(auth.user?.kelasId))
  }
  return list
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
  password: ''
})

const formErrors = ref({})

const handleSubmit = async () => {

  formErrors.value = {}

  // Client-side Validation: All fields must be filled
  const errors = {}
  if (!form.value.nama_depan) {
    errors.first_name = 'Nama depan harus diisi'
  }
  if (!form.value.nik) {
    errors.nik = 'NIK harus diisi'
  }
  if (!form.value.nisn) {
    errors.nisn = 'NISN harus diisi'
  }
  if (!form.value.tempat_lahir) {
    errors.tempat_lahir = 'Tempat lahir harus diisi'
  }
  if (!form.value.tanggal_lahir) {
    errors.tanggal_lahir = 'Tanggal lahir harus diisi'
  }
  if (!form.value.jenis_kelamin) {
    errors.jenis_kelamin = 'Jenis kelamin harus diisi'
  }
  if (!form.value.agama) {
    errors.agama = 'Agama harus diisi'
  }
  if (!form.value.email) {
    errors.email = 'E-mail harus diisi'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.email = 'Format e-mail tidak valid'
  }
  if (!form.value.no_hp) {
    errors.phone = 'No. Telp harus diisi'
  }
  if (!form.value.alamat) {
    errors.address = 'Alamat lengkap harus diisi'
  }
  if (!form.value.nama_wali) {
    errors.nama_wali = 'Nama Wali harus diisi'
  }
  if (!form.value.hubungan_siswa) {
    errors.hubungan_siswa = 'Hubungan siswa harus diisi'
  }
  if (!form.value.kelamin_wali) {
    errors.kelamin_wali = 'Jenis Kelamin Wali harus diisi'
  }
  if (!form.value.email_wali) {
    errors.email_wali = 'Email Wali harus diisi'
  }
  if (!form.value.no_hp_wali) {
    errors.no_hp_wali = 'No HP Wali harus diisi'
  }
  if (!form.value.emailLogin) {
    errors.emailLogin = 'E-mail login wali harus diisi'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.emailLogin)) {
    errors.emailLogin = 'Format e-mail login tidak valid'
  }
  if (!form.value.noHpLogin) {
    errors.noHpLogin = 'No. HP login wali harus diisi'
  }
  if (!form.value.tahun_masuk) {
    errors.tahun_masuk = 'Tahun Masuk harus diisi'
  }
  if (!form.value.kelas) {
    errors.kelas = 'Kelas harus diisi'
  }
  if (!form.value.status) {
    errors.status = 'Status harus diisi'
  }

  if (Object.keys(errors).length > 0) {
    formErrors.value = errors
    toast.error('Gagal Menyimpan', {
      description: 'Harap lengkapi semua data formulir sebelum menyimpan.'
    })
    return
  }

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
      password: res.data.password
    }
    showSuccessModal.value = true
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal menambahkan siswa baru.'
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
      title="Tambah Siswa"
      description="Lengkapi formulir berikut untuk menambahkan data siswa baru"
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
      :errors="formErrors"
    />

    <FormFooter
      :loading="isLoading"
      submit-label="Simpan Data Siswa"
      @submit="handleSubmit"
    />
  </div>

  <SuccessAccountDialog
    v-model:open="showSuccessModal"
    title="Siswa Berhasil Ditambahkan"
    description="Data siswa berhasil disimpan dan akun administrator siswa telah dibuat."
    :email="generatedAccount.email"
    :phone="generatedAccount.phone"
    :password="generatedAccount.password"
    @close="goToList"
  />
</template>
