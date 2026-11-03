import { z } from 'zod'
import { v } from '@/utils/validators'

const trimStr = val => (val === null || val === undefined ? '' : String(val).trim())

/**
 * Schema validasi Zod real-time untuk form Tambah & Edit Siswa.
 *
 * @param {{ isSuperAdmin?: boolean }} options
 */
export const createSiswaSchema = ({ isSuperAdmin = false } = {}) =>
  z
    .object({
      // 1. Foto & Kontak Siswa (Opsional)
      foto: v.optionalImage({ maxSizeMB: 10 }),
      email: v.optionalEmail('E-mail siswa'),
      no_hp: v.optionalPhone('No. Telepon / WhatsApp siswa'),

      // 2. Informasi Akademik
      foundation_id: isSuperAdmin ? v.requiredSelect('Yayasan') : z.any(),
      sekolahId: isSuperAdmin ? v.requiredSelect('Sekolah') : z.any(),
      tahun_masuk: v.requiredDate('Tahun masuk', { notFuture: true }),
      kelas: v.optionalString(),
      status: v.requiredSelect('Status'),

      // 3. Informasi Identitas Siswa
      nama_depan: v.personName('Nama depan', { required: true, min: 2, max: 100 }),
      nama_belakang: v.personName('Nama belakang', { required: false, min: 1, max: 100 }),
      nik: v.nik('NIK'),
      nisn: v.nisn('NISN'),
      tempat_lahir: v.placeName('Tempat lahir', { min: 2, max: 100 }),
      tanggal_lahir: v.birthDate('Tanggal lahir', { minAge: 3, maxAge: 25 }),
      jenis_kelamin: v.requiredSelect('Jenis kelamin'),
      agama: v.requiredSelect('Agama'),
      alamat: v.requiredString('Alamat lengkap', { min: 5, max: 500 }),

      // 4A. Data Ayah
      nama_ayah: v.personName('Nama ayah', { required: false, min: 2, max: 100 }),
      pekerjaan_ayah: v.optionalString({ max: 100 }),
      email_ayah: v.optionalEmail('E-mail ayah'),
      no_hp_ayah: v.optionalPhone('No. HP ayah'),
      alamat_ayah: v.optionalString({ max: 500 }),

      // 4B. Data Ibu
      nama_ibu: v.personName('Nama ibu', { required: false, min: 2, max: 100 }),
      pekerjaan_ibu: v.optionalString({ max: 100 }),
      email_ibu: v.optionalEmail('E-mail ibu'),
      no_hp_ibu: v.optionalPhone('No. HP ibu'),
      alamat_ibu: v.optionalString({ max: 500 }),

      // 4C. Data Wali (Opsional jika Ayah & Ibu sudah diisi)
      nama_wali: v.personName('Nama wali', { required: false, min: 2, max: 100 }),
      hubungan_siswa: v.optionalString({ max: 50 }),
      kelamin_wali: v.optionalString({ max: 50 }),
      pekerjaan_wali: v.optionalString({ max: 100 }),
      email_wali: v.optionalEmail('E-mail wali'),
      no_hp_wali: v.optionalPhone('No. HP wali'),
      alamat_wali: v.optionalString({ max: 500 }),

      // 5. Akun Pengguna Orang Tua / Wali
      emailLogin: v.requiredEmail('E-mail login orang tua/wali'),
      noHpLogin: v.optionalPhone('No. HP login orang tua/wali'),
      password_lama: z.string().optional(),
      password_baru: z
        .string()
        .optional()
        .refine(val => !val || val.length >= 8, {
          message: 'Password baru minimal 8 karakter'
        }),
      password_baru_confirmation: z.string().optional()
    })
    .superRefine((data, ctx) => {
      const hasAyahAny = Boolean(
        trimStr(data.nama_ayah) ||
          trimStr(data.pekerjaan_ayah) ||
          trimStr(data.email_ayah) ||
          trimStr(data.no_hp_ayah) ||
          trimStr(data.alamat_ayah)
      )
      const hasIbuAny = Boolean(
        trimStr(data.nama_ibu) ||
          trimStr(data.pekerjaan_ibu) ||
          trimStr(data.email_ibu) ||
          trimStr(data.no_hp_ibu) ||
          trimStr(data.alamat_ibu)
      )
      const hasWaliAny = Boolean(
        trimStr(data.nama_wali) ||
          trimStr(data.hubungan_siswa) ||
          trimStr(data.kelamin_wali) ||
          trimStr(data.pekerjaan_wali) ||
          trimStr(data.email_wali) ||
          trimStr(data.no_hp_wali) ||
          trimStr(data.alamat_wali)
      )

      const ayahFilled = Boolean(trimStr(data.nama_ayah))
      const ibuFilled = Boolean(trimStr(data.nama_ibu))
      const bothParentsFilled = ayahFilled && ibuFilled

      // Ayah wajib jika Wali tidak diisi, atau jika user sudah mulai mengisi kolom Ayah
      if (!hasWaliAny || hasAyahAny || !bothParentsFilled) {
        if (!hasWaliAny || hasAyahAny) {
          if (!trimStr(data.nama_ayah)) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              path: ['nama_ayah'],
              message: 'Nama ayah wajib diisi'
            })
          }
          if (!trimStr(data.no_hp_ayah)) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              path: ['no_hp_ayah'],
              message: 'No. HP ayah wajib diisi'
            })
          }
          if (trimStr(data.alamat_ayah).length < 5) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              path: ['alamat_ayah'],
              message: trimStr(data.alamat_ayah)
                ? 'Alamat ayah minimal 5 karakter'
                : 'Alamat lengkap ayah wajib diisi'
            })
          }
        }
      }

      // Ibu wajib jika Wali tidak diisi, atau jika user sudah mulai mengisi kolom Ibu
      if (!hasWaliAny || hasIbuAny) {
        if (!trimStr(data.nama_ibu)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['nama_ibu'],
            message: 'Nama ibu wajib diisi'
          })
        }
        if (!trimStr(data.no_hp_ibu)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['no_hp_ibu'],
            message: 'No. HP ibu wajib diisi'
          })
        }
        if (trimStr(data.alamat_ibu).length < 5) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['alamat_ibu'],
            message: trimStr(data.alamat_ibu)
              ? 'Alamat ibu minimal 5 karakter'
              : 'Alamat lengkap ibu wajib diisi'
          })
        }
      }

      // Jika Ayah & Ibu keduanya sudah diisi, maka Wali bersifat opsional (kecuali jika kolom Wali mulai diisi)
      const requireWali = !bothParentsFilled ? hasWaliAny : hasWaliAny
      if (requireWali) {
        if (!trimStr(data.nama_wali)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['nama_wali'],
            message: 'Nama wali wajib diisi'
          })
        }
        if (!trimStr(data.hubungan_siswa)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['hubungan_siswa'],
            message: 'Hubungan dengan siswa wajib dipilih'
          })
        }
        if (!trimStr(data.kelamin_wali)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['kelamin_wali'],
            message: 'Jenis kelamin wali wajib dipilih'
          })
        }
        if (!trimStr(data.no_hp_wali)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['no_hp_wali'],
            message: 'No. HP wali wajib diisi'
          })
        }
        if (trimStr(data.alamat_wali).length < 5) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            path: ['alamat_wali'],
            message: trimStr(data.alamat_wali)
              ? 'Alamat wali minimal 5 karakter'
              : 'Alamat lengkap wali wajib diisi'
          })
        }
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

