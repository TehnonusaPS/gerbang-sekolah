<script setup>
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { usePagination } from '@/composables/usePagination'
import { columns, filters, actions } from './data/siswa.js'
import StatCard from '@/components/stat-card/StatCard.vue'
import { useAuthStore } from '@/stores/authStore'
import { computed, onMounted } from 'vue'
import { ref, watch } from 'vue'
import { toast } from 'vue-sonner'
import DataSheet from '@/components/data-sheet/DataSheet.vue'
import { siswaSheetSections } from './data/dataSheetDetail.js'
import { useRouter } from 'vue-router'
import { fetchAllSiswa, deleteSiswa, getSiswaDetail } from '@/services/siswaService'
import { getClassrooms } from '@/services/managementService'
import { getPhotoUrl } from '@/utils/getPhotoUrl.js'
import { Users, UserCheck, UserRound, UserRoundCheck } from 'lucide-vue-next'

const auth = useAuthStore()
const isWaliKelas = computed(() => auth.user?.role === 'wali_kelas')
const isKepalaSekolah = computed(() => auth.user?.role === 'kepala_sekolah')
const perPage = ref(5)
const tableItems = ref([])
const activeClassrooms = ref([])
const isLoading = ref(false)

const stats = computed(() => {
  const list = items.value || []
  const totalVal = list.length
  const aktifVal = list.filter(item => item.status === 'Aktif').length
  const lakiVal = list.filter(item => item.jenisKelamin === 'Laki-laki').length
  const perempuanVal = list.filter(item => item.jenisKelamin === 'Perempuan').length
  
  const lakiProgress = totalVal > 0 ? Math.round((lakiVal / totalVal) * 100) : 0
  const perempuanProgress = totalVal > 0 ? Math.round((perempuanVal / totalVal) * 100) : 0

  return [
    {
      label: 'TOTAL SISWA',
      value: String(totalVal),
      sub: 'Jumlah Siswa Terdaftar',
      icon: Users,
      variant: 'default',
      color: 'primary'
    },
    {
      label: 'SISWA AKTIF',
      value: String(aktifVal),
      sub: 'Siswa Berstatus Aktif',
      icon: UserCheck,
      variant: 'default',
      color: 'emerald'
    },
    {
      label: 'SISWA LAKI-LAKI',
      value: String(lakiVal),
      sub: 'Komposisi Gender',
      progress: lakiProgress,
      icon: UserRound,
      variant: 'progress',
      color: 'blue'
    },
    {
      label: 'SISWA PEREMPUAN',
      value: String(perempuanVal),
      sub: 'Komposisi Gender',
      progress: perempuanProgress,
      icon: UserRoundCheck,
      variant: 'progress',
      color: 'violet'
    }
  ]
})

const filterValues = ref({
  search: '',
  status: 'all',
  kelasId: 'all'
})

const fetchSiswa = async () => {
  isLoading.value = true
  try {
    const params = {}
    if (filterValues.value.search) {
      params.search = filterValues.value.search
    }
    if (filterValues.value.status !== 'all') {
      params.status = filterValues.value.status
    }
    if (filterValues.value.kelasId !== 'all') {
      params.kelasId = filterValues.value.kelasId
    }
    const res = await fetchAllSiswa(params)
    tableItems.value = res.data.map(item => ({
      ...item,
      foto: getPhotoUrl(item.foto)
    }))

    if (res.stats) {
      stats.value[0].value = String(res.stats.total)
      stats.value[1].value = String(res.stats.active)
      stats.value[2].value = String(res.stats.male)
      stats.value[3].value = String(res.stats.female)

      const sum = res.stats.male + res.stats.female
      if (sum > 0) {
        stats.value[2].progress = Math.round((res.stats.male / sum) * 100)
        stats.value[3].progress = Math.round((res.stats.female / sum) * 100)
      } else {
        stats.value[2].progress = 0
        stats.value[3].progress = 0
      }
    }
  } catch (err) {
    toast.error('Gagal memuat data siswa')
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  try {
    const resClass = await getClassrooms()
    activeClassrooms.value = resClass.data

    const classFilter = filters.find(f => f.key === 'kelasId')

    const classroomOptions = activeClassrooms.value.map(c => ({
      label: c.name,
      value: String(c.id)
    }))

    if (!classFilter) {
      filters.push({
        type: 'select',
        key: 'kelasId',
        label: 'Kelas:',
        placeholder: 'Semua Kelas',
        options: classroomOptions
      })
    } else {
      classFilter.placeholder = 'Semua Kelas'
      classFilter.options = classroomOptions
    }
    
    // Add classroom filter dynamically
    // const classFilter = filters.find(f => f.key === 'kelasId')
    // if (!classFilter) {
    //   filters.push({
    //     type: 'select',
    //     key: 'kelasId',
    //     label: 'Kelas:',
    //     placeholder: 'Semua Kelas',
    //     options: [
    //       { label: 'Semua Kelas', value: 'all' },
    //       ...activeClassrooms.value.map(c => ({ label: c.name, value: String(c.id) }))
    //     ]
    //   })
    // } else {
    //   classFilter.options = [
    //     { label: 'Semua Kelas', value: 'all' },
    //     ...activeClassrooms.value.map(c => ({ label: c.name, value: String(c.id) }))
    //   ]
    // }
  } catch (err) {
    console.error('Gagal mengambil data kelas', err)
  }

  fetchSiswa()
})

