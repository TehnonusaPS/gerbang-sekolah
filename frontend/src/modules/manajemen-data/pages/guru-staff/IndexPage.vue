<script setup>
import { getPhotoUrl } from '@/utils/getPhotoUrl'
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { columns, filters, actions } from './data/guruStaff.js'
import StatCard from '@/components/stat-card/StatCard.vue'
import { useAuthStore } from '@/stores/authStore'
import { usePagination } from '@/composables/usePagination'
import { computed, ref, watch, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import DataSheet from '@/components/data-sheet/DataSheet.vue'
import { guruStaffSheetSections } from './data/dataSheetDetail.js'
import { getTeachers, deleteTeacher } from '@/services/managementService'
import { School, BookCheck, GraduationCap, BookX } from 'lucide-vue-next'

const auth = useAuthStore()
const isAdminYayasan = computed(() => auth.user?.role === 'admin_yayasan')
const perPage = ref(5)
const tableItems = ref([])
const isLoading = ref(false)

const stats = computed(() => {
  const list = tableItems.value || []
  const totalVal = list.length
  const aktifVal = list.filter(item => item.status_aktif === 'Aktif').length
  const guruVal = list.filter(item => ['guru', 'wali_kelas', 'kepala_sekolah'].includes(item.role)).length
  const staffVal = list.filter(item => ['tata_usaha', 'admin_sekolah', 'admin_yayasan'].includes(item.role)).length

  const guruProgress = totalVal > 0 ? Math.round((guruVal / totalVal) * 100) : 0
  const staffProgress = totalVal > 0 ? Math.round((staffVal / totalVal) * 100) : 0

  return [
    {
      label: 'TOTAL PEGAWAI',
      value: String(totalVal),
      sub: 'Total Guru & Staff Terdaftar',
      icon: School,
      illustration: 'globe',
      variant: 'primary'
    },
    {
      label: 'PEGAWAI AKTIF',
      value: String(aktifVal),
      sub: 'Pegawai Berstatus Aktif',
      icon: BookCheck,
      illustration: 'school_bell',
      variant: 'emerald'
    },
    {
      label: 'TOTAL GURU',
      value: String(guruVal),
      sub: 'Proporsi Tenaga Pendidik',
      progress: guruProgress,
      icon: GraduationCap,
      illustration: 'open_book',
      variant: 'amber'
    },
    {
      label: 'TOTAL STAFF',
      value: String(staffVal),
      sub: 'Proporsi Tenaga Kependidikan',
      progress: staffProgress,
      icon: BookX,
      illustration: 'abc_board',
      variant: 'violet'
    }
  ]
})

const filterValues = ref({
  search: '',
  status: 'all'
})

const fetchTeachers = async () => {
  isLoading.value = true
  try {
    const params = {}
    const res = await getTeachers(params)
    tableItems.value = res.data.map(item => ({
      ...item,
      id: item.id,
      nama: item.nama,
      nip: item.nip_nuptk || '-',
      nip_nuptk: item.nip_nuptk || '-',
      unitKerja: item.unit_kerja,
      unitId: item.unit_id,
      jabatan: item.jabatan,
      masaKerja: item.masaKerja,
      statusKepegawaian: item.status_kepegawaian,
      status: item.status_aktif,
      foto: getPhotoUrl(item.foto)
    }))

    // if (res.stats) {
    //   stats.value[0].value = String(res.stats.total)
    //   stats.value[1].value = String(res.stats.active)
    //   stats.value[2].value = String(res.stats.guru)
    //   stats.value[3].value = String(res.stats.staff)
    // }
  } catch (err) {
    toast.error('Gagal mengambil data guru dan staff')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchTeachers()
})

const deleteItem = async (id, item) => {
  try {
    await deleteTeacher(id)
    toast.success('Berhasil dihapus', {
      description: `${item.nama} telah dihapus dari sistem.`
    })
    fetchTeachers()
  } catch (err) {
    toast.error('Gagal menghapus guru/staff')
  }
}

const filteredItems = computed(() => {
  return tableItems.value.filter(item => {
    const searchVal = filterValues.value.search?.trim().toLowerCase() || ''
    const searchMatch =
      !searchVal ||
      item.nama?.toLowerCase().includes(searchVal)

    const statusVal = filterValues.value.status
    const statusMatch = !statusVal || statusVal === 'all' || item.statusKepegawaian?.toLowerCase() === statusVal.toLowerCase()

    return searchMatch && statusMatch
  })
})

const { currentPage, total, from, to, paginatedItems } = usePagination(filteredItems, perPage)

watch(filteredItems, () => {
  currentPage.value = 1
})

const isDetailSheetOpen = ref(false)
const selectedItemForDetail = ref(null)

const handleViewDetail = id => {
  const item = tableItems.value.find(x => x.id === id)
  if (item) {
    selectedItemForDetail.value = item
    isDetailSheetOpen.value = true
    console.log('test', selectedItemForDetail.value)
  }
}

</script>
 
<template>
  <div class="space-y-6 w-full mx-auto px-0">
    <PageHeader
      title="Data Guru dan Staff"
      description="Kelola informasi data tenaga pendidik dan kependidikan secara lengkap dan terstruktur di sini"
    />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        v-for="(stat, index) in stats"
        :key="index"
        :label="stat.label"
        :value="stat.value"
        :loading="isLoading"
        :trend="stat.trend"
        :trendDirection="stat.trendDirection"
        :icon="stat.icon"
        :sub="stat.sub"
        :progress="stat.progress"
        :variant="stat.variant"
        :illustration="stat.illustration"
      />
    </div>

    <DataTableCard
      :columns="columns"
      :items="paginatedItems"
      :loading="isLoading"
      :filters="filters"
      :actions="actions"
      v-model:filterValues="filterValues"
      v-model:perPage="perPage"
      illustration="abc_board"
      :from="from"
      :to="to"
      :total="total"
      :page="currentPage"
      @update:page="currentPage = $event"
      @view="handleViewDetail"
      @edit="$router.push(`/manajemen-data/guru-staff/edit/${$event}`)"
      @delete="deleteItem"
    />

    <DataSheet
      v-model:open="isDetailSheetOpen"
      :item="selectedItemForDetail"
      title-key="nama"
      description-key="nip_nuptk"
      description-prefix="NIP/NUPTK: "
      avatar-key="foto"
      badge-key="status"
      :sections="guruStaffSheetSections"
    />
  </div>
</template>
