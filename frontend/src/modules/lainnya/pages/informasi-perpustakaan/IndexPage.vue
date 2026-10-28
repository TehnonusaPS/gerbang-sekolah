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
import { Book, Hash, Calendar, BookOpen, Layers, School } from 'lucide-vue-next'

import PerpustakaanStatCards from '../../components/PerpustakaanStatCards.vue'
import PerpustakaanTable from '../../components/PerpustakaanTable.vue'
import { usePerpustakaanStore } from '@/stores/perpustakaanStore'
import PageHeader from '@/components/page-header/PageHeader.vue'

const router = useRouter()
const auth = useAuthStore()
const store = usePerpustakaanStore()

const isYayasan = computed(() => auth.user?.role === 'admin_yayasan')

const books = computed(() => store.items)

onMounted(async () => {
  await store.fetchItems()
})
const isViewModalOpen = ref(false)
const selectedBook = ref(null)

const categoryBadgeClass = category => {
  if (!category) return 'bg-slate-500/10 text-slate-600 border border-slate-500/20'
  const lowerCat = category.toLowerCase()
  if (lowerCat === 'sains')
    return 'bg-blue-500/10 text-blue-600 border border-blue-500/20 shadow-xs'
  if (lowerCat === 'tik')
    return 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 shadow-xs'
  if (lowerCat === 'bahasa')
    return 'bg-amber-500/10 text-amber-600 border border-amber-500/20 shadow-xs'
  if (lowerCat === 'sejarah')
    return 'bg-orange-500/10 text-orange-600 border border-orange-500/20 shadow-xs'
  return 'bg-violet-500/10 text-violet-600 border border-violet-500/20 shadow-xs'
}

