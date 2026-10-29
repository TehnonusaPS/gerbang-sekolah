<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/page-header/PageHeader.vue'
import SuccessAccountDialog from '@/components/dialogs/SuccessAccountDialog.vue'
import YayasanForm from './components/YayasanForm.vue'
import { defaultForm } from './data/defaultForm'
import { statusOptions } from './data/yayasan.js'
import { createYayasanSchema, fieldErrorMap } from './data/yayasanSchema.js'
import { useZodForm } from '@/composables/useZodForm'
import FormFooter from '@/components/forms/FormFooter.vue'
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
import { createFoundation } from '@/services/managementService'
import { glassFade, glassSlide } from '@/config/motion'

const router = useRouter()
const isLoading = ref(false)
const isConfirmOpen = ref(false)

const form = ref({ ...defaultForm })

const imagePreview = ref('')
const logoFile = ref(null)

const {
  errors: formErrors,
  validate,
  setServerErrors
} = useZodForm(() => createYayasanSchema({ isEdit: false }), form, { fieldMap: fieldErrorMap })

const handleImage = (file) => {
  logoFile.value = file || null
  form.value.logo = file || null
  imagePreview.value = file ? URL.createObjectURL(file) : ''
}

const showSuccessModal = ref(false)

const generatedAccount = ref({
  email: '',
  password: ''
})

function onClickSave() {
  if (isLoading.value) return
  if (!validate()) {
    toast.error('Gagal Menyimpan', {
      description: formErrors.value.logo || 'Harap lengkapi semua isian wajib dan periksa format data terlebih dahulu.'
    })
    return
  }

  isConfirmOpen.value = true
}

const handleSubmit = async () => {
  if (isLoading.value) return
  isConfirmOpen.value = false

  if (!validate()) {
    toast.error('Gagal Menyimpan', {
      description: formErrors.value.logo || 'Harap periksa kembali isian formulir sebelum menyimpan.'
    })
    return
  }

  isLoading.value = true

  try {
    const formData = new FormData()
    formData.append('name', form.value.nama || '')
    if (form.value.tanggal_berdiri) formData.append('established_date', form.value.tanggal_berdiri)
    formData.append('status', form.value.status ? form.value.status.toLowerCase() : 'active')
    if (form.value.alamat) formData.append('address', form.value.alamat)
    if (form.value.email) formData.append('email', form.value.email)
    if (form.value.no_hp) formData.append('phone', form.value.no_hp)
    if (form.value.website) formData.append('website', form.value.website)
    if (form.value.no_akta) formData.append('deed_number', form.value.no_akta)
    if (form.value.tanggal_akta) formData.append('deed_date', form.value.tanggal_akta)
    if (form.value.no_sk) formData.append('decree_number', form.value.no_sk)
    if (form.value.tanggal_sk) formData.append('decree_date', form.value.tanggal_sk)
    if (form.value.emailLogin) formData.append('emailLogin', form.value.emailLogin)
    if (logoFile.value) {
      formData.append('logo', logoFile.value)
    }

    const resFoundation = await createFoundation(formData)
    const adminAcc = resFoundation.admin_account || resFoundation.data?.admin_account

    generatedAccount.value = {
      email: adminAcc?.email || form.value.emailLogin,
      password: adminAcc?.password || '-'
    }

    showSuccessModal.value = true
  } catch (err) {
    if (setServerErrors(err)) {
      const serverErrors = err.response?.data?.errors || {}
      const firstError = Object.values(serverErrors).flat()[0]
      toast.error('Gagal Menyimpan', {
        description: firstError || 'Terdapat isian yang tidak valid. Silakan periksa kembali formulir.'
      })
    } else {
      const errorMsg = err.response?.data?.message || err.message || 'Gagal menyimpan data yayasan.'
      toast.error('Gagal Menyimpan', { description: errorMsg })
    }
  } finally {
    isLoading.value = false
  }
}

const goToList = () => {
  router.push('/manajemen-data/yayasan')
}
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 p-1 pb-16 text-left"
  >
    <!-- Header dengan Tombol Kembali -->
    <PageHeader
      back
      title="Tambah Yayasan Baru"
      description="Lengkapi formulir berikut untuk menambahkan data yayasan pendidikan baru."
    />

    <!-- Form Utama -->
    <div
      v-motion
      :initial="glassSlide.initial"
      :visible-once="{ ...glassSlide.visible, transition: { ...glassSlide.visible.transition, delay: 100 } }"
    >
      <YayasanForm
        v-model:form="form"
        :image-preview="imagePreview"
        :status-options="statusOptions"
        :errors="formErrors"
        @image-change="handleImage"
      />
    </div>

    <FormFooter
      :loading="isLoading"
      submit-label="Simpan Data Yayasan"
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
              Konfirmasi Simpan Data Yayasan
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
          <div><span class="text-muted-foreground">Email Administrator:</span> <span class="font-semibold text-foreground dark:text-zinc-100">{{ form.emailLogin }}</span></div>
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
    title="Yayasan Berhasil Ditambahkan"
    description="Data yayasan berhasil disimpan dan akun administrator yayasan telah dibuat."
    :email="generatedAccount.email"
    :password="generatedAccount.password"
    @close="goToList"
  />
</template>
