<script setup>
import { Mail, Phone, MapPin } from 'lucide-vue-next'

const contactItems = [
  {
    icon: Mail,
    title: 'Email',
    value: 'info@gerbangsekolah.id'
  },
  {
    icon: Phone,
    title: 'Telepon / WhatsApp',
    value: '+62 812-XXXX-XXXX'
  },
  {
    icon: MapPin,
    title: 'Kantor',
    value: 'Jakarta, Indonesia'
  }
]
</script>

<template>
  <div class="space-y-8 lg:pr-6">
    <div class="space-y-6">
      <div
        v-for="(item, idx) in contactItems"
        :key="idx"
        class="flex items-center gap-4 group"
      >
        <div
          class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary-foreground/90 group-hover:scale-105 transition-transform duration-200"
        >
          <component
            :is="item.icon"
            class="h-5 w-5"
          />
        </div>
        <div class="space-y-1">
          <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
            {{ item.title }}
          </p>
          <p class="text-base font-bold text-slate-800 dark:text-slate-200">{{ item.value }}</p>
        </div>
      </div>
    </div>

    <!-- Info Banner Card -->
    <div
      class="relative overflow-hidden rounded-3xl border border-primary/15 bg-primary/5 p-6 dark:border-primary/20 dark:bg-primary/5"
    >
      <div class="relative space-y-2">
        <h4 class="text-sm font-bold text-primary dark:text-primary-foreground/90">
          Demo Gratis Tersedia
        </h4>
        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
          Kami menyediakan sesi demo online gratis 30 menit khusus untuk kepala sekolah dan pengurus
          yayasan yang tertarik bergabung.
        </p>
      </div>
    </div>
  </div>
</template>
