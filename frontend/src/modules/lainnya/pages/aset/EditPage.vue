<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAsetStore } from '@/stores/asetStore'
import { toast } from 'vue-sonner'
import AsetForm from '@/modules/lainnya/components/AsetForm.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'

const router = useRouter()
const route = useRoute()
const store = useAsetStore()

const asetData = ref(null)
const isLoading = ref(true)

onMounted(async () => {
  const targetId = route.params.id

  try {
    if (store.items.length === 0) {
      await store.fetchItems()
    }

    const foundData = store.getById(targetId)

    if (foundData) {
      asetData.value = foundData
    } else {
      toast.error('Data aset tidak ditemukan')
      router.push('/lainnya/aset')
    }
  } catch (error) {
    toast.error('Gagal memuat data aset')
  } finally {
    isLoading.value = false
  }
})

const isSubmitting = ref(false)

async function handleSubmit(formData) {
  isSubmitting.value = true
  try {
    await store.update(route.params.id, formData)
    toast.success('Perubahan berhasil disimpan!')
    router.push('/lainnya/aset')
  } catch (err) {
    toast.error('Gagal menyimpan perubahan')
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
      title="Edit Aset Sekolah"
      description="Ubah informasi aset sekolah di bawah ini"
      back
    />

    <div
      v-if="isLoading"
      class="flex justify-center items-center py-20"
    >
      <p class="text-muted-foreground font-medium animate-pulse">Memuat data aset...</p>
    </div>

    <AsetForm
      v-else
      :initial-data="asetData"
      is-edit
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </div>
</template>
