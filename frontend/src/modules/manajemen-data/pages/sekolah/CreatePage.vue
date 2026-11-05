<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { akreditasi, jenjangOptions, statusOptions } from './data/sekolah'
import { useAuthStore } from '@/stores/authStore'
import SuccessAccountDialog from '@/components/dialogs/SuccessAccountDialog.vue'
import SekolahForm from './components/SekolahForm.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { defaultForm } from './data/defaultForm'
import { createSekolahSchema, fieldErrorMap } from './data/sekolahSchema'
import { useZodForm } from '@/composables/useZodForm'
import { toast } from 'vue-sonner'
import { getFoundations, createSchool, getRoles, createUser } from '@/services/managementService'
import { glassFade, glassSlide } from '@/config/motion'

const auth = useAuthStore()
const isSuperAdmin = computed(() => auth.user?.role === 'superadmin')

const foundationOptions = ref([])
const router = useRouter()
const isLoading = ref(false)

onMounted(async () => {
  try {
    const res = await getFoundations()
    foundationOptions.value = res.data.data.map(y => ({
      label: y.name,
      value: y.id
    }))
    if (!isSuperAdmin.value) {
      if (auth.user?.foundation_id) {
        form.value.yayasan = auth.user.foundation_id
      } else if (foundationOptions.value.length > 0) {
        form.value.yayasan = foundationOptions.value[0].value
      }
    }
  } catch (err) {
    toast.error('Gagal mengambil daftar yayasan')
  }
})

const form = ref({
  ...defaultForm,
  yayasan: !isSuperAdmin.value && auth.user?.foundation_id ? auth.user.foundation_id : ''
})
const imagePreview = ref('')
const logoFile = ref(null)

const {
  errors: formErrors,
  validate,
  setServerErrors
} = useZodForm(
  () => createSekolahSchema({ isEdit: false, isSuperAdmin: isSuperAdmin.value }),
  form,
  { fieldMap: fieldErrorMap }
)

const handleImage = (file) => {
  logoFile.value = file || null
  form.value.logo = file || null
  imagePreview.value = file ? URL.createObjectURL(file) : ''
}

const showSuccessModal = ref(false)

const generatedAccount = ref({
  email: '',
  phone: '',
  password: ''
})

// Trigger confirmation dialog before actual save
function onBeforeConfirm() {
  if (isLoading.value) return false

  if (!validate()) {
    toast.error('Gagal Menyimpan', {
      description: 'Harap periksa kembali kolom yang ditandai merah pada formulir.'
    })
    return false
  }

  return true
}

