<script setup>
import { ref, computed } from 'vue'
import { Eye, EyeOff } from 'lucide-vue-next'
import { Field, FieldContent, FieldError, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'

const props = defineProps({
  label: String,
  placeholder: String,
  modelValue: [String, Number],
  error: String,
  required: Boolean,
  disabled: Boolean,
  numericOnly: Boolean,
  maxlength: [Number, String],
  type: {
    type: String,
    default: 'text'
  }
})

defineEmits(['update:modelValue'])

const isPasswordVisible = ref(false)
const isPasswordField = computed(() => props.type === 'password')
const resolvedType = computed(() =>
  isPasswordField.value ? (isPasswordVisible.value ? 'text' : 'password') : props.type
)
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
      <div class="relative">
        <Input
          :type="resolvedType"
          :inputmode="numericOnly ? 'numeric' : undefined"
          :maxlength="maxlength"
          :placeholder="placeholder"
          :model-value="modelValue"
          :disabled="disabled"
          :aria-invalid="!!error"
          @update:model-value="$emit('update:modelValue', $event)"
          :class="[
            { 'border-destructive ring-1 ring-destructive/20': error },
            isPasswordField ? 'pr-10' : ''
          ]"
        />
        <button
          v-if="isPasswordField"
          type="button"
          tabindex="-1"
          :disabled="disabled"
          @click="isPasswordVisible = !isPasswordVisible"
          class="absolute inset-y-0 right-0 pr-3 flex items-center text-muted-foreground hover:text-foreground transition-colors cursor-pointer disabled:pointer-events-none disabled:opacity-50"
        >
          <Eye v-if="!isPasswordVisible" class="size-4" />
          <EyeOff v-else class="size-4" />
        </button>
      </div>
    </FieldContent>
    <FieldError v-if="error">{{ error }}</FieldError>
  </Field>
</template>
