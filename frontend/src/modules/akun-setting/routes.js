import { useAuthStore } from '@/stores/authStore'

export default [
  {
    path: 'akun-setting',
    meta: {
      requiresAuth: true
    },
    redirect: () => {
      const auth = useAuthStore()
      const role = auth.user?.role
      if (role === 'superadmin') return '/akun-setting/superadmin'
      if (role === 'admin_yayasan') return '/akun-setting/admin-yayasan'
      if (role === 'kepala_sekolah') return '/akun-setting/kepala-sekolah'
      if (role === 'admin_sekolah') return '/akun-setting/admin-sekolah'
      if (role === 'tata_usaha') return '/akun-setting/tata-usaha'
      if (role === 'guru') return '/akun-setting/guru'
      if (role === 'siswa') return '/akun-setting/siswa'
      if (role === 'orang_tua') return '/akun-setting/orang-tua'
      return '/dashboard'
    }
  },
  {
    path: 'akun-setting/superadmin',
    component: () => import('./pages/superadmin/SettingPage.vue'),
    meta: { requiresAuth: true, roles: ['superadmin'], title: 'Pengaturan Akun' }
  },
  {
    path: 'akun-setting/admin-yayasan',
    component: () => import('./pages/admin-yayasan/SettingPage.vue'),
    meta: { requiresAuth: true, roles: ['admin_yayasan'], title: 'Pengaturan Akun' }
  },
  {
    path: 'akun-setting/kepala-sekolah',
    component: () => import('./pages/kepala-sekolah/SettingPage.vue'),
    meta: { requiresAuth: true, roles: ['kepala_sekolah'], title: 'Pengaturan Akun' }
  },
  {
    path: 'akun-setting/admin-sekolah',
    component: () => import('./pages/admin-sekolah/SettingPage.vue'),
    meta: { requiresAuth: true, roles: ['admin_sekolah'], title: 'Pengaturan Akun' }
  },
  {
    path: 'akun-setting/tata-usaha',
    component: () => import('./pages/tata-usaha/SettingPage.vue'),
    meta: { requiresAuth: true, roles: ['tata_usaha'], title: 'Pengaturan Akun' }
  },
  {
    path: 'akun-setting/guru',
    component: () => import('./pages/guru/SettingPage.vue'),
    meta: { requiresAuth: true, roles: ['guru'], title: 'Pengaturan Akun' }
  },
  {
    path: 'akun-setting/wali-kelas',
    redirect: '/akun-setting/guru'
  },
  {
    path: 'akun-setting/siswa',
    component: () => import('./pages/siswa/SettingPage.vue'),
    meta: { requiresAuth: true, roles: ['siswa'], title: 'Pengaturan Akun' }
  },
  {
    path: 'akun-setting/orang-tua',
    component: () => import('./pages/orang-tua/SettingPage.vue'),
    meta: { requiresAuth: true, roles: ['orang_tua'], title: 'Pengaturan Akun' }
  }
]
