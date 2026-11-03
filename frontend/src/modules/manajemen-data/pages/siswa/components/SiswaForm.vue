<script setup>
import { ref, computed, watch } from 'vue'
import { Phone, Image, UserCog, SquareUserRound, GraduationCap, ShieldUser, Lock } from 'lucide-vue-next'
import { Separator } from '@/components/ui/separator'
import { Checkbox } from '@/components/ui/checkbox'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import ImageUpload from '@/components/forms/ImageUpload.vue'
import FormInput from '@/components/forms/FormInput.vue'
import FormTextArea from '@/components/forms/FormTextArea.vue'
import FormDate from '@/components/forms/FormDate.vue'
import FormSection from '@/components/forms/FormSection.vue'
import FormSelect from '@/components/forms/FormSelect.vue'
import BentoFormLayout from '@/components/forms/BentoFormLayout.vue'
import { getPhotoUrl } from '@/utils/getPhotoUrl'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
  form: { type: Object, required: true },
  imagePreview: { type: String, default: '' },
  yayasanOptions: { type: Array, default: () => [] },
  sekolahOptions: { type: Array, default: () => [] },
  statusOptions: { type: Array, default: () => [] },
  kelasOptions: { type: Array, default: () => [] },
  pekerjaanOptions: { type: Array, default: () => [] },
  agamaOptions: { type: Array, default: () => [] },
  kelaminOptions: { type: Array, default: () => [] },
  hubunganOptions: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  isReadOnlyAcademic: { type: Boolean, default: false },
  showPasswordFields: { type: Boolean, default: false },
  requireOldPassword: { type: Boolean, default: false }
})

const emit = defineEmits(['image-change'])
const auth = useAuthStore()
const isSuperAdmin = computed(() => auth.user?.role === 'superadmin')

const filteredSekolahOptions = computed(() => {
  if (!isSuperAdmin.value) return props.sekolahOptions
  const selectedFoundationId = String(props.form.foundation_id || '')
  if (!selectedFoundationId) return []
  return props.sekolahOptions.filter(opt => String(opt.foundation_id || '') === selectedFoundationId)
})

const filteredKelasOptions = computed(() => {
  if (!isSuperAdmin.value) return props.kelasOptions
  const selectedSchoolId = String(props.form.sekolahId || '')
  if (!selectedSchoolId) return []
  return props.kelasOptions.filter(opt => !opt.school_id || String(opt.school_id) === selectedSchoolId)
})

watch(
  [() => props.form.foundation_id, filteredSekolahOptions],
  ([foundationId, validSchools]) => {
    if (!isSuperAdmin.value) return
    if (!foundationId) {
      if (props.form.sekolahId) props.form.sekolahId = ''
      if (props.form.kelas) props.form.kelas = ''
      return
    }
    if (props.sekolahOptions.length === 0) return
    const isValid = validSchools.some(opt => String(opt.value) === String(props.form.sekolahId))
    if (!isValid && props.form.sekolahId) {
      props.form.sekolahId = ''
      props.form.kelas = ''
    }
  }
)

watch(
  [() => props.form.sekolahId, filteredKelasOptions],
  ([sekolahId, validClasses]) => {
    if (!isSuperAdmin.value) return
    if (!sekolahId) {
      if (props.form.kelas) props.form.kelas = ''
      return
    }
    if (props.kelasOptions.length === 0) return
    const isValid = validClasses.some(opt => String(opt.value) === String(props.form.kelas))
    if (!isValid && props.form.kelas) {
      props.form.kelas = ''
    }
  }
)

const computedPreview = computed(() => {
  if (props.imagePreview) return props.imagePreview
  const raw = props.form?.foto || props.form?.photo
  if (raw && typeof raw === 'string') {
    return getPhotoUrl(raw)
  }
  return ''
})

const handleImageChange = file => {
  emit('image-change', file)
}

const activeParentTab = ref('ayah')
const sameAddress = ref({ ayah: false, ibu: false, wali: false })

