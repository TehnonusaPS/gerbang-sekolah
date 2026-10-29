<script setup>
import { computed, ref } from 'vue'
import { Check, ChevronDown } from 'lucide-vue-next'

import {
  Field,
  FieldContent,
  FieldError,
  FieldLabel
} from '@/components/ui/field'

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
  CommandList,
  CommandSeparator
} from '@/components/ui/command'

const props = defineProps({
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Pilih data'
  },
  modelValue: {
    type: Array,
    default: () => []
  },
  error: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  searchable: {
    type: Boolean,
    default: true
  },
  showSelectAll: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  options: {
    type: Array,
    default: () => []
  },
  itemName: {
    type: String,
    default: 'mata pelajaran'
  },
  size: {
    type: String,
    default: 'default'
  }
})

const emit = defineEmits(['update:modelValue'])

const isOpen = ref(false)

const selectedValues = computed(() => {
  return (props.modelValue || []).map(value => String(value))
})

const selectedOptions = computed(() => {
  return props.options.filter(option =>
    selectedValues.value.includes(String(option.value))
  )
})

const capitalizedItemName = computed(() => {
  return props.itemName
    .split(' ')
    .map(w => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ')
})

const selectedLabel = computed(() => {
  const total = selectedOptions.value.length

  if (total === 0) {
    return props.placeholder
  }

  if (total === 1) {
    return selectedOptions.value[0].label
  }

  if (total === props.options.length && props.options.length > 0) {
    return `Semua ${props.itemName} dipilih`
  }

  return `${total} ${props.itemName} dipilih`
})

const isSelected = value => {
  return selectedValues.value.includes(String(value))
}

const allSelected = computed(() => {
  return (
    props.options.length > 0 &&
    props.options.every(option =>
      selectedValues.value.includes(String(option.value))
    )
  )
})

const toggleOption = value => {
  const optionValue = String(value)
  const values = [...selectedValues.value]
  const index = values.indexOf(optionValue)

  if (index >= 0) {
    values.splice(index, 1)
  } else {
    values.push(optionValue)
  }

  emit('update:modelValue', values)
}

const toggleSelectAll = () => {
  if (allSelected.value) {
    emit('update:modelValue', [])
    return
  }

  emit(
    'update:modelValue',
    props.options.map(option => String(option.value))
  )
}
</script>

<template>
  <Field :data-invalid="!!error">
    <FieldLabel v-if="label">
      {{ label }}

      <span
        v-if="required"
        class="text-destructive"
      >
        *
      </span>
    </FieldLabel>

    <FieldContent>
      <Popover v-model:open="isOpen">
        <PopoverTrigger as-child>
          <button
            type="button"
            role="combobox"
            data-slot="select-trigger"
            :data-size="size"
            :aria-expanded="isOpen"
            :aria-invalid="!!error"
            :disabled="disabled"
            class="border-input dark:bg-input/30 dark:hover:bg-input/50 focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:aria-invalid:border-destructive/50 flex h-8 w-full items-center justify-between gap-1.5 rounded-lg border bg-transparent px-2.5 py-1 text-left text-sm font-normal transition-colors outline-none select-none focus-visible:ring-3 aria-invalid:ring-3 disabled:cursor-not-allowed disabled:opacity-50"
            :class="{'border-destructive ring-1 ring-destructive/20': error}"
          >
            <span
              class="truncate"
              :class="
                selectedOptions.length > 0
                  ? 'text-foreground'
                  : 'text-muted-foreground'
              "
            >
              {{ selectedLabel }}
            </span>

            <ChevronDown
              class="size-4 shrink-0 text-muted-foreground transition-transform duration-200"
              :class="{ 'rotate-180': isOpen }"
            />
          </button>
        </PopoverTrigger>

        <PopoverContent
          class="w-[var(--reka-popover-trigger-width)] rounded-xl p-0"
          align="start"
          :side-offset="4"
        >
          <Command class="rounded-xl">
            <CommandInput
              v-if="searchable"
              :placeholder="`Cari ${itemName}...`"
            />

            <CommandList>
              <CommandEmpty>
                Data {{ itemName }} tidak ditemukan.
              </CommandEmpty>

              <!-- Pilih Semua -->
              <CommandGroup v-if="showSelectAll && options.length > 0">
                <CommandItem
                  :value="`pilih-semua-${itemName}`"
                  class="cursor-pointer font-medium"
                  @select.prevent="toggleSelectAll"
                >
                  <div
                    class="mr-2 flex size-4 shrink-0 items-center justify-center rounded border"
                    :class="
                      allSelected
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-input bg-background'
                    "
                  >
                    <Check
                      v-if="allSelected"
                      class="size-3"
                    />
                  </div>

                  <span>
                    Pilih Semua {{ capitalizedItemName }}
                  </span>
                </CommandItem>
              </CommandGroup>

              <CommandSeparator
                v-if="showSelectAll && options.length > 0"
              />

              <!-- Daftar mata pelajaran -->
              <CommandGroup>
                <CommandItem
                  v-for="item in options"
                  :key="item.value"
                  :value="`${item.label}-${item.value}`"
                  class="cursor-pointer"
                  @select.prevent="toggleOption(item.value)"
                >
                  <div
                    class="mr-2 flex size-4 shrink-0 items-center justify-center rounded border"
                    :class="
                      isSelected(item.value)
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'border-input bg-background'
                    "
                  >
                    <Check
                      v-if="isSelected(item.value)"
                      class="size-3"
                    />
                  </div>

                  <span class="truncate">
                    {{ item.label }}
                  </span>
                </CommandItem>
              </CommandGroup>
            </CommandList>
          </Command>
        </PopoverContent>
      </Popover>
    </FieldContent>

    <FieldError v-if="error">
      {{ error }}
    </FieldError>
  </Field>
</template>