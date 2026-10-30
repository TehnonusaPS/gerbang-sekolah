<script setup>
/**
 * TimeSlotConfigSheet.vue
 *
 * Modal sheet untuk mengonfigurasi jam pelajaran sekolah (KBM & Istirahat).
 * Dilengkapi Engine Validasi Terpusat (Format 24h, Jam Mulai < Selesai, dan Urutan Sesi).
 */
import { ref, computed, watch } from 'vue'
import { toast } from 'vue-sonner'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import FormFooter from '@/components/forms/FormFooter.vue'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'
import { Plus, Trash2, Coffee, Clock, AlertCircle } from 'lucide-vue-next'
import { getTimeSlots, saveTimeSlotsBulk } from '@/services/scheduleService'

// --- Props & Emits ---
const props = defineProps({
  open: { type: Boolean, default: false }
})

const emit = defineEmits(['update:open', 'saved'])

// --- State ---
const slots = ref([])
const isLoading = ref(false)

// --- Data Fetching ---
async function loadTimeSlots() {
  isLoading.value = true
  try {
    const res = await getTimeSlots()
    slots.value = (res.data || []).map(slot => ({
      id: slot.id,
      slot_number: slot.slot_number,
      start_time: slot.start_time ? slot.start_time.substring(0, 5) : '07:30',
      end_time: slot.end_time ? slot.end_time.substring(0, 5) : '08:10',
      is_break: Boolean(slot.is_break),
      label: slot.label || ''
    }))
  } catch (err) {
    console.error('Error loading time slots:', err)
    toast.error('Gagal memuat jam pelajaran.')
  } finally {
    isLoading.value = false
  }
}

watch(() => props.open, (isOpen) => {
  if (isOpen) loadTimeSlots()
})

// --- Slot Handlers ---
function addSlot() {
  const nextNum = slots.value.length > 0
    ? Math.max(...slots.value.map(s => s.slot_number)) + 1
    : 1

  slots.value.push({
    slot_number: nextNum,
    start_time: '07:30',
    end_time: '08:10',
    is_break: false,
    label: `Jam ${nextNum}`
  })
}

function removeSlot(index) {
  slots.value.splice(index, 1)
  slots.value.forEach((slot, idx) => {
    slot.slot_number = idx + 1
    if (!slot.is_break && (!slot.label || slot.label.startsWith('Jam '))) {
      slot.label = `Jam ${idx + 1}`
    }
  })
}

function toggleBreak(slot) {
  slot.is_break = !slot.is_break
  if (slot.is_break && (!slot.label || slot.label.startsWith('Jam '))) {
    slot.label = 'Istirahat'
  } else if (!slot.is_break && slot.label === 'Istirahat') {
    slot.label = `Jam ${slot.slot_number}`
  }
}

// --- Time Helpers & Validation Engine ---
function isValid24h(t) {
  if (!t) return false
  const str = t.trim()
  if (/^([0-1]?[0-9]|2[0-3]):([0-5][0-9])$/.test(str)) return true
  if (/^\d{4}$/.test(str)) {
    const h = parseInt(str.substring(0, 2), 10)
    const m = parseInt(str.substring(2, 4), 10)
    return h >= 0 && h <= 23 && m >= 0 && m <= 59
  }
  return false
}

function toMinutes(t) {
  if (!isValid24h(t)) return -1
  let clean = t.trim()
  if (/^\d{4}$/.test(clean)) {
    clean = `${clean.substring(0, 2)}:${clean.substring(2, 4)}`
  }
  const parts = clean.split(':')
  return parseInt(parts[0] || '0', 10) * 60 + parseInt(parts[1] || '0', 10)
}

function formatTo24hString(t) {
  if (!t) return '00:00:00'
  let clean = t.trim()
  if (/^\d{4}$/.test(clean)) {
    clean = `${clean.substring(0, 2)}:${clean.substring(2, 4)}`
  }
  const parts = clean.split(':')
  const h = (parts[0] || '00').padStart(2, '0')
  const m = (parts[1] || '00').padStart(2, '0')
  return `${h}:${m}:00`
}

