<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAsetStore } from '@/stores/asetStore'
import { toast } from 'vue-sonner'
import AsetForm from '@/modules/lainnya/components/AsetForm.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'

const router = useRouter()
const store = useAsetStore()

const isSubmitting = ref(false)

async function handleSubmit(formData) {
  isSubmitting.value = true
  try {
    await store.add(formData)
    toast.success('Aset berhasil ditambahkan!')
    router.push('/lainnya/aset')
  } catch (err) {
    toast.error('Gagal menambahkan aset')
  } finally {
    isSubmitting.value = false
  }
}

function handleCancel() {
  router.push('/lainnya/aset')
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Tambah Aset Sekolah"
      description="Lengkapi formulir dibawah ini untuk menambahkan aset sekolah"
      back
    />
    <AsetForm
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </div>
</template>
