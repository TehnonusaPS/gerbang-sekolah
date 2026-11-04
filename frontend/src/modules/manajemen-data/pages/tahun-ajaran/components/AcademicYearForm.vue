<script setup>
import { Calendar, Layers } from 'lucide-vue-next'
import { Input } from '@/components/ui/input'
import { Checkbox } from '@/components/ui/checkbox'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

defineProps({
  form: {
    type: Object,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  isEdit: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <div class="space-y-5 text-left">
    <!-- Tahun Ajaran Input -->
    <div class="space-y-1.5 w-full">
      <label class="text-xs font-bold text-foreground flex items-center gap-1.5">
        <Calendar class="size-3.5 text-primary" />
        Tahun Ajaran <span class="text-rose-500">*</span>
      </label>
      <Input
        v-model="form.tahun"
        placeholder="Contoh: 2025/2026"
        class="h-10 rounded-xl bg-background border-border/80 text-xs font-semibold px-3.5"
        :class="errors.tahun ? 'border-rose-500' : ''"
      />
      <p v-if="errors.tahun" class="text-[10px] text-rose-500">{{ errors.tahun }}</p>
    </div>

    <!-- Semester Ganjil Section -->
    <div class="p-4 rounded-2xl border border-border/80 bg-muted/20 space-y-3">
      <span class="text-xs font-bold text-foreground flex items-center gap-2">
        <span class="h-2.5 w-2.5 rounded-full bg-primary inline-block"></span>
        Semester Ganjil
      </span>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="space-y-1">
          <label class="text-[11px] font-semibold text-muted-foreground">
            Tanggal Mulai <span class="text-rose-500">*</span>
          </label>
          <Input
            type="date"
            v-model="form.oddTanggalMulai"
            class="h-10 text-xs rounded-xl"
            :class="errors.oddTanggalMulai ? 'border-rose-500' : ''"
          />
          <p v-if="errors.oddTanggalMulai" class="text-[9px] text-rose-500">{{ errors.oddTanggalMulai }}</p>
        </div>
        <div class="space-y-1">
          <label class="text-[11px] font-semibold text-muted-foreground">
            Tanggal Selesai <span class="text-rose-500">*</span>
          </label>
          <Input
            type="date"
            v-model="form.oddTanggalSelesai"
            class="h-10 text-xs rounded-xl"
            :class="errors.oddTanggalSelesai ? 'border-rose-500' : ''"
          />
          <p v-if="errors.oddTanggalSelesai" class="text-[9px] text-rose-500">{{ errors.oddTanggalSelesai }}</p>
        </div>
      </div>
    </div>

    <!-- Semester Genap Section -->
    <div class="p-4 rounded-2xl border border-border/80 bg-muted/20 space-y-3">
      <span class="text-xs font-bold text-foreground flex items-center gap-2">
        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 inline-block"></span>
        Semester Genap
      </span>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="space-y-1">
          <label class="text-[11px] font-semibold text-muted-foreground">
            Tanggal Mulai <span class="text-rose-500">*</span>
          </label>
          <Input
            type="date"
            v-model="form.evenTanggalMulai"
            class="h-10 text-xs rounded-xl"
            :class="errors.evenTanggalMulai ? 'border-rose-500' : ''"
          />
          <p v-if="errors.evenTanggalMulai" class="text-[9px] text-rose-500">{{ errors.evenTanggalMulai }}</p>
        </div>
        <div class="space-y-1">
          <label class="text-[11px] font-semibold text-muted-foreground">
            Tanggal Selesai <span class="text-rose-500">*</span>
          </label>
          <Input
            type="date"
            v-model="form.evenTanggalSelesai"
            class="h-10 text-xs rounded-xl"
            :class="errors.evenTanggalSelesai ? 'border-rose-500' : ''"
          />
          <p v-if="errors.evenTanggalSelesai" class="text-[9px] text-rose-500">{{ errors.evenTanggalSelesai }}</p>
        </div>
      </div>
    </div>

    <!-- Active Semester Selector (Hanya saat Tambah Baru) -->
    <div v-if="!isEdit" class="space-y-1.5 w-full">
      <label class="text-xs font-bold text-foreground">Semester Aktif Pertama <span class="text-rose-500">*</span></label>
      <Select v-model="form.activeSemester">
        <SelectTrigger class="w-full h-10 rounded-xl bg-background border-border/80 text-xs font-semibold px-3.5">
          <SelectValue placeholder="Pilih Semester Aktif..." />
        </SelectTrigger>
        <SelectContent class="rounded-xl">
          <SelectItem value="odd" class="text-xs font-semibold">Semester Ganjil (Aktif)</SelectItem>
          <SelectItem value="even" class="text-xs font-semibold">Semester Genap (Aktif)</SelectItem>
          <SelectItem value="none" class="text-xs font-semibold">Nonaktifkan Keduanya</SelectItem>
        </SelectContent>
      </Select>
    </div>

    <!-- Salin Struktur Kelas (Hanya saat Tambah Baru) -->
    <div v-if="!isEdit" class="p-3.5 rounded-2xl border border-border/80 bg-muted/20 flex items-start gap-3">
      <Checkbox
        id="duplicate-classes"
        :checked="form.duplicateClasses"
        @update:checked="form.duplicateClasses = $event"
        class="mt-0.5"
      />
      <label for="duplicate-classes" class="text-xs cursor-pointer select-none space-y-0.5">
        <span class="font-bold text-foreground flex items-center gap-1.5">
          <Layers class="size-3 text-primary" />
          Salin Struktur Kelas dari Tahun Sebelumnya
        </span>
        <span class="text-[11px] text-muted-foreground block leading-relaxed">
          Otomatis menyalin nama kelas, tingkat, ruangan, dan kapasitas dari tahun ajaran sebelumnya ke tahun ajaran baru ini jika tersedia.
        </span>
      </label>
    </div>
  </div>
</template>

