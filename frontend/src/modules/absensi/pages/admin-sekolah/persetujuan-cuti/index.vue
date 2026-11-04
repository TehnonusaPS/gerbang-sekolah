<script setup>
import { ref, onMounted, computed } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs'
import { Check, X, AlertCircle } from 'lucide-vue-next'
import { getAdminLeaveRequests, actionLeaveRequest } from '@/services/api/absensi'
import PageHeader from '@/components/page-header/PageHeader.vue'
import { glassFade, glassSlide, tableRowFade } from '@/config/motion'

// ─── STATE ──────────────────────────────────────────────────
const isPageLoading = ref(true)
const isSubmitting = ref(false)
const isRejectDialogOpen = ref(false)

const leaveList = ref([])
const activeTab = ref('pending')
const selectedLeave = ref(null)
const rejectReason = ref('')

// ─── COMPUTED ───────────────────────────────────────────────
const filteredLeaves = computed(() => {
  if (activeTab.value === 'pending') {
    return leaveList.value.filter(leave => leave.status === 'pending')
  } else {
    return leaveList.value.filter(leave => leave.status !== 'pending')
  }
})

// ─── METHODS ────────────────────────────────────────────────
async function loadLeaveRequests() {
  isPageLoading.value = true
  try {
    const response = await getAdminLeaveRequests()
    if (response.success) {
      leaveList.value = response.data
    }
  } catch (error) {
    console.error(error)
    toast.error('Gagal memuat daftar permintaan cuti/izin')
  } finally {
    isPageLoading.value = false
  }
}

async function handleApprove(leave) {
  isSubmitting.value = true
  try {
    const response = await actionLeaveRequest(leave.id, { status: 'approved' })
    if (response.success) {
      toast.success('Persetujuan Berhasil', { description: response.message })
      loadLeaveRequests()
    }
  } catch (error) {
    console.error(error)
    toast.error('Gagal menyetujui pengajuan')
  } finally {
    isSubmitting.value = false
  }
}

function openRejectDialog(leave) {
  selectedLeave.value = leave
  rejectReason.value = ''
  isRejectDialogOpen.value = true
}

