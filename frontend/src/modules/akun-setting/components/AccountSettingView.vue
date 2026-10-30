<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  SquareUserRound,
  Image as ImageIcon,
  UserCog,
  BriefcaseBusiness,
  GraduationCap,
  ShieldUser,
  School,
  Building2,
  ShieldCheck,
  Scale,
  Phone,
  Globe,
  FileText
} from 'lucide-vue-next'
import { toast } from 'vue-sonner'
import { z } from 'zod'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormSection from '@/components/forms/FormSection.vue'
import FormInput from '@/components/forms/FormInput.vue'
import FormSelect from '@/components/forms/FormSelect.vue'
import FormDate from '@/components/forms/FormDate.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import ImageUpload from '@/components/forms/ImageUpload.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import BentoFormLayout from '@/components/forms/BentoFormLayout.vue'
import { Separator } from '@/components/ui/separator'
import { useAuthStore } from '@/stores/authStore'
import { useZodForm } from '@/composables/useZodForm'
import { v } from '@/utils/validators'
import { getPhotoUrl } from '@/utils/getPhotoUrl'
import { updateProfile } from '@/services/authService'
import YayasanForm from '@/modules/manajemen-data/pages/yayasan/components/YayasanForm.vue'
import { statusOptions as yayasanStatusOptions } from '@/modules/manajemen-data/pages/yayasan/data/yayasan.js'

const props = defineProps({
  roleTitle: {
    type: String,
    default: 'Pengguna'
  }
})

const auth = useAuthStore()
const isSaving = ref(false)
const imagePreview = ref('')

// Deteksi Role Spesifik Selaras Alur Pembuatan di Manajemen Data
const roleName = computed(() => auth.user?.role || '')

const isEduStaff = computed(() =>
  ['guru', 'wali_kelas', 'kepala_sekolah'].includes(roleName.value)
)
const isTu = computed(() => roleName.value === 'tata_usaha')
const isAdminSekolah = computed(() => roleName.value === 'admin_sekolah')
const isAdminYayasan = computed(() => roleName.value === 'admin_yayasan')
const isSuperAdmin = computed(() => roleName.value === 'superadmin')
const isStudent = computed(() => roleName.value === 'siswa')
const isParent = computed(() => roleName.value === 'orang_tua')

// Data Instansi Pendukung (Sekolah / Yayasan)
const schoolData = computed(() => auth.user?.school || {})
const foundationData = computed(() => auth.user?.foundation || auth.user?.school?.foundation || {})

// Slot Visual Identitas: Logo Sekolah / Logo Yayasan / Foto Profil Pengguna Sesuai Role
const visualSlot = computed(() => {
  if (isAdminSekolah.value) {
    const rawLogo = schoolData.value.logo || auth.user?.school?.logo || auth.user?.photo
    return {
      title: 'Logo Unit Sekolah',
      description: 'Identitas visual resmi unit sekolah (Read-Only)',
      icon: School,
      preview: rawLogo ? getPhotoUrl(rawLogo) : '',
      disabled: true,
      fit: 'contain',
      note: 'Logo resmi unit sekolah dikelola melalui menu Manajemen Data'
    }
  }
  if (isAdminYayasan.value) {
    const rawLogo = foundationData.value.logo || auth.user?.foundation?.logo || auth.user?.photo
    return {
      title: 'Logo Yayasan',
      description: 'Identitas visual resmi yayasan induk (Read-Only)',
      icon: Building2,
      preview: rawLogo ? getPhotoUrl(rawLogo) : '',
      disabled: true,
      fit: 'contain',
      note: 'Logo resmi yayasan induk dikelola melalui menu Manajemen Data'
    }
  }
  return {
    title: 'Foto Profil',
    description: 'Upload foto profil resmi akun Anda',
    icon: ImageIcon,
    preview: imagePreview.value,
    disabled: false,
    fit: 'cover',
    note: 'Format: JPG atau PNG. Maksimal 2MB. Dimensi rasio 1:1'
  }
})

const formatDate = val => {
  if (!val) return '-'
  return String(val).split('T')[0]
}

// Pilihan Standar Selaras Manajemen Data
const kelaminOptions = [
  { label: 'Laki-laki', value: 'JK01' },
  { label: 'Perempuan', value: 'JK02' }
]

const agamaOptions = [
  { label: 'Islam', value: 'A01' },
  { label: 'Kristen', value: 'A02' },
  { label: 'Katolik', value: 'A03' },
  { label: 'Buddha', value: 'A04' },
  { label: 'Hindu', value: 'A05' },
  { label: 'Konghucu', value: 'A06' }
]

const statusPernikahanOptions = [
  { label: 'Belum Menikah', value: 'SP01' },
  { label: 'Menikah', value: 'SP02' },
  { label: 'Janda', value: 'SP03' },
  { label: 'Duda', value: 'SP04' }
]

