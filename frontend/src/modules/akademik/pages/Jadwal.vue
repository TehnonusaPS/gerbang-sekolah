<script setup>
import { ref } from 'vue'
import { Plus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card } from '@/components/ui/card'
import { useJadwal } from '../composables/useJadwal'
import { useTeacherAgenda } from '../composables/useTeacherAgenda'
import ScheduleMonthYearPicker from '@/components/jadwal/ScheduleMonthYearPicker.vue'
import ScheduleCalendar from '@/components/jadwal/ScheduleCalendar.vue'
import ScheduleDetailPanel from '@/components/jadwal/ScheduleDetailPanel.vue'
import ScheduleUpcomingSheet from '@/components/jadwal/ScheduleUpcomingSheet.vue'
import TeacherAgendaSheet from '@/components/jadwal/TeacherAgendaSheet.vue'

const {
  role,
  selectedDate,
  visibleMonth,
  visibleYear,
  selectedDateDetails,
  upcomingEvents,
  getDateMarkers,
  getHolidayForDate,
  getExamsForDate,
  getAssignmentsForDate,
  fetchAgendas
} = useJadwal()

const {
  isSheetOpen: isTeacherSheetOpen,
  dialogMode,
  isSaving,
  isFromCalendarCell,
  form,
  formErrors,
  classroomOptions,
  subjectOptions,
  openAddDialog,
  openEditDialog,
  saveAgenda,
  handleDelete
} = useTeacherAgenda(() => {
  fetchAgendas()
})

const isUpcomingSheetOpen = ref(false)

const onSelectDate = (date) => {
  selectedDate.value = date
  const clickedMonth = date.getMonth()
  const clickedYear = date.getFullYear()
  if (clickedMonth !== visibleMonth.value || clickedYear !== visibleYear.value) {
    visibleMonth.value = clickedMonth
    visibleYear.value = clickedYear
  }
}

// Top button "+ Tambah Agenda" -> pick date allowed
const onTambahAgenda = () => {
  openAddDialog(selectedDate.value, false)
}

// Direct cell button "+ Tambah" -> date is fixed to cell date, no pick required!
const onCellAddAgenda = (dateStr) => {
  if (dateStr) {
    onSelectDate(new Date(dateStr))
  }
  openAddDialog(dateStr, true)
}
</script>

<template>
  <!-- Full height wrapper — no page scroll -->
  <div class="flex flex-col lg:h-[calc(100dvh-7rem)] lg:overflow-hidden h-auto overflow-visible">
    <!-- Main Two-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-[5fr_3fr] gap-5 lg:gap-6 flex-1 min-h-0">
      <!-- LEFT: Calendar Card (fixed height, no internal scroll) -->
      <Card class="relative flex flex-col overflow-hidden min-h-0 border-border/60 shadow-2xs">
        <!-- Month/Year Picker + Legend + Action Button -->
        <ScheduleMonthYearPicker
          v-model:month="visibleMonth"
          v-model:year="visibleYear"
        >
          <template #action v-if="role === 'guru'">
            <Button
              @click="onTambahAgenda"
              size="sm"
              class="h-7 text-xs font-bold rounded-lg gap-1.5 px-3 bg-primary text-primary-foreground hover:bg-primary/90 shadow-2xs cursor-pointer"
            >
              <Plus class="size-3.5" />
              Tambah Agenda
            </Button>
          </template>
        </ScheduleMonthYearPicker>

        <!-- Separator -->
        <div class="h-px bg-border/30 mx-4 shrink-0" />

        <!-- Full-size Calendar Grid -->
        <ScheduleCalendar
          :month="visibleMonth"
          :year="visibleYear"
          :selectedDate="selectedDate"
          :role="role"
          :getDateMarkers="getDateMarkers"
          :getHolidayForDate="getHolidayForDate"
          :getExamsForDate="getExamsForDate"
          :getAssignmentsForDate="getAssignmentsForDate"
          @select-date="onSelectDate"
          @add-agenda="onCellAddAgenda"
        />
      </Card>

      <!-- RIGHT: Detail Panel (only this scrolls) -->
      <ScheduleDetailPanel
        :role="role"
        :selectedDate="selectedDate"
        :selectedDateDetails="selectedDateDetails"
        :upcomingEvents="upcomingEvents"
        @view-all="isUpcomingSheetOpen = true"
        @edit-agenda="openEditDialog"
        @delete-agenda="handleDelete"
      />
    </div>

    <!-- Sheet: Lihat Semua Mendatang -->
    <ScheduleUpcomingSheet
      v-model:open="isUpcomingSheetOpen"
      :upcomingEvents="upcomingEvents"
    />

    <!-- Sheet: Tambah / Edit Agenda Guru (Ultra Modern Slide-over) -->
    <TeacherAgendaSheet
      v-model:open="isTeacherSheetOpen"
      :mode="dialogMode"
      :isSaving="isSaving"
      :isFromCalendarCell="isFromCalendarCell"
      :form="form"
      :formErrors="formErrors"
      :classroomOptions="classroomOptions"
      :subjectOptions="subjectOptions"
      @save="saveAgenda"
    />
  </div>
</template>
