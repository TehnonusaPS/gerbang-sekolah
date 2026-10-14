<script setup>
import { Check, ShieldCheck } from 'lucide-vue-next'

const plans = [
  {
    name: 'Starter',
    price: 'Rp 1,5jt',
    desc: 'Untuk 1 sekolah, hingga 300 siswa.',
    features: ['Akademik & Rapor', 'Absensi digital', 'Komunikasi orang tua', 'Support email'],
    color: 'bg-secondary text-secondary-foreground',
    featured: false
  },
  {
    name: 'Yayasan',
    price: 'Rp 4,9jt',
    desc: 'Multi-sekolah, hingga 2.000 siswa.',
    features: [
      'Semua di Starter',
      'Dashboard yayasan',
      'Keuangan multi-unit',
      'PPDB online',
      'Support prioritas'
    ],
    color: 'bg-primary text-primary-foreground',
    featured: true
  },
  {
    name: 'Enterprise',
    price: 'Custom',
    desc: 'Yayasan besar, kustomisasi penuh.',
    features: ['Semua di Yayasan', 'Single Sign-On', 'Integrasi kustom', 'Dedicated manager'],
    color: 'bg-accent text-accent-foreground',
    featured: false
  }
]
</script>

<template>
  <section id="harga" class="relative px-4 py-16 md:px-6 md:py-24">
    <div class="mx-auto max-w-6xl">
      <div class="mx-auto max-w-2xl text-center">
        <span class="text-sm font-semibold uppercase tracking-wider text-primary">Harga</span>
        <h2 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight md:text-5xl">
          Transparan, tumbuh bersama yayasan Anda
        </h2>
        <p class="mt-4 text-sm sm:text-base text-muted-foreground leading-relaxed">
          Per bulan, dibayar tahunan. Tanpa biaya tersembunyi.
        </p>
      </div>

      <div class="mt-14 grid gap-5 md:grid-cols-3">
        <div
          v-for="plan in plans"
          :key="plan.name"
          class="relative rounded-3xl p-7 glass"
          :class="
            plan.featured
              ? 'ring-2 ring-primary/40 shadow-2xl shadow-primary/20'
              : ''
          "
        >
          <span
            v-if="plan.featured"
            class="absolute -top-3 left-7 rounded-full bg-primary px-3 py-1 text-xs font-bold text-primary-foreground shadow-sm"
          >
            Paling Populer
          </span>
          <div
            class="mb-5 inline-flex h-10 w-10 items-center justify-center rounded-xl font-medium"
            :class="plan.color"
          >
            <ShieldCheck class="h-5 w-5" />
          </div>
          <h3 class="text-xl font-bold text-foreground">{{ plan.name }}</h3>
          <p class="mt-1 text-sm text-muted-foreground">
            {{ plan.desc }}
          </p>
          <div class="mt-5 flex items-baseline gap-1">
            <span class="text-4xl font-bold text-foreground">{{ plan.price }}</span>
            <span class="text-sm text-muted-foreground">/bulan</span>
          </div>
          <ul class="mt-6 space-y-3 text-sm text-foreground">
            <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2">
              <Check class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
              {{ feature }}
            </li>
          </ul>
          <button
            class="mt-7 w-full rounded-full px-5 py-3 text-sm font-semibold transition cursor-pointer"
            :class="
              plan.featured
                ? 'bg-primary text-primary-foreground hover:opacity-90 shadow-md shadow-primary/25'
                : 'bg-foreground text-background hover:opacity-90'
            "
            type="button"
          >
            Pilih {{ plan.name }}
          </button>
        </div>
      </div>
    </div>
  </section>
</template>
