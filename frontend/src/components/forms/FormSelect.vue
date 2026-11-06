<script setup>
import { computed, ref } from 'vue'
import { Check, ChevronDown } from 'lucide-vue-next'
import { Field, FieldContent, FieldError, FieldLabel } from '@/components/ui/field'
import {
  Popover,
  PopoverContent,
  PopoverTrigger
} from '@/components/ui/popover'
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList
} from '@/components/ui/command'
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
  modelValue: [String, Number],
  error: String,
  required: Boolean,
  disabled: Boolean,
  options: {
    type: Array,
    default: () => []
  },
  searchable: {
    type: Boolean,
    default: false
  },
  searchPlaceholder: {
    type: String,
    default: 'Cari...'
  },
  emptyText: {
    type: String,
    default: 'Data tidak ditemukan.'
  }
})

defineEmits(['update:modelValue'])

const isOpen = ref(false)

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
      <!-- SEARCHABLE MODE (Popover + Command) -->
      <Popover v-if="searchable" v-model:open="isOpen">
        <PopoverTrigger as-child>
          <button
            type="button"
            role="combobox"
            data-slot="select-trigger"
            :aria-expanded="isOpen"
            :aria-invalid="!!error"
            :disabled="disabled"
            class="border-input dark:bg-input/30 dark:hover:bg-input/50 focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:aria-invalid:border-destructive/50 flex h-8 w-full items-center justify-between gap-1.5 rounded-lg border bg-transparent px-2.5 py-1 text-left text-sm font-normal transition-colors outline-none select-none focus-visible:ring-3 aria-invalid:ring-3 disabled:cursor-not-allowed disabled:opacity-50"
            :class="{'border-destructive ring-1 ring-destructive/20': error}"
          >
            <span
              class="truncate"
              :class="selectedLabel ? 'text-foreground' : 'text-muted-foreground'"
            >
              {{ selectedLabel || placeholder || 'Pilih...' }}
            </span>
            <ChevronDown
              class="size-4 shrink-0 text-muted-foreground transition-transform duration-200"
              :class="{ 'rotate-180': isOpen }"
            />
          </button>
        </PopoverTrigger>

        <PopoverContent
          class="w-[var(--reka-popover-trigger-width)] min-w-[280px] sm:min-w-[340px] max-w-[calc(100vw-2rem)] rounded-xl p-0 shadow-xl border border-border z-[70]"
          align="start"
          :side-offset="4"
        >
          <Command class="rounded-xl">
            <CommandInput :placeholder="searchPlaceholder" class="h-9" />
            <CommandList class="max-h-[250px] overflow-y-auto">
              <CommandEmpty class="py-4 text-center text-sm text-muted-foreground">
                {{ emptyText }}
              </CommandEmpty>
              <CommandGroup>
                <CommandItem
                  v-for="item in sanitizedOptions"
                  :key="item.value"
                  :value="`${item.label} ${item.value}`"
                  @select="() => {
                    $emit('update:modelValue', item.value)
                    isOpen = false
                  }"
                  class="flex items-center justify-between cursor-pointer py-2 px-3 text-sm"
                >
                  <span class="line-clamp-2 leading-snug pr-2 text-left">{{ item.label }}</span>
                  <Check
                    v-if="String(item.value) === String(modelValue)"
                    class="h-4 w-4 text-primary shrink-0 ml-auto"
                  />
                </CommandItem>
              </CommandGroup>
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>

      <!-- STANDARD SELECT MODE -->
      <Select
        v-else
        :model-value="modelValue ? String(modelValue) : undefined"
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

