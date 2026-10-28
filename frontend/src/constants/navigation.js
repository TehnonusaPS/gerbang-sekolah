import {
  LayoutDashboard,
  Database,
  GraduationCap,
  Wallet,
  ClipboardList,
  MessageSquare,
  FileBarChart,
  MoreHorizontal,
  Palette,
  LayoutTemplate,
  Settings,
  Image,
  BookOpen,
  Building,
  HeartHandshake,
  Layers,
  PhoneCall,
  Users
} from 'lucide-vue-next'

export const navMain = [
  {
    title: 'Dashboard',
    url: '/dashboard',
    icon: LayoutDashboard
  },
  
  {
    title: 'Manajemen Data',
    url: '/manajemen-data',
    icon: Database,
    excludeRoles: ['guru', 'siswa', 'orang_tua'],
    items: [
      { 
        title: 'Siswa', 
        url: '/manajemen-data/siswa',
        roles: ['admin_sekolah', 'tata_usaha', 'kepala_sekolah', 'wali_kelas']
      },
      { 
        title: 'Yayasan', 
        url: '/manajemen-data/yayasan',
        roles: ['superadmin']
      },
      { 
        title: 'Sekolah', 
        url: '/manajemen-data/sekolah',
        roles: ['superadmin', 'admin_yayasan']
      },
      { 
        title: 'Pengguna', 
        url: '/manajemen-data/hak-akses',
        roles: ['superadmin']
      },
      { 
        title: 'Guru dan Staff', 
        url: '/manajemen-data/guru-staff',
        roles: ['admin_yayasan', 'admin_sekolah', 'kepala_sekolah']
      },
      { 
        title: 'Monitoring Kelas', 
        url: '/manajemen-data/monitoring-kelas',
        roles: ['kepala_sekolah']
      },
      { 
        title: 'Ekstrakulikuler', 
        url: '/manajemen-data/ekskul',
        roles: ['kepala_sekolah', 'admin_sekolah']
      },
      { 
        title: 'Jadwal Pelajaran', 
        url: '/manajemen-data/jadwal',
        roles: ['kepala_sekolah', 'admin_sekolah']
      },
      { title: 'Kelas', 
        url: '/manajemen-data/kelas', 
        roles: ['admin_sekolah']
      },
      { title: 'Mata Pelajaran', url: '/manajemen-data/mata-pelajaran', excludeRoles: ['tata_usaha', 'superadmin', 'admin_yayasan', 'wali_kelas'] },
      { 
        title: 'Tahun Ajaran', 
        url: '/manajemen-data/tahun-ajaran',
        roles: ['admin_sekolah', 'kepala_sekolah']
      },
      { title: 'Tahun Ajaran', url: '/manajemen-data/tahun-ajaran', excludeRoles: ['tata_usaha', 'superadmin', 'admin_yayasan', 'kepala_sekolah', 'admin_sekolah', 'wali_kelas'] }
    ]
  },
  {
    title: 'Akademik',
    url: '/akademik',
    icon: GraduationCap,
    excludeRoles: ['superadmin', 'admin_yayasan', 'tata_usaha'],
    items: [
      { title: 'Kalender Sekolah', url: '/akademik/jadwal', excludeRoles: ['kepala_sekolah', 'admin_sekolah', 'wali_kelas', 'orang_tua'] },
      { title: 'Nilai & Rapor', url: '/akademik/nilai', excludeRoles: ['kepala_sekolah', 'admin_sekolah', 'guru', 'wali_kelas', 'siswa', 'orang_tua'] },
      { title: 'Ujian & Penilaian', url: '/akademik/ujian', roles: ['admin_sekolah', 'superadmin', 'kepala_sekolah'] },
      { title: 'Kurikulum', url: '/akademik/kurikulum', excludeRoles: ['kepala_sekolah', 'admin_sekolah', 'guru', 'wali_kelas', 'siswa', 'orang_tua'] },
      { title: 'Mata Pelajaran', url: '/akademik/mapel', excludeRoles: ['kepala_sekolah', 'admin_sekolah', 'wali_kelas', 'siswa', 'orang_tua'] },
      { title: 'Ekstrakurikuler', url: '/akademik/ekskul', excludeRoles: ['kepala_sekolah', 'admin_sekolah', 'guru', 'wali_kelas', 'siswa', 'orang_tua'] },
      {
        title: 'Kalender',
        url: '/akademik/kalender',
        roles: ['kepala_sekolah', 'admin_sekolah']
      },
      {
        title: 'Kegiatan',
        url: '/akademik/kegiatan',
        roles: ['kepala_sekolah', 'admin_sekolah']
      },
      {
        title: 'Agenda Siswa',
        url: '/akademik/siswa/agenda',
        roles: ['siswa']
      },
      {
        title: 'Nilai & Raport',
        url: '/akademik/nilai',
        roles: ['siswa']
      },
      {
        title: 'Mata Pelajaran',
        url: '/akademik/mapel',
        roles: ['siswa']
      },
      {
        title: 'Nilai & Rapor',
        url: '/akademik/nilai',
        roles: ['orang_tua']
      },
      {
        title: 'Jadwal Anak',
        url: '/akademik/orang-tua/jadwal-anak',
        roles: ['orang_tua']
      },
      {
        title: 'Kalender Sekolah',
        url: '/akademik/jadwal',
        roles: ['orang_tua']
      }
    ]
  },
  {
    title: 'Keuangan',
    url: '/keuangan',
    icon: Wallet,
    excludeRoles: ['kepala_sekolah', 'guru', 'wali_kelas'],
    items: [
      { title: 'SPP Siswa', url: '/keuangan/spp', excludeRoles: ['tata_usaha', 'superadmin', 'admin_yayasan', 'admin_sekolah', 'siswa', 'orang_tua'] },
      { 
        title: 'SPP Siswa', 
        url: '/keuangan/spp',
        roles: ['tata_usaha']
      },
      { title: 'Transaksi', url: '/keuangan/tagihan', excludeRoles: ['tata_usaha', 'superadmin', 'admin_yayasan', 'admin_sekolah', 'siswa', 'orang_tua'] },
      { 
        title: 'Transaksi', 
        url: '/keuangan/tagihan',
        roles: ['tata_usaha']
      },
      { title: 'Pengeluaran', url: '/keuangan/pengeluaran', excludeRoles: ['tata_usaha', 'superadmin', 'admin_yayasan', 'admin_sekolah', 'siswa', 'orang_tua'] },
      { title: 'Laporan Keuangan', url: '/keuangan/laporan', excludeRoles: ['tata_usaha', 'superadmin', 'admin_yayasan', 'admin_sekolah', 'siswa', 'orang_tua'] },
      {
        title: 'Subscribtion',
        url: '/keuangan/subscription',
        roles: ['superadmin']
      },
      {
        title: 'Keuangan Yayasan',
        url: '/keuangan/monitoring-yayasan',
        roles: ['admin_yayasan']
      },
      {
        title: 'Paket Subcription',
        url: '/keuangan/paket-subscription',
        roles: ['admin_yayasan']
      },
      {
        title: 'Dana Yayasan',
        url: '/keuangan/kelola-dana-yayasan',
        roles: ['admin_sekolah']
      },
      {
        title: 'Tarif SPP',
        url: '/keuangan/tarif-spp',
        roles: ['admin_sekolah']
      },
      {
        title: 'SPP',
        url: '/keuangan/spp',
        roles: ['siswa']
      },
      {
        title: 'SPP',
        url: '/keuangan/spp',
        roles: ['orang_tua']
      },
      {
        title: 'Transaksi',
        url: '/keuangan/tagihan',
        roles: ['orang_tua']
      }
    ]
  },
  {
    title: 'Absensi',
    url: '/absensi',
    icon: ClipboardList,
    excludeRoles: ['superadmin', 'admin_yayasan'],
    items: [
      { title: 'Absensi Siswa', url: '/absensi/siswa', roles: ['admin_sekolah'] },
      { title: 'Absensi Staff', url: '/absensi/guru-staff', roles: ['admin_sekolah', 'kepala_sekolah', 'tata_usaha'] },
      { title: 'Persetujuan Cuti/Izin', url: '/absensi/admin-sekolah/persetujuan-cuti', roles: ['admin_sekolah'] },
      { title: 'Pengaturan Absensi', url: '/absensi/admin-sekolah/pengaturan', roles: ['admin_sekolah'] },
      { title: 'Pengajuan Izin/Cuti', url: '/absensi/admin-sekolah/izin-cuti', roles: ['admin_sekolah'] },
      { title: 'Rekap Bulanan', url: '/absensi/rekap', roles: ['admin_sekolah'] },
      {
        title: 'Absensi Guru',
        url: '/absensi/guru-staff',
        roles: ['guru', 'wali_kelas']
      },
      {
        title: 'Pengajuan Izin/Cuti',
        url: '/absensi/guru/izin-cuti',
        roles: ['guru']
      },
      {
        title: 'Pengajuan Izin/Cuti',
        url: '/absensi/wali-kelas/izin-cuti',
        roles: ['wali_kelas']
      },
      {
        title: 'Pengajuan Izin/Cuti',
        url: '/absensi/kepala-sekolah/izin-cuti',
        roles: ['kepala_sekolah']
      },
      {
        title: 'Pengajuan Izin/Cuti',
        url: '/absensi/tata-usaha/izin-cuti',
        roles: ['tata_usaha']
      },
      {
        title: 'Kehadiran',
        url: '/absensi/input',
        roles: ['guru', 'wali_kelas']
      },
      {
        title: 'Rekap Absensi',
        url: '/absensi/rekap',
        roles: ['siswa']
      },
      {
        title: 'Rekap Absensi',
        url: '/absensi/rekap',
        roles: ['orang_tua']
      }
    ]
  },
  {
    title: 'Komunikasi',
    url: '/komunikasi',
    icon: MessageSquare,
    excludeRoles: ['superadmin'],
    items: [
      { title: 'Pengumuman', url: '/komunikasi/pengumuman', excludeRoles: ['tata_usaha', 'orang_tua', 'guru', 'wali_kelas', 'siswa'] },
      { title: 'Berita Kegiatan', url: '/komunikasi/berita-kegiatan', excludeRoles: ['tata_usaha', 'admin_yayasan'] },
      { title: 'Feedback', url: '/komunikasi/feedback', excludeRoles: ['tata_usaha', 'admin_yayasan', 'guru', 'wali_kelas', 'siswa'] },
      { title: 'Pesan Internal', url: '/komunikasi/pesan', excludeRoles: ['tata_usaha', 'admin_yayasan', 'kepala_sekolah', 'admin_sekolah', 'orang_tua', 'guru', 'wali_kelas', 'siswa'] },
      { title: 'Notifikasi', url: '/komunikasi/notifikasi', excludeRoles: ['tata_usaha', 'admin_yayasan', 'kepala_sekolah', 'admin_sekolah', 'orang_tua', 'guru', 'wali_kelas', 'siswa'] },
      { title: 'Keterangan Aktif', url: '/komunikasi/persuratan/aktif', roles: ['tata_usaha'] },
      {
        title: 'Surat Dispensasi',
        url: '/komunikasi/persuratan/dispensasi',
        roles: ['tata_usaha']
      },
      { title: 'Keterangan Lulus', url: '/komunikasi/persuratan/lulus', roles: ['tata_usaha'] },
      {
        title: 'Peringatan',
        url: '/komunikasi/persuratan/peringatan',
        roles: ['tata_usaha']
      }
    ]
  },
  {
    title: 'Laporan',
    url: '/laporan',
    icon: FileBarChart,
    excludeRoles: ['siswa'],
    items: [
      { title: 'Akademik', url: '/laporan/akademik', excludeRoles: ['tata_usaha', 'superadmin', 'admin_yayasan', 'kepala_sekolah', 'admin_sekolah', 'guru', 'wali_kelas', 'orang_tua'] },
      { 
        title: 'Akademik', 
        url: '/laporan/akademik',
        roles: ['tata_usaha']
      },
      { 
        title: 'Perkelas', 
        url: '/laporan/akademik',
        roles: ['kepala_sekolah', 'admin_sekolah']
      },
      { title: 'Keuangan', url: '/laporan/keuangan', excludeRoles: ['tata_usaha', 'superadmin', 'admin_yayasan', 'kepala_sekolah', 'admin_sekolah', 'guru', 'wali_kelas', 'orang_tua'] },
      { 
        title: 'Keuangan', 
        url: '/laporan/keuangan',
        roles: ['tata_usaha']
      },
      { 
        title: 'Keuangan', 
        url: '/laporan/keuangan',
        roles: ['kepala_sekolah', 'admin_sekolah']
      },
      { title: 'Absensi', url: '/laporan/absensi', excludeRoles: ['tata_usaha', 'superadmin', 'admin_yayasan', 'kepala_sekolah', 'admin_sekolah', 'guru', 'wali_kelas', 'orang_tua'] },
      { 
        title: 'Absensi', 
        url: '/laporan/absensi',
        roles: ['tata_usaha']
      },
      { title: 'Ekspor Data', url: '/laporan/ekspor', excludeRoles: ['tata_usaha', 'superadmin', 'admin_yayasan', 'kepala_sekolah', 'admin_sekolah', 'guru', 'wali_kelas', 'orang_tua'] },
      {
        title: 'Laporan Nilai',
        url: '/laporan/nilai',
        roles: ['guru', 'wali_kelas']
      },
      {
        title: 'Kehadiran',
        url: '/laporan/kehadiran-siswa',
        roles: ['guru', 'wali_kelas']
      },
      {
        title: 'Raport Siswa',
        url: '/laporan/raport',
        roles: ['wali_kelas']
      },
      {
        title: 'Perkembangan',
        url: '/laporan/perkembangan',
        roles: ['orang_tua']
      },
      {
        title: 'Yayasan',
        url: '/laporan/konsolidasi',
        roles: ['superadmin']
      },
      {
        title: 'Data siswa',
        url: '/laporan/siswa-yayasan',
        roles: ['admin_yayasan']
      },
      {
        title: 'Keuangan',
        url: '/laporan/keuangan-yayasan',
        roles: ['admin_yayasan']
      },
      {
        title: 'Akademik',
        url: '/laporan/akademik-yayasan',
        roles: ['admin_yayasan']
      },
      {
        title: 'SDM',
        url: '/laporan/sdm-yayasan',
        roles: ['admin_yayasan']
      },
      {
        title: 'Infrastruktur',
        url: '/laporan/infrastruktur-yayasan',
        roles: ['admin_yayasan']
      },
      {
        title: 'Kepegawaian',
        url: '/laporan/kepegawaian',
        roles: ['kepala_sekolah', 'admin_sekolah']
      }
    ]
  },
  {
    title: 'Lainnya',
    url: '/lainnya',
    icon: MoreHorizontal,
    excludeRoles: ['tata_usaha', 'guru', 'wali_kelas', 'siswa', 'orang_tua'],
    items: [
      { title: 'Pengaturan Sekolah', url: '/lainnya/pengaturan', excludeRoles: ['superadmin', 'admin_yayasan', 'kepala_sekolah', 'admin_sekolah'] },
      { title: 'Pengguna', url: '/lainnya/pengguna', excludeRoles: ['superadmin', 'admin_yayasan', 'kepala_sekolah', 'admin_sekolah'] },
      { title: 'Backup & Restore', url: '/lainnya/backup', excludeRoles: ['superadmin', 'admin_yayasan', 'kepala_sekolah', 'admin_sekolah'] },
      { title: 'Ruangan', url: '/lainnya/ruangan', excludeRoles: ['superadmin', 'admin_yayasan', 'kepala_sekolah'] },
      { title: 'Aset', url: '/lainnya/aset', excludeRoles: ['superadmin', 'admin_yayasan', 'kepala_sekolah'] },
      { title: 'Perpustakaan', url: '/lainnya/perpustakaan', excludeRoles: ['superadmin', 'admin_yayasan', 'kepala_sekolah'] },
      {
        title: 'Konfigurasi Global',
        url: '/lainnya/konfigurasi-global',
        roles: ['superadmin']
      },
      {
        title: 'Ruangan',
        url: '/lainnya/informasi-ruangan',
        roles: ['admin_yayasan', 'kepala_sekolah']
      },
      {
        title: 'Aset',
        url: '/lainnya/informasi-aset',
        roles: ['admin_yayasan', 'kepala_sekolah']
      },
      {
        title: 'Perpustakaan',
        url: '/lainnya/informasi-perpustakaan',
        roles: ['admin_yayasan', 'kepala_sekolah']
      },        
    ]
  },
  {
    title: 'UI Components',
    url: '/components',
    icon: Palette,
    roles: ['superadmin']
  },
  {
    title: 'Website Builder',
    url: '/landing-editor',
    icon: LayoutTemplate,
    roles: ['admin_yayasan', 'admin_sekolah', 'kepala_sekolah'],
    requiresLandingPageEnabled: true,
    items: [
      {
        title: 'Pengaturan Umum',
        url: '/landing-editor/general',
        icon: Settings,
        roles: ['admin_yayasan', 'admin_sekolah', 'kepala_sekolah']
      },
      {
        title: 'Hero Banner',
        url: '/landing-editor/hero',
        icon: Image,
        roles: ['admin_yayasan', 'admin_sekolah', 'kepala_sekolah']
      },
      {
        title: 'Profil Yayasan',
        url: '/landing-editor/about',
        icon: BookOpen,
        roles: ['admin_yayasan']
      },
      {
        title: 'Profil Akademik',
        url: '/landing-editor/about',
        icon: BookOpen,
        roles: ['admin_sekolah', 'kepala_sekolah']
      },
      {
        title: 'Informasi PPDB',
        url: '/landing-editor/ppdb',
        icon: Users,
        roles: ['admin_sekolah', 'kepala_sekolah']
      },
      {
        title: 'Lembaga Naungan',
        url: '/landing-editor/institutions',
        icon: Building,
        roles: ['admin_yayasan']
      },
      {
        title: 'Rekening Donasi',
        url: '/landing-editor/donasi',
        icon: HeartHandshake,
        roles: ['admin_yayasan']
      },
      {
        title: 'Kelola Section',
        url: '/landing-editor/sections',
        icon: Layers,
        roles: ['admin_yayasan', 'admin_sekolah', 'kepala_sekolah']
      },
      {
        title: 'Kontak & Sosmed',
        url: '/landing-editor/contact',
        icon: PhoneCall,
        roles: ['admin_yayasan', 'admin_sekolah', 'kepala_sekolah']
      }
    ]
  }
]
