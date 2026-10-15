<script setup>
import { BarChart3, BookOpen, CalendarCheck, MessageCircle, Users, Wallet } from 'lucide-vue-next'

const features = [
  {
    icon: BookOpen,
    title: 'Akademik & Rapor',
    desc: 'Kurikulum, jadwal, nilai, dan rapor digital terintegrasi.',
    color: 'bg-primary text-primary-foreground'
  },
  {
    icon: Wallet,
    title: 'Keuangan & SPP',
    desc: 'Tagihan otomatis, pembayaran online, laporan keuangan real-time.',
    color: 'bg-accent text-accent-foreground'
  },
  {
    icon: Users,
    title: 'Kesiswaan & PPDB',
    desc: 'Pendaftaran online, profil siswa, dan riwayat lengkap.',
    color: 'bg-secondary text-secondary-foreground'
  },
  {
    icon: CalendarCheck,
    title: 'Absensi & Kehadiran',
    desc: 'Absen QR/biometrik untuk siswa & guru, otomatis terekap.',
    color: 'bg-primary/20 text-primary'
  },
  {
    icon: MessageCircle,
    title: 'Komunikasi Orang Tua',
    desc: 'Broadcast, chat, dan notifikasi via app & WhatsApp.',
    color: 'bg-secondary/80 text-secondary-foreground'
  },
  {
    icon: BarChart3,
    title: 'Dashboard Yayasan',
    desc: 'Lihat performa semua sekolah dalam satu layar.',
    color: 'bg-accent/80 text-accent-foreground'
  }
]
</script>

<template>
  <section id="fitur" class="relative px-4 py-16 md:px-6 md:py-24">
    <div class="mx-auto max-w-6xl">
      <div class="mx-auto max-w-2xl text-center">
        <span class="text-sm font-semibold uppercase tracking-wider text-primary"
          >Fitur Lengkap</span
        >
        <h2 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight md:text-5xl">
          Semua yang dibutuhkan yayasan modern
        </h2>
        <p class="mt-4 text-sm sm:text-base text-muted-foreground leading-relaxed">
          Dari ruang kelas hingga ruang yayasan - Gerbang Sekolah menyatukan operasional pendidikan Anda.
        </p>
      </div>

      <div class="mt-14 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="feature in features"
          :key="feature.title"
          class="glass group relative overflow-hidden rounded-3xl p-6 transition hover:-translate-y-1"
        >
          <div
            class="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-2xl"
            :class="feature.color"
          >
            <component :is="feature.icon" class="h-6 w-6" />
          </div>
          <h3 class="text-lg font-semibold">{{ feature.title }}</h3>
          <p class="mt-2 text-sm text-muted-foreground">{{ feature.desc }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
