<script setup>
import { MapPin, Phone, Mail, Instagram, Facebook, Youtube } from 'lucide-vue-next'

const props = defineProps({
  branding: Object,
  contact: Object,
  social: Object,
  data: Object
})
</script>

<template>
  <footer
    id="contact"
    class="bg-gray-900 text-white pt-20 pb-8"
  >
    <div class="max-w-7xl mx-auto px-6">
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
        <!-- Brand -->
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
              class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-xl"
              :style="{
                background: `linear-gradient(135deg, ${branding.primaryColor}, ${branding.accentColor})`
              }"
            >
              {{ (data?.meta_title || 'S')[0] }}
            </div>
            <div>
              <h3 class="font-bold text-lg">{{ data?.meta_title || (branding?.entityName || 'Instansi') }}</h3>
              <p v-if="data?.slogan" class="text-gray-400 text-xs italic mt-0.5">"{{ data?.slogan }}"</p>
              <p v-else-if="data?.hero_subtitle" class="text-gray-400 text-xs mt-0.5">{{ data?.hero_subtitle }}</p>
              <p v-if="data?.legal_number" class="text-xs text-emerald-400/90 font-medium mt-2">
                No. Izin/Legalitas: {{ data?.legal_number }}
              </p>
            </div>
          </div>
          <p class="text-gray-400 leading-relaxed max-w-md text-sm">
            {{
              data?.meta_description ||
              'Membangun generasi unggul, berkarakter, dan berprestasi untuk masa depan Indonesia yang lebih baik.'
            }}
          </p>
        </div>

        <!-- Contact Info -->
        <div>
          <h4 class="font-bold text-sm uppercase tracking-widest text-gray-400 mb-6">Kontak</h4>
          <div class="space-y-4 text-sm">
            <a
              v-if="contact.email"
              :href="`mailto:${contact.email}`"
              class="flex items-center gap-3 text-gray-300 hover:text-white transition-colors"
            >
              <Mail
                class="w-4 h-4 flex-shrink-0"
                :style="{ color: branding.accentColor }"
              />
              {{ contact.email }}
            </a>
            <a
              v-if="contact.phone"
              :href="`tel:${contact.phone}`"
              class="flex items-center gap-3 text-gray-300 hover:text-white transition-colors"
            >
              <Phone
                class="w-4 h-4 flex-shrink-0"
                :style="{ color: branding.accentColor }"
              />
              {{ contact.phone }}
            </a>
            <div
              v-if="contact.address"
              class="flex items-start gap-3 text-gray-300"
            >
              <MapPin
                class="w-4 h-4 flex-shrink-0 mt-0.5"
                :style="{ color: branding.accentColor }"
              />
              {{ contact.address }}
            </div>
          </div>
        </div>

        <!-- Social -->
        <div>
          <h4 class="font-bold text-sm uppercase tracking-widest text-gray-400 mb-6">Ikuti Kami</h4>
          <div class="flex gap-3">
            <a
              v-if="social.instagram"
              :href="social.instagram"
              target="_blank"
              class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
            >
              <Instagram class="w-5 h-5" />
            </a>
            <a
              v-if="social.facebook"
              :href="social.facebook"
              target="_blank"
              class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
            >
              <Facebook class="w-5 h-5" />
            </a>
            <a
              v-if="social.youtube"
              :href="social.youtube"
              target="_blank"
              class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
            >
              <Youtube class="w-5 h-5" />
            </a>
            <a
              v-if="social.tiktok"
              :href="social.tiktok"
              target="_blank"
              class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 flex items-center justify-center transition-colors"
            >
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>
              </svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Google Maps Embed -->
      <div
        v-if="contact.mapsEmbed"
        class="mb-16 rounded-2xl overflow-hidden"
      >
        <iframe
          :src="contact.mapsEmbed"
          class="w-full h-64 border-0"
          allowfullscreen
          loading="lazy"
        ></iframe>
      </div>

      <!-- Divider & Copyright -->
      <div class="border-t border-gray-800 pt-8">
        <div
          class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm text-gray-500"
        >
          <p>
            &copy; {{ new Date().getFullYear() }} {{ data?.meta_title || (branding?.entityName || 'Instansi') }}. Hak Cipta Dilindungi.
          </p>
          <p>Powered by <span class="font-semibold text-gray-400">Gerbang Sekolah</span></p>
        </div>
      </div>
    </div>
  </footer>
</template>
