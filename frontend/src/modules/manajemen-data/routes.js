export default [
  // SISWA
  {
    path: 'manajemen-data/siswa',
    component: () => import('./pages/siswa/IndexPage.vue'),
    meta: {
      title: 'Siswa',
      parent: 'Manajemen Data',
      description: 'Kelola data seluruh siswa aktif.'
      // roles: ['superadmin', 'admin_sekolah'] <-- Contoh cara membatasi rute
    }
  },
  {
    path: 'manajemen-data/siswa/tambah',
    component: () => import('./pages/siswa/CreatePage.vue'),
    meta: {
      title: 'Tambah Siswa',
      parent: 'Siswa'
    }
  },
  {
    path: 'manajemen-data/siswa/edit',
    component: () => import('./pages/siswa/EditPage.vue'),
    meta: {
      title: 'Edit Siswa',
      parent: 'Siswa'
    }
  },
  {
    path: 'manajemen-data/kelas',
    component: () => import('./pages/data-kelas/IndexPage.vue'),
    meta: {
      title: 'Data Kelas',
      parent: 'Manajemen Data',
      description: 'Kelola pembagian dan struktur kelas.'
    }
  },
  {
    path: 'manajemen-data/kelas/detail/:id',
    component: () => import('./pages/data-kelas/DetailPage.vue'),
    meta: {
      title: 'Detail Kelas',
      parent: 'Data Kelas',
      description: 'Detail anggota kelas dan wali kelas'
    }
  },
  {
    path: 'manajemen-data/kelas/detail/:id/manage',
    component: () => import('./pages/data-kelas/ManagePage.vue'),
    meta: {
      title: 'Kelola',
      parent: 'Detail Kelas',
      description: 'Kelola anggota kelas dan wali kelas'
    }
  },
  // MATA PELAJARAN
  {
    path: 'manajemen-data/mata-pelajaran',
    component: () => import('./pages/mata-pelajaran/IndexPage.vue'),
    meta: {
      title: 'Mata Pelajaran',
      parent: 'Manajemen Data',
      description: 'Kelola daftar mata pelajaran yang tersedia.'
    }
  },
  // KURIKULUM
  {
    path: 'manajemen-data/kurikulum',
    component: () => import('../akademik/pages/Kurikulum.vue'),
    meta: {
      title: 'Jenis Kurikulum & Mapel Wajib',
      parent: 'Manajemen Data',
      description: 'Master data jenis kurikulum dan mata pelajaran wajib standar nasional.'
    }
  },
  // TAHUN AJARAN
  {
    path: 'manajemen-data/tahun-ajaran',
    component: () => import('./pages/TahunAjaran.vue'),
    meta: {
      title: 'Tahun Ajaran',
      parent: 'Manajemen Data',
      description: 'Data Siswa dan Kelas Bedasarkan Tahun Ajaran.'
    }
  },
  // YAYASAN
  {
    path: 'manajemen-data/yayasan',
    component: () => import('./pages/yayasan/IndexPage.vue'),
    meta: {
      title: 'Yayasan',
      parent: 'Manajemen Data',
      description: 'Daftar dan kelola informasi yayasan pendidikan terdaftar.'
    }
  },
  {
    path: 'manajemen-data/yayasan/tambah',
    component: () => import('./pages/yayasan/CreatePage.vue'),
    meta: {
      title: 'Tambah Yayasan',
      parent: 'Yayasan'
    }
  },
  {
    path: 'manajemen-data/yayasan/edit',
    component: () => import('./pages/yayasan/EditPage.vue'),
    meta: {
      title: 'Edit Yayasan',
      parent: 'Yayasan'
    }
  },
  // SEKOLAH
  {
    path: 'manajemen-data/sekolah',
    component: () => import('./pages/sekolah/IndexPage.vue'),
    meta: {
      title: 'Sekolah',
      parent: 'Manajemen Data',
      description: 'Manajemen daftar unit sekolah di bawah naungan yayasan.'
    }
  },
  {
    path: 'manajemen-data/sekolah/tambah',
    component: () => import('./pages/sekolah/CreatePage.vue'),
    meta: {
      title: 'Tambah Sekolah',
      parent: 'Sekolah'
    }
  },
  {
    path: 'manajemen-data/sekolah/edit',
    component: () => import('./pages/sekolah/EditPage.vue'),
    meta: {
      title: 'Edit Sekolah',
      parent: 'Sekolah'
    }
  },
  // HAK AKSES
  {
    path: 'manajemen-data/hak-akses',
    component: () => import('./pages/HakAkses.vue'),
    meta: {
      title: 'Hak Akses Pengguna',
      parent: 'Manajemen Data',
      description: 'Kelola kewenangan, lisensi, dan hak akses pengguna sistem. mmmmmm'
    }
  },
  // GURU & STAFF
  {
    path: 'manajemen-data/guru-staff',
    component: () => import('./pages/guru-staff/IndexPage.vue'),
    meta: {
      title: 'Guru dan Staff',
      parent: 'Manajemen Data',
      description: 'Pengelolaan data tenaga pendidik dan kependidikan di seluruh yayasan.'
    }
  },
  {
    path: 'manajemen-data/guru-staff/tambah',
    component: () => import('./pages/guru-staff/CreatePage.vue'),
    meta: {
      title: 'Tambah Guru/Staff',
      parent: 'Guru dan Staff'
    }
  },
  {
    path: 'manajemen-data/guru-staff/edit/:id',
    component: () => import('./pages/guru-staff/EditPage.vue'),
    meta: {
      title: 'Edit Guru/Staff',
      parent: 'Guru dan Staff'
    }
  },
  {
    path: 'manajemen-data/monitoring-kelas',
    component: () => import('./pages/MonitoringKelas.vue'),
    meta: {
      title: 'Monitoring Kelas',
      parent: 'Manajemen Data',
      description: 'Pantau status dan kondisi ruang kelas secara berkala.'
    }
  },
  {
    path: 'manajemen-data/ekskul',
    component: () => import('./pages/EkskulData.vue'),
    meta: {
      title: 'Ekstrakulikuler',
      parent: 'Manajemen Data',
      description: 'Daftar data dan pembina kegiatan ekstrakurikuler sekolah.'
    }
  },
  {
    path: 'manajemen-data/jadwal',
    component: () => import('./pages/JadwalData.vue'),
    meta: {
      title: 'Jadwal Pelajaran',
      parent: 'Manajemen Data',
      description: 'Daftar data jadwal pelajaran kelas dan guru pengajar.'
    }
  }
]