const pendidikanOptions = [
  { label: 'Sekolah Dasar (SD)', value: 'P01' },
  { label: 'Sekolah Menengah Pertama (SMP)', value: 'P02' },
  { label: 'Sekolah Menengah Atas/Kejuruan (SMA/SMK)', value: 'P03' },
  { label: 'Diploma I (D1)', value: 'P04' },
  { label: 'Diploma III (D3)', value: 'P05' },
  { label: 'Diploma IV (D4)', value: 'P06' },
  { label: 'Sarjana (S1)', value: 'P07' },
  { label: 'Magister (S2)', value: 'P08' },
  { label: 'Doktor (S3)', value: 'P09' }
]

const pekerjaanOptions = [
  { label: 'PNS', value: 'pns' },
  { label: 'BUMN', value: 'bumn' },
  { label: 'Wiraswasta', value: 'wiraswasta' },
  { label: 'Pegawai Swasta', value: 'pegawai swasta' },
  { label: 'Ibu Rumah Tangga', value: 'ibu rumah tangga' },
  { label: 'Lainnya', value: 'lainnya' }
]

const hubunganOptions = [
  { label: 'Ayah', value: 'ayah' },
  { label: 'Ibu', value: 'ibu' },
  { label: 'Wali', value: 'wali' },
  { label: 'Lainnya', value: 'lainnya' }
]

// State Form Selaras Karakteristik Asli Role
const form = ref({
  foto: null,
  emailLogin: '',
  noHpLogin: '',
  password_lama: '',
  password_baru: '',
  password_baru_confirmation: '',
  // Data Personal
  nama_depan: '',
  nama_belakang: '',
  gelar_depan: '',
  gelar_belakang: '',
  nik: '',
  jenis_kelamin: '',
  tempat_lahir: '',
  tanggal_lahir: '',
  agama: '',
  status_pernikahan: '',
  pendidikan_terakhir: '',
  alamat: '',
  // Khusus Orang Tua
  nama_wali: '',
  hubungan_siswa: '',
  kelamin_wali: '',
  pekerjaan_wali: '',
  // Informasi Read-Only
  nip_nuptk: '',
  jabatan: '',
  unit_kerja: '',
  status_kepegawaian: '',
  nisn: '',
  kelas: '',
  nama_siswa: ''
})

// Skema Validasi Zod Menyesuaikan Role
const schema = computed(() => {
  return z
    .object({
      nama_depan: (isParent.value || isAdminSekolah.value || isAdminYayasan.value)
        ? z.string().optional()
        : v.personName('Nama Depan', { required: true, min: 2, max: 100 }),
      nama_belakang: v.personName('Nama Belakang', { required: false, min: 1, max: 100 }),
      nama_wali: isParent.value
        ? v.personName('Nama Lengkap Wali', { required: true, min: 2, max: 100 })
        : z.string().optional(),
      nik: z.string().optional(),
      noHpLogin: v.optionalPhone('No. HP / WhatsApp'),
      gelar_depan: isEduStaff.value ? v.academicTitle('Gelar Depan') : z.string().optional(),
      gelar_belakang: isEduStaff.value ? v.academicTitle('Gelar Belakang') : z.string().optional(),
      jenis_kelamin: (isEduStaff.value || isTu.value || isStudent.value)
        ? v.requiredSelect('Jenis Kelamin')
        : z.string().optional(),
      tempat_lahir: (isEduStaff.value || isTu.value || isStudent.value)
        ? v.placeName('Tempat Lahir', { min: 2, max: 100 })
        : z.string().optional(),
      tanggal_lahir: (isEduStaff.value || isTu.value || isStudent.value)
        ? v.requiredDate('Tanggal Lahir')
        : z.string().optional(),
      agama: (isEduStaff.value || isTu.value || isStudent.value)
        ? v.requiredSelect('Agama')
        : z.string().optional(),
      status_pernikahan: (isEduStaff.value || isTu.value)
        ? v.requiredSelect('Status Pernikahan')
        : z.string().optional(),
      pendidikan_terakhir: (isEduStaff.value || isTu.value)
        ? v.requiredSelect('Pendidikan Terakhir')
        : z.string().optional(),
      alamat: (isEduStaff.value || isTu.value || isStudent.value || isParent.value)
        ? v.requiredString('Alamat Lengkap', { min: 5, max: 500 })
        : z.string().optional(),
      hubungan_siswa: isParent.value
        ? v.requiredSelect('Hubungan dengan Siswa')
        : z.string().optional(),
      kelamin_wali: isParent.value
        ? v.requiredSelect('Jenis Kelamin Wali')
        : z.string().optional(),
      pekerjaan_wali: isParent.value
        ? v.requiredSelect('Pekerjaan Wali')
        : z.string().optional(),
      // Validasi Password Opsional
      password_lama: z.string().optional(),
      password_baru: z.string().optional(),
      password_baru_confirmation: z.string().optional()
    })
    .superRefine((data, ctx) => {
      // Jika user mengisi password baru
      if (data.password_baru && data.password_baru.trim() !== '') {
        if (!data.password_lama || data.password_lama.trim() === '') {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: 'Password lama wajib diisi untuk mengubah password',
            path: ['password_lama']
          })
        }
        if (data.password_baru.length < 8) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: 'Password baru minimal 8 karakter',
            path: ['password_baru']
          })
        }
        if (data.password_baru !== data.password_baru_confirmation) {
          ctx.addIssue({
            code: z.ZodIssueCode.custom,
            message: 'Konfirmasi password baru tidak cocok',
            path: ['password_baru_confirmation']
          })
        }
      }
    })
})

