<script setup>
import { getPhotoUrl } from '@/utils/getPhotoUrl'
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { columns, filters, actions, jenjangOptions } from './data/sekolah.js'

const jenjangLabelMap = {
  ...Object.fromEntries(jenjangOptions.map(opt => [opt.value.toUpperCase(), opt.label])),
  TK: 'Taman Kanak-Kanak (TK)',
  SD: 'Sekolah Dasar (SD)',
  SMP: 'Sekolah Menengah Pertama (SMP)',
  SMA: 'Sekolah Menengah Atas (SMA)',
  SMK: 'Sekolah Menengah Kejuruan (SMK)'
}

const formatJenjangLong = val => {
  if (!val) return '-'
  return jenjangLabelMap[String(val).trim().toUpperCase()] || val
}

const formatJenjangShort = val => {
  if (!val) return '-'
  const upper = String(val).trim().toUpperCase()
  const shortMap = {
    JP001: 'TK',
    JP002: 'SD',
    JP003: 'SMP',
    JP004: 'SMA',
    JP005: 'SMK'
  }
  return shortMap[upper] || upper
}
import StatCard from '@/components/stat-card/StatCard.vue'
import { useAuthStore } from '@/stores/authStore'
import { computed } from 'vue'
import { ref, watch, onMounted } from 'vue'
import { toast } from 'vue-sonner'
import DataSheet from '@/components/data-sheet/DataSheet.vue'
import { sekolahSheetSections } from './data/dataSheetDetail.js'
import { getSchools, deleteSchool } from '@/services/managementService'
import { School, BookCheck, BookAlert, BookX } from 'lucide-vue-next'

const auth = useAuthStore()
const isSuperAdmin = computed(() => auth.user?.role === 'superadmin')
const perPage = ref(5)
const currentPage = ref(1)
const total = ref(0)
const from = ref(1)
const to = ref(1)
const tableItems = ref([])
const isLoading = ref(false)

const statsData = ref({
  total: 0,
  aktif: 0,
  trial: 0,
  inactive: 0
})

const fetchStats = async () => {
  try {
    const res = await getSchools({ per_page: 1000 })
    if (res.available_levels && Array.isArray(res.available_levels)) {
      dynamicJenjangOptions.value = res.available_levels
    }
    const list = Array.isArray(res.data) ? res.data : (res.data.data || [])
    statsData.value = {
      total: list.length,
      aktif: list.filter(item => item.status === 'active').length,
      trial: list.filter(item => item.status === 'trial').length,
      inactive: list.filter(item => item.status === 'inactive').length
    }
  } catch (err) {
    console.error('Gagal mengambil data statistik sekolah', err)
  }
}

const stats = computed(() => [
  {
    label: 'TOTAL SEKOLAH',
    value: String(statsData.value.total),
    sub: 'Total Unit Sekolah Terdaftar',
    icon: School,
    illustration: 'globe',
    variant: 'primary'
  },
  {
    label: 'SEKOLAH AKTIF',
    value: String(statsData.value.aktif),
    sub: 'Unit Sekolah Berstatus Aktif',
    icon: BookCheck,
    illustration: 'school_bell',
    variant: 'emerald'
  },
  {
    label: 'SEKOLAH SEDANG TRIAL',
    value: String(statsData.value.trial),
    sub: 'Unit Sekolah Masa Percobaan',
    icon: BookAlert,
    illustration: 'pencil',
    variant: 'amber'
  },
  {
    label: 'SEKOLAH TIDAK AKTIF',
    value: String(statsData.value.inactive),
    sub: 'Unit Sekolah Nonaktif',
    icon: BookX,
    illustration: 'ruler',
    variant: 'violet'
  }
])

const filterValues = ref({
  search: '',
  status: 'all',
  level: 'all'
})

const sortBy = ref('jenjang')
const sortOrder = ref('asc')

const dynamicJenjangOptions = ref([])

const dynamicFilters = computed(() => {
  const result = [
    {
      type: 'search',
      key: 'search',
      placeholder: 'Cari nama sekolah disini...'
    }
  ]

  if (dynamicJenjangOptions.value.length > 0) {
    result.push({
      type: 'select',
      key: 'level',
      label: 'Filter Jenjang:',
      placeholder: 'Semua',
      options: dynamicJenjangOptions.value
    })
  }

  result.push({
    type: 'select',
    key: 'status',
    label: 'Filter Status:',
    placeholder: 'Semua',
    options: [
      { label: 'Aktif', value: 'active' },
      { label: 'Nonaktif', value: 'inactive' },
      { label: 'Trial', value: 'trial' }
    ]
  })

  return result
})

const formatDateDisplay = (dateStr) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return d.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    })
  } catch {
    return dateStr
  }
}

