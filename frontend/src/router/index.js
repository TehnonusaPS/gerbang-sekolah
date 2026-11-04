import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/authStore'
import SidebarLayout from '../layouts/SidebarLayout.vue'

// Import rute dari setiap module
import landingRoutes from '../modules/landing/routes'
import authRoutes from '../modules/auth/routes'
import dashboardRoutes from '../modules/dashboard/routes'
import manajemenDataRoutes from '../modules/manajemen-data/routes'
import akademikRoutes from '../modules/akademik/routes'
import keuanganRoutes from '../modules/keuangan/routes'
import absensiRoutes from '../modules/absensi/routes'
import komunikasiRoutes from '../modules/komunikasi/routes'
import laporanRoutes from '../modules/laporan/routes'
import lainnyaRoutes from '../modules/lainnya/routes'
import devRoutes from '../modules/dev/routes'
import administrasiRoutes from '../modules/administrasi/routes'
import akunSettingRoutes from '../modules/akun-setting/routes'
import schoolLandingRoutes from '../modules/school-landing/routes'
import landingEditorRoutes from '../modules/landing-editor/routes'

const routes = [
  // Landing page
  ...landingRoutes,

  // Rute Auth (Login, dll) biasanya tidak pakai SidebarLayout
  ...authRoutes,

  // Public: Landing page sekolah/yayasan by slug
  ...schoolLandingRoutes,

  // Landing page publik
  {
    path: '/',
    name: 'Landing',
    component: () => import('../modules/public/pages/LandingPage.vue'),
    meta: { requiresAuth: false }
  },

  // ─── Halaman Presensi (Fullscreen, tanpa Sidebar) ───
  {
    path: '/absensi/siswa/scan',
    component: () => import('../modules/absensi/pages/admin-sekolah/presensi-siswa/index.vue'),
    meta: {
      requiresAuth: true,
      roles: ['admin_sekolah'],
      title: 'Kiosk Presensi Siswa'
    }
  },
  {
    path: '/absensi/input/print',
    name: 'CetakKehadiran',
    meta: {
      requiresAuth: true,
      roles: ['guru']
    },
    redirect: () => {
      const auth = useAuthStore()
      if (auth.isWaliKelas) return '/absensi/wali-kelas/input-kehadiran/print'
      return '/absensi/guru/input-kehadiran/print'
    }
  },
  {
    path: '/absensi/guru/input-kehadiran/print',
    component: () => import('../modules/absensi/pages/guru/input-kehadiran/print.vue'),
    meta: {
      requiresAuth: true,
      roles: ['guru'],
      title: 'Cetak Kehadiran Siswa'
    }
  },
  {
    path: '/absensi/wali-kelas/input-kehadiran/print',
    component: () => import('../modules/absensi/pages/wali-kelas/input-kehadiran/print.vue'),
    meta: {
      requiresAuth: true,
      roles: ['guru'],
      requiresWaliKelas: true,
      title: 'Cetak Kehadiran Siswa'
    }
  },

  // Semua rute aplikasi yang pakai Sidebar
  {
    path: '/',
    component: SidebarLayout,
    meta: { requiresAuth: true },
    children: [
      ...dashboardRoutes,
      ...manajemenDataRoutes,
      ...akademikRoutes,
      ...keuanganRoutes,
      ...absensiRoutes,
      ...komunikasiRoutes,
      ...laporanRoutes,
      ...lainnyaRoutes,
      ...devRoutes,
      ...administrasiRoutes,
      ...akunSettingRoutes,
      ...landingEditorRoutes
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

/* 🔥 INI DIA PROTEKSI ROLE */
router.beforeEach(async (to, from) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth) {
    // ❌ belum login sama sekali
    if (!auth.token) {
      return '/login'
    }

    // Jika ada token tapi data user belum ter-load (misal habis direfresh)
    if (!auth.user) {
      await auth.initUser()
      // Jika setelah initUser data user masih kosong (token tidak valid/expired)
      if (!auth.user) {
        auth.logout()
        return '/login'
      }
    }
  }

  // ❌ butuh hak akses wali kelas tapi bukan wali kelas
  if (to.meta.requiresWaliKelas && !auth.isWaliKelas) {
    return '/dashboard'
  }

  // ❌ role tidak sesuai
  if (to.meta.role && auth.user?.role !== to.meta.role) {
    return '/login'
  }

  // ❌ role tidak termasuk daftar yang diizinkan
  if (to.meta.roles && !to.meta.roles.includes(auth.user?.role)) {
    return '/login'
  }

  // Vue Router 4+: Tidak me-return apapun (atau return true) akan otomatis melanjutkan rute
})

export default router