const { errors, validate, clearErrors, setServerErrors } = useZodForm(schema, form)

// Helper mapping gender DB <-> Form Select Code
const toGenderCode = val => {
  if (!val) return ''
  const s = String(val).toLowerCase()
  if (s === 'male' || s === 'laki-laki' || s === 'jk01') return 'JK01'
  if (s === 'female' || s === 'perempuan' || s === 'jk02') return 'JK02'
  return ''
}

// Load data asli pengguna dari backend/store
const loadUserData = () => {
  const u = auth.user
  if (!u) return

  const tp = u.teacher_profile || u.teacherProfile || {}
  const sp = u.student_profile || u.studentProfile || {}
  const pp = u.parent_profile || u.parentProfile || {}

  const nameParts = (u.name || '').split(' ')
  const namaDepan = u.nama_depan || nameParts[0] || ''
  const namaBelakang = u.nama_belakang || (nameParts.length > 1 ? nameParts.slice(1).join(' ') : '')

  // Foto Profil
  imagePreview.value = getPhotoUrl(u.photo) || ''

  // Anak yang terhubung untuk akun orang tua
  const studentLink = pp.students?.[0] || {}
  const studentUser = studentLink.user || {}
  const studentClass = studentLink.classroom || {}

  const f = u.foundation || u.school?.foundation || {}

  form.value = {
    // Data Yayasan (Read-Only di Akun Setting)
    nama: f.name || '',
    status: f.status ? f.status.charAt(0).toUpperCase() + f.status.slice(1) : '',
    tanggal_berdiri: (f.established_date || '').split('T')[0],
    alamat: f.address || '',
    no_akta: f.deed_number || '',
    tanggal_akta: (f.deed_date || '').split('T')[0],
    no_sk: f.decree_number || '',
    tanggal_sk: (f.decree_date || '').split('T')[0],
    email: f.email || '',
    no_hp: f.phone || '',
    website: f.website || '',
    logo: f.logo || null,
    foto: null,
    emailLogin: u.email || '',
    noHpLogin: u.phone || '',
    password_lama: '',
    password_baru: '',
    password_baru_confirmation: '',
    // Data Personal
    nama_depan: namaDepan,
    nama_belakang: namaBelakang,
    gelar_depan: tp.front_title || '',
    gelar_belakang: tp.back_title || '',
    nik: tp.nik || sp.nik || pp.nik || '',
    jenis_kelamin: toGenderCode(tp.gender || sp.gender),
    tempat_lahir: tp.birth_place || sp.birth_place || '',
    tanggal_lahir: (tp.birth_date || sp.birth_date || '').split('T')[0],
    agama: tp.religion || '',
    status_pernikahan: tp.marital_status || '',
    pendidikan_terakhir: tp.last_education || '',
    alamat: tp.address || sp.address || pp.address || '',
    // Khusus Orang Tua
    nama_wali: u.name || '',
    hubungan_siswa: pp.relationship || 'ayah',
    kelamin_wali: toGenderCode(pp.gender || 'JK01'),
    pekerjaan_wali: pp.occupation || 'wiraswasta',
    // Informasi Read-Only
    nip_nuptk: tp.nip_nuptk || '',
    jabatan: u.roleLabel || u.role || '-',
    unit_kerja: u.school?.name || u.foundation?.name || 'Pusat Yayasan',
    status_kepegawaian: tp.employment_status || 'Pegawai Tetap',
    nisn: sp.nisn || '-',
    kelas: sp.classroom?.name || '-',
    nama_siswa: studentUser.name ? `${studentUser.name} (${studentClass.name || 'Kelas'})` : '-'
  }

  clearErrors()
}

onMounted(async () => {
  await auth.initUser()
  loadUserData()
})

const handleImageChange = file => {
  if (!file) return
  form.value.foto = file
  imagePreview.value = URL.createObjectURL(file)
}