const fetchSchools = async () => {
  isLoading.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: perPage.value,
      search: filterValues.value.search,
    }
    if (sortBy.value && sortOrder.value) {
      params.sort_by = sortBy.value
      params.sort_order = sortOrder.value
    }
    if (filterValues.value.status && filterValues.value.status !== 'all') {
      const statusMap = {
        aktif: 'active',
        nonaktif: 'inactive',
        trial: 'trial',
        Aktif: 'active',
        Nonaktif: 'inactive',
        Trial: 'trial'
      }
      params.status = statusMap[filterValues.value.status] || filterValues.value.status
    }
    if (filterValues.value.level && filterValues.value.level !== 'all') {
      params.level = filterValues.value.level
    }
    const res = await getSchools(params)
    if (res.available_levels && Array.isArray(res.available_levels)) {
      dynamicJenjangOptions.value = res.available_levels
    }
    tableItems.value = res.data.data.map(item => ({
      ...item,
      id: item.id,
      nama: item.name,
      npsn: item.npsn,
      yayasan: item.foundation ? item.foundation.name : '-',
      namaYayasan: item.foundation ? item.foundation.name : '-',
      jenjang: formatJenjangShort(item.level),
      jenjang_lengkap: formatJenjangLong(item.level),
      kurikulum: item.curriculum?.name || '-',
      tanggal_berdiri: formatDateDisplay(item.established_date),
      alamat: item.address,
      alamatSekolah: item.address,
      email: item.email,
      no_hp: item.phone,
      website: item.website,
      instagram: item.instagram,
      facebook: item.facebook,
      no_sk: item.decree_number,
      tanggal_sk: formatDateDisplay(item.decree_date),
      no_izin: item.permit_number,
      tanggal_izin: formatDateDisplay(item.permit_date),
      akreditasi: item.accreditation == 0 ? 'Belum Akreditasi' : item.accreditation,
      tanggal_akreditasi: formatDateDisplay(item.accreditation_date),
      no_akreditasi: item.accreditation_number,
      status: item.status === 'active' ? 'Aktif' : (item.status === 'inactive' ? 'Nonaktif' : 'Trial'),
      foto: getPhotoUrl(item.logo) || '/defaults/sd/logo.png',
      logo: getPhotoUrl(item.logo) || '/defaults/sd/logo.png',
      emailLogin: item.users?.[0]?.email || '-',
      noHpLogin: item.users?.[0]?.phone || '-',
      jmlSiswa: item.students_count || 0
    }))
    total.value = res.data.total
    from.value = res.data.from || 1
    to.value = res.data.to || 1

    if (res.stats) {
      stats.value[0].value = String(res.stats.total)
      stats.value[1].value = String(res.stats.active)
      stats.value[2].value = String(res.stats.trial)
      stats.value[3].value = String(res.stats.inactive)
    }
  } catch (error) {
    toast.error('Gagal mengambil data sekolah')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchSchools()
  fetchStats()
})

watch(
  () => [filterValues.value.search, filterValues.value.status, filterValues.value.level],
  () => {
    if (currentPage.value !== 1) {
      currentPage.value = 1
    } else {
      fetchSchools()
    }
  }
)

watch([currentPage, perPage, sortBy, sortOrder], () => {
  fetchSchools()
})

const deleteItem = async (id, item) => {
  try {
    const res = await deleteSchool(id)
    toast.success('Berhasil dihapus', {
      description: res.message || `${item.nama} telah dihapus dari sistem.`
    })
    fetchSchools()
    fetchStats()
  } catch (err) {
    const errorMsg = err.response?.data?.message || 'Gagal menghapus sekolah.'
    toast.error('Gagal Menghapus Sekolah', {
      description: errorMsg
    })
  }
}

const isDetailSheetOpen = ref(false)
const selectedItemForDetail = ref(null)

const handleViewDetail = id => {
  const item = tableItems.value.find(x => x.id === id)
  if (item) {
    selectedItemForDetail.value = item
    isDetailSheetOpen.value = true
  }
}

</script>

<template>
  <div class="space-y-6 w-full mx-auto px-0">
    <PageHeader
      title="Data Sekolah"
      description="Kelola informasi dan profil sekolah secara lengkap dan terstruktur di sini"
    />
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard
        v-for="(stat, index) in stats"
        :key="index"
        :label="stat.label"
        :value="stat.value"
        :loading="isLoading"
        :sub="stat.sub"
        :icon="stat.icon"
        :variant="stat.variant"
        :illustration="stat.illustration"
      />
    </div>

    <DataTableCard
      :columns="columns"
      :items="tableItems"
      :loading="isLoading"
      :filters="dynamicFilters"
      :actions="actions"
      v-model:filterValues="filterValues"
      v-model:perPage="perPage"
      v-model:sortBy="sortBy"
      v-model:sortOrder="sortOrder"
      manual-sort
      illustration="globe"
      :from="from"
      :to="to"
      :total="total"
      :page="currentPage"
      @update:page="currentPage = $event"
      @view="handleViewDetail"
      @edit="$router.push(`/manajemen-data/sekolah/edit?id=${$event}`)"
      @delete="deleteItem"
    />

  </div>

  <!-- Detail Sheet -->
  <DataSheet
    v-model:open="isDetailSheetOpen"
    :item="selectedItemForDetail"
    title-key="nama"
    description-key="npsn"
    description-prefix="NPSN: "
    avatar-key="logo"
    badge-key="status"
    :sections="sekolahSheetSections"
  />
</template>
