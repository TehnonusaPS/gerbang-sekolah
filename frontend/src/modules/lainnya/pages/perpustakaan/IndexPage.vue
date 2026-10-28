<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { useRouter } from 'vue-router'
import { Lock } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

import PageHeader from '@/components/page-header/PageHeader.vue'
import PerpustakaanStatCards from '../../components/PerpustakaanStatCards.vue'
import PerpustakaanTable from '../../components/PerpustakaanTable.vue'
import { usePerpustakaanStore } from '@/stores/perpustakaanStore'
import { computed, onMounted } from 'vue'
import { toast } from 'vue-sonner'

const auth = useAuthStore()
const router = useRouter()
const store = usePerpustakaanStore()

onMounted(async () => {
  await store.fetchItems()
})

const books = computed(() => store.items)

async function handleDelete(id) {
  try {
    await store.delete(id)
    toast.success('Buku berhasil dihapus!')
  } catch (error) {
    toast.error('Gagal menghapus buku')
  }
}

function handleExportExcel(customItems) {
  const targetBooks = Array.isArray(customItems) ? customItems : books.value
  if (!targetBooks || targetBooks.length === 0) {
    toast.error('Tidak ada data buku untuk diekspor')
    return
  }

  const currentDate = new Date().toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })

  const schoolName = auth.user?.school?.name || targetBooks[0]?.school_name || ''
  const totalTitles = targetBooks.length
  const totalStock = targetBooks.reduce((acc, curr) => acc + (Number(curr.jumlahStok) || 0), 0)

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
        .badge-kategori { background-color: #f1f5f9; font-weight: 600; text-align: center; }
        .footer-total { background-color: #f1f5f9; font-weight: bold; font-size: 10pt; }
      </style>
    </head>
    <body>
      <div class="title">KATALOG BUKU PERPUSTAKAAN SEKOLAH</div>
      ${schoolName ? `<div class="school-name">${schoolName.toUpperCase()}</div>` : ''}
      <div class="subtitle">Dicetak pada: ${currentDate} | Total: ${totalTitles} Judul (${totalStock} Eksemplar)</div>
      <table>
        <thead>
          <tr>
            <th style="width: 40px;">No</th>
            <th>ISBN</th>
            <th>Judul Buku</th>
            <th>Kategori</th>
            <th>Penulis / Pengarang</th>
            <th>Penerbit</th>
            <th>Tahun</th>
            <th>Lokasi Rak</th>
            <th>Jumlah Stok</th>
          </tr>
        </thead>
        <tbody>
  `

  targetBooks.forEach((book, index) => {
    html += `
      <tr>
        <td class="text-center">${index + 1}</td>
        <td class="text-center" style="mso-number-format:'\\@';">${book.isbn || '-'}</td>
        <td class="text-left" style="font-weight: 600;">${book.name || '-'}</td>
        <td class="badge-kategori">${book.kategori || '-'}</td>
        <td class="text-left">${book.penulis || '-'}</td>
        <td class="text-left">${book.penerbit || '-'}</td>
        <td class="text-center">${book.tahunTerbit || '-'}</td>
        <td class="text-center">${book.lokasiRak || '-'}</td>
        <td class="text-center" style="font-weight: 600;">${book.jumlahStok || 0}</td>
      </tr>
    `
  })

  html += `
        </tbody>
        <tfoot>
          <tr class="footer-total">
            <td colspan="8" class="text-right" style="padding: 10px;">TOTAL KOLEKSI BUKU:</td>
            <td class="text-center" style="padding: 10px; color: #2563eb;">${totalStock} Eksemplar</td>
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
  link.setAttribute('download', `Katalog_Perpustakaan_${new Date().toISOString().slice(0, 10)}.xls`)
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)

  toast.success('Ekspor Excel Berhasil!', {
    description: `Katalog ${totalTitles} judul buku perpustakaan berhasil diunduh.`
  })
}

