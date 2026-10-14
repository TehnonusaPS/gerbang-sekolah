/**
 * =========================================================================
 * 🎨 LOGO RESMI — GERBANG SEKOLAH (EDISI FIX)
 * =========================================================================
 * Logo resmi tunggal Gerbang Sekolah:
 * - currentLogo       : Logo berwarna tajam (tight) untuk navbar, login, footer
 * - currentLogoWhite  : Logo versi putih untuk background gelap
 * - currentLogoSquare : Logo proporsi kotak 1:1 untuk icon, favicon, avatar, widget
 * =========================================================================
 */

import logoLight from '@/assets/logo/logoround-tight.png'
import logoWhite from '@/assets/logo/logoround-white.png'
import logoSquare from '@/assets/logo/logoround-square.png'

export const activeLogo = 'logoround'
export const currentLogo = logoLight
export const currentLogoWhite = logoWhite
export const currentLogoSquare = logoSquare

export default {
  activeLogo,
  currentLogo,
  currentLogoWhite,
  currentLogoSquare
}
