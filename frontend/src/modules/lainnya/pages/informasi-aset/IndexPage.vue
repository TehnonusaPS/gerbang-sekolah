<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'
import { useAuthStore } from '@/stores/authStore'

import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Package, MapPin, Calendar, Tag, Info, School } from 'lucide-vue-next'

import AsetStatCards from '../../components/AsetStatCards.vue'
import AsetTable from '../../components/AsetTable.vue'
import { useAsetStore } from '@/stores/asetStore'
import PageHeader from '@/components/page-header/PageHeader.vue'

const router = useRouter()
const auth = useAuthStore()
const store = useAsetStore()

const isYayasan = computed(() => auth.user?.role === 'admin_yayasan')

const asets = computed(() => store.items)

onMounted(async () => {
  await store.fetchItems()
})

const isViewModalOpen = ref(false)
const selectedAset = ref(null)

const formatCurrency = value => {
  if (!value) return '-'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value)
}

const statusBadgeClass = status => {
  if (!status) return 'bg-slate-500/10 text-slate-600 border border-slate-500/20'
  const lowerStatus = status.toLowerCase()
  if (lowerStatus === 'baik')
    return 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 shadow-xs'
  if (lowerStatus === 'rusak ringan')
    return 'bg-amber-500/10 text-amber-600 border border-amber-500/20 shadow-xs'
  if (lowerStatus === 'rusak berat')
    return 'bg-rose-500/10 text-rose-600 border border-rose-500/20 shadow-xs'
  if (lowerStatus === 'perbaikan')
    return 'bg-blue-500/10 text-blue-600 border border-blue-500/20 shadow-xs'
  return 'bg-slate-500/10 text-slate-600 border border-slate-500/20'
}

function handleView(id) {
  const aset = store.getById(id)
  if (aset) {
    selectedAset.value = aset
    isViewModalOpen.value = true
  }
}

function handleExportExcel(customItems) {
  const targetAsets = Array.isArray(customItems) ? customItems : asets.value
  if (!targetAsets || targetAsets.length === 0) {
    toast.error('Tidak ada data aset untuk diekspor')
    return
  }

  const currentDate = new Date().toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })

  const totalValue = targetAsets.reduce((acc, curr) => acc + (Number(curr.value) || 0), 0)
  const formattedTotalValue = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(totalValue)

  let html = `
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
      <meta charset="utf-8">
      <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; }
        .title { font-size: 16pt; font-weight: bold; text-align: center; color: #0f172a; margin-bottom: 4px; }
        .subtitle { font-size: 10pt; text-align: center; color: #64748b; margin-bottom: 16px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th { background-color: #1e293b; color: #ffffff; border: 1px solid #334155; padding: 10px 8px; font-weight: bold; font-size: 10pt; text-align: center; }
        td { border: 1px solid #cbd5e1; padding: 7px 8px; font-size: 9.5pt; color: #1e293b; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .badge-baik { background-color: #ecfdf5; color: #047857; font-weight: bold; text-align: center; }
        .badge-rusak-ringan { background-color: #fffbeb; color: #b45309; font-weight: bold; text-align: center; }
        .badge-rusak-berat { background-color: #fef2f2; color: #b91c1c; font-weight: bold; text-align: center; }
        .footer-total { background-color: #f1f5f9; font-weight: bold; font-size: 10pt; }
      </style>
    </head>
    <body>
      <div class="title">LAPORAN INVENTARIS ASET SEKOLAH</div>
      <div class="subtitle">Dicetak pada: ${currentDate} | Total: ${targetAsets.length} Unit Aset</div>
      <table>
        <thead>
          <tr>
            <th style="width: 40px;">No</th>
            ${isYayasan.value ? '<th>Cabang Sekolah</th>' : ''}
            <th>Kode Aset</th>
            <th>Nama Aset</th>
            <th>Kategori</th>
            <th>Merk / Brand</th>
            <th>Lokasi Gedung / Ruang</th>
            <th>Kondisi</th>
            <th>Tgl Pengadaan</th>
            <th>Nilai Aset (Rp)</th>
          </tr>
        </thead>
        <tbody>
  `

  targetAsets.forEach((aset, index) => {
    const conditionClass =
      aset.condition === 'Baik'
        ? 'badge-baik'
        : aset.condition === 'Rusak Ringan'
          ? 'badge-rusak-ringan'
          : 'badge-rusak-berat'

    const location =
      [aset.building, aset.floor ? `Lt. ${aset.floor}` : '', aset.room].filter(Boolean).join(' - ') || '-'
    const formattedVal = aset.value ? Number(aset.value).toLocaleString('id-ID') : '-'

    html += `
      <tr>
        <td class="text-center">${index + 1}</td>
        ${isYayasan.value ? `<td>${aset.school_name || '-'}</td>` : ''}
        <td class="text-center" style="mso-number-format:'\\@';">${aset.code || '-'}</td>
        <td class="text-left" style="font-weight: 600;">${aset.name || '-'}</td>
        <td class="text-center">${aset.category || '-'}</td>
        <td class="text-center">${aset.brand || '-'}</td>
        <td class="text-left">${location}</td>
        <td class="${conditionClass}">${aset.condition || '-'}</td>
        <td class="text-center">${aset.date || '-'}</td>
        <td class="text-right">${formattedVal}</td>
      </tr>
    `
  })

  const colSpanCount = isYayasan.value ? 9 : 8
  html += `
        </tbody>
        <tfoot>
          <tr class="footer-total">
            <td colspan="${colSpanCount}" class="text-right" style="padding: 10px;">TOTAL NILAI INVENTARIS:</td>
            <td class="text-right" style="padding: 10px; color: #047857;">${formattedTotalValue}</td>
          </tr>
        </tfoot>
      </table>
    </body>
    </html>
  `

  const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `Laporan_Aset_Sekolah_${new Date().toISOString().slice(0, 10)}.xls`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)

  toast.success('Ekspor Excel Berhasil!', {
    description: `File inventaris ${asets.value.length} data aset berhasil diunduh.`
  })
}