/**
 * Engine Validasi Terpusat (Clean & Non-Spaghetti)
 * Memeriksa:
 * 1. Format 24 Jam (00:00 - 23:59)
 * 2. Jam Mulai < Jam Selesai
 * 3. Urutan Kronologis (Jam N start_time >= Jam N-1 end_time)
 */
const slotValidationList = computed(() => {
  const list = slots.value
  return list.map((slot, idx) => {
    const res = {
      startError: false,
      endError: false,
      message: ''
    }

    // 1. Validasi Format 24 Jam
    if (!isValid24h(slot.start_time)) {
      res.startError = true
      res.message = `Format jam mulai (${slot.start_time || 'kosong'}) tidak valid (00:00 - 23:59).`
    }
    if (!isValid24h(slot.end_time)) {
      res.endError = true
      if (!res.message) res.message = `Format jam selesai (${slot.end_time || 'kosong'}) tidak valid (00:00 - 23:59).`
    }
    if (res.startError || res.endError) return res

    const startMin = toMinutes(slot.start_time)
    const endMin = toMinutes(slot.end_time)

    // 2. Validasi Jam Mulai < Jam Selesai
    if (startMin >= endMin) {
      res.startError = true
      res.endError = true
      res.message = `Jam mulai (${slot.start_time}) harus lebih awal dari jam selesai (${slot.end_time}).`
      return res
    }

    // 3. Validasi Urutan Kronologis (Slot N tidak boleh lebih awal dari Slot N-1)
    if (idx > 0) {
      const prevSlot = list[idx - 1]
      const prevEndMin = toMinutes(prevSlot.end_time)
      if (prevEndMin > 0 && startMin < prevEndMin) {
        res.startError = true
        res.message = `Jam ${slot.slot_number} (${slot.start_time}) tidak boleh lebih awal dari jam selesai Jam ${prevSlot.slot_number} (${prevSlot.end_time}).`
      }
    }

    return res
  })
})

const isSaving = ref(false)

function validateSlots() {
  for (let i = 0; i < slots.value.length; i++) {
    const v = slotValidationList.value[i]
    if (v && v.message) {
      const slot = slots.value[i]
      toast.error('Urutan Jam Pelajaran Salah', {
        description: `[${slot.label || 'Jam ' + slot.slot_number}]: ${v.message}`
      })
      return false
    }
  }
  return true
}

