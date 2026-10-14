<script setup>
import { Mail, Phone, MapPin, ArrowRight } from 'lucide-vue-next'
</script>

<template>
  <section
    id="kontak"
    class="py-20 bg-gray-50"
  >
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-14">
        <span
          class="inline-block px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-semibold mb-3"
          >Hubungi Kami</span
        >
        <h2 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-4">
          Siap Bergabung Bersama Kami?
        </h2>
        <p class="text-gray-500 max-w-md mx-auto text-sm">
          Isi formulir di bawah dan tim kami akan menghubungi Anda untuk demonstrasi gratis sesuai
          kebutuhan sekolah atau yayasan Anda.
        </p>
      </div>

      <div class="grid md:grid-cols-2 gap-10 max-w-5xl mx-auto">
        <!-- Info Kontak -->
        <div class="space-y-5">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
              <Mail class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <div class="font-semibold text-gray-900 text-sm">Email</div>
              <div class="text-gray-500 text-sm">info@gerbangsekolah.id</div>
            </div>
          </div>

          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
              <Phone class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <div class="font-semibold text-gray-900 text-sm">Telepon / WhatsApp</div>
              <div class="text-gray-500 text-sm">+62 812-XXXX-XXXX</div>
            </div>
          </div>

          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center shrink-0">
              <MapPin class="w-5 h-5 text-blue-600" />
            </div>
            <div>
              <div class="font-semibold text-gray-900 text-sm">Kantor</div>
              <div class="text-gray-500 text-sm">Jakarta, Indonesia</div>
            </div>
          </div>

          <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5">
            <div class="font-semibold text-blue-800 mb-2 text-sm">Demo Gratis Tersedia</div>
            <p class="text-blue-600 text-sm leading-relaxed">
              Kami menyediakan sesi demo online gratis 30 menit khusus untuk kepala sekolah dan
              pengurus yayasan yang tertarik bergabung.
            </p>
          </div>
        </div>

        <!-- Form Kontak -->
        <form class="bg-white rounded-2xl shadow-md p-6 space-y-4 border border-gray-100">
          <div class="grid sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Nama Anda</label>
              <input
                type="text"
                placeholder="Nama lengkap"
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Jabatan</label>
              <input
                type="text"
                placeholder="Kepala Sekolah / Ketua Yayasan"
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1"
              >Nama Sekolah / Yayasan</label
            >
            <input
              type="text"
              placeholder="Nama institusi Anda"
              class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Nomor WhatsApp</label>
            <input
              type="tel"
              placeholder="08xx-xxxx-xxxx"
              class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
            />
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Pesan / Kebutuhan</label>
            <textarea
              rows="3"
              placeholder="Ceritakan kebutuhan sekolah atau yayasan Anda..."
              class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition resize-none"
            ></textarea>
          </div>

          <button
            type="submit"
            class="w-full py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all text-sm flex items-center justify-center gap-2"
          >
            Kirim & Jadwalkan Demo <ArrowRight class="w-4 h-4" />
          </button>
        </form>
      </div>
    </div>
  </section>
</template>
