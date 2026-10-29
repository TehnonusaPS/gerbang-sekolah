<script setup>
import { computed, ref, watch } from 'vue'
import { Calendar as CalendarIcon } from 'lucide-vue-next'
import { Calendar } from '@/components/ui/calendar'
import { RangeCalendar } from '@/components/ui/range-calendar'
import { Label } from '@/components/ui/label'
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover'
import { formatDate } from '@/utils/formatDate'
import { parseDate } from '@internationalized/date'

const props = defineProps({
  modelValue: {
    type: [Object, String],
    default: undefined
  },
  defaultValue: {
    type: [Object, String],
    default: undefined
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Pilih tanggal'
  },
  layout: {
    type: String,
    default: 'month-and-year'
  },
  id: {
    type: String,
    default: 'date'
  },
  mode: {
    type: String,
    default: 'single' // 'single' | 'range'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  error: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'default'
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const open = ref(false)
const calendarPlaceholder = ref(undefined)

// Convert props.modelValue to DateValue for Calendar Root
const internalDateValue = computed(() => {
  const val = props.modelValue ?? props.defaultValue
  if (!val) return undefined

  if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val)) {
    try {
      return parseDate(val)
    } catch (e) {
      return undefined
    }
  }

  return val
})

// Sync calendarPlaceholder with selected modelValue date
function syncPlaceholder() {
  if (internalDateValue.value) {
    if (props.mode === 'range') {
      if (internalDateValue.value.start) {
        calendarPlaceholder.value = internalDateValue.value.start
      }
    } else {
      calendarPlaceholder.value = internalDateValue.value
    }
  }
}

watch(internalDateValue, () => {
  syncPlaceholder()
}, { immediate: true })

watch(open, (isOpen) => {
  if (isOpen) {
    syncPlaceholder()
  }
})

function handleUpdateDate(newVal) {
  if (!newVal) {
    emit('update:modelValue', '')
    emit('change', '')
    return
  }

  if (props.mode === 'range') {
    emit('update:modelValue', newVal)
    emit('change', newVal)
  } else {
    // Return YYYY-MM-DD string format
    const strVal = newVal.toString()
    emit('update:modelValue', strVal)
    emit('change', strVal)
    open.value = false // Close popover on selection
  }
}

// Format display text based on single or range mode
const displayText = computed(() => {
  if (!props.modelValue) return props.placeholder

  if (props.mode === 'range') {
    const start = props.modelValue?.start
    const end = props.modelValue?.end

    if (start && end) {
      return `${formatDate(start)} - ${formatDate(end)}`
    } else if (start) {
      return formatDate(start)
    }
    return props.placeholder
  }

  return formatDate(internalDateValue.value || props.modelValue)
})
</script>

<template>
  <div class="flex flex-col gap-1.5 w-full">
    <Label v-if="label" :for="id" class="px-0 text-sm font-medium text-foreground dark:text-zinc-200">
      {{ label }}
    </Label>
    <Popover v-model:open="open">
      <PopoverTrigger as-child>
        <button
          :id="id"
          type="button"
          role="combobox"
          data-slot="select-trigger"
          :data-size="size"
          :aria-invalid="!!error"
          :disabled="disabled"
          class="border-input dark:bg-input/30 dark:hover:bg-input/50 focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:aria-invalid:border-destructive/50 flex h-8 w-full items-center justify-between gap-1.5 rounded-lg border bg-transparent px-2.5 py-1 text-sm font-normal text-foreground transition-colors outline-none select-none focus-visible:ring-3 aria-invalid:ring-3 disabled:cursor-not-allowed disabled:opacity-50"
          :class="{ 'border-destructive ring-1 ring-destructive/20': error }"
        >
          <span class="truncate" :class="!modelValue ? 'text-muted-foreground' : 'text-foreground'">
            {{ displayText }}
          </span>
          <CalendarIcon class="text-muted-foreground size-4 pointer-events-none shrink-0" />
        </button>
      </PopoverTrigger>
      <PopoverContent class="w-auto overflow-hidden p-0 bg-popover dark:bg-zinc-900 border border-border dark:border-zinc-800 shadow-xl" align="start">
        <!-- Range Mode -->
        <RangeCalendar
          v-if="mode === 'range'"
          :model-value="internalDateValue"
          v-model:placeholder="calendarPlaceholder"
          :number-of-months="2"
          @update:model-value="handleUpdateDate"
        />
        <!-- Single Mode -->
        <Calendar
          v-else
          :model-value="internalDateValue"
          v-model:placeholder="calendarPlaceholder"
          :layout="layout"
          @update:model-value="handleUpdateDate"
        />
      </PopoverContent>
    </Popover>
  </div>
</template>
