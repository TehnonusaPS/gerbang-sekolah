<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import type { ButtonVariants } from '.'
import { computed, useAttrs } from 'vue'
import { Primitive } from 'reka-ui'
import { Spinner } from '@/components/ui/spinner'
import { cn } from '@/lib/utils'
import { buttonVariants } from '.'

interface Props extends PrimitiveProps {
  variant?: ButtonVariants['variant']
  size?: ButtonVariants['size']
  class?: HTMLAttributes['class']
  loading?: boolean
  disabled?: boolean
  cooldownMs?: number
}

const props = withDefaults(defineProps<Props>(), {
  as: 'button',
  loading: false,
  disabled: false,
  cooldownMs: 500
})

const attrs = useAttrs()
let lastClickAt = 0

const isTriggerButton = computed(() => {
  return (
    props.asChild ||
    attrs.role === 'combobox' ||
    attrs['data-slot'] === 'popover-trigger' ||
    attrs['data-slot'] === 'dropdown-menu-trigger' ||
    attrs['data-slot'] === 'select-trigger'
  )
})

const isDisabled = computed(() => Boolean(props.disabled || props.loading))

const handleClickCapture = (event: MouseEvent) => {
  if (isTriggerButton.value) return

  const now = Date.now()
  const isCoolingDown = props.cooldownMs > 0 && now - lastClickAt < props.cooldownMs

  if (isDisabled.value || isCoolingDown) {
    event.preventDefault()
    event.stopPropagation()
    event.stopImmediatePropagation()
    return
  }

  lastClickAt = now
}
</script>

<template>
  <Primitive
    data-slot="button"
    :data-variant="variant"
    :data-size="size"
    :as="as"
    :as-child="asChild"
    :disabled="isDisabled || undefined"
    :aria-busy="loading ? 'true' : undefined"
    :class="cn(buttonVariants({ variant, size }), props.class)"
    @click.capture="handleClickCapture"
  >
    <Spinner
      v-if="loading && !asChild"
      class="mr-1.5 size-3.5 shrink-0"
    />
    <slot />
  </Primitive>
</template>