async function handleSave() {
  slots.value.sort((a, b) => a.slot_number - b.slot_number)
  isSaving.value = true

  try {
    const payload = slots.value.map(slot => ({
      slot_number: slot.slot_number,
      start_time: formatTo24hString(slot.start_time),
      end_time: formatTo24hString(slot.end_time),
      is_break: Boolean(slot.is_break),
      label: slot.label || (slot.is_break ? 'Istirahat' : `Jam ${slot.slot_number}`)
    }))

    await saveTimeSlotsBulk(payload)
    toast.success('Berhasil Disimpan', { description: 'Konfigurasi jam pelajaran & istirahat telah diperbarui.' })
    emit('update:open', false)
    emit('saved')
  } catch (err) {
    console.error('Error saving time slots:', err)
    toast.error('Gagal menyimpan jam pelajaran.')
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <Sheet :open="open" @update:open="emit('update:open', $event)">
    <SheetContent class="sm:max-w-[500px] flex flex-col h-full text-left p-6">
      
      <!-- Modal Header -->
      <SheetHeader class="border-b border-border/60 pb-3.5 text-left">
        <div class="flex items-center gap-2">
          <div class="p-2 rounded-xl bg-primary/10 text-primary">
            <Clock class="size-4" />
          </div>
          <div>
            <SheetTitle class="text-base font-bold text-foreground">
              Konfigurasi Jam Pelajaran
            </SheetTitle>
            <SheetDescription class="text-xs text-muted-foreground mt-0.5">
              Atur durasi KBM, label jam, dan istirahat (Format 24 Jam & Urutan Kronologis).
            </SheetDescription>
          </div>
        </div>
      </SheetHeader>

      <!-- Scrollable Slot Cards List -->
      <div class="flex-1 overflow-y-auto py-4 space-y-3.5 no-scrollbar">
        
        <!-- Loading State -->
        <div v-if="isLoading" class="text-center text-sm py-12 text-muted-foreground font-medium">
          Memuat jam pelajaran...
        </div>

        <!-- Empty State -->
        <div v-else-if="slots.length === 0" class="text-center py-10 space-y-3 border border-dashed rounded-2xl">
          <p class="text-xs text-muted-foreground font-medium">Belum ada jam pelajaran yang diatur.</p>
          <Button type="button" size="sm" variant="outline" @click="addSlot" class="text-xs font-bold rounded-xl">
            <Plus class="size-3.5 mr-1" /> Tambah Jam Pertama
          </Button>
        </div>

        <!-- Cards List -->
        <div v-else class="space-y-3.5">
          <div
            v-for="(slot, idx) in slots"
            :key="idx"
            class="group relative rounded-2xl border transition-all duration-200 p-3.5 space-y-3"
            :class="[
              slot.is_break
                ? 'bg-amber-500/5 dark:bg-amber-950/20 border-amber-500/30 hover:border-amber-500/50'
                : 'bg-card border-border/80 hover:border-primary/40 shadow-2xs'
            ]"
          >
            <!-- Card Header: Slot Number/Icon, Editable Label, Type Switch, Delete -->
            <div class="flex items-center gap-2.5">
              
              <!-- Badge Slot Number or Break Icon -->
              <div
                class="size-8 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 border select-none transition-colors"
                :class="[
                  slot.is_break
                    ? 'bg-amber-500/20 text-amber-700 dark:text-amber-300 border-amber-500/40'
                    : 'bg-primary/10 text-primary border-primary/20'
                ]"
              >
                <Coffee v-if="slot.is_break" class="size-4" />
                <span v-else>{{ slot.slot_number }}</span>
              </div>

              <!-- Input Label Nama Jam -->
              <div class="flex-1 min-w-0">
                <Input
                  type="text"
                  v-model="slot.label"
                  :placeholder="slot.is_break ? 'Istirahat / Sholat' : `Jam ${slot.slot_number}`"
                  class="h-8 text-xs font-bold text-foreground bg-background/80 border-border/60 focus:border-primary rounded-xl px-2.5 w-full"
                />
              </div>

              <!-- Switch Istirahat Toggle Pill -->
              <button
                type="button"
                @click="toggleBreak(slot)"
                class="flex items-center gap-1.5 cursor-pointer select-none px-2.5 py-1 rounded-xl border transition-all shrink-0"
                :class="[
                  slot.is_break
                    ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/40'
                    : 'bg-muted/50 text-muted-foreground border-border/60 hover:bg-muted'
                ]"
                title="Toggle Jenis Slot (KBM / Istirahat)"
              >
                <span class="text-[10px] font-extrabold uppercase tracking-wider">
                  {{ slot.is_break ? 'Istirahat' : 'KBM' }}
                </span>
                
                <!-- Smooth Sliding Knob -->
                <div
                  class="relative inline-flex h-4 w-7 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200"
                  :class="slot.is_break ? 'bg-amber-500' : 'bg-zinc-300 dark:bg-zinc-700'"
                >
                  <span
                    class="inline-block h-3 w-3 transform rounded-full bg-white shadow-2xs transition duration-200"
                    :class="slot.is_break ? 'translate-x-3' : 'translate-x-0'"
                  />
                </div>
              </button>

              <!-- Delete Button -->
              <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 text-muted-foreground hover:text-rose-500 hover:bg-rose-500/10 rounded-xl cursor-pointer shrink-0"
                @click="removeSlot(idx)"
                title="Hapus Jam Pelajaran"
              >
                <Trash2 class="size-3.5" />
              </Button>
            </div>

            <!-- Card Body: Time Range Pickers (Format 24 Jam & Range/Sequence Check) -->
            <div
              class="grid grid-cols-2 gap-2.5 p-2.5 rounded-xl border transition-colors"
              :class="[
                slot.is_break
                  ? 'bg-amber-500/10 border-amber-500/20'
                  : 'bg-muted/40 border-border/40'
              ]"
            >
              <!-- Jam Mulai (24 Jam) -->
              <div
                class="flex items-center gap-2 bg-background dark:bg-zinc-900 px-3 py-1.5 rounded-xl border transition-all"
                :class="[
                  slotValidationList[idx]?.startError
                    ? 'border-rose-500/80 bg-rose-500/10 dark:bg-rose-950/30 text-rose-500 focus-within:border-rose-500'
                    : 'border-border/60 focus-within:border-primary'
                ]"
              >
                <Clock class="size-4 shrink-0" :class="slotValidationList[idx]?.startError ? 'text-rose-500' : 'text-muted-foreground'" />
                <div class="flex-1 text-left min-w-0">
                  <div class="flex items-center justify-between">
                    <label class="block text-[8px] font-bold uppercase tracking-widest leading-none mb-0.5" :class="slotValidationList[idx]?.startError ? 'text-rose-500 font-extrabold' : 'text-muted-foreground'">
                      Jam Mulai
                    </label>
                    <AlertCircle v-if="slotValidationList[idx]?.startError" class="size-3 text-rose-500 shrink-0" />
                  </div>
                  <input
                    type="text"
                    v-model="slot.start_time"
                    placeholder="07:30"
                    maxlength="5"
                    class="w-full bg-transparent border-none p-0 text-xs font-extrabold focus:outline-none focus:ring-0 placeholder:text-muted-foreground/30 font-mono"
                    :class="slotValidationList[idx]?.startError ? 'text-rose-500 font-extrabold' : 'text-foreground'"
                  />
                </div>
              </div>

              <!-- Jam Selesai (24 Jam) -->
              <div
                class="flex items-center gap-2 bg-background dark:bg-zinc-900 px-3 py-1.5 rounded-xl border transition-all"
                :class="[
                  slotValidationList[idx]?.endError
                    ? 'border-rose-500/80 bg-rose-500/10 dark:bg-rose-950/30 text-rose-500 focus-within:border-rose-500'
                    : 'border-border/60 focus-within:border-primary'
                ]"
              >
                <Clock class="size-4 shrink-0" :class="slotValidationList[idx]?.endError ? 'text-rose-500' : 'text-muted-foreground'" />
                <div class="flex-1 text-left min-w-0">
                  <div class="flex items-center justify-between">
                    <label class="block text-[8px] font-bold uppercase tracking-widest leading-none mb-0.5" :class="slotValidationList[idx]?.endError ? 'text-rose-500 font-extrabold' : 'text-muted-foreground'">
                      Jam Selesai
                    </label>
                    <AlertCircle v-if="slotValidationList[idx]?.endError" class="size-3 text-rose-500 shrink-0" />
                  </div>
                  <input
                    type="text"
                    v-model="slot.end_time"
                    placeholder="08:10"
                    maxlength="5"
                    class="w-full bg-transparent border-none p-0 text-xs font-extrabold focus:outline-none focus:ring-0 placeholder:text-muted-foreground/30 font-mono"
                    :class="slotValidationList[idx]?.endError ? 'text-rose-500 font-extrabold' : 'text-foreground'"
                  />
                </div>
              </div>

              <!-- Centralized Error Banner (Range / Sequence / Format) -->
              <div
                v-if="slotValidationList[idx]?.message"
                class="col-span-2 text-[9px] font-bold text-rose-600 dark:text-rose-400 bg-rose-500/10 border border-rose-500/30 rounded-xl p-2 flex items-center gap-2 mt-0.5"
              >
                <AlertCircle class="size-3.5 text-rose-500 shrink-0" />
                <span>{{ slotValidationList[idx].message }}</span>
              </div>
            </div>

          </div>

          <!-- Add Button -->
          <Button
            type="button"
            variant="outline"
            class="w-full h-10 rounded-2xl border-dashed border-2 flex items-center justify-center gap-1.5 font-bold text-xs cursor-pointer hover:bg-primary/5 hover:border-primary/50 transition-all"
            @click="addSlot"
          >
            <Plus class="size-4 text-primary" />
            Tambah Jam Pelajaran Baru
          </Button>
        </div>
      </div>

      <!-- Footer Action Buttons -->
      <FormFooter
        :loading="isSaving"
        is-edit
        label="Simpan Jam Pelajaran"
        entity-name="Konfigurasi Jam Pelajaran"
        :before-confirm="validateSlots"
        @save="handleSave"
      />

    </SheetContent>
  </Sheet>
</template>