function handleExportPdf(customItems) {
  const targetAsets = Array.isArray(customItems) ? customItems : asets.value
  if (!targetAsets || targetAsets.length === 0) {
    toast.error('Tidak ada data aset untuk diekspor')
    return
  }

  const currentDate = new Date().toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })

  const totalValue = targetAsets.reduce((acc, curr) => acc + (Number(curr.value) || 0), 0)
  const formattedTotalValue = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(totalValue)

  const printWindow = window.open('', '_blank')
  if (!printWindow) {
    toast.error('Popup diblokir oleh browser. Izinkan popup untuk mencetak/export PDF.')
    return
  }

  let rowsHtml = ''
  targetAsets.forEach((aset, index) => {
    const location =
      [aset.building, aset.floor ? `Lt. ${aset.floor}` : '', aset.room].filter(Boolean).join(' - ') || '-'
    const formattedVal = aset.value ? Number(aset.value).toLocaleString('id-ID') : '-'
    const condColor =
      aset.condition === 'Baik' ? '#059669' : aset.condition === 'Rusak Ringan' ? '#d97706' : '#dc2626'

    rowsHtml += `
      <tr>
        <td style="text-align: center;">${index + 1}</td>
        ${isYayasan.value ? `<td>${aset.school_name || '-'}</td>` : ''}
        <td style="font-family: monospace; font-weight: bold; text-align: center;">${aset.code || '-'}</td>
        <td style="font-weight: 600;">${aset.name || '-'}</td>
        <td style="text-align: center;">${aset.category || '-'}</td>
        <td style="text-align: center;">${aset.brand || '-'}</td>
        <td>${location}</td>
        <td style="text-align: center; color: ${condColor}; font-weight: bold;">${aset.condition || '-'}</td>
        <td style="text-align: center;">${aset.date || '-'}</td>
        <td style="text-align: right;">${formattedVal}</td>
      </tr>
    `
  })

  const colSpanCount = isYayasan.value ? 9 : 8
  const printContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Laporan Inventaris Aset Sekolah</title>
      <meta charset="utf-8">
      <style>
        @page { size: A4 landscape; margin: 15mm; }
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; padding: 20px; color: #1e293b; font-size: 10pt; }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #0f172a; padding-bottom: 12px; }
        .header h1 { margin: 0 0 6px 0; font-size: 18pt; text-transform: uppercase; letter-spacing: 0.5px; }
        .header p { margin: 0; color: #64748b; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background-color: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px 6px; font-weight: 700; font-size: 9pt; text-align: center; text-transform: uppercase; }
        td { border: 1px solid #cbd5e1; padding: 7px 6px; font-size: 9pt; vertical-align: middle; }
        .total-row { background-color: #f8fafc; font-weight: bold; }
        @media print {
          body { padding: 0; }
        }
      </style>
    </head>
    <body>
      <div class="header">
        <h1>Laporan Inventaris Aset Sekolah</h1>
        <p>Tanggal Cetak: ${currentDate} | Total Item: ${targetAsets.length} Unit Aset</p>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 30px;">No</th>
            ${isYayasan.value ? '<th>Cabang</th>' : ''}
            <th>Kode</th>
            <th>Nama Aset</th>
            <th>Kategori</th>
            <th>Merk</th>
            <th>Lokasi</th>
            <th>Kondisi</th>
            <th>Tgl Pengadaan</th>
            <th>Nilai Aset (Rp)</th>
          </tr>
        </thead>
        <tbody>
          ${rowsHtml}
        </tbody>
        <tfoot>
          <tr class="total-row">
            <td colspan="${colSpanCount}" style="text-align: right; padding: 8px;">TOTAL NILAI ASET:</td>
            <td style="text-align: right; padding: 8px; color: #047857; font-weight: bold;">${formattedTotalValue}</td>
          </tr>
        </tfoot>
      </table>

      <div style="margin-top: 40px; display: table; width: 100%; page-break-inside: avoid;">
        <div style="display: table-cell; width: 50%; text-align: center;">
          <p>Petugas Sarana & Prasarana,</p>
          <div style="height: 60px;"></div>
          <p><strong>( .................................................. )</strong></p>
        </div>
        <div style="display: table-cell; width: 50%; text-align: center;">
          <p>Mengetahui,<br>Kepala Sekolah</p>
          <div style="height: 60px;"></div>
          <p><strong>( .................................................. )</strong></p>
        </div>
      </div>
    </body>
    </html>
  `

  printWindow.document.write(printContent)
  printWindow.document.close()
  printWindow.focus()
  setTimeout(() => {
    printWindow.print()
  }, 300)

  toast.success('Mempersiapkan Dokumen PDF...', {
    description: 'Pilih opsi "Save as PDF" / "Simpan sebagai PDF" pada dialog cetak.'
  })
}
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Informasi Aset Sekolah"
      :description="
        isYayasan
          ? 'Lihat informasi aset barang di seluruh cabang sekolah yang terdaftar'
          : 'Lihat informasi aset barang dan kondisinya yang ada di sekolah'
      "
    />

    <!-- Stats Cards -->
    <AsetStatCards :is-yayasan="isYayasan" />

    <!-- Data Table & Filters -->
    <AsetTable
      :items="asets"
      readonly
      :is-yayasan="isYayasan"
      @view="handleView"
      @export-excel="handleExportExcel"
      @export-pdf="handleExportPdf"
    />

    <!-- View Aset Modal -->
    <Dialog v-model:open="isViewModalOpen">
      <DialogContent
        :show-close-button="false"
        class="sm:max-w-[500px] p-0 overflow-hidden border-border/80 rounded-2xl"
      >
        <DialogHeader class="px-6 pt-6 pb-4 border-b border-border/50 bg-muted/20">
          <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
              <DialogTitle class="text-xl font-bold flex items-center gap-2">
                <Package class="size-5 text-primary" />
                Detail Aset Sekolah
              </DialogTitle>
              <DialogDescription class="text-xs">
                Rincian lengkap dari inventaris yang dipilih.
              </DialogDescription>
            </div>
            <Badge
              v-if="selectedAset"
              :class="statusBadgeClass(selectedAset.condition)"
              class="rounded-full px-3 py-1 font-bold uppercase tracking-wider text-[10px] whitespace-nowrap"
            >
              {{ selectedAset.condition }}
            </Badge>
          </div>
        </DialogHeader>

        <div
          v-if="selectedAset"
          class="p-6 space-y-6"
        >
          <div
            class="flex items-center gap-4 p-4 rounded-xl bg-primary/5 border border-primary/10 shadow-xs"
          >
            <!-- Foto Aset / Placeholder -->
            <div
              class="size-16 sm:size-20 rounded-lg overflow-hidden bg-muted border border-border/50 shrink-0 shadow-inner relative"
            >
              <img
                v-if="selectedAset.photo || selectedAset.image"
                :src="selectedAset.photo || selectedAset.image"
                :alt="selectedAset.name"
                class="w-full h-full object-cover"
              />
              <div
                v-else
                class="w-full h-full flex flex-col items-center justify-center text-muted-foreground bg-slate-100 dark:bg-slate-800"
              >
                <Tag class="size-6 mb-1 opacity-50" />
                <span class="text-[8px] font-semibold uppercase tracking-widest opacity-60"
                  >No Photo</span
                >
              </div>
            </div>

            <div>
              <p class="text-sm font-medium text-muted-foreground">Nama Aset / Barang</p>
              <p class="text-lg font-bold text-foreground leading-tight">{{ selectedAset.name }}</p>
              <p
                class="text-xs font-mono text-muted-foreground mt-0.5 font-semibold bg-muted/50 inline-block px-1.5 py-0.5 rounded"
              >
                {{ selectedAset.code }}
              </p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <!-- Cabang Sekolah (Yayasan only) -->
            <div
              v-if="isYayasan"
              class="space-y-1.5 col-span-2"
            >
              <div
                class="flex items-center gap-1.5 text-[10px] font-bold text-muted-foreground uppercase tracking-wider"
              >
                <School class="size-3.5" />
                Cabang Sekolah
              </div>
              <p class="text-sm font-semibold">{{ selectedAset.school_name || '-' }}</p>
            </div>

            <div class="space-y-1.5">
              <div
                class="flex items-center gap-1.5 text-[10px] font-bold text-muted-foreground uppercase tracking-wider"
              >
                <Info class="size-3.5" />
                Spesifikasi
              </div>
              <p class="text-sm font-semibold">
                {{ selectedAset.brand || '-' }}
                <span class="text-muted-foreground font-medium text-xs"
                  >({{ selectedAset.category }})</span
                >
              </p>
            </div>

            <div class="space-y-1.5">
              <div
                class="flex items-center gap-1.5 text-[10px] font-bold text-muted-foreground uppercase tracking-wider"
              >
                <Calendar class="size-3.5" />
                Tgl Pengadaan
              </div>
              <p class="text-sm font-semibold">{{ selectedAset.date || '-' }}</p>
            </div>

            <div class="space-y-1.5 col-span-2">
              <div
                class="flex items-center gap-1.5 text-[10px] font-bold text-muted-foreground uppercase tracking-wider"
              >
                <MapPin class="size-3.5" />
                Lokasi Terkini
              </div>
              <p class="text-sm font-medium bg-muted/30 p-2.5 rounded-xl border border-border/60">
                <span class="font-bold block">{{ selectedAset.room || '-' }}</span>
                <span class="text-muted-foreground text-xs"
                  >{{ selectedAset.building || '-' }}, Lantai {{ selectedAset.floor || '-' }}</span
                >
              </p>
            </div>

            <div class="space-y-1.5 col-span-2">
              <div
                class="flex items-center gap-1.5 text-[10px] font-bold text-muted-foreground uppercase tracking-wider"
              >
                <Package class="size-3.5" />
                Nilai / Harga
              </div>
              <p class="text-sm font-semibold">{{ formatCurrency(selectedAset.value) }}</p>
            </div>
          </div>
        </div>

        <DialogFooter class="px-6 pt-4 pb-6 border-t border-border/50 bg-muted/20">
          <Button
            variant="outline"
            @click="isViewModalOpen = false"
            class="w-full sm:w-auto font-semibold rounded-xl"
            >Tutup</Button
          >
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
