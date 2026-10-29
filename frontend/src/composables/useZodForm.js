import { ref, unref, watch } from 'vue'

/**
 * Clone nilai primitif/array sederhana untuk perbandingan perubahan per field.
 */
function cloneSnapshot(obj) {
  if (!obj || typeof obj !== 'object') return {}
  const snap = {}
  for (const key of Object.keys(obj)) {
    const val = obj[key]
    snap[key] = Array.isArray(val) ? [...val] : val
  }
  return snap
}

function isSameValue(a, b) {
  if (Array.isArray(a) && Array.isArray(b)) {
    if (a.length !== b.length) return false
    return a.every((v, i) => v === b[i])
  }
  return a === b
}

/**
 * Composable reusable untuk validasi form berbasis Zod secara real-time per field
 * maupun saat submit keseluruhan.
 *
 * - Saat user mengetik/mengubah field: langsung divalidasi real-time.
 *   Jika belum sesuai aturan -> langsung merah (muncul pesan error).
 *   Jika sudah sesuai aturan -> error langsung hilang otomatis.
 * - Saat klik Simpan (`validate()`): memvalidasi seluruh field yang wajib diisi.
 *
 * @param {import('zod').ZodSchema | import('vue').ComputedRef<import('zod').ZodSchema> | (() => import('zod').ZodSchema)} schemaSource
 * @param {import('vue').Ref<Record<string, any>>} formRef
 */
export function useZodForm(schemaSource, formRef) {
  const errors = ref({})
  const touchedFields = new Set()
  let previousSnapshot = cloneSnapshot(unref(formRef))
  let isSilentReset = false

  const resolveSchema = () => {
    if (typeof schemaSource === 'function') {
      return schemaSource()
    }
    return unref(schemaSource)
  }

  /**
   * Ekstrak map error { [fieldKey]: firstMessage } dari hasil safeParse Zod.
   */
  const extractErrorsMap = zodError => {
    const map = {}
    if (!zodError?.issues) return map

    for (const issue of zodError.issues) {
      const key = issue.path?.[0]
      if (key && !map[key]) {
        map[key] = issue.message
      }
    }
    return map
  }

  /**
   * Validasi seluruh form sekaligus (dipanggil saat klik Simpan).
   * Menandai semua field sebagai touched agar semua error tampil & terpantau real-time.
   * @returns {boolean} true jika valid, false jika terdapat error
   */
  const validate = () => {
    const schema = resolveSchema()
    if (!schema) return true

    const currentData = unref(formRef) || {}
    Object.keys(currentData).forEach(k => touchedFields.add(k))

    const result = schema.safeParse(currentData)
    if (result.success) {
      errors.value = {}
      return true
    }

    errors.value = extractErrorsMap(result.error)
    return false
  }

  /**
   * Validasi satu field spesifik saja.
   * @param {string} fieldKey
   * @returns {boolean}
   */
  const validateField = fieldKey => {
    touchedFields.add(fieldKey)
    const schema = resolveSchema()
    if (!schema) return true

    const result = schema.safeParse(unref(formRef))
    if (result.success) {
      if (errors.value[fieldKey]) {
        const next = { ...errors.value }
        delete next[fieldKey]
        errors.value = next
      }
      return true
    }

    const latestMap = extractErrorsMap(result.error)
    const next = { ...errors.value }
    if (latestMap[fieldKey]) {
      next[fieldKey] = latestMap[fieldKey]
    } else {
      delete next[fieldKey]
    }
    errors.value = next
    return !latestMap[fieldKey]
  }

  /**
   * Bersihkan semua pesan error & reset status touched (misal setelah load data awal di EditPage).
   */
  const clearErrors = () => {
    isSilentReset = true
    touchedFields.clear()
    errors.value = {}
    previousSnapshot = cloneSnapshot(unref(formRef))
    setTimeout(() => {
      isSilentReset = false
    }, 0)
  }

  /**
   * Petakan error validasi 422 dari backend Laravel ke dalam state errors.
   * @param {any} axiosError
   * @returns {boolean} true jika merupakan error 422
   */
  const setServerErrors = axiosError => {
    if (axiosError?.response?.status === 422 && axiosError?.response?.data?.errors) {
      const serverErrors = axiosError.response.data.errors
      const mapped = {}
      Object.keys(serverErrors).forEach(key => {
        const cleanKey = key.split('.')[0]
        touchedFields.add(cleanKey)
        if (!mapped[cleanKey]) {
          mapped[cleanKey] = Array.isArray(serverErrors[key])
            ? serverErrors[key][0]
            : String(serverErrors[key])
        }
      })
      errors.value = mapped
      return true
    }
    return false
  }

  // Real-time validation setiap kali user mengetik / mengubah nilai di field manapun
  watch(
    formRef,
    newVal => {
      if (!newVal || isSilentReset) {
        previousSnapshot = cloneSnapshot(newVal)
        return
      }

      // Deteksi field mana saja yang baru diubah oleh user
      for (const key of Object.keys(newVal)) {
        if (!isSameValue(newVal[key], previousSnapshot[key])) {
          touchedFields.add(key)
        }
      }
      previousSnapshot = cloneSnapshot(newVal)

      if (touchedFields.size === 0 && Object.keys(errors.value).length === 0) {
        return
      }

      const schema = resolveSchema()
      if (!schema) return

      const result = schema.safeParse(newVal)
      if (result.success) {
        if (Object.keys(errors.value).length > 0) {
          errors.value = {}
        }
        return
      }

      const latestMap = extractErrorsMap(result.error)
      const nextErrors = {}

      // Tampilkan error hanya untuk field yang sudah disentuh/diubah user atau yang sebelumnya sudah error
      const keysToCheck = new Set([...touchedFields, ...Object.keys(errors.value)])
      for (const key of keysToCheck) {
        if (latestMap[key]) {
          nextErrors[key] = latestMap[key]
        }
      }

      errors.value = nextErrors
    },
    { deep: true }
  )

  return {
    errors,
    validate,
    validateField,
    clearErrors,
    setServerErrors
  }
}
