<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import PageHeader from '@/components/page-header/PageHeader.vue'
import YayasanForm from './components/YayasanForm.vue'
import { defaultForm } from './data/defaultForm'
import { statusOptions } from './data/yayasan.js'
import { createYayasanSchema, fieldErrorMap } from './data/yayasanSchema.js'
import { useZodForm } from '@/composables/useZodForm'
import { toast } from 'vue-sonner'
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
import { getFoundation, updateFoundation } from '@/services/managementService'

const router = useRouter()
const route = useRoute()
const isLoading = ref(false)
const isConfirmOpen = ref(false)
const foundationId = route.query.id

const form = ref({ ...defaultForm })

const imagePreview = ref('')
const logoFile = ref(null)

const {
  errors: formErrors,
  validate,
  clearErrors,
  setServerErrors
} = useZodForm(
  () => createYayasanSchema({ isEdit: true, hasExistingLogo: Boolean(imagePreview.value) }),
  form,
  { fieldMap: fieldErrorMap }
)

const handleImage = (file) => {
  logoFile.value = file || null
  form.value.logo = file || null
  imagePreview.value = file ? URL.createObjectURL(file) : ''
}

onMounted(async () => {
  if (!foundationId) {
    toast.error('ID Yayasan tidak ditemukan')
    router.push('/manajemen-data/yayasan')
    return
  }

  isLoading.value = true
  try {
    const res = await getFoundation(foundationId)
    const foundation = res.data
    form.value = {
      nama: foundation.name || '',
      tanggal_berdiri: foundation.established_date ? foundation.established_date.split('T')[0] : '',
      status: foundation.status ? foundation.status.charAt(0).toUpperCase() + foundation.status.slice(1) : '',
      alamat: foundation.address || '',
      email: foundation.email || '',
      no_hp: foundation.phone || '',
      website: foundation.website || '',
      no_akta: foundation.deed_number || '',
      tanggal_akta: foundation.deed_date ? foundation.deed_date.split('T')[0] : '',
      no_sk: foundation.decree_number || '',
      tanggal_sk: foundation.decree_date ? foundation.decree_date.split('T')[0] : '',
      emailLogin: foundation.users && foundation.users[0] ? foundation.users[0].email : '',
      logo: foundation.logo || null
    }
    if (foundation.logo) {
      const baseUrl = (import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api').replace(/\/api$/, '')
      imagePreview.value = foundation.logo.startsWith('http') ? foundation.logo : `${baseUrl}/storage/${foundation.logo}`
    }
    clearErrors()
  } catch (err) {
    toast.error('Gagal mengambil data yayasan')
    router.push('/manajemen-data/yayasan')
  } finally {
    isLoading.value = false
  }
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

    await updateFoundation(foundationId, formData)
    toast.success('Data yayasan berhasil diperbarui', {
      description: 'Perubahan data yayasan telah berhasil disimpan.'
    })
    router.push('/manajemen-data/yayasan')
  } catch (err) {
    if (setServerErrors(err)) {
      const serverErrors = err.response?.data?.errors || {}
      const firstError = Object.values(serverErrors).flat()[0]
      toast.error('Gagal Menyimpan', {
        description: firstError || 'Terdapat isian yang tidak valid. Silakan periksa kembali formulir.'
      })
    } else {
      const errorMsg = err.response?.data?.message || err.message || 'Gagal menyimpan perubahan yayasan.'
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
  <div class="space-y-6 p-1 pb-16">
    <!-- Header dengan Tombol Kembali -->
    <PageHeader back title="Edit Data Yayasan"
      description="Lengkapi formulir berikut untuk memperbarui data yayasan." />

    <YayasanForm v-model:form="form" :image-preview="imagePreview" :status-options="statusOptions" :errors="formErrors"
      @image-change="handleImage" />

    <FormFooter
      :loading="isLoading"
      submit-label="Simpan Perubahan"
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
              Konfirmasi Perubahan Data Yayasan
            </DialogTitle>
            <DialogDescription class="text-xs text-muted-foreground dark:text-zinc-400 mt-0.5">
              Apakah Anda yakin ingin menyimpan perubahan data pada {{ form.nama }}?
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <DialogFooter class="gap-2 sm:gap-0">
        <Button variant="outline" type="button" @click="isConfirmOpen = false" :disabled="isLoading">
          Batal
        </Button>
        <Button type="button" :disabled="isLoading" @click="handleSubmit" class="gap-1.5">
          <CheckCircle2 class="h-4 w-4" />
          {{ isLoading ? 'Memproses...' : 'Ya, Simpan Perubahan' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
