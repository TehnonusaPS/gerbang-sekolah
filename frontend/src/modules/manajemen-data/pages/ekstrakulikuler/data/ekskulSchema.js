import { z } from 'zod'
import { v } from '@/utils/validators'

export const ekskulSchema = z.object({
  nama: v.requiredString('Nama ekstrakulikuler', { min: 2, max: 100 }),
  deskripsi: v.optionalString({ max: 500 }),
  status: v.requiredSelect('Status').default('aktif')
})
