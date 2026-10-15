<script setup>
import DataTableCard from '@/components/data-table/DataTableCard.vue'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { Building2, School, Settings, Users2, VenusAndMars } from 'lucide-vue-next'
import { actions, allItems, columns, filters } from './data/siswaKelas'
import { usePagination } from '@/composables/usePagination'
import { ref, computed } from 'vue'
import { toast } from 'vue-sonner'
import Card from '@/components/ui/card/Card.vue'
import CardHeader from '@/components/ui/card/CardHeader.vue'
import CardContent from '@/components/ui/card/CardContent.vue'
import CardDescription from '@/components/ui/card/CardDescription.vue'
import CardTitle from '@/components/ui/card/CardTitle.vue'
import CardFooter from '@/components/ui/card/CardFooter.vue'
import Button from '@/components/ui/button/Button.vue'
import Separator from '@/components/ui/separator/Separator.vue'
import Avatar from '@/components/ui/avatar/Avatar.vue'
import AvatarImage from '@/components/ui/avatar/AvatarImage.vue'
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const perPage = ref(10)
const tableItems = ref([...allItems.value])

const goToManageStudent = () => {
  const classId = route.params.id
  if (classId) {
    router.push(`/manajemen-data/kelas/detail/${classId}/manage`)
  } else {
    router.push('/manajemen-data/kelas')
  }
}

const headerAction = [
  {
    label: 'Kelola Anggota Kelas',
    icon: Settings,
    click: goToManageStudent
  }
]

const filterValues = ref({
  search: '',
  status: 'all'
})

const deleteItem = (id, item) => {
  tableItems.value = tableItems.value.filter(item => item.id !== id)

  toast.success('Berhasil dihapus', {
    description: `${item.nama} telah dihapus dari sistem.`
  })
}

const filteredItems = computed(() => {
  return tableItems.value.filter(item => {
    const searchVal = filterValues.value.search?.trim().toLowerCase() || ''
    const searchMatch = !searchVal || item.nama.toLowerCase().includes(searchVal)

    const statusVal = filterValues.value.status
    const statusMatch = !statusVal || statusVal === 'all' || item.status === statusVal

    return searchMatch && statusMatch
  })
})

const { currentPage, total, from, to, paginatedItems } = usePagination(filteredItems, perPage)
</script>

<template>
  <div class="space-y-6 w-full mx-auto px-0 mb-6">
    <PageHeader
      back
      title="Kelas 1-A"
      description="Lihat informasi lengkap tentang kelas, termasuk profil kelas, wali kelas, daftar siswa, dan data lainnya."
      :actions="headerAction"
    />
  </div>

  <div class="grid gap-6 md:grid-cols-4">
    <!-- Kolom Kiri: Overview Kelas -->
    <div class="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle class="text-lg">Informasi Kelas</CardTitle>
          <CardDescription>Informasi mengenai kelas 1-A</CardDescription>
        </CardHeader>
        <Separator />
        <CardContent>
          <div class="flex items-center gap-4 border px-4 py-3 rounded-lg bg-background mb-4">
            <School class="w-6 h-6 text-primary" />
            <div>
              <p class="text-xs text-muted-foreground">KELAS</p>
              <p class="font-semibold text-sm">1-A</p>
            </div>
          </div>

          <div class="flex items-center gap-4 border px-4 py-3 rounded-lg bg-background mb-4">
            <Users2 class="w-6 h-6 text-primary" />
            <div>
              <p class="text-xs text-muted-foreground">TOTAL SISWA</p>
              <p class="font-semibold text-sm">25</p>
            </div>
          </div>

          <div class="flex items-center gap-4 border px-4 py-3 rounded-lg bg-background mb-4">
            <VenusAndMars class="w-6 h-6 text-primary" />
            <div>
              <p class="text-xs text-muted-foreground">LAKI-LAKI/PEREMPUAN</p>
              <p class="font-semibold text-sm">13/12</p>
            </div>
          </div>

          <div class="flex items-center gap-4 border px-4 py-3 rounded-lg bg-background mb-1">
            <Building2 class="w-6 h-6 text-primary" />
            <div>
              <p class="text-xs text-muted-foreground">RUANGAN</p>
              <p class="font-semibold text-sm">Gedung A, Lantai 2 (A-201)</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="text-lg">Wali Kelas</CardTitle>
          <CardDescription>Informasi wali kelas 1-A</CardDescription>
        </CardHeader>
        <Separator />
        <CardContent>
          <div class="flex items-center gap-4 py-1">
            <Avatar class="shrink-0 size-20">
              <AvatarImage
                src="https://i.pravatar.cc/150?img=12"
                alt="Foto Profil"
              />
              <AvatarFallback class="text-[11px] font-bold"> AA </AvatarFallback>
            </Avatar>
            <div>
              <p class="font-semibold text-base mb-1">Fajar Mukarami, S.Pd.</p>
              <p class="text-xs text-muted-foreground mb-1">GURU BAHASA INDONESIA</p>
              <p class="font-medium text-sm text-primary mb-1">NIP: 0123456789</p>
            </div>
          </div>
        </CardContent>
        <CardFooter class="flex justify-end gap-2">
          <Button>Hubungi Wali Kelas</Button>
        </CardFooter>
      </Card>
    </div>

    <!-- Kolom Kanan: Tabel daftar siswa -->
    <div class="md:col-span-3 space-y-6">
      <DataTableCard
        :columns="columns"
        :items="paginatedItems"
        :filters="filters"
        :actions="actions"
        v-model:filterValues="filterValues"
        v-model:perPage="perPage"
        illustration="globe"
        :from="from"
        :to="to"
        :total="total"
        :page="currentPage"
        @update:page="currentPage = $event"
        @edit="$router.push('/manajemen-data/manajemen-kelas/edit')"
        @delete="deleteItem"
      >
        <template #cell-kehadiran="{ item }">
          <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
              <span
                class="text-sm font-medium"
                :class="item.kehadiran >= 100 ? 'text-primary' : 'text-amber-600'"
              >
                {{ item.kehadiran }}
              </span>
              <span class="text-muted-foreground text-xs">%</span>
            </div>
            <div class="w-full bg-secondary rounded-full h-1.5 overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-300"
                :class="item.kehadiran >= 100 ? 'bg-primary' : 'bg-amber-500'"
                :style="{ width: `${Math.min((item.kehadiran / 100) * 100, 100)}%` }"
              ></div>
            </div>
          </div>
        </template>
      </DataTableCard>
    </div>
  </div>
</template>
