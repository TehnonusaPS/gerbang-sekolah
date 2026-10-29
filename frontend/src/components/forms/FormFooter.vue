<script setup>
import { Save } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

defineProps({
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
  }
})

const emit = defineEmits(['submit'])
</script>

<template>
  <div class="flex items-center justify-end pt-6 border-t border-border dark:border-zinc-800">
    <Button
      type="button"
      :disabled="loading || disabled"
      class="gap-1.5 px-6 shadow-sm w-full sm:w-auto"
      @click="emit('submit')"
    >
      <span
        v-if="loading"
        class="h-4 w-4 animate-spin border-2 border-current border-t-transparent rounded-full"
      />
      <Save v-else class="h-4 w-4" />
      {{ loading ? loadingLabel : submitLabel }}
    </Button>
  </div>
</template>
