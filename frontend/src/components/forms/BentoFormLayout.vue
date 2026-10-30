<script setup>
defineProps({
  // Opsional: properti tambahan jika dibutuhkan di masa mendatang
})
</script>

<template>
  <!-- Layout Bento 2-Kolom Proporsional Standar Gerbang Sekolah (Kiri 35% : Kanan 65%) -->
  <div class="grid grid-cols-1 md:grid-cols-[35fr_65fr] gap-6 text-left items-stretch">
    <!-- ================= KOLOM KIRI (35% LEBAR) ================= -->
    <div class="flex flex-col gap-6 h-full [&>*:last-child]:flex-1">
      <slot name="left" />
    </div>

    <!-- ================= KOLOM KANAN (65% LEBAR) ================= -->
    <div class="flex flex-col gap-6 h-full [&>*:last-child]:flex-1">
      <slot name="right" />
    </div>
  </div>
</template>
