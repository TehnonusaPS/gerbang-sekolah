import { useAuthStore } from '@/stores/authStore'

export default [
  {
    path: 'komunikasi/pengumuman',
    meta: {
      requiresAuth: true,
      roles: ['admin_yayasan', 'kepala_sekolah', 'admin_sekolah']
    },
    redirect: to => {
      const auth = useAuthStore()
      const role = auth.user?.role
      if (role === 'admin_yayasan') return { path: '/komunikasi/admin-yayasan/pengumuman', query: to.query }
      if (role === 'kepala_sekolah' || role === 'admin_sekolah') return { path: '/komunikasi/kepala-sekolah/pengumuman', query: to.query }
      return '/dashboard'
    }
  },
  {
    path: 'komunikasi/admin-yayasan/pengumuman',
    component: () => import('./pages/admin-yayasan/pengumuman/index.vue'),
    meta: {
      title: 'Pengumuman',
      parent: 'Komunikasi',
      description: 'Buat dan kelola pengumuman sekolah.',
      requiresAuth: true,
      roles: ['admin_yayasan']
    }
  },
  {
    path: 'komunikasi/kepala-sekolah/pengumuman',
    component: () => import('./pages/kepala-sekolah/pengumuman/index.vue'),
    meta: {
      title: 'Pengumuman',
      parent: 'Komunikasi',
      description: 'Daftar pengumuman sekolah.',
      requiresAuth: true,
      roles: ['kepala_sekolah', 'admin_sekolah']
    }
  },
  {
    path: 'komunikasi/berita-kegiatan',
    component: () => import('./pages/kepala-sekolah/berita-kegiatan/index.vue'),
    meta: {
      title: 'Berita Kegiatan',
      parent: 'Komunikasi',
      description: 'Kelola berita kegiatan untuk siswa dan orang tua.',
      requiresAuth: true,
      roles: ['kepala_sekolah', 'admin_sekolah', 'guru', 'siswa', 'orang_tua']
    }
  },
  {
    path: 'komunikasi/feedback',
    component: () => import('./pages/Feedback.vue'),
    meta: {
      title: 'Feedback',
      parent: 'Komunikasi',
      description: 'Daftar masukan dan saran dari orang tua murid.'
    }
  },
  {
    path: 'komunikasi/pesan',
    component: () => import('./pages/Pesan.vue'),
    meta: {
      title: 'Pesan Internal',
      parent: 'Komunikasi',
      description: 'Pesan internal antar warga sekolah.',
      requiresAuth: true,
      roles: ['guru', 'siswa', 'orang_tua']
    }
  },
  {
    path: 'komunikasi/notifikasi',
    component: () => import('./pages/Notifikasi.vue'),
    meta: {
      title: 'Notifikasi',
      parent: 'Komunikasi',
      description: 'Pengaturan notifikasi dan pemberitahuan.'
    }
  },
  {
    path: 'komunikasi/chat',
    meta: {
      requiresAuth: true,
      roles: ['guru', 'siswa', 'orang_tua']
    },
    redirect: to => {
      const auth = useAuthStore()
      const role = auth.user?.role
      if (role === 'guru') return auth.isWaliKelas ? '/komunikasi/wali-kelas/chat' : '/komunikasi/guru/chat'
      if (role === 'siswa') return '/komunikasi/siswa/chat'
      if (role === 'orang_tua') return '/komunikasi/orang-tua/chat'
      return '/dashboard'
    }
  },
  {
    path: 'komunikasi/guru/chat',
    component: () => import('./pages/guru/chat/index.vue'),
    meta: {
      title: 'Chat Guru',
      parent: 'Komunikasi',
      requiresAuth: true,
      roles: ['guru']
    }
  },
  {
    path: 'komunikasi/wali-kelas/chat',
    component: () => import('./pages/wali-kelas/chat/index.vue'),
    meta: {
      title: 'Chat Wali Kelas',
      parent: 'Komunikasi',
      requiresAuth: true,
      roles: ['guru'],
      requiresWaliKelas: true
    }
  },
  {
    path: 'komunikasi/siswa/chat',
    component: () => import('./pages/siswa/chat/index.vue'),
    meta: {
      title: 'Chat Siswa',
      parent: 'Komunikasi',
      requiresAuth: true,
      roles: ['siswa']
    }
  },
  {
    path: 'komunikasi/orang-tua/chat',
    component: () => import('./pages/orang-tua/chat/index.vue'),
    meta: {
      title: 'Chat Orang Tua',
      parent: 'Komunikasi',
      requiresAuth: true,
      roles: ['orang_tua']
    }
  },
  {
    path: 'komunikasi/persuratan/aktif',
    component: () => import('./pages/tata-usaha/surat-aktif/index.vue'),
    meta: {
      title: 'Surat Keterangan Aktif',
      parent: 'Komunikasi',
      description: 'Daftar surat keterangan siswa aktif yang diterbitkan.',
      requiresAuth: true,
      roles: ['tata_usaha']
    }
  },
  {
    path: 'komunikasi/persuratan/dispensasi',
    component: () => import('./pages/tata-usaha/surat-dispensasi/index.vue'),
    meta: {
      title: 'Surat Dispensasi',
      parent: 'Komunikasi',
      description: 'Manajemen surat izin and dispensasi kegiatan siswa.',
      requiresAuth: true,
      roles: ['tata_usaha']
    }
  },
  {
    path: 'komunikasi/persuratan/lulus',
    component: () => import('./pages/tata-usaha/surat-lulus/index.vue'),
    meta: {
      title: 'Surat Keterangan Lulus',
      parent: 'Komunikasi',
      description: 'Penerbitan Surat Keterangan Lulus (SKL) sementara.',
      requiresAuth: true,
      roles: ['tata_usaha']
    }
  },
  {
    path: 'komunikasi/persuratan/peringatan',
    component: () => import('./pages/tata-usaha/surat-peringatan/index.vue'),
    meta: {
      title: 'Peringatan',
      parent: 'Komunikasi',
      description: 'Manajemen surat peringatan, tagihan, dan pemanggilan.',
      requiresAuth: true,
      roles: ['tata_usaha']
    }
  }
]