async function handleRejectSubmit() {
  if (!rejectReason.value) {
    toast.error('Alasan Penolakan Wajib Diisi')
    return
  }

  isSubmitting.value = true
  try {
    const response = await actionLeaveRequest(selectedLeave.value.id, {
      status: 'rejected',
      rejection_reason: rejectReason.value
    })
    if (response.success) {
      toast.success('Penolakan Berhasil', { description: response.message })
      isRejectDialogOpen.value = false
      selectedLeave.value = null
      rejectReason.value = ''
      loadLeaveRequests()
    }
  } catch (error) {
    console.error(error)
    toast.error('Gagal menolak pengajuan')
  } finally {
    isSubmitting.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

// ─── LIFECYCLE ──────────────────────────────────────────────
onMounted(() => {
  loadLeaveRequests()
})
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 animate-in fade-in duration-300 w-full pb-10"
  >
    <!-- ── Header ── -->
    <PageHeader
      title="Persetujuan Cuti & Izin Staff"
      description="Kelola dan verifikasi permohonan cuti atau izin kehadiran kerja dari guru dan staff sekolah."
    />

    <!-- Tabs for Filtering -->
    <Tabs v-model="activeTab" class="w-full">
      <div class="flex items-center justify-between pb-2 border-b">
        <TabsList>
          <TabsTrigger value="pending">Menunggu Persetujuan</TabsTrigger>
          <TabsTrigger value="history">Riwayat Keputusan</TabsTrigger>
        </TabsList>
      </div>

      <TabsContent value="pending" class="mt-6">
        <Card
          v-motion
          :initial="glassSlide.initial"
          :visible-once="glassSlide.visible"
        >
          <CardContent class="p-0 overflow-auto">
            <Table>
              <TableHeader>
                <TableRow class="bg-muted/50 hover:bg-muted/50">
                  <TableHead>Nama Staff</TableHead>
                  <TableHead>Peran</TableHead>
                  <TableHead>Tipe</TableHead>
                  <TableHead>Mulai Tanggal</TableHead>
                  <TableHead>Selesai Tanggal</TableHead>
                  <TableHead class="max-w-xs">Alasan</TableHead>
                  <TableHead class="text-right">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="isPageLoading">
                  <TableRow v-for="i in 3" :key="`skel-pending-${i}`">
                    <TableCell><Skeleton class="h-5 w-32" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-20" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-16" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-24" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-24" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-40" /></TableCell>
                    <TableCell><Skeleton class="h-8 w-24 ml-auto" /></TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow 
                    v-for="(leave, index) in filteredLeaves" 
                    :key="leave.id"
                    v-motion
                    :initial="tableRowFade.initial"
                    :visible-once="{ ...tableRowFade.visible, transition: { ...tableRowFade.visible.transition, delay: index * 60 } }"
                    class="hover:bg-muted/30 transition-colors"
                  >
                    <TableCell class="font-semibold">{{ leave.user?.name }}</TableCell>
                    <TableCell class="capitalize text-muted-foreground text-xs">
                      {{ leave.user?.role?.name === 'guru' ? (leave.user?.is_wali_kelas ? 'Wali Kelas' : 'Guru') : leave.user?.role?.name === 'kepala_sekolah' ? 'Kepala Sekolah' : leave.user?.role?.name === 'tata_usaha' ? 'Tata Usaha' : 'Admin' }}
                    </TableCell>
                    <TableCell>
                      <Badge 
                        :variant="leave.type === 'Cuti' ? 'default' : 'secondary'"
                        :class="leave.type === 'Cuti' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400'"
                      >
                        {{ leave.type }}
                      </Badge>
                    </TableCell>
                    <TableCell>{{ formatDate(leave.start_date) }}</TableCell>
                    <TableCell>{{ formatDate(leave.end_date) }}</TableCell>
                    <TableCell class="max-w-xs truncate text-muted-foreground text-sm" :title="leave.reason">
                      {{ leave.reason }}
                    </TableCell>
                    <TableCell class="text-right">
                      <div class="flex items-center justify-end gap-2">
                        <Button 
                          size="sm" 
                          variant="outline" 
                          class="h-8 px-2.5 text-green-600 hover:text-green-700 hover:bg-green-50"
                          @click="handleApprove(leave)"
                          :disabled="isSubmitting"
                        >
                          <Check class="size-4 mr-1" />
                          Setujui
                        </Button>
                        <Button 
                          size="sm" 
                          variant="outline" 
                          class="h-8 px-2.5 text-red-600 hover:text-red-700 hover:bg-red-50"
                          @click="openRejectDialog(leave)"
                          :disabled="isSubmitting"
                        >
                          <X class="size-4 mr-1" />
                          Tolak
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>

                  <TableRow v-if="filteredLeaves.length === 0">
                    <TableCell colspan="7" class="h-32 text-center text-muted-foreground">
                      <div class="flex flex-col items-center justify-center gap-2">
                        <AlertCircle class="size-8 text-muted-foreground" />
                        <span>Tidak ada permohonan yang menunggu persetujuan.</span>
                      </div>
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </TabsContent>

      <TabsContent value="history" class="mt-6">
        <Card
          v-motion
          :initial="glassSlide.initial"
          :visible-once="glassSlide.visible"
        >
          <CardContent class="p-0 overflow-auto">
            <Table>
              <TableHeader>
                <TableRow class="bg-muted/50 hover:bg-muted/50">
                  <TableHead>Nama Staff</TableHead>
                  <TableHead>Tipe</TableHead>
                  <TableHead>Mulai</TableHead>
                  <TableHead>Selesai</TableHead>
                  <TableHead class="max-w-xs">Alasan</TableHead>
                  <TableHead>Status Keputusan</TableHead>
                  <TableHead>Alasan Ditolak</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <template v-if="isPageLoading">
                  <TableRow v-for="i in 3" :key="`skel-history-${i}`">
                    <TableCell><Skeleton class="h-5 w-32" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-16" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-24" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-24" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-40" /></TableCell>
                    <TableCell><Skeleton class="h-6 w-20 rounded-full" /></TableCell>
                    <TableCell><Skeleton class="h-5 w-32" /></TableCell>
                  </TableRow>
                </template>
                <template v-else>
                  <TableRow 
                    v-for="(leave, index) in filteredLeaves" 
                    :key="leave.id"
                    v-motion
                    :initial="tableRowFade.initial"
                    :visible-once="{ ...tableRowFade.visible, transition: { ...tableRowFade.visible.transition, delay: index * 60 } }"
                    class="hover:bg-muted/30 transition-colors"
                  >
                    <TableCell class="font-semibold">{{ leave.user?.name }}</TableCell>
                    <TableCell>
                      <Badge 
                        :variant="leave.type === 'Cuti' ? 'default' : 'secondary'"
                        :class="leave.type === 'Cuti' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400'"
                      >
                        {{ leave.type }}
                      </Badge>
                    </TableCell>
                    <TableCell>{{ formatDate(leave.start_date) }}</TableCell>
                    <TableCell>{{ formatDate(leave.end_date) }}</TableCell>
                    <TableCell class="max-w-xs truncate text-muted-foreground text-sm" :title="leave.reason">
                      {{ leave.reason }}
                    </TableCell>
                    <TableCell>
                      <Badge
                        :variant="leave.status === 'approved' ? 'default' : 'destructive'"
                        :class="{
                          'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400 border-none': leave.status === 'approved',
                          'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 border-none': leave.status === 'rejected',
                        }"
                      >
                        {{ leave.status === 'approved' ? 'Disetujui' : 'Ditolak' }}
                      </Badge>
                    </TableCell>
                    <TableCell class="text-sm text-red-500 font-medium">
                      {{ leave.rejection_reason || '-' }}
                    </TableCell>
                  </TableRow>

                  <TableRow v-if="filteredLeaves.length === 0">
                    <TableCell colspan="7" class="h-32 text-center text-muted-foreground">
                      <div class="flex flex-col items-center justify-center gap-2">
                        <AlertCircle class="size-8 text-muted-foreground" />
                        <span>Belum ada riwayat keputusan pengajuan.</span>
                      </div>
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </TabsContent>
    </Tabs>

    <!-- Dialog Penolakan Cuti -->
    <Dialog :open="isRejectDialogOpen" @update:open="(val) => isRejectDialogOpen = val">
      <DialogContent class="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Tolak Pengajuan Cuti / Izin</DialogTitle>
          <DialogDescription>
            Tuliskan alasan penolakan untuk staff <strong>{{ selectedLeave?.user?.name }}</strong>. Alasan ini akan dikirimkan melalui notifikasi ke staff tersebut.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-3 py-4">
          <Label for="reject-reason">Alasan Penolakan</Label>
          <Textarea 
            id="reject-reason" 
            placeholder="Tulis alasan penolakan secara jelas..." 
            v-model="rejectReason"
            rows="4"
          />
        </div>

        <DialogFooter>
          <Button variant="outline" @click="isRejectDialogOpen = false" :disabled="isSubmitting">Batal</Button>
          <Button variant="destructive" @click="handleRejectSubmit" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-destructive-foreground border-t-transparent"></span>
            Tolak Pengajuan
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
