<script setup>
import { ref, watch, computed } from 'vue'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription
} from '@/components/ui/sheet'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Button } from '@/components/ui/button'
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger
} from '@/components/ui/accordion'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'
import { Camera, UploadCloud, Eye, EyeOff, HelpCircle, CheckCircle2 } from 'lucide-vue-next'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  item: {
    type: Object,
    default: null
  },
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    default: ''
  },
  sections: {
    type: Array,
    default: () => []
  },
  avatarKey: {
    type: [String, Boolean],
    default: 'foto'
  },
  titleKey: {
    type: String,
    default: 'nama'
  },
  allowAvatarEdit: {
    type: Boolean,
    default: true
  },
  submitLabel: {
    type: String,
    default: 'Simpan'
  },
  cancelLabel: {
    type: String,
    default: 'Batal'
  },
  loading: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  confirm: {
    type: Boolean,
    default: true
  },
  confirmTitle: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:open', 'submit', 'cancel', 'change'])

const isOpen = computed({
  get: () => props.open,
  set: val => emit('update:open', val)
})

const localForm = ref({})
const fileInput = ref(null)
const passwordVisible = ref({})

watch(
  () => props.open,
  newOpen => {
    if (newOpen) {
      const form = {}
      props.sections.forEach(section => {
        section.fields.forEach(field => {
          if (field.key) {
            form[field.key] = props.item ? (props.item[field.key] ?? '') : ''
            if (field.type === 'file' || field.file) {
              form[field.key + '_fileName'] = props.item
                ? (props.item[field.key + '_fileName'] ?? props.item[field.key] ?? '')
                : ''
            }
          }
        })
      })
      if (props.avatarKey && typeof props.avatarKey === 'string') {
        form[props.avatarKey] = props.item ? (props.item[props.avatarKey] ?? '') : ''
      }
      if (props.item && props.item.id !== undefined) {
        form.id = props.item.id
      }
      localForm.value = form
    }
  },
  { immediate: true }
)

// Sync localForm changes back to parent
watch(
  localForm,
  newVal => {
    emit('change', { ...newVal })
  },
  { deep: true }
)

// Sync prop item updates to localForm (e.g. parent dynamically updating fields like grades or file names)
watch(
  () => props.item,
  newItem => {
    if (newItem && props.open) {
      props.sections.forEach(section => {
        section.fields.forEach(field => {
          if (field.key && newItem[field.key] !== undefined) {
            localForm.value[field.key] = newItem[field.key]
            if (field.type === 'file' || field.file) {
              const fileNameKey = field.key + '_fileName'
              if (newItem[fileNameKey] !== undefined) {
                localForm.value[fileNameKey] = newItem[fileNameKey]
              }
            }
          }
        })
      })
      if (
        props.avatarKey &&
        typeof props.avatarKey === 'string' &&
        newItem[props.avatarKey] !== undefined
      ) {
        localForm.value[props.avatarKey] = newItem[props.avatarKey]
      }
    }
  },
  { deep: true }
)

