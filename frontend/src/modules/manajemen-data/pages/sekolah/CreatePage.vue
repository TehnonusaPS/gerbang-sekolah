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
import { CheckCircle2, HelpCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'
import { toast } from 'vue-sonner'
import { getFoundations, createSchool, getRoles, createUser } from '@/services/managementService'
import { glassFade, glassSlide } from '@/config/motion'

const auth = useAuthStore()
const isSuperAdmin = computed(() => auth.user?.role === 'superadmin')

const foundationOptions = ref([])
const router = useRouter()
const isLoading = ref(false)
const isConfirmOpen = ref(false)

onMounted(async () => {
  try {
    const res = await getFoundations()
    foundationOptions.value = res.data.data.map(y => ({
      label: y.name,
      value: y.id
    }))
  } catch (err) {
    toast.error('Gagal mengambil daftar yayasan')
  }
})

const form = ref({ ...defaultForm })
const imagePreview = ref('')
const logoFile = ref(null)

const handleImage = (file) => {
  logoFile.value = file
  imagePreview.value = URL.createObjectURL(file)
}

const showSuccessModal = ref(false)

const generatedAccount = ref({
  email: '',
  phone: '',
  password: ''
})

const formErrors = ref({})

// Trigger confirmation dialog before actual save
function onClickSave() {
  formErrors.value = {}

  let foundationId = isSuperAdmin.value ? form.value.yayasan : auth.user?.foundation_id

  if (isSuperAdmin.value && !foundationId) {
    formErrors.value.foundation_id = 'Pilihan yayasan wajib dipilih.'
  }
  if (!form.value.nama?.trim()) formErrors.value.name = 'Nama sekolah wajib diisi.'
  
  const loginEmail = form.value.emailLogin?.trim() || form.value.email?.trim()
  const loginPhone = form.value.noHpLogin?.trim() || form.value.no_hp?.trim()

  if (!loginEmail) formErrors.value.emailLogin = 'Email login administrator wajib diisi.'
  if (!loginPhone) formErrors.value.noHpLogin = 'No. HP login administrator wajib diisi.'

  if (Object.keys(formErrors.value).length > 0) {
    toast.error('Gagal Menyimpan', { description: 'Harap lengkapi semua isian wajib terlebih dahulu.' })
    return
  }

  isConfirmOpen.value = true
}

const handleSubmit = async () => {
  isConfirmOpen.value = false
  formErrors.value = {}

  let foundationId = isSuperAdmin.value ? form.value.yayasan : auth.user?.foundation_id

  // Client-side Validation: All fields must be filled
  const errors = {}
  if (!logoFile.value) {
    errors.logo = 'Logo sekolah harus diunggah'
  }
  if (!form.value.email) {
    errors.email = 'E-mail sekolah harus diisi'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
    errors.email = 'Format e-mail tidak valid'
  }
  if (!form.value.no_hp) {
    errors.phone = 'No. Telp harus diisi'
  }
  if (!form.value.nama) {
    errors.name = 'Nama sekolah harus diisi'
  }
  if (!form.value.npsn) {
    errors.npsn = 'NPSN harus diisi'
  }
  if (isSuperAdmin.value && !form.value.yayasan) {
    errors.foundation_id = 'Yayasan harus dipilih'
  }
  if (!form.value.jenjang) {
    errors.level = 'Jenjang pendidikan harus dipilih'
  }
  if (!form.value.tanggal_berdiri) {
    errors.established_date = 'Tanggal berdiri harus diisi'
  }
  if (!form.value.status) {
    errors.status = 'Status harus dipilih'
  }
  if (!form.value.alamat) {
    errors.address = 'Alamat lengkap harus diisi'
  }
  if (!form.value.no_sk) {
    errors.decree_number = 'No. SK Pendirian harus diisi'
  }
  if (!form.value.tanggal_sk) {
    errors.decree_date = 'Tanggal SK Pendirian harus diisi'
  }
  if (!form.value.no_izin) {
    errors.permit_number = 'No. Izin Operasional harus diisi'
  }
  if (!form.value.tanggal_izin) {
    errors.permit_date = 'Tanggal Izin Operasional harus diisi'
  }
  if (!form.value.akreditasi) {
    errors.accreditation = 'Akreditasi harus dipilih'
  }
  if (!form.value.tanggal_akreditasi) {
    errors.accreditation_date = 'Tanggal akreditasi harus diisi'
  }
  if (!form.value.no_akreditasi) {
    errors.accreditation_number = 'No. SK Akreditasi harus diisi'
  }
  if (!form.value.emailLogin) {
    errors.emailLogin = 'E-mail login administrator harus diisi'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.emailLogin)) {
    errors.emailLogin = 'Format e-mail login tidak valid'
  }
  if (!form.value.noHpLogin) {
    errors.noHpLogin = 'No. HP login administrator harus diisi'
  }

  if (Object.keys(errors).length > 0) {
    formErrors.value = errors
    toast.error('Gagal Menyimpan', {
      description: 'Harap lengkapi semua data formulir sebelum menyimpan.'
    })
    return
  }

  if (!foundationId) {
    toast.error('Gagal', { description: 'Yayasan tidak boleh kosong.' })
    return
  }

  // isLoading.value = true

  let newSchoolId = null
  const loginEmail = form.value.emailLogin?.trim() || form.value.email?.trim()
  const loginPhone = form.value.noHpLogin?.trim() || form.value.no_hp?.trim()
  const schoolEmail = form.value.email?.trim() || loginEmail
  const schoolPhone = form.value.no_hp?.trim() || loginPhone

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
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const serverErrors = err.response.data.errors
      const localErrors = {}
      Object.keys(serverErrors).forEach(key => {
        localErrors[key] = serverErrors[key][0]
      })
      formErrors.value = localErrors
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
        v-model:form="form"
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
      @submit="onClickSave"
    />
  </div>

  <!-- Confirmation Dialog (Konfirmasi Sebelum Simpan) -->
  <Dialog :open="isConfirmOpen" @update:open="isConfirmOpen = false">
    <DialogContent class="sm:max-w-md bg-card dark:bg-zinc-900 border border-border dark:border-zinc-800">
      <DialogHeader>
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
            <HelpCircle class="h-5 w-5" />
          </div>
          <div>
            <DialogTitle class="text-base font-semibold text-foreground dark:text-zinc-100">
              Konfirmasi Simpan Data Sekolah
            </DialogTitle>
            <DialogDescription class="text-xs text-muted-foreground dark:text-zinc-400 mt-0.5">
              Mohon periksa kembali isian formulir sebelum melanjutkan.
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <div class="py-3 text-sm text-foreground dark:text-zinc-300 space-y-2">
        <p>Apakah Anda yakin data <strong>{{ form.nama }}</strong> sudah sesuai?</p>
        <div class="p-3 rounded-lg bg-accent/40 dark:bg-zinc-800/50 border border-border/50 dark:border-zinc-800 text-xs space-y-1">
          <div><span class="text-muted-foreground">Email Administrator Sekolah:</span> <span class="font-semibold text-foreground dark:text-zinc-100">{{ form.emailLogin }}</span></div>
          <div><span class="text-muted-foreground">No. HP Administrator:</span> <span class="font-semibold text-foreground dark:text-zinc-100">{{ form.noHpLogin }}</span></div>
        </div>
      </div>

      <DialogFooter class="gap-2 sm:gap-0">
        <Button variant="outline" type="button" @click="isConfirmOpen = false" :disabled="isLoading">
          Kembali Periksa
        </Button>
        <Button type="button" :disabled="isLoading" @click="handleSubmit" class="gap-1.5">
          <CheckCircle2 class="h-4 w-4" />
          {{ isLoading ? 'Memproses...' : 'Ya, Simpan Sekarang' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

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
