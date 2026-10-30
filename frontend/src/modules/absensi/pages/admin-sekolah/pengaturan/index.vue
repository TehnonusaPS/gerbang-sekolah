<script setup>
import { ref, onMounted, computed } from 'vue'
import { toast } from 'vue-sonner'
import { Save, Clock, Settings, HelpCircle } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { getAbsensiSettings, updateAbsensiSettings } from '@/services/api/absensi'
import PageHeader from '@/components/page-header/PageHeader.vue'
import FormFooter from '@/components/forms/FormFooter.vue'
import { glassFade, glassSlide } from '@/config/motion'

// ─── STATE ──────────────────────────────────────────────────
const isPageLoading = ref(true)
const isSubmitting = ref(false)
const attendanceLateThreshold = ref('07:30')

// ─── METHODS ────────────────────────────────────────────────
async function loadAbsensiSettings() {
  isPageLoading.value = true
  try {
    const response = await getAbsensiSettings()
    if (response.success) {
      attendanceLateThreshold.value = response.data.attendance_late_threshold
    }
  } catch (error) {
    console.error('Failed to load absensi settings:', error)
    toast.error('Gagal mengambil pengaturan absensi')
  } finally {
    isPageLoading.value = false
  }
}

function validateForm() {
  if (!attendanceLateThreshold.value) {
    toast.error('Waktu wajib diisi')
    return false
  }
  return true
}

async function handleSave() {
  isSubmitting.value = true
  try {
    const response = await updateAbsensiSettings({
      attendance_late_threshold: attendanceLateThreshold.value
    })
    
    if (response.success) {
      toast.success('Pengaturan Berhasil Diperbarui', {
        description: 'Batas waktu jam masuk absensi sekolah telah diperbarui.'
      })
    }
  } catch (error) {
    console.error(error)
    toast.error('Gagal memperbarui pengaturan absensi.')
  } finally {
    isSubmitting.value = false
  }
}

// ─── LIFECYCLE ──────────────────────────────────────────────
onMounted(() => {
  loadAbsensiSettings()
})
</script>

<template>
  <div
    v-motion
    :initial="glassFade.initial"
    :visible-once="glassFade.visible"
    class="space-y-6 animate-in fade-in duration-300 w-full pb-10 text-left"
  >
    <!-- ── Header ── -->
    <PageHeader
      title="Pengaturan Batas Jam Masuk"
      description="Konfigurasi waktu operasional absensi masuk untuk seluruh guru dan staff sekolah."
    />

    <div class="grid gap-6 md:grid-cols-3">
      <!-- Pengaturan Form Card -->
      <Card
        v-motion
        :initial="glassSlide.initial"
        :visible-once="glassSlide.visible"
        class="md:col-span-2 overflow-hidden relative"
      >
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/50 to-white dark:from-indigo-950/20 dark:to-transparent pointer-events-none"></div>
        
        <CardHeader class="relative z-10">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-primary/10 text-primary rounded-xl">
              <Clock class="size-5" />
            </div>
            <div>
              <CardTitle class="text-lg">Konfigurasi Batas Waktu Terlambat</CardTitle>
              <CardDescription>Aturan ini berlaku secara global untuk seluruh unit kerja/role staff.</CardDescription>
            </div>
          </div>
        </CardHeader>
        
        <CardContent class="relative z-10 space-y-6 pt-4">
          <div class="space-y-2">
            <Label for="time-limit" class="text-sm font-semibold text-foreground">Batas Waktu Masuk (Jam Terlambat)</Label>
            <div class="flex items-center gap-4 max-w-sm">
              <Input 
                id="time-limit"
                type="time" 
                v-model="attendanceLateThreshold" 
                :disabled="isPageLoading || isSubmitting"
                class="text-lg font-mono tracking-wider h-12"
              />
              <span class="text-sm text-muted-foreground">WIB</span>
            </div>
            <p class="text-xs text-muted-foreground mt-1">
              * Staff yang melakukan *Clock In* melewati jam ini akan otomatis tercatat berstatus <strong class="text-destructive font-semibold">Terlambat</strong>.
            </p>
          </div>

          <FormFooter
            :loading="isSubmitting"
            :disabled="isPageLoading"
            is-edit
            label="Simpan Pengaturan"
            entity-name="Pengaturan Absensi"
            :before-confirm="validateForm"
            @save="handleSave"
          />
        </CardContent>
      </Card>

      <!-- Informasi Panduan Card -->
      <Card class="md:col-span-1 border-dashed bg-muted/30">
        <CardHeader>
          <div class="flex items-center gap-2 text-primary">
            <HelpCircle class="size-5" />
            <CardTitle class="text-base">Panduan Pengaturan</CardTitle>
          </div>
        </CardHeader>
        <CardContent class="text-xs text-muted-foreground space-y-3 leading-relaxed">
          <p>
            1. Batas waktu ini digunakan oleh sistem absensi kamera/wajah staff untuk menentukan kualifikasi kedatangan kerja.
          </p>
          <p>
            2. Anda dapat mengubah pengaturan ini kapan saja, namun perubahan waktu hanya berlaku bagi absensi yang tercatat <strong>setelah</strong> perubahan disimpan.
          </p>
          <p>
            3. Hanya role <strong>Admin Sekolah</strong> yang memiliki otorisasi penuh untuk mengakses dan merubah batas jam absensi masuk ini.
          </p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
