import { z } from 'zod'
import { v } from '@/utils/validators'

export const academicYearSchema = z
  .object({
    tahun: v.requiredString('Tahun ajaran').refine(
      val => /^\d{4}\/\d{4}$/.test(val),
      'Format harus YYYY/YYYY (contoh: 2025/2026)'
    ),
    oddTanggalMulai: v.requiredString('Tanggal mulai ganjil'),
    oddTanggalSelesai: v.requiredString('Tanggal selesai ganjil'),
    evenTanggalMulai: v.requiredString('Tanggal mulai genap'),
    evenTanggalSelesai: v.requiredString('Tanggal selesai genap'),
    activeSemester: z.string().optional(),
    duplicateClasses: z.boolean().optional()
  })
  .superRefine((data, ctx) => {
    if (data.oddTanggalMulai && data.oddTanggalSelesai) {
      if (new Date(data.oddTanggalMulai) >= new Date(data.oddTanggalSelesai)) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          path: ['oddTanggalSelesai'],
          message: 'Tanggal selesai ganjil harus setelah tanggal mulai ganjil'
        })
      }
    }

    if (data.evenTanggalMulai && data.evenTanggalSelesai) {
      if (new Date(data.evenTanggalMulai) >= new Date(data.evenTanggalSelesai)) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          path: ['evenTanggalSelesai'],
          message: 'Tanggal selesai genap harus setelah tanggal mulai genap'
        })
      }
    }
  })
