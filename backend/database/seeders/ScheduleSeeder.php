<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicYear = AcademicYear::where('is_active', true)->first();
        $classroom = $academicYear ? Classroom::where('academic_year_id', $academicYear->id)->first() : null;
        $subjects = Subject::where('is_active', true)->get();
        $timeSlots = TimeSlot::where('is_break', false)->orderBy('slot_number')->get();
        $teachers = User::whereHas('role', function($q) {
            $q->where('name', 'guru');
        })->get();

        if (!$academicYear || !$classroom || $subjects->isEmpty() || $timeSlots->isEmpty() || $teachers->isEmpty()) {
            return;
        }

        // We will seed schedules for Monday (day 1) and Tuesday (day 2)
        // Monday:
        // Slot 1 & 2: Matematika (Guru Budi)
        // Slot 3 & 5: Bahasa Indonesia (Guru Pengajar 2)
        
        $mtk = $subjects->firstWhere('code', 'MTK') ?: $subjects->first();
        $bin = $subjects->firstWhere('code', 'BIN') ?: $subjects->first();

        $teacher1 = $teachers->first();
        $teacher2 = $teachers->skip(1)->first() ?: $teachers->first();

        // Find time slot ids
        $slot1 = $timeSlots->firstWhere('slot_number', 1);
        $slot2 = $timeSlots->firstWhere('slot_number', 2);
        $slot3 = $timeSlots->firstWhere('slot_number', 3);
        $slot4 = $timeSlots->firstWhere('slot_number', 5); // slot_number 4 is break, so slot_number 5 is 4th study period

        if ($slot1 && $slot2 && $mtk && $teacher1) {
            Schedule::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'classroom_id'     => $classroom->id,
                    'time_slot_id'     => $slot1->id,
                    'day_of_week'      => 1
                ],
                [
                    'school_id'  => $classroom->school_id,
                    'subject_id' => $mtk->id,
                    'teacher_id' => $teacher1->id
                ]
            );

            Schedule::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'classroom_id'     => $classroom->id,
                    'time_slot_id'     => $slot2->id,
                    'day_of_week'      => 1
                ],
                [
                    'school_id'  => $classroom->school_id,
                    'subject_id' => $mtk->id,
                    'teacher_id' => $teacher1->id
                ]
            );
        }

        if ($slot3 && $slot4 && $bin && $teacher2) {
            Schedule::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'classroom_id'     => $classroom->id,
                    'time_slot_id'     => $slot3->id,
                    'day_of_week'      => 1
                ],
                [
                    'school_id'  => $classroom->school_id,
                    'subject_id' => $bin->id,
                    'teacher_id' => $teacher2->id
                ]
            );

            Schedule::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'classroom_id'     => $classroom->id,
                    'time_slot_id'     => $slot4->id,
                    'day_of_week'      => 1
                ],
                [
                    'school_id'  => $classroom->school_id,
                    'subject_id' => $bin->id,
                    'teacher_id' => $teacher2->id
                ]
            );
        }
    }
}
