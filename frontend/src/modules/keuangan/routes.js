export default [
  {
    path: 'keuangan/spp',
    component: () => import('./pages/Spp.vue'),
    meta: {
      title: 'SPP',
      parent: 'Keuangan',
      description: 'Kelola pembayaran SPP bulanan siswa.'
    }
  },
  {
    path: 'keuangan/tagihan',
    component: () => import('./pages/RiwayatKeuangan.vue'),
    meta: {
      title: 'Transaksi',
      parent: 'Keuangan',
      description: 'Riwayat dan status tagihan pembayaran.'
    }
  },
  {
    path: 'keuangan/pengeluaran',
    component: () => import('./pages/Pengeluaran.vue'),
    meta: {
      title: 'Pengeluaran',
      parent: 'Keuangan',
      description: 'Catatan pengeluaran operasional sekolah.'
    }
  },
  {
    path: 'keuangan/laporan',
    component: () => import('./pages/Laporan.vue'),
    meta: {
      title: 'Laporan Keuangan',
      parent: 'Keuangan',
      description: 'Laporan keuangan bulanan dan tahunan.'
    }
  },
  {
    path: 'keuangan/cetak-kwitansi',
    component: () => import('./pages/CetakKwitansi.vue'),
    meta: {
      title: 'Cetak Kwitansi',
      parent: 'Keuangan',
      description: 'Cetak kwitansi pembayaran SPP dan pengeluaran.'
    }
  },
  {
    path: 'keuangan/subscription',
    component: () => import('./pages/Subscription.vue'),
    meta: {
      title: 'Subscription',
      parent: 'Keuangan',
      description: 'Pantau status langganan dan tagihan lisensi sekolah.'
    }
  },
  {
    path: 'keuangan/subscription/tambah',
    component: () => import('./pages/formTambahLangganan.vue'),
    meta: {
      title: 'Tambah Langganan Baru',
      parent: 'Keuangan',
      description: 'Daftarkan institusi baru ke dalam paket langganan.'
    }
  },
  {
    path: 'keuangan/monitoring-yayasan',
    component: () => import('./pages/MonitoringYayasan.vue'),
    meta: {
      title: 'Keuangan Yayasan',
      parent: 'Keuangan',
      description: 'Pantau rekap dan arus kas keuangan seluruh unit sekolah di bawah yayasan.'
    }
  },
  {
    path: 'keuangan/paket-subscription',
    component: () => import('./pages/PaketSubscription.vue'),
    meta: {
      title: 'Paket Subcription',
      parent: 'Keuangan',
      description: 'Kelola paket langganan dan tagihan lisensi aplikasi Gerbang Sekolah.'
    }
  },
  {
    path: 'keuangan/kelola-dana-yayasan',
    component: () => import('./pages/MengelolaDanaYayasan.vue'),
    meta: {
      title: 'Dana Yayasan',
      parent: 'Keuangan',
      description: 'Manajemen alokasi dan penggunaan dana hibah yayasan.'
    }
  },
  {
    path: 'keuangan/kelola-dana-yayasan/tambah',
    component: () => import('./pages/formTambahPengeluaranYayasan.vue'),
    meta: {
      title: 'Input Pengeluaran Yayasan',
      parent: 'Keuangan',
      description: 'Catat pengeluaran alokasi dana yayasan baru.'
    }
  },
  {
    path: 'keuangan/tarif-spp',
    component: () => import('./pages/TarifSpp.vue'),
    meta: {
      title: 'Pengaturan Tarif SPP',
      parent: 'Keuangan',
      description: 'Manajemen pengaturan tarif SPP dan biaya sekolah.'
    }
  },
  {
    path: 'keuangan/input-pengeluaran-kecil',
    component: () => import('./pages/PengeluaranKecil.vue'),
    meta: {
      title: 'Input Pengeluaran Kecil',
      parent: 'Keuangan',
      description: 'Input pengeluaran kecil.'
    }
  },
  {
    path: 'keuangan/penerimaan-pembayaran',
    component: () => import('./pages/PenerimaanPembayaran.vue'),
    meta: {
      title: 'Penerimaan Pembayaran',
      parent: 'Keuangan',
      description: 'Terima pembayaran SPP siswa.'
    }
  }
]
