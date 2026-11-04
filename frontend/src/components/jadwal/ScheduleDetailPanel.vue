<script setup lang="ts">
import { computed } from 'vue';
import { Sparkles, CalendarDays, ArrowRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import ScheduleEventCard from './ScheduleEventCard.vue';

const props = defineProps<{
  role?: string;
  selectedDate: Date;
  selectedDateDetails: {
    dateStr: string;
    dateObj: Date;
    holiday: any;
    lessons: any[];
    exams: any[];
    assignments: any[];
    calendarEvents: any[];
  };
  upcomingEvents: any[];
}>();

const emit = defineEmits<{
  (e: 'view-all'): void;
  (e: 'edit-agenda', agenda: any): void;
  (e: 'delete-agenda', id: number): void;
}>();

const formattedSelectedDate = computed(() => {
  return props.selectedDate.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
});

const limitedUpcomingEvents = computed(() => {
  return props.upcomingEvents.slice(0, 3);
});

const hasNoEvents = computed(() => {
  return !props.selectedDateDetails.holiday &&
    props.selectedDateDetails.exams.length === 0 &&
    props.selectedDateDetails.assignments.length === 0 &&
    props.selectedDateDetails.lessons.length === 0 &&
    (!props.selectedDateDetails.calendarEvents || props.selectedDateDetails.calendarEvents.length === 0);
});

const getTaskSubtitle = (task: any) => {
  const parts: string[] = [];
  if (task.classroom_name) {
    parts.push(task.classroom_name);
  }
  if (task.subject_name) {
    parts.push(task.subject_name);
  }
  if (parts.length > 0) {
    return parts.join(' • ');
  }
  return task.mapel || task.classroom_name || 'Umum';
};
</script>

<template>
  <Card class="flex flex-col overflow-hidden min-h-0 border-border/60 shadow-2xs">
    <!-- Header (shrink-0 — tidak ikut scroll) -->
    <CardHeader class="px-5 py-4 border-b border-border/30 shrink-0 space-y-1">
      <div class="flex items-center gap-2">
        <CalendarDays class="size-4 text-primary" />
        <CardTitle class="text-sm font-bold">Detail Jadwal & Kegiatan</CardTitle>
      </div>
      <p class="text-xs text-primary font-semibold">
        {{ formattedSelectedDate }}
      </p>
    </CardHeader>

    <!-- Scrollable Content — hanya bagian ini yang bisa scroll -->
    <ScrollArea class="flex-1 min-h-0">
      <div class="px-5 py-4 space-y-5">

        <!-- Section 1: Agenda Terpilih -->
        <div class="space-y-3">
          <h3 class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
            Agenda Tanggal Ini
          </h3>

          <ScheduleEventCard v-if="selectedDateDetails.holiday" type="holiday"
            :title="selectedDateDetails.holiday.localName" subtitle="Libur" time="Sepanjang Hari" />

          <!-- Calendar Events from Database (Kegiatan, Ujian, Libur) -->
          <div v-if="selectedDateDetails.calendarEvents && selectedDateDetails.calendarEvents.length > 0"
            class="space-y-2.5">
            <ScheduleEventCard v-for="event in selectedDateDetails.calendarEvents" :key="`cal-${event.id}`"
              :type="event.type === 'libur_nasional' || event.type === 'tanggal_merah' ? 'holiday' : (event.type === 'ujian' ? 'exam' : 'assignment')"
              :title="event.title"
              :subtitle="event.type === 'libur_nasional' || event.type === 'tanggal_merah' ? 'Hari Libur' : (event.type === 'ujian' ? 'Ujian Akademik' : 'Kegiatan Sekolah')"
              :time="event.startDate === event.endDate ? 'Sepanjang Hari' : `${event.startDate} s.d ${event.endDate}`"
              :description="event.description" />
          </div>

          <div v-if="selectedDateDetails.exams.length > 0" class="space-y-2.5">
            <ScheduleEventCard 
              v-for="exam in selectedDateDetails.exams" 
              :key="exam.id" 
              type="exam" 
              :title="`Ujian ${exam.nama}`"
              :subtitle="exam.event_title ? `${exam.event_title} (${exam.mapel})` : exam.mapel" 
              :time="exam.waktu" 
              :location="exam.ruang" 
            />
          </div>

          <!-- Teacher Agendas / Assignments -->
          <div v-if="selectedDateDetails.assignments.length > 0" class="space-y-2.5">
            <ScheduleEventCard
              v-for="task in selectedDateDetails.assignments"
              :key="`agenda-${task.id}`"
              :type="task.type || 'tugas'"
              :title="task.title || task.nama"
              :subtitle="task.classroom_name || 'Semua Kelas'"
              :subject="task.subject_name || undefined"
              :time="task.time || (task.date === task.end_date ? 'Satu Hari' : `${task.date} s.d ${task.end_date}`)"
              :description="task.description || task.deskripsi"
              :canManage="role === 'guru'"
              @edit="emit('edit-agenda', task)"
              @delete="emit('delete-agenda', task.id)"
            />
          </div>

          <div v-if="selectedDateDetails.lessons.length > 0" class="space-y-2.5">
            <ScheduleEventCard 
              v-for="lesson in selectedDateDetails.lessons" 
              :key="lesson.id" 
              :type="lesson.is_break ? 'break' : 'lesson'"
              :title="lesson.mapel" 
              :subtitle="lesson.is_break ? 'Sesi Istirahat Sekolah' : lesson.kelas" 
              :time="`${lesson.mulai} - ${lesson.selesai} WIB`"
              :location="lesson.ruang" 
              :guru="lesson.guru" 
            />
          </div>

          <!-- Empty State -->
          <div v-if="hasNoEvents"
            class="flex flex-col items-center justify-center py-8 text-center bg-muted/10 border border-dashed border-border/30 rounded-xl">
            <Sparkles class="size-6 text-muted-foreground/30 mb-2" />
            <p class="text-xs text-muted-foreground px-6 leading-relaxed">
              Tidak ada jadwal pelajaran atau kegiatan sekolah pada tanggal ini.
            </p>
          </div>
        </div>

        <!-- Divider -->
        <div class="h-px bg-border/20" />

        <!-- Section 2: Mendatang -->
        <div class="space-y-3">
          <div class="flex items-center justify-between">
            <h3 class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">
              Mendatang
            </h3>
            <Button v-if="upcomingEvents.length > 3" variant="link" size="sm"
              class="h-auto p-0 text-xs font-bold text-primary hover:no-underline flex items-center gap-0.5"
              @click="emit('view-all')">
              Lihat Semua
              <ArrowRight class="size-3" />
            </Button>
          </div>

          <div v-if="upcomingEvents.length === 0" class="text-center py-6 text-xs text-muted-foreground">
            Tidak ada agenda mendatang.
          </div>

          <div v-else class="space-y-3">
            <div v-for="event in limitedUpcomingEvents" :key="event.id"
              class="relative pl-3.5 border-l-2 border-primary/20 space-y-1.5">
              <span class="text-[10px] font-bold text-primary bg-primary/10 px-2.5 py-0.5 rounded-full inline-block">
                {{ new Date(event.tanggal).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }) }}
              </span>
              <ScheduleEventCard
                :type="event.type"
                :title="event.title"
                :subtitle="event.subtitle"
                :time="event.time"
                :location="event.location"
                :canManage="role === 'guru' && event.isTeacherAgenda"
                @edit="emit('edit-agenda', event.agendaData)"
                @delete="emit('delete-agenda', event.agendaId)"
              />
            </div>
          </div>
        </div>

      </div>
    </ScrollArea>
  </Card>
</template>
