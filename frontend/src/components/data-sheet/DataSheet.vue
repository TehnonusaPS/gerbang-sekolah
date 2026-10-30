<script setup>
import { computed, ref, watch } from 'vue'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription
} from '@/components/ui/sheet'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger
} from '@/components/ui/accordion'
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'
import { Spinner } from '@/components/ui/spinner'
import { File } from 'lucide-vue-next'
import { useApiLoading } from '@/composables/useApiLoading'

const props = defineProps({
  open: {
    type: Boolean,
    required: true
  },
  item: {
    type: Object,
    default: null
  },
  title: {
    type: String,
    default: ''
  },
  titleKey: {
    type: String,
    default: 'nama'
  },
  description: {
    type: String,
    default: ''
  },
  descriptionKey: {
    type: String,
    default: ''
  },
  descriptionPrefix: {
    type: String,
    default: ''
  },
  avatar: {
    type: [String, Boolean],
    default: null
  },
  avatarKey: {
    type: String,
    default: 'foto'
  },
  avatarFallback: {
    type: String,
    default: null
  },
  avatarFallbackKey: {
    type: String,
    default: null
  },
  badge: {
    type: String,
    default: null
  },
  badgeKey: {
    type: String,
    default: 'status'
  },
  badgeVariant: {
    type: [String, Object, Function],
    default: 'default'
  },
  sections: {
    type: Array,
    default: () => []
  },
  loading: {
    type: Boolean,
    default: undefined
  }
})

const { isFetching } = useApiLoading()
const isSheetLoading = computed(() =>
  props.loading !== undefined ? props.loading : (isFetching.value && !props.item)
)

const emit = defineEmits(['update:open'])

const isOpen = computed({
  get: () => props.open,
  set: val => emit('update:open', val)
})

const imageError = ref(false)
watch([isOpen, () => props.item], () => {
  imageError.value = false
})

const resolvedTitle = computed(() => {
  if (props.title) return props.title
  if (props.item && props.titleKey) return props.item[props.titleKey] ?? ''
  return ''
})

const resolvedDescription = computed(() => {
  if (props.description) return props.description
  if (props.item && props.descriptionKey) {
    const val = props.item[props.descriptionKey]
    if (val !== undefined && val !== null) {
      return (props.descriptionPrefix || '') + val
    }
  }
  return ''
})

const resolvedAvatar = computed(() => {
  if (props.avatar !== null && props.avatar !== false) return props.avatar
  if (props.item && props.avatarKey) return props.item[props.avatarKey] ?? null
  return null
})

const showAvatar = computed(() => {
  if (props.avatar === false) return false
  if (props.avatar !== null) return true
  if (props.item && props.avatarKey && props.item[props.avatarKey] !== undefined) return true
  return false
})

const resolvedAvatarFallback = computed(() => {
  if (props.avatarFallback) return props.avatarFallback
  if (props.item && props.avatarFallbackKey && props.item[props.avatarFallbackKey]) {
    return props.item[props.avatarFallbackKey]
  }
  const titleVal = resolvedTitle.value
  if (!titleVal) return '?'
  const parts = String(titleVal).trim().split(/\s+/)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})

const resolvedBadge = computed(() => {
  if (props.badge !== null) return props.badge
  if (props.item && props.badgeKey) return props.item[props.badgeKey] ?? null
  return null
})

const resolvedBadgeVariant = computed(() => {
  const label = resolvedBadge.value
  if (!label) return 'default'

  if (typeof props.badgeVariant === 'string' && props.badgeVariant !== 'default') {
    return props.badgeVariant
  }

  const defaultMapping = {
    Aktif: 'green',
    Nonaktif: 'gray',
    Pending: 'amber',
    Alumni: 'blue',
    'Guru Tetap': 'green',
    Superadmin: 'blue'
  }

  if (typeof props.badgeVariant === 'object' && props.badgeVariant !== null) {
    return props.badgeVariant[label] || 'default'
  }

  if (typeof props.badgeVariant === 'function') {
    return props.badgeVariant(label)
  }

  return defaultMapping[label] || 'default'
})

const resolveFieldValue = field => {
  if (field.value !== undefined && field.value !== null) {
    return field.value
  }
  if (props.item && field.key) {
    return props.item[field.key] ?? ''
  }
  return ''
}
</script>

