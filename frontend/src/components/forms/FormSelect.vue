<script setup>
import { computed } from 'vue'
import { Field, FieldContent, FieldError, FieldLabel } from '@/components/ui/field'

import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'

const props = defineProps({
  label: String,
  placeholder: String,
  modelValue: String,
  error: String,
  required: Boolean,
  disabled: Boolean,
  options: {
    type: Array,
    default: () => []
  }
})

defineEmits(['update:modelValue'])

/**
 * Compute display label from options array.
 * This is needed because Reka UI's SelectValue can only resolve labels
 * for SelectItem components that have been mounted (dropdown opened).
 * When modelValue is set programmatically from API data without ever
 * opening the dropdown, SelectValue shows the placeholder instead.
 */
const selectedLabel = computed(() => {
  if (props.modelValue === undefined || props.modelValue === null || props.modelValue === '') return ''
  const match = props.options.find(o => String(o.value) === String(props.modelValue))
  return match ? match.label : ''
})

const sanitizedOptions = computed(() => {
  return (props.options || []).filter(o => o && o.value !== undefined && o.value !== null && String(o.value) !== '')
})
</script>

<template>
  <Field :data-invalid="!!error">
    <FieldLabel v-if="label">
      {{ label }}
      <span
        v-if="required"
        class="text-destructive"
        >*</span
      >
    </FieldLabel>

    <FieldContent>
      <Select
        :model-value="modelValue"
        :disabled="disabled"
        @update:model-value="$emit('update:modelValue', $event)"
      >
        <SelectTrigger
          class="w-full"
          :aria-invalid="!!error"
          :class="{ 'border-destructive ring-1 ring-destructive/20': error }"
        >
          <!-- Manually resolve label when Reka UI can't (value set programmatically) -->
          <span v-if="selectedLabel" class="block truncate">{{ selectedLabel }}</span>
          <SelectValue v-else :placeholder="placeholder" />
        </SelectTrigger>

        <SelectContent>
          <SelectItem
            v-for="item in sanitizedOptions"
            :key="item.value"
            :value="String(item.value)"
            :disabled="item.disabled"
          >
            {{ item.label }}
          </SelectItem>
        </SelectContent>
      </Select>
    </FieldContent>

    <FieldError v-if="error">{{ error }}</FieldError>
  </Field>
</template>

