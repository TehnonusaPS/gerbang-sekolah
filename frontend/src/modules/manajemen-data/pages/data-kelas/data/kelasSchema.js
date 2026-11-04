import { z } from 'zod'
import { v } from '@/utils/validators'

export const kelasSchema = z.object({
  academic_year_id: v.requiredSelect('Tahun ajaran'),
  name: v.requiredString('Nama kelas'),
  grade: v.requiredSelect('Tingkat kelas'),
  major: v.optionalString({ max: 100 }),
  homeroom_teacher_id: z.any().optional(),
  homeroom_teacher: z.any().optional(),
  room: v.optionalString({ max: 50 }),
  capacity: z.preprocess(
    val => (val === '' || val === null || val === undefined ? undefined : Number(val)),
    z.number({ required_error: 'Kapasitas wajib diisi', invalid_type_error: 'Kapasitas harus berupa angka' })
      .min(1, 'Kapasitas minimal 1 siswa')
      .max(100, 'Kapasitas maksimal 100 siswa')
  ),
  status: v.requiredSelect('Status kelas')
})

