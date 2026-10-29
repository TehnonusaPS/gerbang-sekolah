<script setup>
import { Field, FieldContent, FieldError, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'

defineProps({
  label: String,
  placeholder: String,
  modelValue: [String, Number],
  error: String,
  required: Boolean,
  numericOnly: Boolean,
  maxlength: [Number, String],
  type: {
    type: String,
    default: 'text'
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
      <Input
        :type="type"
        :inputmode="numericOnly ? 'numeric' : undefined"
        :maxlength="maxlength"
        :placeholder="placeholder"
        :model-value="modelValue"
        :aria-invalid="!!error"
        @update:model-value="$emit('update:modelValue', $event)"
        :class="{ 'border-destructive ring-1 ring-destructive/20': error }"
      />
    </FieldContent>
    <FieldError v-if="error">{{ error }}</FieldError>
  </Field>
</template>
