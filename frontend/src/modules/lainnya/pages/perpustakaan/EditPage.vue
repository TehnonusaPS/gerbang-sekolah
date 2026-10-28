<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePerpustakaanStore } from '@/stores/perpustakaanStore'
import { toast } from 'vue-sonner'
import PerpustakaanForm from '@/modules/lainnya/components/PerpustakaanForm.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'

const router = useRouter()
const route = useRoute()
const store = usePerpustakaanStore()

const bookData = ref(null)
const isLoading = ref(true)

onMounted(async () => {
  const targetId = route.params.id

  try {
    if (store.items.length === 0) {
      await store.fetchItems()
    }

    const foundData = store.getById(targetId)

    if (foundData) {
      bookData.value = foundData
    } else {
      toast.error('Data buku tidak ditemukan')
      router.push('/lainnya/perpustakaan')
    }
  } catch (error) {
    toast.error('Gagal memuat data buku')
  } finally {
    isLoading.value = false
  }
})

async function handleSubmit(formData) {
  try {
    await store.update(route.params.id, formData)
    toast.success('Perubahan buku berhasil disimpan!')
    router.push('/lainnya/perpustakaan')
  } catch (error) {
    toast.error('Gagal menyimpan perubahan buku')
    console.error(error)
  }
}

function handleCancel() {
  router.push('/lainnya/perpustakaan')
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Edit Buku Perpustakaan"
      description="Ubah informasi data buku di bawah ini"
      back
    />

    <div
      v-if="isLoading"
      class="flex justify-center items-center py-20"
    >
      <p class="text-muted-foreground font-medium animate-pulse">Memuat data buku...</p>
    </div>

    <PerpustakaanForm
      v-else
      :initial-data="bookData"
      is-edit
      @submit="handleSubmit"
      @cancel="handleCancel"
    />
  </div>
</template>