const toggleSameAddress = (role, checked) => {
  sameAddress.value[role] = checked
  props.form[`alamat_${role}`] = checked ? props.form.alamat || '' : ''
}

watch(
  () => props.form.alamat,
  newAlamat => {
    ;['ayah', 'ibu', 'wali'].forEach(role => {
      if (sameAddress.value[role]) {
        props.form[`alamat_${role}`] = newAlamat || ''
      }
    })
  }
)

;['ayah', 'ibu', 'wali'].forEach(role => {
  watch(
    () => props.form[`alamat_${role}`],
    val => {
      if (sameAddress.value[role] && val !== (props.form.alamat || '')) {
        sameAddress.value[role] = false
      }
    }
  )
})

const isWaliOptional = computed(() => {
  return Boolean(
    String(props.form.nama_ayah || '').trim() && String(props.form.nama_ibu || '').trim()
  )
})

const hasAyahErrors = computed(() =>
  Boolean(
    props.errors.nama_ayah ||
      props.errors.pekerjaan_ayah ||
      props.errors.email_ayah ||
      props.errors.no_hp_ayah ||
      props.errors.alamat_ayah
  )
)

const hasIbuErrors = computed(() =>
  Boolean(
    props.errors.nama_ibu ||
      props.errors.pekerjaan_ibu ||
      props.errors.email_ibu ||
      props.errors.no_hp_ibu ||
      props.errors.alamat_ibu
  )
)

const hasWaliErrors = computed(() =>
  Boolean(
    props.errors.nama_wali ||
      props.errors.hubungan_siswa ||
      props.errors.kelamin_wali ||
      props.errors.pekerjaan_wali ||
      props.errors.email_wali ||
      props.errors.no_hp_wali ||
      props.errors.alamat_wali
  )
)
</script>