// Simpan Perubahan Akun & Profil Sesuai AGENTS.md
const handleSave = async () => {
  if (!validate()) {
    toast.error('Periksa kembali form', {
      description: 'Mohon periksa data yang ditandai merah sebelum menyimpan.'
    })
    return
  }

  isSaving.value = true
  try {
    const formData = new FormData()

    // Nama (Hanya dikirim untuk akun yang memiliki field nama personal tersendiri)
    if (isParent.value) {
      formData.append('name', form.value.nama_wali.trim())
    } else if (!isAdminSekolah.value && !isAdminYayasan.value) {
      formData.append('nama_depan', form.value.nama_depan.trim())
      if (form.value.nama_belakang) {
        formData.append('nama_belakang', form.value.nama_belakang.trim())
      }
    }

    // Telepon
    if (form.value.noHpLogin) {
      formData.append('noHpLogin', form.value.noHpLogin.trim())
    }

    // Foto
    if (form.value.foto instanceof File) {
      formData.append('foto', form.value.foto)
    }

    // Password jika diisi
    if (form.value.password_baru) {
      formData.append('password_lama', form.value.password_lama)
      formData.append('password_baru', form.value.password_baru)
      formData.append('password_baru_confirmation', form.value.password_baru_confirmation)
    }

    // Data Per Kategori Role
    if (isEduStaff.value) {
      if (form.value.gelar_depan) formData.append('gelar_depan', form.value.gelar_depan.trim())
      if (form.value.gelar_belakang) formData.append('gelar_belakang', form.value.gelar_belakang.trim())
      if (form.value.nik) formData.append('nik', form.value.nik.trim())
      if (form.value.jenis_kelamin) formData.append('jenis_kelamin', form.value.jenis_kelamin)
      if (form.value.tempat_lahir) formData.append('tempat_lahir', form.value.tempat_lahir.trim())
      if (form.value.tanggal_lahir) formData.append('tanggal_lahir', form.value.tanggal_lahir)
      if (form.value.agama) formData.append('agama', form.value.agama)
      if (form.value.status_pernikahan) formData.append('status_pernikahan', form.value.status_pernikahan)
      if (form.value.pendidikan_terakhir) formData.append('pendidikan_terakhir', form.value.pendidikan_terakhir)
      if (form.value.alamat) formData.append('alamat', form.value.alamat.trim())
    } else if (isTu.value) {
      if (form.value.nik) formData.append('nik', form.value.nik.trim())
      if (form.value.jenis_kelamin) formData.append('jenis_kelamin', form.value.jenis_kelamin)
      if (form.value.tempat_lahir) formData.append('tempat_lahir', form.value.tempat_lahir.trim())
      if (form.value.tanggal_lahir) formData.append('tanggal_lahir', form.value.tanggal_lahir)
      if (form.value.agama) formData.append('agama', form.value.agama)
      if (form.value.status_pernikahan) formData.append('status_pernikahan', form.value.status_pernikahan)
      if (form.value.pendidikan_terakhir) formData.append('pendidikan_terakhir', form.value.pendidikan_terakhir)
      if (form.value.alamat) formData.append('alamat', form.value.alamat.trim())
    } else if (isStudent.value) {
      if (form.value.nik) formData.append('nik', form.value.nik.trim())
      if (form.value.tempat_lahir) formData.append('tempat_lahir', form.value.tempat_lahir.trim())
      if (form.value.tanggal_lahir) formData.append('tanggal_lahir', form.value.tanggal_lahir)
      if (form.value.jenis_kelamin) formData.append('jenis_kelamin', form.value.jenis_kelamin)
      if (form.value.agama) formData.append('agama', form.value.agama)
      if (form.value.alamat) formData.append('alamat', form.value.alamat.trim())
    } else if (isParent.value) {
      if (form.value.hubungan_siswa) formData.append('hubungan_siswa', form.value.hubungan_siswa)
      if (form.value.kelamin_wali) formData.append('kelamin_wali', form.value.kelamin_wali)
      if (form.value.pekerjaan_wali) formData.append('pekerjaan_wali', form.value.pekerjaan_wali)
      if (form.value.nik) formData.append('nik', form.value.nik.trim())
      if (form.value.alamat) formData.append('alamat', form.value.alamat.trim())
    } else {
      // Administrator: admin_sekolah, admin_yayasan, superadmin
      // Akun murni kredensial user (nama, noHpLogin, foto, password)
    }

    const response = await updateProfile(formData)
    if (response?.user) {
      auth.user = response.user
      loadUserData()
    }

    // Reset password field setelah berhasil
    form.value.password_lama = ''
    form.value.password_baru = ''
    form.value.password_baru_confirmation = ''

    toast.success('Pengaturan Akun Berhasil Diperbarui', {
      description: 'Perubahan data profil dan akun Anda telah disimpan dengan sukses.'
    })
  } catch (err) {
    if (!setServerErrors(err)) {
      toast.error('Gagal memperbarui pengaturan akun', {
        description: err.response?.data?.message || 'Terjadi kesalahan sistem saat menyimpan data.'
      })
    }
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="space-y-6 pb-24 text-left">
    <!-- Header Halaman -->
    <PageHeader
      back
      title="Pengaturan Akun"
      :description="`Kelola informasi profil pribadi dan pengaturan keamanan akun ${roleTitle} Anda`"
    />

    <!-- Formulir Grid Standar Pembuatan Data Gerbang Sekolah -->
    <form class="space-y-6" @submit.prevent="handleSave">
      <!-- ================= 1. ADMIN YAYASAN (Reusable Form Yayasan Standar) ================= -->
      <template v-if="isAdminYayasan">
        <YayasanForm
          :form="form"
          :image-preview="visualSlot.preview"
          :status-options="yayasanStatusOptions"
          :errors="errors"
          :is-read-only-institutional="true"
          :show-password-fields="true"
          :require-old-password="true"
          @image-change="handleImageChange"
        />
      </template>

      <!-- ================= 2. ROLE LAINNYA ================= -->
      <BentoFormLayout v-else>
        <!-- Kolom Kiri: Foto Profil / Logo & Akun Pengguna / Password (35%) -->
        <template #left>
          <!-- 1. Foto Profil atau Logo Resmi Sesuai Role -->
          <FormSection
            :title="visualSlot.title"
            :description="visualSlot.description"
            :icon="visualSlot.icon"
          >
            <ImageUpload
              :preview="visualSlot.preview"
              :disabled="visualSlot.disabled"
              :fit="visualSlot.fit"
              :note="visualSlot.note"
              :error="errors.foto"
              @change="handleImageChange"
            />
          </FormSection>

          <!-- 2. Akun Pengguna & Kredensial Login -->
          <FormSection
            title="Akun Pengguna"
            description="Kredensial yang digunakan untuk login ke sistem"
            :icon="UserCog"
          >
            <div class="space-y-4">
              <FormInput
                v-model="form.emailLogin"
                type="email"
                label="E-mail Login"
                disabled
              />

              <FormInput
                v-model="form.noHpLogin"
                label="No. HP / WhatsApp (Opsional)"
                placeholder="Contoh: 081234567890"
                numeric-only
                :maxlength="15"
                :error="errors.noHpLogin"
              />

              <Separator class="my-2" />

              <div class="space-y-1">
                <p class="text-xs font-semibold text-foreground tracking-tight">Keamanan Kata Sandi</p>
                <p class="text-[11px] text-muted-foreground leading-relaxed">
                  Kosongkan bagian ini jika Anda tidak ingin mengganti kata sandi akun.
                </p>
              </div>

              <FormInput
                v-model="form.password_lama"
                type="password"
                label="Password Lama"
                placeholder="Masukkan password saat ini"
                :error="errors.password_lama"
              />

              <FormInput
                v-model="form.password_baru"
                type="password"
                label="Password Baru"
                placeholder="Minimal 8 karakter"
                :error="errors.password_baru"
              />

              <FormInput
                v-model="form.password_baru_confirmation"
                type="password"
                label="Konfirmasi Password Baru"
                placeholder="Ulangi password baru"
                :error="errors.password_baru_confirmation"
              />
            </div>
          </FormSection>
        </template>

        <!-- Kolom Kanan: Informasi Pribadi & Informasi Instansi (65%) -->
        <template #right>
          <!-- ================= 1. ADMIN SEKOLAH (Dibuat Saat Pembuatan Sekolah) ================= -->
          <template v-if="isAdminSekolah">
            <!-- 1. Informasi Dasar Sekolah (Read-Only) -->
            <FormSection
              title="Informasi Dasar Sekolah"
              description="Rincian nama sekolah, NPSN, jenjang, dan kurikulum (Dikelola melalui menu Manajemen Data)"
              :icon="School"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  :model-value="schoolData.name || '-'"
                  label="Nama Unit Sekolah"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.npsn || '-'"
                  label="NPSN"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.foundation?.name || foundationData.name || '-'"
                  label="Yayasan Induk"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.level ? schoolData.level.toUpperCase() : '-'"
                  label="Jenjang Pendidikan"
                  disabled
                />
                <FormInput
                  :model-value="formatDate(schoolData.established_date)"
                  label="Tanggal Berdiri"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.status || '-'"
                  label="Status Operasional"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.curriculum?.name || '-'"
                  label="Jenis Kurikulum Sekolah"
                  class="md:col-span-2"
                  disabled
                />
              </div>

              <Separator class="my-2" />

              <FormTextArea
                :model-value="schoolData.address || '-'"
                :rows="3"
                label="Alamat Lengkap Sekolah"
                disabled
              />
            </FormSection>

            <!-- 3. Dokumen Legalitas & Akreditasi (Read-Only) -->
            <FormSection
              title="Dokumen Legalitas & Akreditasi"
              description="Informasi SK Pendirian, Izin Operasional, dan Akreditasi (Dikelola melalui menu Manajemen Data)"
              :icon="Scale"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  :model-value="schoolData.decree_number || '-'"
                  label="No. SK Pendirian"
                  disabled
                />
                <FormInput
                  :model-value="formatDate(schoolData.decree_date)"
                  label="Tanggal SK Pendirian"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.permit_number || '-'"
                  label="No. Izin Operasional"
                  disabled
                />
                <FormInput
                  :model-value="formatDate(schoolData.permit_date)"
                  label="Tanggal Izin Operasional"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.accreditation || '-'"
                  label="Peringkat Akreditasi"
                  disabled
                />
                <FormInput
                  :model-value="formatDate(schoolData.accreditation_date)"
                  label="Tanggal Akreditasi"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.accreditation_number || '-'"
                  label="No. SK Akreditasi"
                  class="md:col-span-2"
                  disabled
                />
              </div>
            </FormSection>

            <!-- 4. Kontak & Media Sosial Resmi Sekolah (Read-Only) -->
            <FormSection
              title="Kontak Resmi Sekolah"
              description="Informasi email, telepon, website, dan medsos resmi sekolah (Dikelola melalui menu Manajemen Data)"
              :icon="Phone"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  :model-value="schoolData.email || '-'"
                  label="E-mail Resmi Sekolah"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.phone || '-'"
                  label="No. Telepon Sekolah"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.website || '-'"
                  label="Website Resmi Sekolah"
                  class="md:col-span-2"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.instagram || '-'"
                  label="Akun Instagram"
                  disabled
                />
                <FormInput
                  :model-value="schoolData.facebook || '-'"
                  label="Halaman Facebook"
                  disabled
                />
              </div>
            </FormSection>
          </template>

          <!-- ================= 3. SUPERADMIN ================= -->
          <template v-else-if="isSuperAdmin">
            <FormSection
              title="Informasi Administrator"
              description="Data identitas super administrator"
              :icon="SquareUserRound"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  v-model="form.nama_depan"
                  label="Nama Depan"
                  placeholder="Contoh: Super"
                  required
                  :error="errors.nama_depan"
                />
                <FormInput
                  v-model="form.nama_belakang"
                  label="Nama Belakang (Opsional)"
                  placeholder="Contoh: Admin"
                  :error="errors.nama_belakang"
                />
              </div>
            </FormSection>

            <FormSection
              title="Hak Akses Sistem"
              description="Kewenangan akses sistem platform Gerbang Sekolah"
              :icon="ShieldCheck"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  v-model="form.jabatan"
                  label="Peran Akun"
                  disabled
                />
                <FormInput
                  model-value="Platform Gerbang Sekolah (Pusat)"
                  label="Cakupan Akses"
                  disabled
                />
              </div>
            </FormSection>
          </template>

          <!-- ================= 4. GURU & KEPALA SEKOLAH (Tenaga Pendidik Bergelar) ================= -->
          <template v-else-if="isEduStaff">
            <!-- 1. Informasi Pribadi Guru/Kepala Sekolah -->
            <FormSection
              title="Informasi Pribadi"
              description="Identitas diri, data kelahiran, kualifikasi pendidikan, dan domisili"
              :icon="SquareUserRound"
            >
              <!-- Grup A: Nama & Gelar -->
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  v-model="form.nama_depan"
                  label="Nama Depan"
                  placeholder="Contoh: Ahmad"
                  required
                  :error="errors.nama_depan"
                />
                <FormInput
                  v-model="form.nama_belakang"
                  label="Nama Belakang (Opsional)"
                  placeholder="Contoh: Fauzi"
                  :error="errors.nama_belakang"
                />
                <FormInput
                  v-model="form.gelar_depan"
                  label="Gelar Depan (Opsional)"
                  placeholder="Contoh: Drs."
                  :error="errors.gelar_depan"
                />
                <FormInput
                  v-model="form.gelar_belakang"
                  label="Gelar Belakang (Opsional)"
                  placeholder="Contoh: S.Pd., M.Pd."
                  :error="errors.gelar_belakang"
                />
              </div>

              <Separator class="my-2" />

              <!-- Grup B: Identitas Kependudukan & Kelahiran -->
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  :model-value="form.nik || '-'"
                  label="NIK (Nomor Induk Kependudukan)"
                  disabled
                />
                <FormSelect
                  v-model="form.jenis_kelamin"
                  label="Jenis Kelamin"
                  placeholder="Pilih jenis kelamin"
                  :options="kelaminOptions"
                  required
                  :error="errors.jenis_kelamin"
                />
                <FormInput
                  v-model="form.tempat_lahir"
                  label="Tempat Lahir"
                  placeholder="Contoh: Jakarta"
                  required
                  :error="errors.tempat_lahir"
                />
                <FormDate
                  v-model="form.tanggal_lahir"
                  label="Tanggal Lahir"
                  required
                  :error="errors.tanggal_lahir"
                />
                <FormSelect
                  v-model="form.agama"
                  label="Agama"
                  placeholder="Pilih agama"
                  :options="agamaOptions"
                  required
                  :error="errors.agama"
                />
                <FormSelect
                  v-model="form.status_pernikahan"
                  label="Status Pernikahan"
                  placeholder="Pilih status pernikahan"
                  :options="statusPernikahanOptions"
                  required
                  :error="errors.status_pernikahan"
                />
              </div>

              <Separator class="my-2" />

              <!-- Grup C: Kualifikasi Pendidikan & Domisili -->
              <div class="grid gap-4 md:grid-cols-1">
                <FormSelect
                  v-model="form.pendidikan_terakhir"
                  label="Pendidikan Terakhir"
                  placeholder="Pilih jenjang pendidikan terakhir"
                  :options="pendidikanOptions"
                  required
                  :error="errors.pendidikan_terakhir"
                />
                <FormTextArea
                  v-model="form.alamat"
                  :rows="3"
                  label="Alamat Lengkap"
                  placeholder="Contoh: Jl. Pendidikan No. 10, RT 01/RW 02..."
                  required
                  :error="errors.alamat"
                />
              </div>
            </FormSection>

            <!-- 2. Informasi Kepegawaian (Read-Only) -->
            <FormSection
              title="Informasi Kepegawaian"
              description="Penempatan unit kerja, jabatan, dan nomor induk pegawai (Dikelola di Manajemen Data)"
              :icon="BriefcaseBusiness"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  v-model="form.unit_kerja"
                  label="Unit Kerja"
                  disabled
                />
                <FormInput
                  v-model="form.jabatan"
                  label="Jabatan"
                  disabled
                />
                <FormInput
                  v-model="form.nip_nuptk"
                  label="NIP / NUPTK"
                  disabled
                />
                <FormInput
                  v-model="form.status_kepegawaian"
                  label="Status Kepegawaian"
                  disabled
                />
              </div>
            </FormSection>
          </template>

          <!-- ================= 5. TATA USAHA (Staf Administrasi) ================= -->
          <template v-else-if="isTu">
            <FormSection
              title="Informasi Pribadi"
              description="Identitas diri, data kelahiran, kualifikasi pendidikan, dan domisili staf TU"
              :icon="SquareUserRound"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  v-model="form.nama_depan"
                  label="Nama Depan"
                  placeholder="Contoh: Tata"
                  required
                  :error="errors.nama_depan"
                />
                <FormInput
                  v-model="form.nama_belakang"
                  label="Nama Belakang (Opsional)"
                  placeholder="Contoh: Usaha"
                  :error="errors.nama_belakang"
                />
              </div>

              <Separator class="my-2" />

              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  :model-value="form.nik || '-'"
                  label="NIK (Nomor Induk Kependudukan)"
                  disabled
                />
                <FormSelect
                  v-model="form.jenis_kelamin"
                  label="Jenis Kelamin"
                  placeholder="Pilih jenis kelamin"
                  :options="kelaminOptions"
                  required
                  :error="errors.jenis_kelamin"
                />
                <FormInput
                  v-model="form.tempat_lahir"
                  label="Tempat Lahir"
                  placeholder="Contoh: Jakarta"
                  required
                  :error="errors.tempat_lahir"
                />
                <FormDate
                  v-model="form.tanggal_lahir"
                  label="Tanggal Lahir"
                  required
                  :error="errors.tanggal_lahir"
                />
                <FormSelect
                  v-model="form.agama"
                  label="Agama"
                  placeholder="Pilih agama"
                  :options="agamaOptions"
                  required
                  :error="errors.agama"
                />
                <FormSelect
                  v-model="form.status_pernikahan"
                  label="Status Pernikahan"
                  placeholder="Pilih status pernikahan"
                  :options="statusPernikahanOptions"
                  required
                  :error="errors.status_pernikahan"
                />
              </div>

              <Separator class="my-2" />

              <div class="grid gap-4 md:grid-cols-1">
                <FormSelect
                  v-model="form.pendidikan_terakhir"
                  label="Pendidikan Terakhir"
                  placeholder="Pilih jenjang pendidikan terakhir"
                  :options="pendidikanOptions"
                  required
                  :error="errors.pendidikan_terakhir"
                />
                <FormTextArea
                  v-model="form.alamat"
                  :rows="3"
                  label="Alamat Lengkap"
                  placeholder="Contoh: Jl. Cut Mutia No. 88..."
                  required
                  :error="errors.alamat"
                />
              </div>
            </FormSection>

            <FormSection
              title="Informasi Kepegawaian"
              description="Penempatan unit kerja dan jabatan staf tata usaha (Dikelola di Manajemen Data)"
              :icon="BriefcaseBusiness"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  v-model="form.unit_kerja"
                  label="Unit Kerja"
                  disabled
                />
                <FormInput
                  v-model="form.jabatan"
                  label="Jabatan"
                  disabled
                />
                <FormInput
                  v-model="form.nip_nuptk"
                  label="NIP / NUPTK"
                  disabled
                />
                <FormInput
                  v-model="form.status_kepegawaian"
                  label="Status Kepegawaian"
                  disabled
                />
              </div>
            </FormSection>
          </template>

          <!-- ================= 6. SISWA (Dibuat di Manajemen Siswa) ================= -->
          <template v-else-if="isStudent">
            <FormSection
              title="Informasi Siswa"
              description="Informasi identitas dasar siswa terdaftar"
              :icon="SquareUserRound"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  v-model="form.nama_depan"
                  label="Nama Depan"
                  placeholder="Contoh: John"
                  required
                  :error="errors.nama_depan"
                />
                <FormInput
                  v-model="form.nama_belakang"
                  label="Nama Belakang (Opsional)"
                  placeholder="Contoh: Doe"
                  :error="errors.nama_belakang"
                />
                <FormInput
                  :model-value="form.nik || '-'"
                  label="NIK (Nomor Induk Kependudukan)"
                  disabled
                />
                <FormInput
                  v-model="form.nisn"
                  label="NISN Siswa"
                  disabled
                />
                <FormInput
                  v-model="form.tempat_lahir"
                  label="Tempat Lahir"
                  placeholder="Contoh: Jakarta"
                  required
                  :error="errors.tempat_lahir"
                />
                <FormDate
                  v-model="form.tanggal_lahir"
                  label="Tanggal Lahir"
                  required
                  :error="errors.tanggal_lahir"
                />
                <FormSelect
                  v-model="form.jenis_kelamin"
                  label="Jenis Kelamin"
                  placeholder="Pilih jenis kelamin"
                  :options="kelaminOptions"
                  required
                  :error="errors.jenis_kelamin"
                />
                <FormSelect
                  v-model="form.agama"
                  label="Agama"
                  placeholder="Pilih agama"
                  :options="agamaOptions"
                  required
                  :error="errors.agama"
                />
              </div>

              <Separator class="my-2" />

              <FormTextArea
                v-model="form.alamat"
                :rows="3"
                label="Alamat Lengkap"
                placeholder="Contoh: Jl. Kemang Pratama B/15..."
                required
                :error="errors.alamat"
              />
            </FormSection>

            <FormSection
              title="Informasi Akademik"
              description="Data sekolah dan kelas aktif siswa saat ini (Dikelola di Manajemen Data)"
              :icon="GraduationCap"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  v-model="form.unit_kerja"
                  label="Nama Sekolah"
                  disabled
                />
                <FormInput
                  v-model="form.kelas"
                  label="Kelas Aktif"
                  disabled
                />
              </div>
            </FormSection>
          </template>

          <!-- ================= 7. ORANG TUA / WALI (Dibuat di Form Orang Tua Siswa) ================= -->
          <template v-else-if="isParent">
            <FormSection
              title="Informasi Orang Tua / Wali"
              description="Informasi dasar mengenai orang tua atau wali murid"
              :icon="ShieldUser"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  v-model="form.nama_wali"
                  label="Nama Lengkap Wali"
                  placeholder="Contoh: Budi Wibowo"
                  required
                  :error="errors.nama_wali"
                />
                <FormSelect
                  v-model="form.hubungan_siswa"
                  label="Hubungan dengan Siswa"
                  placeholder="Pilih hubungan"
                  :options="hubunganOptions"
                  required
                  :error="errors.hubungan_siswa"
                />
                <FormSelect
                  v-model="form.kelamin_wali"
                  label="Jenis Kelamin Wali"
                  placeholder="Pilih jenis kelamin"
                  :options="kelaminOptions"
                  required
                  :error="errors.kelamin_wali"
                />
                <FormSelect
                  v-model="form.pekerjaan_wali"
                  label="Pekerjaan Wali"
                  placeholder="Pilih pekerjaan"
                  :options="pekerjaanOptions"
                  required
                  :error="errors.pekerjaan_wali"
                />
                <FormInput
                  :model-value="form.nik || '-'"
                  label="NIK (Nomor Induk Kependudukan)"
                  class="md:col-span-2"
                  disabled
                />
              </div>

              <Separator class="my-2" />

              <FormTextArea
                v-model="form.alamat"
                :rows="3"
                label="Alamat Lengkap Domisili"
                placeholder="Contoh: Jl. Cut Mutia No. 88..."
                required
                :error="errors.alamat"
              />
            </FormSection>

            <FormSection
              title="Informasi Siswa yang Didampingi"
              description="Data anak atau siswa yang terhubung dengan akun Anda (Dikelola di Manajemen Data)"
              :icon="SquareUserRound"
            >
              <div class="grid gap-4 md:grid-cols-2">
                <FormInput
                  v-model="form.nama_siswa"
                  label="Nama Siswa & Kelas"
                  disabled
                />
                <FormInput
                  v-model="form.unit_kerja"
                  label="Sekolah Siswa"
                  disabled
                />
              </div>
            </FormSection>
          </template>
        </template>
      </BentoFormLayout>

      <!-- Tombol Simpan FormFooter Sesuai AGENTS.md (Kanan Bawah, Dialog Konfirmasi, Anti-Double-Click) -->
      <FormFooter
        submit-label="Simpan Perubahan Akun"
        :loading="isSaving"
        confirm-title="Simpan Perubahan Akun?"
        confirm-description="Apakah Anda yakin data profil dan pengaturan akun yang Anda masukkan sudah benar?"
        :before-confirm="validate"
      />
    </form>
  </div>
</template>
