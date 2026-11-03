import { z } from 'zod'
import { v } from '@/utils/validators'

/**
 * Schema validasi Zod ketat untuk form Tambah & Edit Guru/Staff.
 *
 * @param {{ isSuperAdmin?: boolean, isAdminSekolah?: boolean }} options
 */
export const createGuruStaffSchema = ({ isSuperAdmin = false, isAdminSekolah = false } = {}) =>
  z
    .object({
      // 1. Foto & Akun Pengguna
      foto: v.optionalImage({ maxSizeMB: 2 }),
      emailLogin: v.requiredEmail('E-mail login'),
      noHpLogin: v.optionalPhone('No. HP / WhatsApp'),
      password_lama: z.string().optional(),
      password_baru: z
        .string()
        .optional()
        .refine(val => !val || val.length >= 8, {
          message: 'Password baru minimal 8 karakter'
        }),
      password_baru_confirmation: z.string().optional(),

      // 2. Informasi Pribadi
      nama_depan: v.personName('Nama depan', { required: true, min: 2, max: 100 }),
      nama_belakang: v.personName('Nama belakang', { required: false, min: 1, max: 100 }),
      gelar_depan: v.academicTitle('Gelar depan', { max: 50 }),
      gelar_belakang: v.academicTitle('Gelar belakang', { max: 50 }),
      nik: v.nik('NIK'),
      jenis_kelamin: v.requiredSelect('Jenis kelamin'),
      tempat_lahir: v.placeName('Tempat lahir', { min: 2, max: 100 }),
      tanggal_lahir: v.birthDate('Tanggal lahir', { minAge: 17, maxAge: 80 }),
      agama: v.requiredSelect('Agama'),
      status_pernikahan: v.requiredSelect('Status pernikahan'),
      pendidikan_terakhir: v.requiredSelect('Pendidikan terakhir'),
      alamat: v.requiredString('Alamat lengkap', { min: 10, max: 500 }),

      // 3. Informasi Kepegawaian
      foundation_id: isSuperAdmin ? v.requiredSelect('Yayasan naungan') : z.any(),
      unit_kerja: isAdminSekolah ? z.any() : v.requiredArray('Unit kerja'),
      jabatan: v.requiredSelect('Jabatan'),
      subject_ids: z.array(z.string()).optional(),
      status_kepegawaian: v.requiredSelect('Status kepegawaian'),
      status_aktif: v.requiredSelect('Status aktif'),
      join_date: v.requiredDate('Tanggal bergabung', { notFuture: true }),
      nip_nuptk: v.optionalNumericString('NIP / NUPTK', { min: 8, max: 18 })
    })
    .superRefine((data, ctx) => {
      const jabatanUpper = String(data.jabatan || '').toUpperCase()
      const isTeacher = ['GURU', 'J004'].includes(jabatanUpper)
      if (isTeacher && (!Array.isArray(data.subject_ids) || data.subject_ids.length === 0)) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          path: ['subject_ids'],
          message: 'Mata pelajaran wajib dipilih untuk jabatan Guru'
        })
      }

      const isPrincipal = ['KEPALA SEKOLAH', 'J003'].includes(jabatanUpper)
      if (isPrincipal && Array.isArray(data.unit_kerja) && data.unit_kerja.length > 1) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          path: ['jabatan'],
          message: 'Kepala Sekolah hanya dapat menjabat di 1 unit kerja sekolah'
        })
      }

      if (
        (data.password_baru || data.password_baru_confirmation) &&
        data.password_baru !== data.password_baru_confirmation
      ) {
        ctx.addIssue({
          code: z.ZodIssueCode.custom,
          path: ['password_baru_confirmation'],
          message: 'Konfirmasi password baru tidak cocok'
        })
      }
    })
