import { z } from 'zod'
import { v } from '@/utils/validators'

/**
 * Pemetaan nama field error 422 dari backend ke key form frontend yang ditampilkan di UI
 */
export const fieldErrorMap = {
  name: 'nama',
  foundation_id: 'yayasan',
  level: 'jenjang',
  established_date: 'tanggal_berdiri',
  address: 'alamat',
  decree_number: 'no_sk',
  decree_date: 'tanggal_sk',
  permit_number: 'no_izin',
  permit_date: 'tanggal_izin',
  accreditation: 'akreditasi',
  accreditation_date: 'tanggal_akreditasi',
  accreditation_number: 'no_akreditasi',
  phone: 'no_hp'
}

/**
 * Schema validasi Zod untuk form Tambah & Edit Unit Sekolah.
 *
 * @param {{ isEdit?: boolean, hasExistingLogo?: boolean, isSuperAdmin?: boolean }} options
 */
export const createSekolahSchema = ({
  isEdit = false,
  hasExistingLogo = false,
  isSuperAdmin = false
} = {}) =>
  z
    .object({
      nama: v.requiredString('Nama sekolah', { min: 2, max: 255 }),
      npsn: v.requiredString('NPSN', { min: 1, max: 50 }),
      yayasan: isSuperAdmin ? v.requiredSelect('Yayasan induk') : z.any().optional(),
      jenjang: v.requiredSelect('Jenjang pendidikan'),
      tanggal_berdiri: v.requiredDate('Tanggal berdiri'),
      status: v.requiredSelect('Status operasional'),
      curriculum_id: z.any().optional(),
      alamat: v.requiredString('Alamat lengkap', { min: 1, max: 500 }),
      no_sk: v.requiredString('No. SK Pendirian', { min: 1, max: 255 }),
      tanggal_sk: v.requiredDate('Tanggal SK Pendirian'),
      no_izin: v.requiredString('No. Izin Operasional', { min: 1, max: 255 }),
      tanggal_izin: v.requiredDate('Tanggal Izin Operasional'),
      akreditasi: v.requiredSelect('Peringkat akreditasi'),
      tanggal_akreditasi: v.optionalString({ max: 50 }),
      no_akreditasi: v.optionalString({ max: 255 }),
      email: v.optionalEmail('E-mail resmi sekolah'),
      no_hp: v.optionalTelephone('No. telepon sekolah'),
      website: v.optionalUrl('Website resmi sekolah'),
      instagram: v.optionalString({ max: 255 }),
      facebook: v.optionalString({ max: 255 }),
      emailLogin: v.requiredEmail('E-mail login administrator'),
      noHpLogin: v.optionalPhone('No. HP / WhatsApp administrator'),
      password_lama: z.string().optional(),
      password_baru: z
        .string()
        .optional()
        .refine(val => !val || val.length >= 8, {
          message: 'Password baru minimal 8 karakter'
        }),
      password_baru_confirmation: z.string().optional(),
      logo:
        isEdit && hasExistingLogo
          ? v.optionalImage({ maxSizeMB: 2 })
          : v.requiredImage('Logo sekolah', { maxSizeMB: 2 })
    })
    .refine(
      data => {
        if (data.password_baru && data.password_baru !== data.password_baru_confirmation) {
          return false
        }
        return true
      },
      {
        message: 'Konfirmasi password baru tidak cocok',
        path: ['password_baru_confirmation']
      }
    )

