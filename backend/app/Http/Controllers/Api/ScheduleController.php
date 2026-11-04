<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Http\Traits\HasSchoolScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScheduleController extends Controller
{
    use HasSchoolScope;

    /**
     * Display a listing of schedules.
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        if ($schoolId === -1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized school access.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id'     => 'nullable|exists:classrooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Schedule::with(['subject', 'teacher', 'timeSlot', 'classroom'])
            ->where('academic_year_id', $request->input('academic_year_id'));

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->has('classroom_id')) {
            $query->where('classroom_id', $request->input('classroom_id'));
        }

        $schedules = $query->get();

        return response()->json([
            'status' => 'success',
            'data'   => $schedules
        ]);
    }

    /**
     * Store a newly created schedule.
     */
    public function store(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        if (!$schoolId || $schoolId === -1) {
            return response()->json(['status' => 'error', 'message' => 'School ID is required.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id'     => 'required|exists:classrooms,id',
            'subject_id'       => 'required|exists:subjects,id',
            'teacher_id'       => 'required|exists:users,id',
            'time_slot_id'     => 'required|exists:time_slots,id',
            'day_of_week'      => 'required|integer|min:1|max:7',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['school_id'] = $schoolId;

        // Perform validations
        $err = $this->validateScheduleConflict($data);
        if ($err) {
            return response()->json([
                'status'  => 'error',
                'message' => $err
            ], 422);
        }

        $schedule = Schedule::create($data);

        // Revert all schedules for this classroom to draft whenever a modification is made
        $this->revertClassroomScheduleToDraft($schedule->academic_year_id, $schedule->classroom_id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Schedule created successfully.',
            'data'    => $schedule
        ], 201);
    }

    /**
     * Bulk store schedules.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        if (!$schoolId || $schoolId === -1) {
            return response()->json(['status' => 'error', 'message' => 'School ID is required.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id'     => 'required|exists:classrooms,id',
            'schedules'        => 'required|array',
            'schedules.*.subject_id'   => 'required|exists:subjects,id',
            'schedules.*.teacher_id'   => 'required|exists:users,id',
            'schedules.*.time_slot_id' => 'required|exists:time_slots,id',
            'schedules.*.day_of_week'  => 'required|integer|min:1|max:7',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $academicYearId = $request->input('academic_year_id');
        $classroomId = $request->input('classroom_id');
        $schedulesData = $request->input('schedules');

        DB::beginTransaction();
        try {
            // Delete existing schedules for this classroom to overwrite
            Schedule::where('academic_year_id', $academicYearId)
                ->where('classroom_id', $classroomId)
                ->delete();

            $created = [];
            foreach ($schedulesData as $item) {
                $item['school_id'] = $schoolId;
                $item['academic_year_id'] = $academicYearId;
                $item['classroom_id'] = $classroomId;
                $item['status'] = 'draft';

                $err = $this->validateScheduleConflict($item);
                if ($err) {
                    DB::rollBack();
                    return response()->json([
                        'status'  => 'error',
                        'message' => $err
                    ], 422);
                }

                $created[] = Schedule::create($item);
            }

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Schedules saved successfully.',
                'data'    => $created
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to save bulk schedules: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified schedule.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json(['status' => 'error', 'message' => 'Schedule not found.'], 404);
        }

        if ($schoolId && $schedule->school_id !== $schoolId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'subject_id'   => 'sometimes|required|exists:subjects,id',
            'teacher_id'   => 'sometimes|required|exists:users,id',
            'time_slot_id' => 'sometimes|required|exists:time_slots,id',
            'day_of_week'  => 'sometimes|required|integer|min:1|max:7',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = array_merge($schedule->toArray(), $validator->validated());
        $data['id'] = $schedule->id;

        $err = $this->validateScheduleConflict($data, $schedule->id);
        if ($err) {
            return response()->json([
                'status'  => 'error',
                'message' => $err
            ], 422);
        }

        $schedule->update($validator->validated());

        // Revert all schedules for this classroom to draft whenever a modification is made
        $this->revertClassroomScheduleToDraft($schedule->academic_year_id, $schedule->classroom_id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Schedule updated successfully.',
            'data'    => $schedule
        ]);
    }

    /**
     * Remove the specified schedule.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json(['status' => 'error', 'message' => 'Schedule not found.'], 404);
        }

        if ($schoolId && $schedule->school_id !== $schoolId) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $ayId = $schedule->academic_year_id;
        $classId = $schedule->classroom_id;

        $schedule->delete();

        // Revert all remaining schedules for this classroom to draft whenever a modification is made
        $this->revertClassroomScheduleToDraft($ayId, $classId);

        return response()->json([
            'status'  => 'success',
            'message' => 'Schedule deleted successfully.'
        ]);
    }

    /**
     * Helper to revert classroom schedule status to draft.
     */
    private function revertClassroomScheduleToDraft(int $academicYearId, int $classroomId): void
    {
        Schedule::where('academic_year_id', $academicYearId)
            ->where('classroom_id', $classroomId)
            ->update(['status' => 'draft']);
    }

    /**
     * Get subjects that have NOT been assigned schedules in a classroom.
     */
    public function unassignedSubjects(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        if ($schoolId === -1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized school access.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id'     => 'required|exists:classrooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $academicYearId = $request->input('academic_year_id');
        $classroomId = $request->input('classroom_id');

        // Get subjects assigned to this classroom's school
        $allSubjects = Subject::where('school_id', $schoolId)->where('is_active', true)->get();

        // Get subjects that are already scheduled in this classroom for the selected semester
        $scheduledSubjectIds = Schedule::where('academic_year_id', $academicYearId)
            ->where('classroom_id', $classroomId)
            ->pluck('subject_id')
            ->unique()
            ->toArray();

        $unassigned = $allSubjects->filter(function ($sub) use ($scheduledSubjectIds) {
            return !in_array($sub->id, $scheduledSubjectIds);
        })->values();

        return response()->json([
            'status' => 'success',
            'data'   => $unassigned
        ]);
    }

    /**
     * Checks if a teacher has a schedule conflict.
     */
    public function teacherConflicts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'teacher_id'       => 'required|exists:users,id',
            'time_slot_id'     => 'required|exists:time_slots,id',
            'day_of_week'      => 'required|integer|min:1|max:7',
            'exclude_id'       => 'nullable|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Schedule::where('academic_year_id', $request->input('academic_year_id'))
            ->where('teacher_id', $request->input('teacher_id'))
            ->where('time_slot_id', $request->input('time_slot_id'))
            ->where('day_of_week', $request->input('day_of_week'))
            ->with(['classroom', 'subject']);

        if ($request->has('exclude_id')) {
            $query->where('id', '!=', $request->input('exclude_id'));
        }

        $conflict = $query->first();

        return response()->json([
            'status'      => 'success',
            'has_conflict' => !empty($conflict),
            'conflict'    => $conflict
        ]);
    }

    /**
     * Publish schedules for a classroom in an academic year.
     */
    public function publish(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        if (!$schoolId || $schoolId === -1) {
            return response()->json(['status' => 'error', 'message' => 'School ID is required.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id'     => 'required|exists:classrooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updatedCount = Schedule::where('school_id', $schoolId)
            ->where('academic_year_id', $request->input('academic_year_id'))
            ->where('classroom_id', $request->input('classroom_id'))
            ->update(['status' => 'published']);

        return response()->json([
            'status'  => 'success',
            'message' => "Berhasil mempublikasikan {$updatedCount} jadwal pelajaran. Jadwal kini dapat dilihat oleh guru, siswa, dan orang tua.",
            'count'   => $updatedCount
        ]);
    }

    /**
     * Unpublish (revert to draft) schedules for a classroom in an academic year.
     */
    public function unpublish(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        if (!$schoolId || $schoolId === -1) {
            return response()->json(['status' => 'error', 'message' => 'School ID is required.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id'     => 'required|exists:classrooms,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $updatedCount = Schedule::where('school_id', $schoolId)
            ->where('academic_year_id', $request->input('academic_year_id'))
            ->where('classroom_id', $request->input('classroom_id'))
            ->update(['status' => 'draft']);

        return response()->json([
            'status'  => 'success',
            'message' => "Jadwal pelajaran berhasil ditarik kembali menjadi Draft.",
            'count'   => $updatedCount
        ]);
    }

    /**
     * Get the schedule for the current logged-in teacher.
     */
    public function mySchedule(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('guru')) {
            return response()->json(['status' => 'error', 'message' => 'Only teachers can access this.'], 403);
        }

        // Get the active academic year
        $activeYear = \App\Models\AcademicYear::where('school_id', $user->school_id)
            ->where('is_active', true)
            ->first();

        if (!$activeYear) {
            return response()->json(['status' => 'error', 'message' => 'No active academic year found.'], 404);
        }

        $schedules = Schedule::with(['subject', 'classroom', 'timeSlot'])
            ->where('academic_year_id', $activeYear->id)
            ->where('teacher_id', $user->id)
            ->published()
            ->get();

        // Format to weekly grouped array: day_of_week => [lessons]
        $formatted = [];
        for ($i = 1; $i <= 7; $i++) {
            $formatted[$i] = [];
        }

        foreach ($schedules as $sched) {
            $formatted[$sched->day_of_week][] = [
                'id'       => $sched->id,
                'mulai'    => $sched->timeSlot ? substr($sched->timeSlot->start_time, 0, 5) : '',
                'selesai'  => $sched->timeSlot ? substr($sched->timeSlot->end_time, 0, 5) : '',
                'mapel'    => $sched->subject ? $sched->subject->name : '',
                'kelas'    => $sched->classroom ? $sched->classroom->name : '',
                'ruang'    => $sched->classroom ? $sched->classroom->room : '',
                'guru'     => $sched->teacher ? $sched->teacher->name : '',
                'is_break' => $sched->timeSlot ? $sched->timeSlot->is_break : false
            ];
        }

        // Sort each day by slot number or start time
        foreach ($formatted as $day => &$lessons) {
            usort($lessons, function($a, $b) {
                return strcmp($a['mulai'], $b['mulai']);
            });
        }

        return response()->json([
            'status' => 'success',
            'data'   => $formatted
        ]);
    }

    /**
     * Get the schedule for the current logged-in student.
     */
    public function studentSchedule(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('siswa')) {
            return response()->json(['status' => 'error', 'message' => 'Only students can access this.'], 403);
        }

        $profile = $user->studentProfile;
        if (!$profile || !$profile->classroom_id) {
            return response()->json(['status' => 'error', 'message' => 'Student has no classroom assignment.'], 404);
        }

        $classroom = Classroom::find($profile->classroom_id);
        if (!$classroom) {
            return response()->json(['status' => 'error', 'message' => 'Classroom not found.'], 404);
        }

        $schedules = Schedule::with(['subject', 'teacher', 'timeSlot'])
            ->where('academic_year_id', $classroom->academic_year_id)
            ->where('classroom_id', $classroom->id)
            ->published()
            ->get();

        // Format to weekly grouped array: day_of_week => [lessons]
        $formatted = [];
        for ($i = 1; $i <= 7; $i++) {
            $formatted[$i] = [];
        }

        // If no published schedules exist for this classroom, return empty schedule array
        if ($schedules->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data'   => $formatted
            ]);
        }

        // Find which days have active published schedules
        $daysWithLessons = $schedules->pluck('day_of_week')->map(fn($d) => (int)$d)->unique()->toArray();

        // Inject all time slots including breaks to show a complete timetable
        $timeSlots = TimeSlot::where('school_id', $user->school_id)->orderBy('slot_number')->get();
        
        foreach ($timeSlots as $slot) {
            for ($day = 1; $day <= 7; $day++) {
                // Find matching schedule for this slot and day
                $sched = $schedules->first(function($s) use ($slot, $day) {
                    return $s->time_slot_id === $slot->id && (int)$s->day_of_week === $day;
                });

                if ($sched) {
                    $formatted[$day][] = [
                        'id'       => $sched->id,
                        'mulai'    => substr($slot->start_time, 0, 5),
                        'selesai'  => substr($slot->end_time, 0, 5),
                        'mapel'    => $sched->subject ? $sched->subject->name : '',
                        'kelas'    => $classroom->name,
                        'ruang'    => $classroom->room,
                        'guru'     => $sched->teacher ? $sched->teacher->name : '',
                        'is_break' => $slot->is_break
                    ];
                } elseif ($slot->is_break && in_array($day, $daysWithLessons)) {
                    // Inject breaks automatically ONLY for days that actually have scheduled lessons
                    $formatted[$day][] = [
                        'id'       => 'break-' . $slot->id,
                        'mulai'    => substr($slot->start_time, 0, 5),
                        'selesai'  => substr($slot->end_time, 0, 5),
                        'mapel'    => $slot->label ?: 'Istirahat',
                        'kelas'    => '',
                        'ruang'    => '',
                        'guru'     => '',
                        'is_break' => true
                    ];
                }
            }
        }

        // Sort each day by start time
        foreach ($formatted as $day => &$lessons) {
            usort($lessons, function($a, $b) {
                return strcmp($a['mulai'], $b['mulai']);
            });
        }

        return response()->json([
            'status' => 'success',
            'data'   => $formatted
        ]);
    }

    /**
     * Internal validator for conflicts.
     */
    private function validateScheduleConflict(array $data, ?int $excludeId = null): ?string
    {
        $queryClassConflict = Schedule::where('academic_year_id', $data['academic_year_id'])
            ->where('classroom_id', $data['classroom_id'])
            ->where('time_slot_id', $data['time_slot_id'])
            ->where('day_of_week', $data['day_of_week']);

        $queryTeacherConflict = Schedule::where('academic_year_id', $data['academic_year_id'])
            ->where('teacher_id', $data['teacher_id'])
            ->where('time_slot_id', $data['time_slot_id'])
            ->where('day_of_week', $data['day_of_week']);

        if ($excludeId) {
            $queryClassConflict->where('id', '!=', $excludeId);
            $queryTeacherConflict->where('id', '!=', $excludeId);
        }

        if ($queryClassConflict->exists()) {
            $className = Classroom::where('id', $data['classroom_id'])->value('name');
            return "Kelas {$className} sudah memiliki jadwal mata pelajaran lain pada slot waktu tersebut.";
        }

        if ($queryTeacherConflict->exists()) {
            $teacherName = \App\Models\User::where('id', $data['teacher_id'])->value('name');
            return "Guru {$teacherName} sudah terjadwal mengajar di kelas lain pada hari dan jam yang sama.";
        }

        // Check rule: "satu mata pelajaran tetap pada hari yang sama selama satu semester"
        $existingDay = Schedule::where('academic_year_id', $data['academic_year_id'])
            ->where('classroom_id', $data['classroom_id'])
            ->where('subject_id', $data['subject_id']);

        if ($excludeId) {
            $existingDay->where('id', '!=', $excludeId);
        }

        $day = $existingDay->value('day_of_week');

        if ($day && $day !== (int) $data['day_of_week']) {
            $subjectName = Subject::where('id', $data['subject_id'])->value('name');
            $daysMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
            $dayName = $daysMap[$day] ?? $day;
            return "Mata pelajaran {$subjectName} sudah dijadwalkan pada hari {$dayName} di semester ini untuk kelas ini. Harap letakkan pada hari yang sama.";
        }

        return null;
    }
}
