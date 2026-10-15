<script setup>
import { ref } from 'vue'
import { School, Send, X } from 'lucide-vue-next'
import { currentLogoSquare } from '@/config/logo'

const open = ref(false)
const msg = ref('')
const phone = '6281234567890'

const handleSend = () => {
  const text = msg.value.trim() || 'Halo, saya tertarik dengan Gerbang Sekolah.'
  window.open(`https://wa.me/${phone}?text=${encodeURIComponent(text)}`, '_blank')
}
</script>

<template>
  <div class="fixed bottom-6 right-6 z-60 flex flex-col items-end gap-3">
    <div
      v-if="open"
      class="animate-chat-pop w-85 max-w-[calc(100vw-3rem)] overflow-hidden rounded-3xl bg-card shadow-2xl ring-1 ring-border"
    >
      <div class="relative bg-[oklch(0.55_0.16_150)] px-5 py-4 text-white">
        <button
          class="absolute right-3 top-3 rounded-full p-1.5 text-white/80 hover:bg-white/15 hover:text-white"
          type="button"
          aria-label="Tutup"
          @click="open = false"
        >
          <X class="h-4 w-4" />
        </button>
        <div class="flex items-center gap-3">
          <div class="relative">
            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white p-1 overflow-hidden shadow-sm">
              <img :src="currentLogoSquare" alt="Gerbang Sekolah" class="w-full h-full object-contain" />
            </div>
            <span
              class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full bg-secondary ring-2 ring-[oklch(0.55_0.16_150)]"
            />
          </div>
          <div>
            <div class="text-sm font-semibold">Tim Gerbang Sekolah</div>
            <div class="text-xs text-white/80">Online - Balas dalam menit</div>
          </div>
        </div>
      </div>
      <div class="space-y-3 bg-[oklch(0.97_0.01_150)] px-5 py-5">
        <div class="max-w-[85%] rounded-2xl rounded-tl-sm bg-white px-4 py-3 text-sm shadow-sm">
          Halo! Ada yang bisa kami bantu seputar ERP sekolah?
        </div>
        <div class="max-w-[85%] rounded-2xl rounded-tl-sm bg-white px-4 py-3 text-sm shadow-sm">
          Klik salah satu topik di bawah, atau ketik pesan Anda.
        </div>
        <div class="flex flex-wrap gap-2 pt-1">
          <button
            v-for="q in ['Demo gratis', 'Harga yayasan', 'Migrasi data']"
            :key="q"
            class="rounded-full border border-border bg-white px-3 py-1.5 text-xs font-medium text-foreground hover:bg-secondary/30"
            type="button"
            @click="msg = `Saya ingin tanya tentang: ${q}`"
          >
            {{ q }}
          </button>
        </div>
      </div>
      <div class="flex items-center gap-2 border-t border-border bg-card p-3">
        <input
          v-model="msg"
          placeholder="Tulis pesan..."
          class="flex-1 rounded-full bg-muted px-4 py-2.5 text-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-primary/30"
          @keydown.enter="handleSend"
        />
        <button
          class="flex h-10 w-10 items-center justify-center rounded-full bg-[oklch(0.55_0.16_150)] text-white hover:opacity-90"
          type="button"
          aria-label="Kirim"
          @click="handleSend"
        >
          <Send class="h-4 w-4" />
        </button>
      </div>
    </div>

    <button
      class="animate-wa-pulse group relative flex h-14 w-14 items-center justify-center rounded-full bg-[oklch(0.55_0.16_150)] text-white shadow-xl transition hover:scale-105"
      type="button"
      aria-label="Chat WhatsApp"
      @click="open = !open"
    >
      <X
        v-if="open"
        class="h-6 w-6"
      />
      <svg
        v-else
        viewBox="0 0 32 32"
        class="h-7 w-7"
        fill="currentColor"
        aria-hidden="true"
      >
        <path
          d="M19.11 17.205c-.372 0-1.088 1.39-1.518 1.39a.63.63 0 0 1-.315-.1c-.802-.402-1.504-.817-2.163-1.447-.545-.516-1.146-1.29-1.46-1.963a.426.426 0 0 1-.073-.215c0-.33.99-.945.99-1.49 0-.143-.73-2.09-.832-2.335-.143-.372-.214-.487-.6-.487-.187 0-.36-.043-.53-.043-.302 0-.53.115-.746.315-.688.645-1.032 1.318-1.06 2.264v.114c-.015.99.472 1.977 1.017 2.78 1.23 1.82 2.506 3.41 4.554 4.34.616.287 2.035.888 2.722.888.817 0 2.15-.515 2.4-1.405.092-.34.157-.7.157-1.045 0-.143-.143-.214-.284-.286-.13-.072-1.847-.872-2.245-.872-.014 0-.014 0-.014-.013zM16 27c-2.057 0-3.971-.605-5.578-1.643L4.4 27.2l1.844-5.85A11.91 11.91 0 0 1 4 16C4 9.373 9.373 4 16 4s12 5.373 12 12-5.373 12-12 12zm0-22.022c-5.547 0-10.022 4.475-10.022 10.022 0 2.205.732 4.252 1.97 5.92l-1.296 3.84 3.99-1.27a9.926 9.926 0 0 0 5.358 1.555c5.546 0 10.02-4.476 10.02-10.022S21.547 4.978 16 4.978z"
        />
      </svg>
      <span
        v-if="!open"
        class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-coral text-[10px] font-bold text-coral-foreground ring-2 ring-background"
      >
        1
      </span>
    </button>
  </div>
</template>