<template>
  <Sheet v-model:open="isOpen">
    <SheetContent
      :show-close-button="false"
      class="gap-2"
    >
      <slot name="header">
        <SheetHeader class="border-b border-border pb-3">
          <div class="flex items-center gap-4">
            <Avatar
              v-if="showAvatar"
              class="size-16 border-2 border-border/80 shrink-0 overflow-hidden"
            >
              <AvatarImage
                v-if="resolvedAvatar && !imageError"
                :src="resolvedAvatar"
                :alt="resolvedTitle"
                class="w-full h-full object-cover"
                @error="imageError = true"
              />
              <AvatarFallback
                v-else
                class="text-lg font-bold"
              >
                {{ resolvedAvatarFallback }}
              </AvatarFallback>
            </Avatar>
            <div class="flex-1 min-w-0 space-y-1 text-left">
              <div class="flex items-center gap-2 flex-wrap">
                <SheetTitle class="text-base font-bold text-foreground truncate">
                  {{ resolvedTitle }}
                </SheetTitle>
                <Badge
                  v-if="resolvedBadge"
                  :variant="resolvedBadgeVariant"
                >
                  {{ resolvedBadge }}
                </Badge>
              </div>
              <SheetDescription
                v-if="resolvedDescription"
                class="text-xs text-muted-foreground"
              >
                {{ resolvedDescription }}
              </SheetDescription>
            </div>
          </div>
        </SheetHeader>
      </slot>

      <!-- Body / Accordion -->
      <div class="overflow-y-auto pr-1 flex-1 no-scrollbar">
        <div v-if="isSheetLoading" class="space-y-4 py-6">
          <div class="flex items-center gap-2 text-xs text-muted-foreground pb-2">
            <Spinner class="size-4 text-primary" />
            <span>Memuat detail data...</span>
          </div>
          <div v-for="n in 4" :key="n" class="space-y-2">
            <Skeleton class="h-3.5 w-28" />
            <Skeleton class="h-8 w-full rounded-lg" />
          </div>
        </div>
        <slot v-else>
          <Accordion
            type="multiple"
            class="w-full"
            :default-value="sections.map(s => s.id)"
          >
            <AccordionItem
              v-for="section in sections"
              :key="section.id"
              :value="section.id"
            >
              <AccordionTrigger class="text-sm font-semibold">
                {{ section.title }}
              </AccordionTrigger>
              <AccordionContent class="space-y-4 pt-3">
                <slot
                  :name="`section-${section.id}`"
                  :section="section"
                >
                  <div
                    v-for="field in section.fields"
                    :key="field.label"
                    class="grid gap-1.5"
                  >
                    <slot
                      :name="`field-${field.key || field.label.toLowerCase().replace(/\s+/g, '-')}`"
                      :field="field"
                      :value="resolveFieldValue(field)"
                    >
                      <div class="flex items-center gap-1.5">
                        <component
                          v-if="field.icon"
                          :is="field.icon"
                          class="size-3.5 text-muted-foreground"
                        />
                        <label class="text-xs font-semibold text-muted-foreground">
                          {{ field.label }}
                        </label>
                      </div>
                      <Textarea
                        v-if="field.textarea || field.type === 'textarea'"
                        :model-value="resolveFieldValue(field)"
                        readonly
                        class="min-h-16 bg-muted/40 cursor-default focus-visible:ring-0 resize-none text-sm"
                      />
                      <div
                        v-else-if="field.file || field.type === 'file'"
                        class="flex items-center gap-2 p-2 rounded-lg border border-border bg-muted/30"
                      >
                        <File class="size-4 text-primary shrink-0" />
                        <span class="text-xs font-medium text-foreground truncate flex-1">
                          {{ resolveFieldValue(field) || 'Tidak ada dokumen' }}
                        </span>
                        <a
                          v-if="resolveFieldValue(field)"
                          :href="resolveFieldValue(field)"
                          target="_blank"
                          class="text-[10px] font-semibold text-primary hover:underline px-2 py-1 bg-primary/10 rounded"
                        >
                          Lihat
                        </a>
                      </div>
                      <Input
                        v-else
                        :model-value="resolveFieldValue(field)"
                        readonly
                        class="h-8 bg-muted/40 cursor-default focus-visible:ring-0 text-sm"
                      />
                    </slot>
                  </div>
                </slot>
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </slot>
      </div>
    </SheetContent>
  </Sheet>
</template>
