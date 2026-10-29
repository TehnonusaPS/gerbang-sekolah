<script setup>
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'

defineProps({
  title: String,
  description: String,
  icon: {
    type: [Object, Function],
    default: null
  },
  required: {
    type: Boolean,
    default: false
  }
})
</script>

<template>
  <Card class="shadow-sm flex flex-col">
    <CardHeader class="pb-4">
      <div
        v-if="icon || title"
        class="flex items-center gap-2 text-primary font-semibold"
      >
        <component
          :is="icon"
          v-if="icon"
          class="h-5 w-5"
        />
        <CardTitle class="inline-flex items-center">
          <slot name="title">
            <span>{{ title }}</span>
          </slot>
          <span
            v-if="required"
            class="text-red-500 text-destructive font-bold ml-1 text-base leading-none select-none"
            aria-hidden="true"
            >*</span
          >
        </CardTitle>
      </div>

      <CardDescription v-if="description">
        {{ description }}
      </CardDescription>
    </CardHeader>

    <CardContent class="space-y-4 flex-1 flex flex-col"><slot /></CardContent>
  </Card>
</template>
