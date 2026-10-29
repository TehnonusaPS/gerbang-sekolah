import { z } from 'zod'

const toTrimmedString = val => (val === null || val === undefined ? '' : String(val).trim())

/**
 * Kumpulan helper & schema Zod ketat yang reusable untuk seluruh form di Gerbang Sekolah.
 */
export const v = {
  /**
   * String wajib diisi (otomatis trim spasi).
   */
  requiredString: (label, { min = 1, max = 255 } = {}) =>
    z.preprocess(
      toTrimmedString,
      z
        .string()
        .min(1, `${label} wajib diisi`)
        .min(min, `${label} minimal ${min} karakter`)
        .max(max, `${label} maksimal ${max} karakter`)
    ),

  /**
   * String opsional (boleh kosong / null / undefined).
   */
  optionalString: ({ max = 255 } = {}) =>
    z
      .preprocess(toTrimmedString, z.string().max(max, `Maksimal ${max} karakter`))
      .optional(),

  /**
   * Nama orang (hanya boleh huruf, spasi, tanda petik, atau strip — tidak boleh angka/simbol).
   */
  personName: (label, { required = true, min = 2, max = 100 } = {}) =>
    z.preprocess(
      toTrimmedString,
      z.string().superRefine((val, ctx) => {
        if (!val) {
          if (required) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `${label} wajib diisi`
            })
          }
          return
        }
        if (!/^[a-zA-ZÀ-ÿ\s'-]+$/.test(val)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} hanya boleh berisi huruf (tidak boleh angka atau simbol)`
          })
          return
        }
        if (val.length < min) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} minimal ${min} karakter`
          })
          return
        }
        if (val.length > max) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} maksimal ${max} karakter`
          })
        }
      })
    ),

  /**
   * Gelar akademik opsional (hanya boleh huruf, titik, koma, spasi, tanda hubung).
   */
  academicTitle: (label, { max = 50 } = {}) =>
    z
      .preprocess(
        toTrimmedString,
        z.string().superRefine((val, ctx) => {
          if (!val) return
          if (!/^[a-zA-ZÀ-ÿ.,\s'-]+$/.test(val)) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `${label} hanya boleh berisi huruf, titik, atau koma (tidak boleh angka)`
            })
            return
          }
          if (val.length > max) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `${label} maksimal ${max} karakter`
            })
          }
        })
      )
      .optional(),

  /**
   * Nama tempat / kota (wajib diisi, hanya huruf, spasi, titik, strip — tidak boleh angka).
   */
  placeName: (label, { min = 2, max = 100 } = {}) =>
    z.preprocess(
      toTrimmedString,
      z.string().superRefine((val, ctx) => {
        if (!val) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} wajib diisi`
          })
          return
        }
        if (!/^[a-zA-ZÀ-ÿ\s.'-]+$/.test(val)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} hanya boleh berisi huruf (tidak boleh angka)`
          })
          return
        }
        if (val.length < min) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} minimal ${min} karakter`
          })
          return
        }
        if (val.length > max) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} maksimal ${max} karakter`
          })
        }
      })
    ),

  /**
   * Pilihan dropdown / select tunggal yang wajib dipilih.
   */
  requiredSelect: label =>
    z.preprocess(toTrimmedString, z.string().min(1, `${label} wajib dipilih`)),

  /**
   * Tanggal wajib diisi (dan opsional cek tidak boleh di masa depan).
   */
  requiredDate: (label, { notFuture = false } = {}) =>
    z.preprocess(
      toTrimmedString,
      z.string().superRefine((val, ctx) => {
        if (!val) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} wajib diisi`
          })
          return
        }
        if (notFuture) {
          const inputDate = new Date(val)
          const today = new Date()
          today.setHours(23, 59, 59, 999)
          if (inputDate > today) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `${label} tidak boleh melebihi tanggal hari ini`
            })
          }
        }
      })
    ),

  /**
   * Tanggal lahir dengan validasi rentang usia wajar.
   */
  birthDate: (label = 'Tanggal lahir', { minAge = 17, maxAge = 80 } = {}) =>
    z.preprocess(
      toTrimmedString,
      z.string().superRefine((val, ctx) => {
        if (!val) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} wajib diisi`
          })
          return
        }
        const bDate = new Date(val)
        const today = new Date()
        if (bDate > today) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} tidak boleh di masa depan`
          })
          return
        }
        let age = today.getFullYear() - bDate.getFullYear()
        const m = today.getMonth() - bDate.getMonth()
        if (m < 0 || (m === 0 && today.getDate() < bDate.getDate())) {
          age--
        }
        if (age < minAge) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `Usia minimal ${minAge} tahun`
          })
          return
        }
        if (age > maxAge) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `Usia maksimal ${maxAge} tahun`
          })
        }
      })
    ),

  /**
   * Array multi-select yang wajib dipilih minimal 1 item.
   */
  requiredArray: (label, min = 1) =>
    z
      .preprocess(val => {
        if (Array.isArray(val)) return val.filter(Boolean)
        if (val !== null && val !== undefined && String(val).trim() !== '') return [val]
        return []
      }, z.array(z.any()))
      .refine(arr => arr.length >= min, {
        message: `${label} wajib dipilih minimal ${min}`
      }),

  /**
   * NIK wajib diisi (hanya angka, tepat 16 digit).
   */
  nik: (label = 'NIK') =>
    z.preprocess(
      toTrimmedString,
      z.string().superRefine((val, ctx) => {
        if (!val) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} wajib diisi`
          })
          return
        }
        if (!/^\d+$/.test(val)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} hanya boleh berisi angka (tidak boleh huruf atau simbol)`
          })
          return
        }
        if (val.length !== 16) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} harus terdiri dari 16 digit angka (saat ini ${val.length} digit)`
          })
        }
      })
    ),

  /**
   * NISN wajib diisi (hanya angka, tepat 10 digit).
   */
  nisn: (label = 'NISN') =>
    z.preprocess(
      toTrimmedString,
      z.string().superRefine((val, ctx) => {
        if (!val) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} wajib diisi`
          })
          return
        }
        if (!/^\d+$/.test(val)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} hanya boleh berisi angka (tidak boleh huruf atau simbol)`
          })
          return
        }
        if (val.length !== 10) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} harus terdiri dari 10 digit angka (saat ini ${val.length} digit)`
          })
        }
      })
    ),

  /**
   * String angka opsional (contoh: NIP / NUPTK).
   * Boleh kosong, tapi jika diisi harus berupa angka dengan panjang min–max digit.
   */
  optionalNumericString: (label, { min = 8, max = 18 } = {}) =>
    z
      .preprocess(
        toTrimmedString,
        z.string().superRefine((val, ctx) => {
          if (!val) return
          if (!/^\d+$/.test(val)) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `${label} hanya boleh berisi angka (tidak boleh huruf atau simbol)`
            })
            return
          }
          if (val.length < min || val.length > max) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `${label} harus terdiri dari ${min}–${max} digit angka (saat ini ${val.length} digit)`
            })
          }
        })
      )
      .optional(),

  /**
   * Email wajib diisi & format harus valid.
   */
  requiredEmail: (label = 'E-mail') =>
    z.preprocess(
      toTrimmedString,
      z.string().superRefine((val, ctx) => {
        if (!val) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} wajib diisi`
          })
          return
        }
        if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(val)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `Format ${label.toLowerCase()} tidak valid (contoh: nama@sekolah.sch.id)`
          })
        }
      })
    ),

  /**
   * Email opsional (boleh kosong, tapi jika diisi formatnya harus valid).
   */
  optionalEmail: (label = 'E-mail') =>
    z
      .preprocess(
        toTrimmedString,
        z.string().superRefine((val, ctx) => {
          if (!val) return
          if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(val)) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `Format ${label.toLowerCase()} tidak valid`
            })
          }
        })
      )
      .optional(),

  /**
   * Nomor HP/Telepon opsional (jika diisi harus angka & format telepon Indonesia 10–15 digit).
   */
  optionalPhone: (label = 'No. HP') =>
    z
      .preprocess(
        toTrimmedString,
        z.string().superRefine((val, ctx) => {
          if (!val) return
          const cleaned = val.replace(/[\s-]/g, '')
          if (!/^\+?\d+$/.test(cleaned)) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `${label} hanya boleh berisi angka (tidak boleh huruf atau simbol)`
            })
            return
          }
          if (!/^(\+628|628|08)\d+$/.test(cleaned)) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `${label} harus diawali dengan 08 atau 628`
            })
            return
          }
          const digitsOnly = cleaned.replace(/^\+/, '')
          if (digitsOnly.length < 10 || digitsOnly.length > 15) {
            ctx.addIssue({
              code: z.ZodIssueCode.custom,
              message: `${label} harus terdiri dari 10–15 digit angka (saat ini ${digitsOnly.length} digit)`
            })
          }
        })
      )
      .optional(),

  /**
   * Nomor HP/Telepon wajib diisi.
   */
  requiredPhone: (label = 'No. HP') =>
    z.preprocess(
      toTrimmedString,
      z.string().superRefine((val, ctx) => {
        if (!val) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} wajib diisi`
          })
          return
        }
        const cleaned = val.replace(/[\s-]/g, '')
        if (!/^\+?\d+$/.test(cleaned)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} hanya boleh berisi angka (tidak boleh huruf atau simbol)`
          })
          return
        }
        if (!/^(\+628|628|08)\d+$/.test(cleaned)) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} harus diawali dengan 08 atau 628`
          })
          return
        }
        const digitsOnly = cleaned.replace(/^\+/, '')
        if (digitsOnly.length < 10 || digitsOnly.length > 15) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: `${label} harus terdiri dari 10–15 digit angka (saat ini ${digitsOnly.length} digit)`
          })
        }
      })
    ),

  /**
   * Validasi file gambar opsional (maks ukuran dalam MB & tipe gambar).
   */
  optionalImage: ({ maxSizeMB = 2 } = {}) =>
    z
      .any()
      .refine(
        file => {
          if (!file || !(file instanceof File)) return true
          return ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'].includes(file.type)
        },
        { message: 'Format foto harus berupa JPG, PNG, atau WEBP' }
      )
      .refine(
        file => {
          if (!file || !(file instanceof File)) return true
          return file.size <= maxSizeMB * 1024 * 1024
        },
        { message: `Ukuran foto maksimal ${maxSizeMB}MB` }
      )
      .optional()
}