/**
 * Pemetaan field error 422 dari backend ke key state form frontend.
 */
export const fieldErrorMap = {
  nama_depan: 'nama_depan',
  nama_belakang: 'nama_belakang',
  first_name: 'nama_depan',
  nisn: 'nisn',
  nik: 'nik',
  tempat_lahir: 'tempat_lahir',
  tanggal_lahir: 'tanggal_lahir',
  jenis_kelamin: 'jenis_kelamin',
  agama: 'agama',
  alamat: 'alamat',
  address: 'alamat',
  email: 'email',
  no_hp: 'no_hp',
  phone: 'no_hp',
  foundation_id: 'foundation_id',
  sekolahId: 'sekolahId',
  school_id: 'sekolahId',
  tahun_masuk: 'tahun_masuk',
  kelas: 'kelas',
  classroom_id: 'kelas',
  status: 'status',
  nama_ayah: 'nama_ayah',
  pekerjaan_ayah: 'pekerjaan_ayah',
  email_ayah: 'email_ayah',
  no_hp_ayah: 'no_hp_ayah',
  alamat_ayah: 'alamat_ayah',
  nama_ibu: 'nama_ibu',
  pekerjaan_ibu: 'pekerjaan_ibu',
  email_ibu: 'email_ibu',
  no_hp_ibu: 'no_hp_ibu',
  alamat_ibu: 'alamat_ibu',
  nama_wali: 'nama_wali',
  hubungan_siswa: 'hubungan_siswa',
  kelamin_wali: 'kelamin_wali',
  pekerjaan_wali: 'pekerjaan_wali',
  email_wali: 'email_wali',
  no_hp_wali: 'no_hp_wali',
  alamat_wali: 'alamat_wali',
  emailLogin: 'emailLogin',
  noHpLogin: 'noHpLogin',
  foto: 'foto'
}