const resolvedAvatarFallback = computed(() => {
  const nameVal = localForm.value[props.titleKey] || ''
  if (!nameVal) return '?'
  const parts = String(nameVal).trim().split(/\s+/)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const showAvatar = computed(() => {
  if (props.avatarKey === false || props.avatarKey === 'false') return false
  return !!props.avatarKey
})

const triggerFileInput = () => {
  if (props.allowAvatarEdit && fileInput.value) {
    fileInput.value.click()
  }
}

const handleFileChange = event => {
  const file = event.target.files?.[0]
  if (file) {
    const previewUrl = URL.createObjectURL(file)
    localForm.value[props.avatarKey] = previewUrl
    localForm.value._avatarFile = file
  }
}

const handleCancel = () => {
  emit('cancel')
  isOpen.value = false
}

const isConfirmOpen = ref(false)

const handleSubmit = () => {
  if (!props.confirm) {
    emit('submit', { ...localForm.value })
    return
  }
  isConfirmOpen.value = true
}

const handleConfirmSubmit = () => {
  isConfirmOpen.value = false
  emit('submit', { ...localForm.value })
}
</script>

<template>
  <Sheet v-model:open="isOpen">
    <SheetContent
      :show-close-button="false"
      class="gap-2 h-full flex flex-col"
    >
      <slot name="header">
        <SheetHeader class="border-b border-border pb-3 shrink-0">
          <div class="flex items-center gap-4">
            <!-- Dynamic Avatar Uploader -->
            <div
              v-if="showAvatar"
              class="relative group cursor-pointer shrink-0 rounded-full select-none"
              @click="triggerFileInput"
            >
              <Avatar class="size-16 border-2 border-border/80 shrink-0">
                <AvatarImage
                  v-if="localForm[avatarKey]"
                  :src="localForm[avatarKey]"
                  :alt="localForm[titleKey] || 'Avatar'"
                />
                <AvatarFallback class="text-lg font-bold">
                  {{ resolvedAvatarFallback }}
                </AvatarFallback>
              </Avatar>
              <!-- Hover Edit Camera Overlay -->
              <div
                v-if="allowAvatarEdit"
                class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200"
              >
                <Camera class="size-5 text-white" />
              </div>
              <input
                v-if="allowAvatarEdit"
                ref="fileInput"
                type="file"
                accept="image/*"
                class="hidden"
                @change="handleFileChange"
              />
            </div>

            <div class="flex-1 min-w-0 space-y-1 text-left">
              <SheetTitle class="text-base font-bold text-foreground truncate">
                {{ title }}
              </SheetTitle>
              <SheetDescription
                v-if="description"
                class="text-xs text-muted-foreground"
              >
                {{ description }}
              </SheetDescription>
            </div>
          </div>
        </SheetHeader>
      </slot>

      <!-- Scrollable Form Body -->
      <div class="overflow-y-auto pr-1 flex-1 no-scrollbar space-y-6 py-2">
        <slot :form="localForm">
          <Accordion
            type="multiple"
            class="w-full"
            :default-value="sections.map(s => s.id)"
          >
            <AccordionItem
              v-for="section in sections"
              :key="section.id"
              :value="section.id"
            >
              <AccordionTrigger class="text-sm font-semibold">
                {{ section.title }}
              </AccordionTrigger>
              <AccordionContent class="space-y-4 pt-3">
                <slot
                  :name="`section-${section.id}`"
                  :section="section"
                  :form="localForm"
                >
                  <div
                    v-for="field in section.fields"
                    :key="field.label"
                    class="grid gap-1.5"
                  >
                    <slot
                      :name="`field-${field.key || field.label.toLowerCase().replace(/\s+/g, '-')}`"
                      :field="field"
                      :form="localForm"
                    >
                      <div class="flex items-center gap-1.5">
                        <component
                          v-if="field.icon"
                          :is="field.icon"
                          class="size-3.5 text-muted-foreground"
                        />
                        <label class="text-xs font-semibold text-muted-foreground">
                          {{ field.label }}
                        </label>
                      </div>
                      <Textarea
                        v-if="field.textarea || field.type === 'textarea'"
                        v-model="localForm[field.key]"
                        :disabled="disabled || field.disabled"
                        class="min-h-16 bg-background border-input focus-visible:ring-1 focus-visible:ring-ring text-sm"
                        :placeholder="field.placeholder || 'Masukkan ' + field.label"
                      />
                      <Select
                        v-else-if="field.type === 'select' || field.select || field.options"
                        v-model="localForm[field.key]"
                        :disabled="disabled || field.disabled"
                      >
                        <SelectTrigger
                          class="h-8 bg-background border-input focus-visible:ring-1 focus-visible:ring-ring text-sm w-full"
                        >
                          <SelectValue :placeholder="field.placeholder || 'Pilih ' + field.label" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem
                            v-for="opt in field.options"
                            :key="opt.value !== undefined ? opt.value : opt"
                            :value="String(opt.value !== undefined ? opt.value : opt)"
                          >
                            {{ opt.label || opt }}
                          </SelectItem>
                        </SelectContent>
                      </Select>
                      <div
                        v-else-if="field.type === 'file' || field.file"
                        class="border-2 border-dashed border-border/80 rounded-xl p-4 flex flex-col items-center justify-center gap-2 cursor-pointer hover:bg-muted/30 transition-all"
                        :class="disabled || field.disabled ? 'pointer-events-none opacity-85' : ''"
                        @click="!(disabled || field.disabled) && $refs['fileInput_' + field.key]?.[0]?.click()"
                      >
                        <UploadCloud class="size-6 text-muted-foreground animate-bounce" />
                        <span
                          class="text-xs font-semibold text-foreground text-center line-clamp-2 px-2"
                        >
                          {{
                            localForm[field.key + '_fileName'] ||
                            localForm[field.key]?.name ||
                            localForm[field.key] ||
                            field.placeholder ||
                            'Pilih file (PDF, DOCX, PPTX atau Gambar)'
                          }}
                        </span>
                        <input
                          v-if="!(disabled || field.disabled)"
                          :ref="'fileInput_' + field.key"
                          type="file"
                          :accept="field.accept || '*/*'"
                          class="hidden"
                          @change="
                            e => {
                              const file = e.target.files?.[0]
                              if (file) {
                                localForm[field.key] = file
                                localForm[field.key + '_fileName'] = file.name
                              }
                            }
                          "
                        />
                      </div>
                      <div
                        v-else-if="field.type === 'password'"
                        class="relative"
                      >
                        <Input
                          v-model="localForm[field.key]"
                          :type="passwordVisible[field.key] ? 'text' : 'password'"
                          :disabled="disabled || field.disabled"
                          class="h-8 bg-background border-input focus-visible:ring-1 focus-visible:ring-ring text-sm pr-8"
                          :placeholder="field.placeholder || ('Masukkan ' + field.label)"
                        />
                        <button
                          type="button"
                          class="absolute inset-y-0 right-2 flex items-center text-muted-foreground hover:text-foreground transition-colors"
                          @click="passwordVisible[field.key] = !passwordVisible[field.key]"
                        >
                          <EyeOff v-if="passwordVisible[field.key]" class="size-3.5" />
                          <Eye v-else class="size-3.5" />
                        </button>
                      </div>
                      <Input
                        v-else
                        v-model="localForm[field.key]"
                        :type="field.type || 'text'"
                        :disabled="disabled || field.disabled"
                        class="h-8 bg-background border-input focus-visible:ring-1 focus-visible:ring-ring text-sm"
                        :placeholder="field.placeholder || 'Masukkan ' + field.label"
                      />
                    </slot>
                  </div>
                </slot>
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </slot>
      </div>

      <!-- Footer Action Buttons -->
      <div
        class="border-t border-border/80 px-6 py-4 flex items-center justify-end gap-3 shrink-0 bg-card/40 dark:bg-card/25 backdrop-blur-md -mx-6 -mb-6 mt-auto"
      >
        <slot
          name="actions"
          :form="localForm"
          :submit="handleSubmit"
          :cancel="handleCancel"
        >
          <Button
            size="sm"
            :loading="loading"
            @click="handleSubmit"
          >
            {{ submitLabel }}
          </Button>
        </slot>
      </div>
    </SheetContent>
  </Sheet>

  <Dialog :open="isConfirmOpen" @update:open="val => (isConfirmOpen = val)">
    <DialogContent class="sm:max-w-md bg-card dark:bg-zinc-900 border border-border dark:border-zinc-800">
      <DialogHeader>
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
            <HelpCircle class="h-5 w-5" />
          </div>
          <div class="text-left">
            <DialogTitle class="text-base font-semibold text-foreground dark:text-zinc-100">
              {{ confirmTitle || `Konfirmasi ${title}` }}
            </DialogTitle>
            <DialogDescription class="text-xs text-muted-foreground dark:text-zinc-400 mt-0.5">
              Mohon periksa kembali isian formulir sebelum melanjutkan.
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <div class="py-3 text-sm text-foreground dark:text-zinc-300 space-y-2 text-left">
        <p v-if="localForm[titleKey] && String(localForm[titleKey]).trim()">
          Apakah Anda yakin data <strong>{{ String(localForm[titleKey]).trim() }}</strong> sudah sesuai?
        </p>
        <p v-else>
          Apakah Anda yakin seluruh data yang dimasukkan sudah sesuai?
        </p>
      </div>

      <DialogFooter class="gap-2 sm:gap-2">
        <Button
          variant="outline"
          type="button"
          :disabled="loading"
          @click="isConfirmOpen = false"
        >
          Kembali Periksa
        </Button>
        <Button
          type="button"
          :disabled="loading"
          class="gap-1.5"
          @click="handleConfirmSubmit"
        >
          <CheckCircle2 class="h-4 w-4" />
          {{ loading ? 'Memproses...' : 'Ya, Simpan Sekarang' }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
