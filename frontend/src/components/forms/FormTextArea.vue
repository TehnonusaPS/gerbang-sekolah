<script setup>
import { Field, FieldContent, FieldError, FieldLabel } from '@/components/ui/field'

import { Textarea } from '@/components/ui/textarea'

defineProps({
  label: String,
  placeholder: String,
  modelValue: String,
  error: String,
  required: Boolean,
  rows: {
    type: Number,
    default: 4
  }
})

defineEmits(['update:modelValue'])
</script>

<template>
  <Field :data-invalid="!!error">
    <FieldLabel>
      {{ label }}
      <span
        v-if="required"
        class="text-destructive"
        >*</span
      >
    </FieldLabel>

    <FieldContent>
      <Textarea
        :rows="rows"
        :placeholder="placeholder"
        :model-value="modelValue"
        :aria-invalid="!!error"
        @update:model-value="$emit('update:modelValue', $event)"
        :class="{
          'border-destructive ring-1 ring-destructive/20': error
        }"
      />
    </FieldContent>

    <FieldError v-if="error">{{ error }}</FieldError>
  </Field>
</template>
