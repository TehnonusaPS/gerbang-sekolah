import { useAuthStore } from '@/stores/authStore'

export default [
  {
    path: 'akademik/jadwal',
    component: () => import('./pages/Jadwal.vue'),
    meta: {
      title: 'Kalender Sekolah',
      parent: 'Akademik',
      description: 'Jadwal pelajaran per kelas dan per guru serta kegiatan kalender sekolah.'
    }
  },
  {
    path: 'akademik/nilai',
    component: () => import('./pages/Nilai.vue'),
    meta: {
      title: 'Nilai & Rapor',
      parent: 'Akademik',
      description: 'Input dan rekap nilai siswa serta pencetakan rapor.'
    }
  },
  {
    path: 'akademik/ujian',
    component: () => import('./pages/Ujian.vue'),
    meta: {
      title: 'Ujian',
      parent: 'Akademik',
      description: 'Manajemen ujian, kuis, dan penilaian harian.'
    }
  },
  {
    path: 'akademik/kurikulum',
    component: () => import('./pages/Kurikulum.vue'),
    meta: {
      title: 'Kurikulum',
      parent: 'Akademik',
      description: 'Pengaturan kurikulum dan silabus pembelajaran.'
    }
  },
  {
    path: 'akademik/mapel',
    component: () => import('./pages/MataPelajaran.vue'),
    meta: {
      title: 'Mata Pelajaran',
      parent: 'Akademik',
      description: 'Lihat Detail Mata Pelajaran.'
    }
  },
  {
    path: 'akademik/ekskul',
    component: () => import('./pages/Ekskul.vue'),
    meta: {
      title: 'Ekstrakurikuler',
      parent: 'Akademik',
      description: 'Manajemen kegiatan ekstrakurikuler siswa.'
    }
  },
  {
    path: 'akademik/kalender',
    meta: {
      requiresAuth: true,
      roles: ['admin_sekolah', 'kepala_sekolah']
    },
    redirect: () => {
      const auth = useAuthStore()
      const role = auth.user?.role
      if (role === 'admin_sekolah') return '/akademik/admin-sekolah/kalender'
      if (role === 'kepala_sekolah') return '/akademik/kepala-sekolah/kalender'
      return '/dashboard'
    }
  },
  {
    path: 'akademik/admin-sekolah/kalender',
    component: () => import('./pages/admin-sekolah/kalender/index.vue'),
    meta: {
      requiresAuth: true,
      roles: ['admin_sekolah'],
      title: 'Kalender Akademik',
      parent: 'Akademik'
    }
  },
  {
    path: 'akademik/admin-sekolah/kalender/create',
    component: () => import('./pages/admin-sekolah/kalender/create.vue'),
    meta: {
      requiresAuth: true,
      roles: ['admin_sekolah'],
      title: 'Buat Kalender Akademik',
      parent: 'Akademik'
    }
  },
  {
    path: 'akademik/admin-sekolah/kalender/edit/:tahun',
    component: () => import('./pages/admin-sekolah/kalender/edit.vue'),
    meta: {
      requiresAuth: true,
      roles: ['admin_sekolah'],
      title: 'Edit Kalender Akademik',
      parent: 'Akademik'
    }
  },
  {
    path: 'akademik/admin-sekolah/kalender/show/:tahun',
    component: () => import('./pages/admin-sekolah/kalender/show.vue'),
    meta: {
      requiresAuth: true,
      roles: ['admin_sekolah'],
      title: 'Detail Kalender Akademik',
      parent: 'Akademik'
    }
  },
  {
    path: 'akademik/kepala-sekolah/kalender',
    component: () => import('./pages/kepala-sekolah/kalender/index.vue'),
    meta: {
      requiresAuth: true,
      roles: ['kepala_sekolah'],
      title: 'Kalender Akademik',
      parent: 'Akademik'
    }
  },
  {
    path: 'akademik/kepala-sekolah/kalender/show/:tahun',
    component: () => import('./pages/kepala-sekolah/kalender/show.vue'),
    meta: {
      requiresAuth: true,
      roles: ['kepala_sekolah'],
      title: 'Review Kalender Akademik',
      parent: 'Akademik'
    }
  },
  {
    path: 'akademik/kalender-akademik',
    component: () => import('./pages/KalenderAkademikReadonly.vue'),
    meta: {
      requiresAuth: true,
      roles: ['guru', 'orang_tua'],
      title: 'Kalender Akademik',
      parent: 'Akademik'
    }
  },
  {
    path: 'akademik/kegiatan',
    component: () => import('./pages/KegiatanAkademik.vue'),
    meta: {
      title: 'Kegiatan akademik sekolah',
      parent: 'Akademik',
      description: 'Daftar rincian kegiatan pembelajaran dan ujian sekolah.'
    }
  },
  {
    path: 'akademik/materi',
    component: () => import('./pages/KelolaMateri.vue'),
    meta: {
      title: 'Materi',
      parent: 'Akademik',
      description: 'Unggah, sunting, dan bagikan materi ajar interaktif.'
    }
  },
  {
    path: 'akademik/tugas',
    component: () => import('./pages/KelolaTugas.vue'),
    meta: {
      title: 'Tugas',
      parent: 'Akademik',
      description: 'Buat tugas baru dan pantau pengumpulan tugas siswa.'
    }
  },
  {
    path: 'akademik/input-nilai',
    component: () => import('./pages/InputNilai.vue'),
    meta: {
      title: 'Input Nilai',
      parent: 'Akademik',
      description: 'Lakukan penginputan nilai harian, UTS, UAS, dan rapor.'
    }
  },
  {
    path: 'akademik/orang-tua/jadwal-anak',
    component: () => import('@/modules/akademik/pages/orang-tua/JadwalAnak.vue'),
    meta: {
      requiresAuth: true,
      roles: ['orang_tua'],
      title: 'Jadwal & Agenda Anak',
      parent: 'Akademik',
      description: 'Pantau jadwal pelajaran mingguan, kalender ujian, dan agenda libur sekolah anak.'
    }
  },
  {
    path: 'akademik/siswa/agenda',
    component: () => import('@/modules/akademik/pages/orang-tua/JadwalAnak.vue'),
    meta: {
      requiresAuth: true,
      roles: ['siswa'],
      title: 'Agenda Siswa',
      parent: 'Akademik',
      description: 'Pantau jadwal pelajaran mingguan, tugas, ujian, dan kegiatan sekolah Anda.'
    }
  }
]