<template>
  <BentoFormLayout>
    <!-- ================= KOLOM KIRI (35% LEBAR) ================= -->
    <template #left>
      <!-- 1. Foto Siswa -->
      <FormSection title="Foto Profil (Opsional)" description="Upload foto profil siswa disini" :icon="Image">
        <ImageUpload
          :preview="computedPreview"
          :error="errors.foto"
          @change="handleImageChange"
          note="Format: JPG atau PNG. Maksimal 10MB. Dimensi rasio 1:1"
          allow-camera
        />
      </FormSection>

      <!-- 2. Kontak Siswa -->
      <FormSection
        title="Kontak Siswa (Opsional)"
        description="Informasi kontak siswa (kosongkan jika belum memiliki)"
        :icon="Phone"
      >
        <div class="space-y-4">
          <FormInput
            v-model="form.email"
            label="E-mail (Opsional)"
            placeholder="Contoh: nama@sekolah.com"
            :error="errors.email"
          />
          <FormInput
            v-model="form.no_hp"
            label="No. Telepon / WhatsApp (Opsional)"
            placeholder="Contoh: 081234567890"
            numeric-only
            :maxlength="15"
            :error="errors.no_hp"
          />
        </div>
      </FormSection>

      <!-- 3. Informasi Akademik -->
      <FormSection
        title="Informasi Akademik"
        :description="isReadOnlyAcademic ? 'Informasi penempatan kelas dan status siswa (Read-Only)' : 'Informasi penempatan kelas dan status siswa'"
        :icon="GraduationCap"
      >
        <div class="space-y-4">
          <template v-if="isSuperAdmin && !isReadOnlyAcademic">
            <FormSelect
              v-model="form.foundation_id"
              label="Yayasan"
              placeholder="Pilih yayasan terlebih dahulu"
              :options="yayasanOptions"
              required
              :error="errors.foundation_id"
            />
            <FormSelect
              v-model="form.sekolahId"
              label="Sekolah"
              :placeholder="!form.foundation_id ? 'Pilih yayasan terlebih dahulu' : 'Pilih sekolah'"
              :options="filteredSekolahOptions"
              :disabled="!form.foundation_id"
              required
              :error="errors.sekolahId"
            />
          </template>
          <FormDate v-model="form.tahun_masuk" label="Tahun Masuk" :disabled="isReadOnlyAcademic" :required="!isReadOnlyAcademic" :error="errors.tahun_masuk"/>
          <FormSelect
            v-model="form.kelas"
            label="Kelas (Opsional)"
            :placeholder="isSuperAdmin && !form.sekolahId ? 'Pilih sekolah terlebih dahulu' : 'Pilih kelas'"
            :options="filteredKelasOptions"
            :disabled="isReadOnlyAcademic || (isSuperAdmin && !form.sekolahId)"
            :error="errors.kelas"
          />
          <FormSelect v-model="form.status" label="Status" placeholder="Pilih status" :options="statusOptions" :disabled="isReadOnlyAcademic" :required="!isReadOnlyAcademic" :error="errors.status"/>
        </div>
      </FormSection>
    </template>

    <!-- ================= KOLOM KANAN (65% LEBAR) ================= -->
    <template #right>
      <!-- 1. Informasi Siswa -->
      <FormSection title="Informasi Siswa" description="Informasi identitas lengkap siswa" :icon="SquareUserRound">
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput v-model="form.nama_depan" label="Nama Depan" placeholder="Contoh: John" required :error="errors.nama_depan"/>
          <FormInput v-model="form.nama_belakang" label="Nama Belakang (Opsional)" placeholder="Contoh: Doe" :error="errors.nama_belakang"/>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput v-model="form.nik" label="NIK" placeholder="16 digit NIK sesuai KK/KTP" numeric-only :maxlength="16" required :error="errors.nik"/>
          <FormInput v-model="form.nisn" label="NISN" placeholder="10 digit NISN" numeric-only :maxlength="10" required :error="errors.nisn"/>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <FormInput v-model="form.tempat_lahir" label="Tempat Lahir" placeholder="Contoh: Jakarta" required :error="errors.tempat_lahir"/>
          <FormDate v-model="form.tanggal_lahir" label="Tanggal Lahir" required :error="errors.tanggal_lahir"/>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <FormSelect v-model="form.jenis_kelamin" label="Jenis Kelamin" placeholder="Pilih jenis kelamin" :options="kelaminOptions" required :error="errors.jenis_kelamin"/>
          <FormSelect v-model="form.agama" label="Agama" placeholder="Pilih agama" :options="agamaOptions" required :error="errors.agama"/>
        </div>  
        <Separator class="my-4" />
        <FormTextArea v-model="form.alamat" :rows="4" label="Alamat Lengkap" placeholder="Contoh: Nama Jalan, RT/RW, Kelurahan, Kecamatan, Kota" required :error="errors.alamat"/>
      </FormSection>

      <!-- 2. Informasi Orang Tua / Wali -->
      <FormSection
        title="Informasi Orang Tua / Wali"
        description="Data ayah, ibu, dan wali siswa (data wali bersifat opsional jika ayah & ibu sudah diisi)"
        :icon="ShieldUser"
      >
        <Tabs v-model="activeParentTab" class="w-full">
          <TabsList class="grid w-full grid-cols-3 mb-4">
            <TabsTrigger value="ayah" class="flex items-center gap-1.5">
              <span>Data Ayah</span>
              <span v-if="hasAyahErrors" class="w-2 h-2 rounded-full bg-destructive" />
            </TabsTrigger>
            <TabsTrigger value="ibu" class="flex items-center gap-1.5">
              <span>Data Ibu</span>
              <span v-if="hasIbuErrors" class="w-2 h-2 rounded-full bg-destructive" />
            </TabsTrigger>
            <TabsTrigger value="wali" class="flex items-center gap-1.5">
              <span>Data Wali (Opsional)</span>
              <span v-if="hasWaliErrors" class="w-2 h-2 rounded-full bg-destructive" />
            </TabsTrigger>
          </TabsList>

          <!-- TAB 1: DATA AYAH -->
          <TabsContent value="ayah" class="space-y-4 mt-0">
            <div class="grid gap-4 md:grid-cols-2">
              <FormInput
                v-model="form.nama_ayah"
                label="Nama Lengkap Ayah"
                placeholder="Contoh: Budi Santoso"
                required
                :error="errors.nama_ayah"
              />
              <FormSelect
                v-model="form.pekerjaan_ayah"
                label="Pekerjaan Ayah (Opsional)"
                placeholder="Pilih pekerjaan ayah"
                :options="pekerjaanOptions"
                :error="errors.pekerjaan_ayah"
              />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
              <FormInput
                v-model="form.email_ayah"
                label="E-mail Ayah (Opsional)"
                placeholder="Contoh: budi.santoso@gmail.com"
                :error="errors.email_ayah"
              />
              <FormInput
                v-model="form.no_hp_ayah"
                label="No. HP / WhatsApp Ayah"
                placeholder="Contoh: 081234567890"
                numeric-only
                :maxlength="15"
                required
                :error="errors.no_hp_ayah"
              />
            </div>
            <Separator class="my-2" />
            <FormTextArea
              v-model="form.alamat_ayah"
              :rows="3"
              label="Alamat Lengkap Ayah"
              placeholder="Contoh: Jl. Pendidikan No. 10, RT 01/RW 02, Kel. Sukamaju, Kec. Cimandiri, Kota Bandung"
              required
              :error="errors.alamat_ayah"
            >
              <template #label-extra>
                <label class="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer select-none">
                  <Checkbox
                    :model-value="sameAddress.ayah"
                    @update:model-value="toggleSameAddress('ayah', $event)"
                  />
                  <span>Sama dengan alamat siswa</span>
                </label>
              </template>
            </FormTextArea>
          </TabsContent>

          <!-- TAB 2: DATA IBU -->
          <TabsContent value="ibu" class="space-y-4 mt-0">
            <div class="grid gap-4 md:grid-cols-2">
              <FormInput
                v-model="form.nama_ibu"
                label="Nama Lengkap Ibu"
                placeholder="Contoh: Siti Aminah"
                required
                :error="errors.nama_ibu"
              />
              <FormSelect
                v-model="form.pekerjaan_ibu"
                label="Pekerjaan Ibu (Opsional)"
                placeholder="Pilih pekerjaan ibu"
                :options="pekerjaanOptions"
                :error="errors.pekerjaan_ibu"
              />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
              <FormInput
                v-model="form.email_ibu"
                label="E-mail Ibu (Opsional)"
                placeholder="Contoh: siti.aminah@gmail.com"
                :error="errors.email_ibu"
              />
              <FormInput
                v-model="form.no_hp_ibu"
                label="No. HP / WhatsApp Ibu"
                placeholder="Contoh: 081234567890"
                numeric-only
                :maxlength="15"
                required
                :error="errors.no_hp_ibu"
              />
            </div>
            <Separator class="my-2" />
            <FormTextArea
              v-model="form.alamat_ibu"
              :rows="3"
              label="Alamat Lengkap Ibu"
              placeholder="Contoh: Jl. Pendidikan No. 10, RT 01/RW 02, Kel. Sukamaju, Kec. Cimandiri, Kota Bandung"
              required
              :error="errors.alamat_ibu"
            >
              <template #label-extra>
                <label class="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer select-none">
                  <Checkbox
                    :model-value="sameAddress.ibu"
                    @update:model-value="toggleSameAddress('ibu', $event)"
                  />
                  <span>Sama dengan alamat siswa</span>
                </label>
              </template>
            </FormTextArea>
          </TabsContent>

          <!-- TAB 3: DATA WALI -->
          <TabsContent value="wali" class="space-y-4 mt-0">
            <div class="grid gap-4 md:grid-cols-2">
              <FormInput
                v-model="form.nama_wali"
                :label="isWaliOptional ? 'Nama Lengkap Wali (Opsional)' : 'Nama Lengkap Wali'"
                placeholder="Contoh: Ahmad Hidayat"
                :required="!isWaliOptional"
                :error="errors.nama_wali"
              />
              <FormSelect
                v-model="form.hubungan_siswa"
                :label="isWaliOptional ? 'Hubungan dengan Siswa (Opsional)' : 'Hubungan dengan Siswa'"
                placeholder="Pilih hubungan"
                :options="hubunganOptions"
                :required="!isWaliOptional"
                :error="errors.hubungan_siswa"
              />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
              <FormSelect
                v-model="form.kelamin_wali"
                :label="isWaliOptional ? 'Jenis Kelamin Wali (Opsional)' : 'Jenis Kelamin Wali'"
                placeholder="Pilih jenis kelamin"
                :options="kelaminOptions"
                :required="!isWaliOptional"
                :error="errors.kelamin_wali"
              />
              <FormSelect
                v-model="form.pekerjaan_wali"
                label="Pekerjaan Wali (Opsional)"
                placeholder="Pilih pekerjaan"
                :options="pekerjaanOptions"
                :error="errors.pekerjaan_wali"
              />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
              <FormInput
                v-model="form.email_wali"
                label="E-mail Wali (Opsional)"
                placeholder="Contoh: ahmad.hidayat@gmail.com"
                :error="errors.email_wali"
              />
              <FormInput
                v-model="form.no_hp_wali"
                :label="isWaliOptional ? 'No. HP / WhatsApp Wali (Opsional)' : 'No. HP / WhatsApp Wali'"
                placeholder="Contoh: 081234567890"
                numeric-only
                :maxlength="15"
                :required="!isWaliOptional"
                :error="errors.no_hp_wali"
              />
            </div>
            <Separator class="my-2" />
            <FormTextArea
              v-model="form.alamat_wali"
              :rows="3"
              :label="isWaliOptional ? 'Alamat Lengkap Wali (Opsional)' : 'Alamat Lengkap Wali'"
              placeholder="Contoh: Jl. Pendidikan No. 10, RT 01/RW 02, Kel. Sukamaju, Kec. Cimandiri, Kota Bandung"
              :required="!isWaliOptional"
              :error="errors.alamat_wali"
            >
              <template #label-extra>
                <label class="flex items-center gap-2 text-xs text-muted-foreground cursor-pointer select-none">
                  <Checkbox
                    :model-value="sameAddress.wali"
                    @update:model-value="toggleSameAddress('wali', $event)"
                  />
                  <span>Sama dengan alamat siswa</span>
                </label>
              </template>
            </FormTextArea>
          </TabsContent>
        </Tabs>
      </FormSection>

      <!-- 3. Akun Pengguna Orang Tua/Wali -->
      <FormSection title="Akun Pengguna Orang Tua/Wali" description="Kredensial login orang tua/wali siswa ke sistem." :icon="UserCog">
        <FormInput v-model="form.emailLogin" label="E-mail Login" placeholder="Contoh: nama_ortu@gmail.com" required :error="errors.emailLogin"/>

        <template v-if="showPasswordFields">
          <Separator class="my-3" />
          <div class="space-y-1 mb-3">
            <div class="flex items-center gap-1.5 text-xs font-semibold text-foreground tracking-tight">
              <Lock class="w-3.5 h-3.5 text-primary" />
              <span>Keamanan Kata Sandi Orang Tua/Wali</span>
            </div>
            <p class="text-[11px] text-muted-foreground leading-relaxed">
              Kosongkan bagian ini jika Anda tidak ingin mengganti kata sandi akun orang tua/wali.
            </p>
          </div>

          <div class="grid gap-4 md:grid-cols-2">
            <FormInput
              v-if="requireOldPassword"
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
        </template>
      </FormSection>
    </template>
  </BentoFormLayout>
</template>
