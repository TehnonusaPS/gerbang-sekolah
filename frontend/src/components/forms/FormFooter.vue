<script setup>
import { ref, computed } from 'vue'
import { Save, HelpCircle, CheckCircle2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
  DialogFooter
} from '@/components/ui/dialog'
import { useApiLoading } from '@/composables/useApiLoading'

const props = defineProps({
  loading: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  submitLabel: {
    type: String,
    default: 'Simpan'
  },
  loadingLabel: {
    type: String,
    default: 'Menyimpan...'
  },
  confirm: {
    type: Boolean,
    default: true
  },
  confirmTitle: {
    type: String,
    default: 'Konfirmasi Simpan Data'
  },
  confirmDescription: {
    type: String,
    default: 'Mohon periksa kembali isian formulir sebelum melanjutkan.'
  },
  confirmButtonLabel: {
    type: String,
    default: 'Ya, Simpan Sekarang'
  },
  entityName: {
    type: String,
    default: ''
  },
  summaryItems: {
    type: Array,
    default: () => []
  },
  beforeConfirm: {
    type: Function,
    default: null
  }
})

const emit = defineEmits(['submit'])

const { isFetching, isMutating } = useApiLoading()
const isConfirmOpen = ref(false)
const isSubmitLocked = ref(false)

const isBusy = computed(() => Boolean(props.loading || isMutating.value || isSubmitLocked.value))
const isButtonDisabled = computed(() => Boolean(props.disabled || isBusy.value || isFetching.value))

const visibleSummaryItems = computed(() =>
  (props.summaryItems || []).filter(item => item && item.value !== null && item.value !== undefined && String(item.value).trim() !== '')
)

const handleClickSave = async () => {
  if (isButtonDisabled.value) return

  if (!props.confirm) {
    isSubmitLocked.value = true
    setTimeout(() => {
      isSubmitLocked.value = false
    }, 800)
    emit('submit')
    return
  }

  if (props.beforeConfirm) {
    const canProceed = await props.beforeConfirm()
    if (canProceed === false) return
  }

  isConfirmOpen.value = true
}

const handleConfirmSubmit = () => {
  if (isBusy.value) return
  isSubmitLocked.value = true
  setTimeout(() => {
    isSubmitLocked.value = false
  }, 800)
  isConfirmOpen.value = false
  emit('submit')
}
</script>

<template>
  <div class="flex items-center justify-end pt-6 border-t border-border dark:border-zinc-800">
    <Button
      type="button"
      size="lg"
      :disabled="isButtonDisabled"
      class="gap-2 px-8 h-11 text-sm font-semibold shadow-sm w-full sm:w-auto transition-all"
      @click="handleClickSave"
    >
      <span
        v-if="isBusy"
        class="h-4 w-4 animate-spin border-2 border-current border-t-transparent rounded-full"
      />
      <Save v-else class="h-4.5 w-4.5" />
      {{ isBusy ? loadingLabel : submitLabel }}
    </Button>

    <Dialog :open="isConfirmOpen" @update:open="val => (isConfirmOpen = val)">
      <DialogContent class="sm:max-w-md bg-card dark:bg-zinc-900 border border-border dark:border-zinc-800">
        <DialogHeader>
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0">
              <HelpCircle class="h-5 w-5" />
            </div>
            <div class="text-left">
              <DialogTitle class="text-base font-semibold text-foreground dark:text-zinc-100">
                {{ confirmTitle }}
              </DialogTitle>
              <DialogDescription class="text-xs text-muted-foreground dark:text-zinc-400 mt-0.5">
                {{ confirmDescription }}
              </DialogDescription>
            </div>
          </div>
        </DialogHeader>

        <div class="py-3 text-sm text-foreground dark:text-zinc-300 space-y-2 text-left">
          <slot name="confirm-body">
            <p v-if="entityName && entityName.trim()">
              Apakah Anda yakin data <strong>{{ entityName.trim() }}</strong> sudah sesuai?
            </p>
            <p v-else>
              Apakah Anda yakin seluruh data yang dimasukkan sudah sesuai?
            </p>

            <div
              v-if="visibleSummaryItems.length > 0"
              class="p-3 rounded-lg bg-accent/40 dark:bg-zinc-800/50 border border-border/50 dark:border-zinc-800 text-xs space-y-1"
            >
              <div v-for="(item, idx) in visibleSummaryItems" :key="idx">
                <span class="text-muted-foreground">{{ item.label }}:</span>
                <span class="font-semibold text-foreground dark:text-zinc-100 ml-1">{{ item.value }}</span>
              </div>
            </div>
          </slot>
        </div>

        <DialogFooter class="gap-2 sm:gap-2">
          <Button
            variant="outline"
            type="button"
            :disabled="isBusy"
            @click="isConfirmOpen = false"
          >
            Kembali Periksa
          </Button>
          <Button
            type="button"
            :disabled="isBusy"
            class="gap-1.5"
            @click="handleConfirmSubmit"
          >
            <CheckCircle2 class="h-4 w-4" />
            {{ isBusy ? 'Memproses...' : confirmButtonLabel }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
