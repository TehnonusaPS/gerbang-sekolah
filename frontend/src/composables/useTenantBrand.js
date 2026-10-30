import { computed } from 'vue'
import { useAuthStore } from '@/stores/authStore'
import { currentLogoSquare } from '@/config/logo'
import { getPhotoUrl } from '@/utils/getPhotoUrl'

/**
 * Identitas tenant aktif (Sekolah → Yayasan → default Gerbang Sekolah).
 */
export function useTenantBrand() {
  const auth = useAuthStore()

  const tenantName = computed(
    () => auth.user?.school?.name || auth.user?.foundation?.name || 'Gerbang Sekolah'
  )

  const tenantLogo = computed(
    () => getPhotoUrl(auth.user?.school?.logo || auth.user?.foundation?.logo) || currentLogoSquare
  )

  // Pakai di <img @error> agar logo rusak/404 kembali ke logo default.
  const onLogoError = (e) => {
    if (e.target.src !== currentLogoSquare) e.target.src = currentLogoSquare
  }

  return { tenantName, tenantLogo, onLogoError }
}

