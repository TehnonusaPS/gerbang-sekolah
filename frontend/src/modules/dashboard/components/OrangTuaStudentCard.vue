<script setup>
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { GraduationCap } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/authStore'
import RollingNumber from '@/components/ui/rolling-number/RollingNumber.vue'
import Card from '@/components/ui/card/Card.vue'
import studentDummy from '@/assets/images/student-dummy.png'
import { getPhotoUrl } from '@/utils/getPhotoUrl'

const props = defineProps({
  delay: { type: Number, default: 0 }
})

const auth = useAuthStore()
const computedDelay = computed(() => (auth.isJustLoggedIn ? 1400 : 0) + props.delay)

const currentStudent = computed(() => {
  const pp = auth.user?.parent_profile || auth.user?.parentProfile
  return pp?.students?.[0] || null
})

const studentName = computed(() => currentStudent.value?.user?.name || 'Ahmad Wibowo')
const studentClass = computed(() => currentStudent.value?.classroom?.name || 'Kelas')
const studentNisn = computed(() => currentStudent.value?.nisn || '-')
const studentPhoto = computed(() => {
  const photo = currentStudent.value?.user?.photo
  return photo ? getPhotoUrl(photo) : studentDummy
})
</script>

<template>
  <Card
    v-motion
    :initial="{ opacity: 0, y: 30, scale: 0.98 }"
    :visible-once="{
      opacity: 1,
      y: 0,
      scale: 1,
      transition: { type: 'spring', stiffness: 200, damping: 20, mass: 0.8, delay: computedDelay }
    }"
    class="relative overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-1 group glass-btn flex flex-row min-h-[210px] w-full p-0 gap-0 rounded-[32px]"
  >
    <!-- Info sisi kiri — primary glass styling (vibrant & watermarked) -->
    <div
      class="flex flex-col justify-between p-6 bg-gradient-to-br from-primary/85 via-primary/80 to-primary/70 w-[60%] text-primary-foreground"
    >
      <!-- Watermark Icon Background -->
      <GraduationCap
        class="absolute -left-8 -bottom-8 size-36 opacity-[0.08] -rotate-12 pointer-events-none text-white"
      />

      <div class="space-y-1 relative z-10">
        <Badge
          class="text-[10px] mb-2 w-fit bg-primary/80 text-primary-foreground glass-btn rounded-md border-none shadow-none"
        >
          Siswa
        </Badge>
        <h2 class="text-xl font-bold leading-tight text-white">{{ studentName }}</h2>
        <p class="text-sm text-white/85 mt-0.5">Kelas {{ studentClass }}</p>
        <p class="text-xs text-white/65">NISN: {{ studentNisn }}</p>
      </div>

      <!-- Mini stats -->
      <div class="flex gap-3 mt-5 relative z-10">
        <div
          class="rounded-xl glass-btn bg-primary-foreground/10 px-4 py-3 text-left flex-1 transition-all duration-300 hover:bg-primary-foreground/15"
        >
          <p class="text-2xl font-bold text-white">
            <RollingNumber
              value="8.8"
              :delay="computedDelay"
            />
          </p>
          <p class="text-xs text-white/70 mt-1">Rata-rata</p>
        </div>
        <div
          class="rounded-xl glass-btn bg-primary-foreground/10 px-4 py-3 text-left flex-1 transition-all duration-300 hover:bg-primary-foreground/15"
        >
          <p class="text-2xl font-bold text-white">
            <RollingNumber
              value="95%"
              :delay="computedDelay"
            />
          </p>
          <p class="text-xs text-white/70 mt-1">Kehadiran</p>
        </div>
      </div>
    </div>

    <!-- Foto siswa sisi kanan -->
    <div class="relative flex-1 overflow-hidden">
      <img
        :src="studentPhoto"
        alt="Foto Siswa"
        class="absolute inset-0 w-full h-full object-cover object-top"
      />
      <div
        class="absolute inset-0 bg-gradient-to-r from-primary/70 via-transparent to-transparent pointer-events-none"
      />
    </div>
  </Card>
</template>
