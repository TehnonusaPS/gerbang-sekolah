<script setup>
import { onMounted } from 'vue'

import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingHeroSection from '@/components/landing/LandingHeroSection.vue'
import WaveSection from '@/components/landing/WaveSection.vue'
import LandingFeaturesSection from '@/components/landing/LandingFeaturesSection.vue'
import LandingModulesSection from '@/components/landing/LandingModulesSection.vue'
import LandingPricingSection from '@/components/landing/LandingPricingSection.vue'
import LandingTestimonialsSection from '@/components/landing/LandingTestimonialsSection.vue'
import LandingCtaSection from '@/components/landing/LandingCtaSection.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'
import LandingChatWidget from '@/components/landing/LandingChatWidget.vue'
import PwaInstallModal from '@/components/landing/PwaInstallModal.vue'
import { usePwa } from '@/composables/usePwa'

const { initPwa } = usePwa()

onMounted(() => {
  // PWA listeners registration
  initPwa()

  // Paksa halaman landing selalu dalam light mode
  document.documentElement.classList.remove('dark')

  // Bersihkan semua class tema atau background yang mungkin terbawa dari dashboard
  document.body.classList.forEach(cls => {
    if (
      cls.startsWith('theme-') ||
      [
        'bg-animated',
        'bg-static_squares',
        'bg-glass',
        'bg-school',
        'bg-solid',
        'bg-modern_minimalist',
        'finish-solid'
      ].includes(cls)
    ) {
      document.body.classList.remove(cls)
    }
  })

  // Kunci tema Landing Page agar selalu biru konsisten sesuai identitas resmi logo (#0071FF)
  document.body.classList.add('theme-blue')
})
</script>

<template>
  <div class="font-sans antialiased text-foreground bg-background overflow-x-hidden">
    <!-- Navbar (floating glass) -->
    <LandingNavbar
      brandName="App School Tehnonusa"
      loginPath="/login"
    />

    <!-- Hero Section -->
    <LandingHeroSection />

    <!-- Wave Carousel (visual asset showcaser & value list) -->
    <WaveSection />

    <!-- Fitur Section -->
    <LandingFeaturesSection />

    <!-- Modul Section -->
    <LandingModulesSection />

    <!-- Harga Section -->
    <LandingPricingSection />

    <!-- Testimoni Section -->
    <LandingTestimonialsSection />

    <!-- CTA Section -->
    <LandingCtaSection />

    <!-- Footer Section -->
    <LandingFooter brandName="App School Tehnonusa" />

    <!-- Live Floating WhatsApp Chat Helper -->
    <LandingChatWidget />

    <!-- Simulated PWA Installer Modal Dialog -->
    <PwaInstallModal />
  </div>
</template>
