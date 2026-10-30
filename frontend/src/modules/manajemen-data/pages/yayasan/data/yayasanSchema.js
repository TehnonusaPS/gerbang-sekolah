import { z } from 'zod'
import { v } from '@/utils/validators'

/**
 * Pemetaan nama field form frontend ke key errors yang ditampilkan di UI
 */
export const fieldErrorMap = {
  nama: 'name',
  email: 'email',
  no_hp: 'phone',
  website: 'website',
  emailLogin: 'emailLogin',
  noHpLogin: 'noHpLogin',
  tanggal_berdiri: 'established_date',
  status: 'status',
  no_akta: 'deed_number',
  tanggal_akta: 'deed_date',
  no_sk: 'decree_number',
  tanggal_sk: 'decree_date',
  alamat: 'address',
  logo: 'logo',
  password_lama: 'password_lama',
  password_baru: 'password_baru',
  password_baru_confirmation: 'password_baru_confirmation'
}

/**
 * Schema validasi Zod ketat untuk form Tambah & Edit Yayasan.
 *
 * @param {{ isEdit?: boolean, hasExistingLogo?: boolean }} options
 */
export const createYayasanSchema = ({ isEdit = false, hasExistingLogo = false } = {}) =>
  z.object({
    nama: v.requiredString('Nama yayasan', { min: 2, max: 255 }),
    tanggal_berdiri: v.requiredDate('Tanggal berdiri'),
    status: v.requiredSelect('Status'),
    alamat: v.requiredString('Alamat lengkap', { min: 1, max: 500 }),
    email: v.optionalEmail('E-mail'),
    no_hp: v.optionalTelephone('No. Telepon'),
    website: v.optionalUrl('Website'),
    no_akta: v.requiredString('No. Akta Pendirian', { min: 1, max: 255 }),
    tanggal_akta: v.requiredDate('Tanggal akta pendirian'),
    no_sk: v.requiredString('No. SK Kemenkumham', { min: 1, max: 255 }),
    tanggal_sk: v.requiredDate('Tanggal SK Kemenkumham'),
    emailLogin: v.requiredEmail('E-mail login administrator'),
    noHpLogin: v.optionalPhone('No. HP / WhatsApp administrator'),
    password_lama: z.string().optional(),
    password_baru: z.string().optional().refine(val => !val || val.length >= 8, {
      message: 'Password baru minimal 8 karakter'
    }),
    password_baru_confirmation: z.string().optional(),
    logo: isEdit && hasExistingLogo
      ? v.optionalImage({ maxSizeMB: 2 })
      : v.requiredImage('Logo yayasan', { maxSizeMB: 2 })
  }).refine(data => {
    if (data.password_baru && data.password_baru !== data.password_baru_confirmation) {
      return false
    }
    return true
  }, {
    message: 'Konfirmasi password baru tidak cocok',
    path: ['password_baru_confirmation']
  })