const items = computed(() => {
  if (isWaliKelas.value) {
      return tableItems.value.filter(
        item => item.kelasId === auth.user?.kelasId
    )
  }
  return tableItems.value
})

const actionButton = computed(() => {
    if (isKepalaSekolah.value) {
      return actions.filter(
        item => item.label == 'Export'
    )
  }

  return actions
})

const deleteItem = async (id, item) => {
  try {
    await deleteSiswa(id)
    toast.success('Berhasil dihapus', {
      description: `${item.nama} telah dihapus dari sistem.`
    })
    fetchSiswa()
  } catch (err) {
    toast.error('Gagal menghapus siswa')
  }
}

const filteredItems = computed(() => {
  return items.value
})

const { currentPage, total, from, to, paginatedItems } = usePagination(filteredItems, perPage)

watch(filterValues, () => {
  currentPage.value = 1
  fetchSiswa()
}, { deep: true })

watch(filteredItems, () => {
  currentPage.value = 1
})

const isDetailSheetOpen = ref(false)
const selectedItemForDetail = ref(null)

const isDetailLoading = ref(false)
const handleViewDetail = async id => {
  selectedItemForDetail.value = null
  isDetailSheetOpen.value = true
  isDetailLoading.value = true
  try {
    const res = await getSiswaDetail(id)
    const statusMap = {
      '1': 'Aktif',
      '0': 'Nonaktif',
      '2': 'Pindah',
      '3': 'Lulus'
    }
    const genderMap = {
      'JK01': 'Laki-laki',
      'JK02': 'Perempuan'
    }
    const relationMap = {
      'ayah': 'Ayah',
      'ibu': 'Ibu',
      'wali': 'Wali',
      'lainnya': 'Lainnya'
    }
    selectedItemForDetail.value = {
      ...res.data,
      status: statusMap[res.data.status] || res.data.status,
      jenis_kelamin: genderMap[res.data.jenis_kelamin] || res.data.jenis_kelamin,
      kelamin_wali: genderMap[res.data.kelamin_wali] || res.data.kelamin_wali,
      hubungan_siswa: relationMap[res.data.hubungan_siswa] || res.data.hubungan_siswa,
      kelas: res.data.kelas_nama || '-'
    }
  } catch (err) {
    isDetailSheetOpen.value = false
    toast.error('Gagal memuat detail siswa')
  } finally {
    isDetailLoading.value = false
  }
}

const router = useRouter()
const handleEdit = (idOrItem) => {
  const id = typeof idOrItem === 'object' ? idOrItem.id : idOrItem
  router.push({ path: '/manajemen-data/siswa/edit', query: { id } })
}

</script>

<template>
  <div class="space-y-6 w-full mx-auto px-0">
    <PageHeader
      title="Data Siswa"
      description="Kelola informasi dan profil siswa secara lengkap dan terstruktur di sini"
    />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        v-for="(stat, index) in stats"
        :key="index"
        :label="stat.label"
        :value="stat.value"
        :loading="isLoading"
        :sub="stat.sub"
        :trend="stat.trend"
        :trendDirection="stat.trendDirection"
        :icon="stat.icon"
        :variant="stat.variant"
        :color="stat.color"
        :progress="stat.progress"
      />
    </div>

    <DataTableCard
      :columns="columns"
      :items="paginatedItems"
      :loading="isLoading"
      :filters="filters"
      :actions="actionButton"
      v-model:filterValues="filterValues"
      v-model:perPage="perPage"
      :from="from"
      :to="to"
      :total="total"
      :page="currentPage"
      @update:page="currentPage = $event"
      @view="handleViewDetail"
      v-bind="!isKepalaSekolah ? { onEdit: handleEdit, onDelete: deleteItem } : {}"
    />

  </div>

  <!-- Detail Sheet -->
  <DataSheet
    v-model:open="isDetailSheetOpen"
    :item="selectedItemForDetail"
    :loading="isDetailLoading"
    title-key="nama"
    description-key="nisn"
    description-prefix="NISN: "
    avatar-key="foto"
    badge-key="status"
    :sections="siswaSheetSections"
  />
</template>