function handleExportPdf(customItems) {
  const targetBooks = Array.isArray(customItems) ? customItems : books.value
  if (!targetBooks || targetBooks.length === 0) {
    toast.error('Tidak ada data buku untuk diekspor')
    return
  }

  const currentDate = new Date().toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })

  const schoolName = auth.user?.school?.name || targetBooks[0]?.school_name || ''
  const totalTitles = targetBooks.length
  const totalStock = targetBooks.reduce((acc, curr) => acc + (Number(curr.jumlahStok) || 0), 0)

  const printWindow = window.open('', '_blank')
  if (!printWindow) {
    toast.error('Popup diblokir oleh browser. Izinkan popup untuk mencetak/export PDF.')
    return
  }

  let rowsHtml = ''
  targetBooks.forEach((book, index) => {
    rowsHtml += `
      <tr>
        <td style="text-align: center;">${index + 1}</td>
        <td style="font-family: monospace; font-weight: bold; text-align: center;">${book.isbn || '-'}</td>
        <td style="font-weight: 600;">${book.name || '-'}</td>
        <td style="text-align: center;">${book.kategori || '-'}</td>
        <td>${book.penulis || '-'}</td>
        <td>${book.penerbit || '-'}</td>
        <td style="text-align: center;">${book.tahunTerbit || '-'}</td>
        <td style="text-align: center;">${book.lokasiRak || '-'}</td>
        <td style="text-align: center; font-weight: bold;">${book.jumlahStok || 0}</td>
      </tr>
    `
  })

  const printContent = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Katalog Buku Perpustakaan Sekolah</title>
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
        <h1>Katalog Buku Perpustakaan Sekolah</h1>
        ${schoolName ? `<div class="school">${schoolName}</div>` : ''}
        <p>Tanggal Cetak: ${currentDate} | Total: ${totalTitles} Judul (${totalStock} Eksemplar)</p>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 30px;">No</th>
            <th>ISBN</th>
            <th>Judul Buku</th>
            <th>Kategori</th>
            <th>Penulis</th>
            <th>Penerbit</th>
            <th>Tahun</th>
            <th>Rak</th>
            <th>Stok</th>
          </tr>
        </thead>
        <tbody>
          ${rowsHtml}
        </tbody>
        <tfoot>
          <tr class="total-row">
            <td colspan="8" style="text-align: right; padding: 8px;">TOTAL BUKU FISIK:</td>
            <td style="text-align: center; padding: 8px; color: #2563eb; font-weight: bold;">${totalStock}</td>
          </tr>
        </tfoot>
      </table>

      <div style="margin-top: 40px; display: table; width: 100%; page-break-inside: avoid;">
        <div style="display: table-cell; width: 50%; text-align: center;">
          <p>Pengelola Perpustakaan,</p>
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

const handleView = id => {
  const item = books.value.find(a => a.id === id)
  if (item) {
    selectedItemForDetail.value = item
    isDetailSheetOpen.value = true
  }
}

const rawDetailItem = computed(() => {
  if (!selectedItemForDetail.value) return {}
  return {
    name: selectedItemForDetail.value.name,
    kategori: selectedItemForDetail.value.kategori
  }
})

const detailSections = computed(() => {
  if (!selectedItemForDetail.value) return []
  const item = selectedItemForDetail.value
  return [
    {
      id: 'info',
      title: 'Informasi Buku',
      fields: [
        { label: 'Judul Buku', value: item.name },
        { label: 'ISBN', value: item.isbn || '-' },
        { label: 'Kategori', value: item.kategori },
        { label: 'Penulis', value: item.penulis || '-' },
        { label: 'Penerbit', value: item.penerbit || '-' },
        { label: 'Tahun Terbit', value: item.tahunTerbit || '-' },
        { label: 'Jumlah Stok', value: `${item.jumlahStok} Buku` },
        { label: 'Lokasi Rak', value: item.lokasiRak || '-' },
        { label: 'Deskripsi', value: item.deskripsi || '-' }
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
      title="Manajemen Buku Perpustakaan"
      description="Kelola koleksi buku yang ada di perpustakaan sekolah"
    />

    <!-- Stats Cards -->
    <PerpustakaanStatCards />

    <!-- Data Table & Filters -->
    <PerpustakaanTable
      :items="books"
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
      :badge="rawDetailItem.kategori"
      :badge-variant="
        rawDetailItem.kategori === 'Sains'
          ? 'blue'
          : rawDetailItem.kategori === 'TIK'
            ? 'green'
            : rawDetailItem.kategori === 'Bahasa'
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
        Halaman Manajemen Perpustakaan untuk role Anda (Kepala Sekolah / Admin Yayasan) belum
        tersedia dan sedang dalam tahap pengembangan.
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
