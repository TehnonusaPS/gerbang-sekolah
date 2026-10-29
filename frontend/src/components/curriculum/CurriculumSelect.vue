<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { fetchCurriculums } from '@/services/curriculumService'
import { BookOpen, Check, Sparkles, Layers } from 'lucide-vue-next'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'

const props = defineProps({
  modelValue: {
    type: [Number, String, Array],
    default: ''
  },
  mode: {
    type: String,
    default: 'foundation' // 'foundation' | 'school'
  },
  levelFilter: {
    type: String,
    default: '' // 'SD' | 'SMP' | 'ALL'
  },
  foundationCurriculum: {
    type: String,
    default: '' // Name/code or ID of foundation curriculum
  },
  placeholder: {
    type: String,
    default: 'Pilih Jenis Kurikulum...'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  label: {
    type: String,
    default: 'Jenis Kurikulum'
  },
  hint: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const curriculums = ref([])
const isLoading = ref(false)

async function loadCurriculums() {
  isLoading.value = true
  try {
    const res = await fetchCurriculums({ is_active: true })
    curriculums.value = res.data || []
  } catch (err) {
    console.error('Error fetching curriculums:', err)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadCurriculums()
})

watch(() => props.levelFilter, () => {
  loadCurriculums()
})

// Filtered options based on mode & level
const availableOptions = computed(() => {
  if (props.mode === 'foundation') {
    // Yayasan level: Only main curriculum groups (Kurikulum Merdeka & Kurikulum 2013)
    return curriculums.value.filter(c => c.level === 'ALL' || c.code === 'KUR-MERDEKA' || c.code === 'K13-REVISI')
  }

  // School level: Filter based on level (SD / SMP)
  let items = curriculums.value.filter(c => c.code !== 'KUR-MERDEKA') // Exclude abstract parent in school level

  if (props.levelFilter === 'SD') {
    items = items.filter(c => c.level === 'SD' || c.level === 'ALL')
  } else if (props.levelFilter === 'SMP') {
    items = items.filter(c => c.level === 'SMP' || c.level === 'ALL')
  }

  return items
})

function handleChange(val) {
  emit('update:modelValue', val)
  const selectedObj = curriculums.value.find(c => String(c.id) === String(val))
  emit('change', selectedObj)
}
</script>

<template>
  <div class="space-y-1.5 w-full text-left">
    <label v-if="label" class="block text-sm font-medium text-foreground dark:text-zinc-200">
      {{ label }}
    </label>

    <Select
      :model-value="modelValue ? String(modelValue) : ''"
      :disabled="disabled || isLoading"
      @update:model-value="handleChange"
    >
      <SelectTrigger
        class="w-full"
        :aria-invalid="!!error"
        :class="{ 'border-destructive ring-1 ring-destructive/20': error }"
      >
        <div class="flex items-center gap-2 truncate">
          <BookOpen class="h-4 w-4 text-primary shrink-0" />
          <SelectValue :placeholder="isLoading ? 'Memuat kurikulum...' : placeholder" />
        </div>
      </SelectTrigger>

      <SelectContent class="bg-popover dark:bg-zinc-900 border border-border dark:border-zinc-800 shadow-xl">
        <SelectItem
          v-for="item in availableOptions"
          :key="item.id"
          :value="String(item.id)"
          class="cursor-pointer focus:bg-accent dark:focus:bg-zinc-800 transition-colors py-2.5"
        >
          <div class="flex items-center justify-between gap-3 w-full">
            <div class="flex flex-col text-left">
              <span class="font-medium text-foreground dark:text-zinc-100 flex items-center gap-1.5">
                {{ item.name }}
              </span>
              <span class="text-xs text-muted-foreground dark:text-zinc-400">
                Kode: {{ item.code }}
              </span>
            </div>
            <Badge
              variant="outline"
              class="text-[10px] uppercase shrink-0 font-bold px-2 py-0.5 border-primary/30 text-primary bg-primary/5"
            >
              {{ item.level === 'ALL' ? 'Nasional / All' : `Jenjang ${item.level}` }}
            </Badge>
          </div>
        </SelectItem>
      </SelectContent>
    </Select>

    <p v-if="hint && !error" class="text-xs text-muted-foreground dark:text-zinc-400">
      {{ hint }}
    </p>

    <p v-if="error" class="text-xs text-destructive font-medium">
      {{ error }}
    </p>
  </div>
</template>
