<script setup>
import { MapPin, Phone, Mail, Instagram, Facebook, Youtube } from 'lucide-vue-next'
const props = defineProps({ branding: Object, contact: Object, social: Object, data: Object })
</script>

<template>
  <footer
    id="contact"
    class="bg-primary text-white pt-20 pb-8 relative"
  >
    <div class="absolute top-0 left-0 w-full overflow-hidden">
      <svg
        viewBox="0 0 1440 60"
        preserveAspectRatio="none"
        class="w-full h-12"
      >
        <path
          d="M0,30 C360,60 720,0 1440,30 L1440,0 L0,0 Z"
          fill="#faf5ff"
        />
      </svg>
    </div>

    <div class="max-w-7xl mx-auto px-6 relative z-10">
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
        <div class="lg:col-span-2">
          <div class="flex items-center gap-3 mb-6">
            <img
              v-if="branding.logo"
              :src="branding.logo"
              alt="Logo"
              class="w-12 h-12 object-contain"
            />
            <div
              v-else
              class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center font-bold text-xl text-white border-2 border-white/20"
            >
              {{ (data?.meta_title || 'S')[0] }}
            </div>
            <div>
              <h3 class="font-extrabold text-lg">{{ data?.meta_title || (branding?.entityName || 'Instansi') }}</h3>
              <p class="text-accent text-xs font-bold">{{ data?.hero_subtitle || '' }}</p>
            </div>
          </div>
          <p class="text-white/90 font-medium leading-relaxed max-w-md text-sm">
            {{ data?.meta_description || '' }}
          </p>
        </div>

        <div>
          <h4 class="font-extrabold text-sm uppercase tracking-widest text-white mb-6">
            Kontak
          </h4>
          <div class="space-y-4 text-sm">
            <a
              v-if="contact.email"
              :href="`mailto:${contact.email}`"
              class="flex items-center gap-3 text-white font-semibold hover:text-white/80 transition-colors"
              ><Mail class="w-4 h-4 text-white" />{{ contact.email }}</a
            >
            <a
              v-if="contact.phone"
              :href="`tel:${contact.phone}`"
              class="flex items-center gap-3 text-white font-semibold hover:text-white/80 transition-colors"
              ><Phone class="w-4 h-4 text-white" />{{ contact.phone }}</a
            >
            <div
              v-if="contact.address"
              class="flex items-start gap-3 text-white font-semibold"
            >
              <MapPin class="w-4 h-4 text-white mt-0.5" />{{ contact.address }}
            </div>
          </div>
        </div>

        <div>
          <h4 class="font-extrabold text-sm uppercase tracking-widest text-white mb-6">
            Sosial Media
          </h4>
          <div class="flex gap-3">
            <a
              v-if="social.instagram"
              :href="social.instagram"
              target="_blank"
              class="w-10 h-10 rounded-xl bg-white/10 hover:bg-secondary flex items-center justify-center transition-colors fun-wiggle"
              ><Instagram class="w-5 h-5"
            /></a>
            <a
              v-if="social.facebook"
              :href="social.facebook"
              target="_blank"
              class="w-10 h-10 rounded-xl bg-white/10 hover:bg-blue-500/30 flex items-center justify-center transition-colors fun-wiggle"
              ><Facebook class="w-5 h-5"
            /></a>
            <a
              v-if="social.youtube"
              :href="social.youtube"
              target="_blank"
              ><Youtube class="w-5 h-5"
            /></a>
          </div>
        </div>
      </div>

      <div
        v-if="contact.mapsEmbed"
        class="mb-16 rounded-3xl overflow-hidden border-3 border-accent/20"
      >
        <iframe
          :src="contact.mapsEmbed"
          class="w-full h-64 border-0"
          allowfullscreen
          loading="lazy"
        ></iframe>
      </div>

      <div class="border-t border-white/20 pt-8">
        <div
          class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm font-semibold text-white/90"
        >
          <p>&copy; {{ new Date().getFullYear() }} {{ data?.meta_title || (branding?.entityName || 'Instansi') }}. All rights reserved.</p>
          <p>Powered by <span class="text-white font-extrabold underline decoration-2">Gerbang Sekolah</span></p>
        </div>
      </div>
    </div>
  </footer>
</template>
