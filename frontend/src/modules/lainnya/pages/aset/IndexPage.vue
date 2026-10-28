<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useRouter } from 'vue-router'
import { Lock } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import PageHeader from '@/components/page-header/PageHeader.vue'

import AsetStatCards from '../../components/AsetStatCards.vue'
import AsetTable from '../../components/AsetTable.vue'
import { useAsetStore } from '@/stores/asetStore'
import { toast } from 'vue-sonner'

const auth = useAuthStore()
const router = useRouter()
const store = useAsetStore()

onMounted(async () => {
  await store.fetchItems()
})

const asets = computed(() => store.items)

async function handleDelete(id) {
  try {
    await store.delete(id)
    toast.success('Aset berhasil dihapus!')
  } catch (error) {
    toast.error('Gagal menghapus aset')
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

  const schoolName = auth.user?.school?.name || targetAsets[0]?.school_name || ''
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
        .school-name { font-size: 11pt; font-weight: bold; text-align: center; color: #334155; margin-bottom: 4px; }
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
      ${schoolName ? `<div class="school-name">${schoolName.toUpperCase()}</div>` : ''}
      <div class="subtitle">Dicetak pada: ${currentDate} | Total: ${targetAsets.length} Unit Aset</div>
      <table>
        <thead>
          <tr>
            <th style="width: 40px;">No</th>
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

  html += `
        </tbody>
        <tfoot>
          <tr class="footer-total">
            <td colspan="8" class="text-right" style="padding: 10px;">TOTAL NILAI INVENTARIS:</td>
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
    description: `File inventaris ${targetAsets.length} data aset berhasil diunduh.`
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

  const schoolName = auth.user?.school?.name || targetAsets[0]?.school_name || ''
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
        .header h1 { margin: 0 0 4px 0; font-size: 18pt; text-transform: uppercase; letter-spacing: 0.5px; }
        .header .school { margin: 0 0 6px 0; font-size: 12pt; font-weight: bold; color: #0f172a; text-transform: uppercase; }
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
        ${schoolName ? `<div class="school">${schoolName}</div>` : ''}
        <p>Tanggal Cetak: ${currentDate} | Total Item: ${targetAsets.length} Unit Aset</p>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 30px;">No</th>
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
            <td colspan="8" style="text-align: right; padding: 8px;">TOTAL NILAI ASET:</td>
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

import DataSheet from '@/components/data-sheet/DataSheet.vue'

const isDetailSheetOpen = ref(false)
const selectedItemForDetail = ref(null)

const handleView = (id) => {
  const item = store.getById(id)
  if (item) {
    selectedItemForDetail.value = item
    isDetailSheetOpen.value = true
  }
}

const rawDetailItem = computed(() => {
  if (!selectedItemForDetail.value) return {}
  return {
    name: selectedItemForDetail.value.name,
    condition: selectedItemForDetail.value.condition
  }
})

const detailSections = computed(() => {
  if (!selectedItemForDetail.value) return []
  const item = selectedItemForDetail.value
  return [
    {
      id: 'info',
      title: 'Informasi Aset',
      fields: [
        { label: 'Nama Aset', value: item.name },
        { label: 'Kode', value: item.code },
        { label: 'Kategori', value: item.category },
        { label: 'Merk/Tipe', value: item.brand || '-' },
        { label: 'Gedung', value: item.building || '-' },
        { label: 'Ruangan', value: item.room || '-' },
        { label: 'Nilai', value: `Rp ${Number(item.value).toLocaleString('id-ID')}` },
        { label: 'Kondisi', value: item.condition }
      ]
    }
  ]
})
</script>

<template>
  <div
    v-if="auth.user?.role === 'admin_sekolah'"
    class="space-y-6"
  >
    <PageHeader
      title="Manajemen Aset Sekolah"
      description="Kelola aset barang yang ada di sekolah"
    />

    <!-- Stats Cards -->
    <AsetStatCards />

    <!-- Data Table & Filters -->
    <AsetTable
      :items="asets"
      @delete="handleDelete"
      @view="handleView"
      @export-excel="handleExportExcel"
      @export-pdf="handleExportPdf"
    />

    <!-- Detail Sheet -->
    <DataSheet
      v-model:open="isDetailSheetOpen"
      :item="rawDetailItem"
      title-key="name"
      :badge="rawDetailItem.condition"
      :badge-variant="
        rawDetailItem.condition === 'Baik'
          ? 'green'
          : rawDetailItem.condition === 'Rusak Ringan'
            ? 'amber'
            : 'red'
      "
      :sections="detailSections"
    />
  </div>

  <div
    v-else
    class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4"
  >
    <div
      class="bg-card border border-border rounded-2xl p-8 max-w-md w-full shadow-sm flex flex-col items-center"
    >
      <div
        class="size-16 bg-primary/10 text-primary rounded-2xl flex items-center justify-center mb-6 border border-primary/20"
      >
        <Lock class="size-8" />
      </div>
      <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-foreground mb-2">
        Akses Dibatasi
      </h2>
      <p class="text-sm text-muted-foreground leading-relaxed mb-8">
        Halaman Manajemen Aset untuk role Anda (Kepala Sekolah / Admin Yayasan) belum tersedia dan
        sedang dalam tahap pengembangan.
      </p>
      <Button
        @click="router.push('/dashboard')"
        size="lg"
        class="w-full font-semibold rounded-xl transition-all active:scale-[0.98]"
      >
        Kembali ke Dashboard
      </Button>
    </div>
  </div>
</template>
