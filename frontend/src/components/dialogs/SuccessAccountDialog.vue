<script setup>
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle
} from '@/components/ui/dialog'

import { Button } from '@/components/ui/button'
import { Check } from 'lucide-vue-next'

defineProps({
  open: Boolean,

  title: {
    type: String,
    default: 'Data Berhasil Disimpan'
  },
  description: {
    type: String,
    default: ''
  },
  email: String,
  phone: String,
  password: String,
  accounts: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:open', 'close'])

const handleClose = () => {
  emit('update:open', false)
  emit('close')
}

const handleOpenChange = value => {
  emit('update:open', value)

  if (!value) {
    emit('close')
  }
}
</script>

<template>
  <Dialog
    :open="open"
    @update:open="handleOpenChange"
  >
    <DialogContent class="sm:max-w-[520px]">
      <DialogHeader>
        <div class="flex justify-center mb-2">
          <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
            <Check class="h-7 w-7 text-green-600" />
          </div>
        </div>

        <DialogTitle class="text-center font-bold">
          {{ title }}
        </DialogTitle>

        <DialogDescription class="text-center">
          {{ description }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <div
          v-if="accounts && accounts.length > 0"
          :class="['grid gap-3', accounts.length > 1 ? 'sm:grid-cols-2' : 'grid-cols-1']"
        >
          <div
            v-for="(acc, idx) in accounts"
            :key="idx"
            class="rounded-lg border overflow-hidden bg-muted/40"
          >
            <div class="bg-primary/10 border-b px-4 py-2.5 text-center">
              <h3 class="font-bold text-sm text-primary">{{ acc.title }}</h3>
            </div>

            <div class="p-4 space-y-2.5 bg-white/20 text-sm">
              <div>
                <div class="text-xs text-muted-foreground">{{ acc.loginLabel || 'Email Login' }}</div>
                <div class="font-medium break-all">{{ acc.loginValue }}</div>
              </div>

              <div v-if="acc.phone">
                <div class="text-xs text-muted-foreground">No. HP Login</div>
                <div class="font-medium">{{ acc.phone }}</div>
              </div>

              <div>
                <div class="text-xs text-muted-foreground">Password Default</div>
                <div class="font-mono font-semibold text-primary">{{ acc.password }}</div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="rounded-lg border overflow-hidden bg-muted/40">
          <div class="bg-primary/10 border-b px-4 py-3 text-center">
            <h3 class="font-bold text-primary">Informasi Akun Administrator</h3>
          </div>

          <div class="p-4 space-y-3 bg-white/20">
            <div>
              <div class="text-muted-foreground">Email Login</div>
              <div class="font-medium">{{ email }}</div>
            </div>

            <div v-if="phone">
              <div class="text-muted-foreground">No. HP Login</div>
              <div class="font-medium">{{ phone }}</div>
            </div>

            <div>
              <div class="text-muted-foreground">Password Default</div>
              <div class="font-mono font-semibold text-primary">{{ password }}</div>
            </div>
          </div>
        </div>

        <div class="rounded-lg border bg-muted/20 p-3 text-sm text-muted-foreground">
          Demi keamanan, pengguna disarankan untuk segera mengganti password default setelah login pertama kali.
        </div>
      </div>

      <DialogFooter>
        <Button @click="handleClose"> Kembali ke Daftar </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>
