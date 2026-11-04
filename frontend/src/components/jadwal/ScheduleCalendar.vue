<script setup lang="ts">
import { computed, ref, watch } from 'vue';
// CalendarRoot + CellTrigger from reka-ui directly (avoid shadcn button variant sizing)
import { CalendarRoot, CalendarCellTrigger } from 'reka-ui';
// Grid structure components from shadcn (thin wrappers)
import {
  CalendarGrid,
  CalendarGridHead,
  CalendarGridBody,
  CalendarGridRow,
  CalendarHeadCell,
  CalendarCell
} from '@/components/ui/calendar';
import { Badge } from '@/components/ui/badge';
import { Plus } from 'lucide-vue-next';
import { CalendarDate, getLocalTimeZone } from '@internationalized/date';
import { formatDateISO } from '@/modules/akademik/composables/useJadwal';
import './ScheduleCalendar.css';

const props = defineProps<{
  month: number; // 0-11
  year: number;
  selectedDate: Date;
  role?: string;
  getDateMarkers: (dateStr: string) => { isHoliday: boolean; isSunday: boolean; isExam: boolean; isAssignment: boolean; isLesson: boolean, isActivity: boolean };
  getHolidayForDate: (dateStr: string) => any;
  getExamsForDate: (dateStr: string) => any[];
  getAssignmentsForDate: (dateStr: string) => any[];
}>();

const emit = defineEmits<{
  (e: 'select-date', date: Date): void;
  (e: 'add-agenda', dateStr: string): void;
}>();

const WEEKDAYS = ['SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB', 'MING'];

// v-model bridge: JS Date ↔ CalendarDate
const modelValue = computed({
  get() {
    const d = props.selectedDate;
    return new CalendarDate(d.getFullYear(), d.getMonth() + 1, d.getDate());
  },
  set(val) {
    if (val) {
      emit('select-date', val.toDate(getLocalTimeZone()));
    }
  }
});

// Placeholder controls which month is displayed
const placeholder = ref<any>(new CalendarDate(props.year, props.month + 1, 1));

watch([() => props.year, () => props.month], ([y, m]) => {
  placeholder.value = new CalendarDate(y, m + 1, 1);
});

const todayStr = formatDateISO(new Date());
const selectedDateStr = computed(() => formatDateISO(props.selectedDate));

// Build dynamic classes for a day cell
const getCellClasses = (weekDate: any, monthValue: any) => {
  const dateStr = weekDate.toString();
  const markers = props.getDateMarkers(dateStr);
  const isOutside = weekDate.month !== monthValue.month;
  const isToday = dateStr === todayStr;
  const isSelected = dateStr === selectedDateStr.value;

  return [
    'sched-cell group/cell',
    isOutside && 'sched-cell--outside',
    isToday && 'sched-cell--today',
    isSelected && 'sched-cell--selected',
    (markers.isHoliday || markers.isSunday) && 'sched-cell--holiday',
    markers.isExam && 'sched-cell--exam'
  ].filter(Boolean).join(' ');
};

const getExamBadgeText = (dateStr: string) => {
  const exams = props.getExamsForDate(dateStr)
  if (exams && exams.length > 0) {
    const title = (exams[0].event_title || exams[0].nama || '').toUpperCase()
    if (title.includes('UTS') || title.includes('MID') || title.includes('PTS')) return 'UTS'
    if (title.includes('UAS') || title.includes('PAS') || title.includes('SAT') || title.includes('FINAL')) return 'UAS'
  }
  return 'Ujian'
};

const handleCellAddAgenda = (dateStr: string) => {
  emit('add-agenda', dateStr);
};
</script>

<template>
  <div class="flex-1 min-h-0 flex flex-col px-3 pb-3">
    <CalendarRoot v-slot="{ grid }" v-model="modelValue" v-model:placeholder="placeholder" locale="id-ID"
      :week-starts-on="1" class="sched-calendar-root flex-1 min-h-0 flex flex-col">
      <CalendarGrid v-for="month in grid" :key="month.value.toString()" class="sched-grid flex-1">
        <!-- Weekday header row -->
        <CalendarGridHead>
          <CalendarGridRow class="sched-row">
            <CalendarHeadCell v-for="day in WEEKDAYS" :key="day" class="sched-head-cell">
              {{ day }}
            </CalendarHeadCell>
          </CalendarGridRow>
        </CalendarGridHead>

        <!-- Day cells -->
        <CalendarGridBody class="sched-body flex-1 min-h-0">
          <CalendarGridRow v-for="(weekDates, index) in month.rows" :key="`weekDate-${index}`"
            class="sched-row sched-row--body">
            <CalendarCell v-for="weekDate in weekDates" :key="weekDate.toString()" :date="weekDate" class="sched-td relative group/cell">
              <!-- Raw reka-ui CellTrigger -->
              <CalendarCellTrigger :day="weekDate" :month="month.value" :class="getCellClasses(weekDate, month.value)">
                <!-- Day number -->
                <div class="flex items-center justify-between w-full h-4">
                  <span class="sched-day-num">{{ weekDate.day }}</span>
                </div>

                <!-- Micro badges -->
                <div class="sched-badges">
                  <Badge v-if="getDateMarkers(weekDate.toString()).isAssignment" variant="green" showDot pulse
                    class="sched-micro-badge">Agenda</Badge>
                  <Badge v-if="getDateMarkers(weekDate.toString()).isLesson" variant="outline" showDot pulse
                    class="sched-micro-badge bg-primary/10 text-primary border-none font-bold">KBM</Badge>
                  <Badge v-if="getDateMarkers(weekDate.toString()).isHoliday" variant="red" showDot pulse
                    :title="getHolidayForDate(weekDate.toString())?.localName" class="sched-micro-badge">Libur</Badge>
                  <Badge v-if="getDateMarkers(weekDate.toString()).isExam" variant="purple" showDot pulse
                    :title="getExamsForDate(weekDate.toString())[0]?.event_title || 'Ujian'" class="sched-micro-badge">{{ getExamBadgeText(weekDate.toString()) }}</Badge>
                  <Badge v-if="getDateMarkers(weekDate.toString()).isActivity" variant="outline" showDot pulse
                    class="sched-micro-badge bg-amber-500/10 text-amber-600 dark:text-amber-400 border-none font-bold">
                    Kegiatan</Badge>
                </div>
              </CalendarCellTrigger>

              <!-- "+ Tambah" button for Guru -->
              <button
                v-if="role === 'guru' && weekDate.month === month.value.month"
                type="button"
                @click.stop="handleCellAddAgenda(weekDate.toString())"
                class="absolute top-1.5 right-1.5 z-10 opacity-0 group-hover/cell:opacity-100 transition-opacity px-1.5 py-0.5 rounded-full border border-dashed border-primary/60 bg-primary/10 hover:bg-primary text-primary hover:text-primary-foreground text-[9px] font-bold flex items-center gap-0.5 shadow-2xs cursor-pointer"
                title="Tambah Agenda pada tanggal ini"
              >
                <Plus class="size-2.5" />
                <span>Tambah</span>
              </button>
            </CalendarCell>
          </CalendarGridRow>
        </CalendarGridBody>
      </CalendarGrid>
    </CalendarRoot>
  </div>
</template>