const handleSubmit = async () => {
  if (isLoading.value) return

  if (!validate()) {
    toast.error('Gagal Menyimpan', {
      description: 'Harap periksa kembali kolom yang ditandai merah pada formulir.'
    })
    return
  }

  let foundationId = isSuperAdmin.value ? form.value.yayasan : auth.user?.foundation_id

  if (!foundationId) {
    toast.error('Gagal', { description: 'Yayasan tidak boleh kosong.' })
    return
  }

  isLoading.value = true

  let newSchoolId = null
  const loginEmail = form.value.emailLogin?.trim() || form.value.email?.trim()
  const loginPhone = form.value.noHpLogin?.trim() || form.value.no_hp?.trim()
  const schoolEmail = form.value.email?.trim()
  const schoolPhone = form.value.no_hp?.trim()

  // 1. Create the school using FormData
  try {
    const formData = new FormData()
    formData.append('foundation_id', foundationId)
    formData.append('name', form.value.nama || '')
    if (form.value.npsn) formData.append('npsn', form.value.npsn)
    if (form.value.jenjang) formData.append('level', form.value.jenjang)
    if (form.value.tanggal_berdiri) formData.append('established_date', form.value.tanggal_berdiri)
    formData.append('status', form.value.status ? form.value.status.toLowerCase() : 'active')
    if (form.value.alamat) formData.append('address', form.value.alamat)
    if (schoolEmail) formData.append('email', schoolEmail)
    if (schoolPhone) formData.append('phone', schoolPhone)
    if (form.value.website) formData.append('website', form.value.website)
    if (form.value.instagram) formData.append('instagram', form.value.instagram)
    if (form.value.facebook) formData.append('facebook', form.value.facebook)
    if (form.value.no_sk) formData.append('decree_number', form.value.no_sk)
    if (form.value.tanggal_sk) formData.append('decree_date', form.value.tanggal_sk)
    if (form.value.no_izin) formData.append('permit_number', form.value.no_izin)
    if (form.value.tanggal_izin) formData.append('permit_date', form.value.tanggal_izin)
    if (form.value.akreditasi) formData.append('accreditation', form.value.akreditasi)
    if (form.value.tanggal_akreditasi) formData.append('accreditation_date', form.value.tanggal_akreditasi)
    if (form.value.no_akreditasi) formData.append('accreditation_number', form.value.no_akreditasi)
    if (form.value.curriculum_id) formData.append('curriculum_id', form.value.curriculum_id)
    if (logoFile.value) {
      formData.append('logo', logoFile.value)
    }

    const resSchool = await createSchool(formData)
    newSchoolId = resSchool.data.id
  } catch (err) {
    if (setServerErrors(err)) {
      toast.error('Gagal', { description: 'Terdapat kesalahan validasi pada data sekolah.' })
    } else {
      const errorMsg = err.response?.data?.message || 'Gagal menyimpan data sekolah.'
      toast.error('Gagal', { description: errorMsg })
    }
    isLoading.value = false
    return
  }

  // 2. Create school admin user using loginEmail & loginPhone
  let adminSekolahRole = null
  try {
    const resRoles = await getRoles()
    adminSekolahRole = resRoles.data.find(r => r.name === 'admin_sekolah')
  } catch (err) {
    toast.error('Gagal mengambil data peran.')
  }

  const generatedPassword = Math.random().toString(36).substring(2, 10) + 'A1!'
  const userData = {
    name: 'Admin ' + form.value.nama,
    email: loginEmail,
    phone: loginPhone,
    password: generatedPassword,
    role_id: adminSekolahRole ? adminSekolahRole.id : 4,
    foundation_id: foundationId,
    school_id: newSchoolId,
    is_active: true
  }

  try {
    await createUser(userData)

    generatedAccount.value = {
      email: loginEmail,
      phone: loginPhone,
      password: generatedPassword
    }

    showSuccessModal.value = true
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const serverErrors = err.response.data.errors
      const localErrors = {}
      if (serverErrors.email) localErrors.emailLogin = serverErrors.email[0]
      if (serverErrors.phone) localErrors.noHpLogin = serverErrors.phone[0]
      formErrors.value = localErrors
      toast.error('Gagal', { description: 'Terdapat kesalahan validasi pada akun administrator.' })
    } else {
      const errorMsg = err.response?.data?.message || 'Gagal membuat akun administrator.'
      toast.error('Gagal', { description: errorMsg })
    }
  } finally {
    isLoading.value = false
  }
}

const goToList = () => {
  router.push('/manajemen-data/sekolah')
}
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 p-1 pb-10 text-left"
  >
    <!-- Header dengan Tombol Kembali -->
    <PageHeader
      back
      title="Tambah Sekolah Baru"
      description="Lengkapi formulir berikut untuk menambahkan data unit sekolah baru."
    />

    <!-- Form Utama -->
    <div
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 100 } }"
    >
      <SekolahForm
        :form="form"
        :image-preview="imagePreview"
        :foundation-options="foundationOptions"
        :jenjang-options="jenjangOptions"
        :status-options="statusOptions"
        :akreditasi="akreditasi"
        :errors="formErrors"
        @image-change="handleImage"
      />
    </div>

    <FormFooter
      :loading="isLoading"
      submit-label="Simpan Data Sekolah"
      confirm-title="Konfirmasi Simpan Data Sekolah"
      :entity-name="form.nama"
      :summary-items="[
        { label: 'Email Administrator Sekolah', value: form.emailLogin },
        { label: 'No. HP Administrator', value: form.noHpLogin }
      ]"
      :before-confirm="onBeforeConfirm"
      @submit="handleSubmit"
    />
  </div>

  <!-- Success Account Dialog -->
  <SuccessAccountDialog
    v-model:open="showSuccessModal"
    title="Sekolah Berhasil Ditambahkan"
    description="Data sekolah berhasil disimpan dan akun administrator sekolah telah dibuat."
    :email="generatedAccount.email"
    :phone="generatedAccount.phone"
    :password="generatedAccount.password"
    @close="goToList"
  />
</template>