function handleView(id) {
  const book = store.getById(id)
  if (book) {
    selectedBook.value = book
    isViewModalOpen.value = true
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

  const totalTitles = targetBooks.length
  const totalStock = targetBooks.reduce((acc, curr) => acc + (Number(curr.jumlahStok) || 0), 0)

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
        .badge-kategori { background-color: #f1f5f9; font-weight: 600; text-align: center; }
        .footer-total { background-color: #f1f5f9; font-weight: bold; font-size: 10pt; }
      </style>
    </head>
    <body>
      <div class="title">KATALOG BUKU PERPUSTAKAAN SEKOLAH</div>
      <div class="subtitle">Dicetak pada: ${currentDate} | Total: ${totalTitles} Judul (${totalStock} Eksemplar)</div>
      <table>
        <thead>
          <tr>
            <th style="width: 40px;">No</th>
            ${isYayasan.value ? '<th>Cabang Sekolah</th>' : ''}
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
        ${isYayasan.value ? `<td>${book.school_name || '-'}</td>` : ''}
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

  const colSpanCount = isYayasan.value ? 9 : 8
  html += `
        </tbody>
        <tfoot>
          <tr class="footer-total">
            <td colspan="${colSpanCount}" class="text-right" style="padding: 10px;">TOTAL KOLEKSI BUKU:</td>
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
        ${isYayasan.value ? `<td>${book.school_name || '-'}</td>` : ''}
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

  const colSpanCount = isYayasan.value ? 9 : 8
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
        <h1>Katalog Buku Perpustakaan Sekolah</h1>
        <p>Tanggal Cetak: ${currentDate} | Total: ${totalTitles} Judul (${totalStock} Eksemplar)</p>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 30px;">No</th>
            ${isYayasan.value ? '<th>Cabang</th>' : ''}
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
            <td colspan="${colSpanCount}" style="text-align: right; padding: 8px;">TOTAL BUKU FISIK:</td>
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
</script>

<template>
  <div class="space-y-6">
    <PageHeader
      title="Informasi Buku Perpustakaan"
      :description="
        isYayasan
          ? 'Lihat katalog dan informasi buku di seluruh perpustakaan cabang sekolah'
          : 'Lihat katalog dan informasi buku di perpustakaan sekolah'
      "
    />

    <!-- Stats Cards -->
    <PerpustakaanStatCards :is-yayasan="isYayasan" />

    <!-- Data Table & Filters -->
    <PerpustakaanTable
      :items="books"
      readonly
      :is-yayasan="isYayasan"
      @view="handleView"
      @export-excel="handleExportExcel"
      @export-pdf="handleExportPdf"
    />

    <!-- View Modal -->
    <Dialog v-model:open="isViewModalOpen">
      <DialogContent
        :show-close-button="false"
        class="sm:max-w-[500px] p-0 overflow-hidden border-border/80 rounded-2xl"
      >
        <DialogHeader class="px-6 pt-6 pb-4 border-b border-border/50 bg-muted/20">
          <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
              <DialogTitle class="text-xl font-bold flex items-center gap-2">
                <Book class="size-5 text-primary" />
                Detail Buku Perpustakaan
              </DialogTitle>
              <DialogDescription class="text-xs">
                Rincian lengkap dari koleksi buku yang dipilih.
              </DialogDescription>
            </div>
            <Badge
              v-if="selectedBook"
              :class="categoryBadgeClass(selectedBook.kategori)"
              class="rounded-full px-3 py-1 font-bold uppercase tracking-wider text-[10px] whitespace-nowrap"
            >
              {{ selectedBook.kategori }}
            </Badge>
          </div>
        </DialogHeader>

        <div
          v-if="selectedBook"
          class="p-6 space-y-6"
        >
          <div
            class="flex items-center gap-4 p-4 rounded-xl bg-primary/5 border border-primary/10 shadow-xs"
          >
            <div
              class="size-16 sm:size-20 rounded-lg overflow-hidden bg-muted border border-border/50 shrink-0 shadow-inner relative"
            >
              <img
                v-if="selectedBook.image"
                :src="selectedBook.image"
                :alt="selectedBook.name"
                class="w-full h-full object-cover"
              />
              <div
                v-else
                class="w-full h-full flex flex-col items-center justify-center text-muted-foreground bg-slate-100 dark:bg-slate-800"
              >
                <Book class="size-6 mb-1 opacity-50" />
                <span class="text-[8px] font-semibold uppercase tracking-widest opacity-60"
                  >No Cover</span
                >
              </div>
            </div>
            <div>
              <p class="text-sm font-medium text-muted-foreground">Judul Buku</p>
              <p class="text-lg font-bold text-foreground leading-tight">{{ selectedBook.name }}</p>
              <p
                class="text-xs font-mono text-muted-foreground mt-0.5 font-semibold bg-muted/50 inline-block px-1.5 py-0.5 rounded"
              >
                ISBN: {{ selectedBook.isbn }}
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
              <p class="text-sm font-semibold">{{ selectedBook.school_name || '-' }}</p>
            </div>

            <div class="space-y-1.5">
              <div
                class="flex items-center gap-1.5 text-[10px] font-bold text-muted-foreground uppercase tracking-wider"
              >
                <BookOpen class="size-3.5" />
                Penulis
              </div>
              <p class="text-sm font-semibold">{{ selectedBook.penulis || '-' }}</p>
            </div>
            <div class="space-y-1.5">
              <div
                class="flex items-center gap-1.5 text-[10px] font-bold text-muted-foreground uppercase tracking-wider"
              >
                <Hash class="size-3.5" />
                Penerbit
              </div>
              <p class="text-sm font-semibold">{{ selectedBook.penerbit || '-' }}</p>
            </div>
            <div class="space-y-1.5">
              <div
                class="flex items-center gap-1.5 text-[10px] font-bold text-muted-foreground uppercase tracking-wider"
              >
                <Calendar class="size-3.5" />
                Tahun Terbit
              </div>
              <p class="text-sm font-semibold">{{ selectedBook.tahunTerbit || '-' }}</p>
            </div>
            <div class="space-y-1.5">
              <div
                class="flex items-center gap-1.5 text-[10px] font-bold text-muted-foreground uppercase tracking-wider"
              >
                <Layers class="size-3.5" />
                Lokasi Rak
              </div>
              <p class="text-sm font-semibold">{{ selectedBook.lokasiRak || '-' }}</p>
            </div>
            <div class="space-y-1.5 col-span-2">
              <div
                class="flex items-center justify-between text-[10px] font-bold text-muted-foreground uppercase tracking-wider w-full"
              >
                <span class="flex items-center gap-1.5"
                  ><Layers class="size-3.5" /> Jumlah Stok</span
                >
                <Badge
                  variant="secondary"
                  class="rounded text-[10px]"
                  >{{ selectedBook.jumlahStok }} Eksemplar</Badge
                >
              </div>
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
