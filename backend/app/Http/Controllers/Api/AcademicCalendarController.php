<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Traits\HasSchoolScope;
use App\Http\Traits\AcademicCalendarHelperTrait;
use App\Models\AcademicCalendarEvent;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\TimeSlot;
use App\Models\ExamSession;
use App\Services\AcademicCalendarService;
use App\Http\Requests\AcademicCalendar\SetupYearDatesRequest;
use App\Http\Requests\AcademicCalendar\BatchStoreEventsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AcademicCalendarController extends Controller
{
    use HasSchoolScope, AcademicCalendarHelperTrait;

    protected AcademicCalendarService $calendarService;

    public function __construct(AcademicCalendarService $calendarService)
    {
        $this->calendarService = $calendarService;
    }

    /**
     * Setup academic year dates (Odd & Even semesters) and return month buffer bounds.
     */
    public function setupYearDates(SetupYearDatesRequest $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        if (!$schoolId) {
            return response()->json(['status' => 'error', 'message' => 'School ID required.'], 400);
        }

        $result = $this->calendarService->setupYearDates(
            $schoolId,
            $request->odd_start_date,
            $request->odd_end_date,
            $request->even_start_date,
            $request->even_end_date
        );

        return response()->json([
            'status'        => 'success',
            'message'       => 'Rentang tanggal kalender & Tahun Ajaran berhasil dibuat.',
            'academic_year' => $result['yearResult']['name'],
            'data'          => $result['yearResult']['years'],
            'buffer'        => $result['bufferBounds'],
        ]);
    }

    /**
     * Get list of events for a specific academic year.
     */
    public function index(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $academicYearId = $request->query('academic_year_id');

        if (!$academicYearId) {
            $activeYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();
            $academicYearId = $activeYear ? $activeYear->id : AcademicYear::where('school_id', $schoolId)->latest()->value('id');
        }

        if (!$academicYearId) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $targetYear = AcademicYear::where('id', $academicYearId)
            ->where('school_id', $schoolId)
            ->first();

        $ayIds = !$targetYear ? [$academicYearId] : AcademicYear::where('school_id', $schoolId)->where('name', $targetYear->name)->pluck('id');

        $events = AcademicCalendarEvent::with('classroom')
            ->where('school_id', $schoolId)
            ->whereIn('academic_year_id', $ayIds)
            ->orderBy('start_date')
            ->get();

        $formatted = $events->map(function($e) {
            $startDate = is_string($e->getRawOriginal('start_date')) ? substr($e->getRawOriginal('start_date'), 0, 10) : $e->start_date->format('Y-m-d');
            $endDate = is_string($e->getRawOriginal('end_date')) ? substr($e->getRawOriginal('end_date'), 0, 10) : $e->end_date->format('Y-m-d');

            return [
                'id' => $e->id,
                'academic_year_id' => $e->academic_year_id,
                'classroom_id' => $e->classroom_id,
                'classroom_name' => $e->classroom ? $e->classroom->name : 'Semua Kelas',
                'title' => $e->title,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'type' => $e->type,
                'description' => $e->description
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formatted
        ]);
    }

    /**
     * Store a single new event.
     */
    public function store(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);

        $validator = Validator::make($request->all(), [
            'academic_year_id' => 'required|exists:academic_years,id',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:libur_nasional,libur_semester,libur_khusus,uts,uas,us,anbk,mpls,rapor,remedi,rapat_guru,kegiatan,p5,tanggal_merah,ujian',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $academicYear = AcademicYear::find($request->academic_year_id);
        if (!$academicYear) {
            return response()->json(['status' => 'error', 'message' => 'Academic Year not found.'], 404);
        }

        if ($academicYear->calendar_status === 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Kalender akademik sedang dalam pengajuan persetujuan Kepala Sekolah dan tidak dapat diubah.'], 422);
        }

        if ($request->classroom_id) {
            $classroom = Classroom::where('id', $request->classroom_id)->where('school_id', $schoolId)->exists();
            if (!$classroom) {
                return response()->json(['status' => 'error', 'message' => 'Classroom not found.'], 404);
            }
        }

        $event = AcademicCalendarEvent::create([
            'school_id' => $schoolId,
            'academic_year_id' => $request->academic_year_id,
            'classroom_id' => $request->classroom_id,
            'title' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'type' => $request->type,
            'description' => $request->description,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Agenda berhasil ditambahkan.',
            'data' => $event
        ], 201);
    }

    /**
     * Batch store events for an academic year (replaces existing draft events atomically).
     */
    public function batchStore(BatchStoreEventsRequest $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $academicYearId = $request->input('academic_year_id');

        try {
            $this->calendarService->batchStoreEvents(
                $schoolId,
                $academicYearId,
                $request->input('events', []),
                $request->user()->id
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Agenda kalender berhasil disimpan.'
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() === 422 ? 422 : 500;
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }

    /**
     * Update an existing event.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $event = AcademicCalendarEvent::where('id', $id)->where('school_id', $schoolId)->first();

        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Agenda tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'classroom_id' => 'nullable|exists:classrooms,id',
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:libur_nasional,libur_semester,libur_khusus,uts,uas,us,anbk,mpls,rapor,remedi,rapat_guru,kegiatan,p5,tanggal_merah,ujian',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $academicYear = AcademicYear::find($event->academic_year_id);
        if (!$academicYear) {
            return response()->json(['status' => 'error', 'message' => 'Academic Year not found.'], 404);
        }

        if ($academicYear->calendar_status === 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Kalender akademik sedang dalam pengajuan persetujuan Kepala Sekolah dan tidak dapat diubah.'], 422);
        }

        if ($request->classroom_id) {
            $classroom = Classroom::where('id', $request->classroom_id)->where('school_id', $schoolId)->exists();
            if (!$classroom) {
                return response()->json(['status' => 'error', 'message' => 'Classroom not found.'], 404);
            }
        }

        $event->update([
            'classroom_id' => $request->classroom_id,
            'title' => $request->title,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'type' => $request->type,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Agenda berhasil diperbarui.',
            'data' => $event
        ]);
    }

    /**
     * Delete an event.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $event = AcademicCalendarEvent::where('id', $id)->where('school_id', $schoolId)->first();

        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Agenda tidak ditemukan.'], 404);
        }

        $academicYear = AcademicYear::find($event->academic_year_id);
        if (!$academicYear) {
            return response()->json(['status' => 'error', 'message' => 'Academic Year not found.'], 404);
        }

        if ($academicYear->calendar_status === 'pending') {
            return response()->json(['status' => 'error', 'message' => 'Kalender akademik sedang dalam pengajuan persetujuan Kepala Sekolah dan tidak dapat diubah.'], 422);
        }

        $event->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Agenda berhasil dihapus.'
        ]);
    }

    /**
     * Submit academic calendar for approval.
     */
    public function submit(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $academicYearId = $request->input('academic_year_id');

        try {
            $academicYear = $this->calendarService->submitCalendar($schoolId, $academicYearId);
            return response()->json([
                'status' => 'success',
                'message' => 'Kalender akademik berhasil diajukan untuk disetujui.',
                'data' => $academicYear
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Approve academic calendar.
     */
    public function approve(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('kepala_sekolah')) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Kepala Sekolah yang dapat menyetujui kalender.'], 403);
        }

        $schoolId = $this->resolveSchoolId($request);
        $academicYearId = $request->input('academic_year_id');

        try {
            $academicYear = $this->calendarService->approveCalendar($schoolId, $academicYearId);
            return response()->json([
                'status' => 'success',
                'message' => 'Kalender akademik berhasil disetujui.',
                'data' => $academicYear
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Reject academic calendar.
     */
    public function reject(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('kepala_sekolah')) {
            return response()->json(['status' => 'error', 'message' => 'Hanya Kepala Sekolah yang dapat menolak kalender.'], 403);
        }

        $schoolId = $this->resolveSchoolId($request);
        $academicYearId = $request->input('academic_year_id');
        $reason = $request->input('reason');

        if (!$reason) {
            return response()->json(['status' => 'error', 'message' => 'Alasan penolakan wajib diisi.'], 422);
        }

        try {
            $academicYear = $this->calendarService->rejectCalendar($schoolId, $academicYearId, $reason);
            return response()->json([
                'status' => 'success',
                'message' => 'Kalender akademik berhasil ditolak.',
                'data' => $academicYear
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Get statuses of academic years' calendars.
     */
    public function calendarStatus(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $academicYears = AcademicYear::where('school_id', $schoolId)->get();

        $statuses = [];
        foreach ($academicYears as $ay) {
            $statuses[$ay->name] = [
                'status' => $ay->calendar_status,
                'rejectedReason' => $ay->calendar_rejected_reason,
                'academic_year_id' => $ay->id
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $statuses
        ]);
    }

    /**
     * Get approved events for active academic year.
     */
    public function publicEvents(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request);

        if (!$schoolId && $user->hasRole('orang_tua') && $user->parentProfile) {
            $child = $user->parentProfile->children()->first();
            if ($child) {
                $schoolId = $child->school_id ?: $child->user?->school_id;
            }
        }
        if (!$schoolId && $user->studentProfile) {
            $schoolId = $user->studentProfile->school_id;
        }
        if (!$schoolId && $user->teacherProfile) {
            $schoolId = $user->teacherProfile->school_id;
        }

        if (!$schoolId) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $activeYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();
        if (!$activeYear) {
            $activeYear = AcademicYear::where('school_id', $schoolId)->orderBy('id', 'desc')->first();
        }

        if (!$activeYear) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $ayIds = AcademicYear::where('school_id', $schoolId)
            ->where('name', $activeYear->name)
            ->pluck('id');

        $query = AcademicCalendarEvent::with('classroom')
            ->where('school_id', $schoolId)
            ->whereIn('academic_year_id', $ayIds);

        if ($activeYear->calendar_status !== 'approved') {
            if (!$user->hasRole('admin_sekolah') && !$user->hasRole('kepala_sekolah') && !$user->hasRole('guru')) {
                return response()->json(['status' => 'success', 'data' => []]);
            }
        }

        if ($user->hasRole('siswa')) {
            $profile = $user->studentProfile;
            if ($profile && $profile->classroom_id) {
                $query->where(function($q) use ($profile) {
                    $q->whereNull('classroom_id')->orWhere('classroom_id', $profile->classroom_id);
                });
            } else {
                $query->whereNull('classroom_id');
            }
        } elseif ($user->hasRole('orang_tua')) {
            $parentProfile = $user->parentProfile;
            if ($parentProfile) {
                $classroomIds = $parentProfile->children()->pluck('classroom_id')->filter()->toArray();
                if (!empty($classroomIds)) {
                    $query->where(function($q) use ($classroomIds) {
                        $q->whereNull('classroom_id')->orWhereIn('classroom_id', $classroomIds);
                    });
                } else {
                    $query->whereNull('classroom_id');
                }
            } else {
                $query->whereNull('classroom_id');
            }
        }

        $events = $query->orderBy('start_date')->get();

        $formatted = $events->map(function($e) {
            $startDate = is_string($e->getRawOriginal('start_date')) ? substr($e->getRawOriginal('start_date'), 0, 10) : $e->start_date->format('Y-m-d');
            $endDate = is_string($e->getRawOriginal('end_date')) ? substr($e->getRawOriginal('end_date'), 0, 10) : $e->end_date->format('Y-m-d');

            return [
                'id' => $e->id,
                'academic_year_id' => $e->academic_year_id,
                'classroom_id' => $e->classroom_id,
                'classroom_name' => $e->classroom ? $e->classroom->name : 'Semua Kelas',
                'title' => $e->title,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'type' => $e->type,
                'description' => $e->description
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $formatted
        ]);
    }

    /**
     * Get parent child schedule.
     */
    public function parentSchedule(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user->hasRole('orang_tua')) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
        }

        $parentProfile = $user->parentProfile;
        if (!$parentProfile) {
            return response()->json(['status' => 'error', 'message' => 'Parent profile not found.'], 404);
        }

        $children = $parentProfile->children;
        if ($children->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'children' => [],
                    'schedule' => []
                ]
            ]);
        }

        $childId = $request->query('child_id');
        $selectedChild = $childId ? $children->firstWhere('id', $childId) : $children->first();

        if (!$selectedChild) {
            return response()->json(['status' => 'error', 'message' => 'Child not found.'], 404);
        }

        $classroomId = $selectedChild->classroom_id;
        if (!$classroomId) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'children' => $children->map(function($c) {
                        return ['id' => $c->id, 'name' => $c->user ? $c->user->name : '', 'classroom_name' => $c->classroom ? $c->classroom->name : ''];
                    })->toArray(),
                    'selected_child' => ['id' => $selectedChild->id, 'name' => $selectedChild->user ? $selectedChild->user->name : '', 'classroom_name' => 'No classroom assignment'],
                    'schedule' => []
                ]
            ]);
        }

        $classroom = Classroom::find($classroomId);
        if (!$classroom) {
            return response()->json(['status' => 'error', 'message' => 'Classroom not found.'], 404);
        }

        $schoolId = $classroom->school_id;

        $schedules = Schedule::with(['subject', 'teacher', 'timeSlot'])
            ->where('academic_year_id', $classroom->academic_year_id)
            ->where('classroom_id', $classroom->id)
            ->published()
            ->get();

        $formatted = [];
        for ($i = 1; $i <= 7; $i++) {
            $formatted[$i] = [];
        }

        if ($schedules->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'children' => $children->map(function($c) {
                        return ['id' => $c->id, 'name' => $c->user ? $c->user->name : '', 'classroom_name' => $c->classroom ? $c->classroom->name : ''];
                    })->toArray(),
                    'selected_child' => ['id' => $selectedChild->id, 'name' => $selectedChild->user ? $selectedChild->user->name : '', 'classroom_name' => $classroom->name],
                    'schedule' => $formatted
                ]
            ]);
        }

        $daysWithLessons = $schedules->pluck('day_of_week')->map(fn($d) => (int)$d)->unique()->toArray();
        $timeSlots = TimeSlot::where('school_id', $classroom->school_id)->orderBy('slot_number')->get();

        foreach ($timeSlots as $slot) {
            for ($day = 1; $day <= 7; $day++) {
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

        foreach ($formatted as $day => &$lessons) {
            usort($lessons, function($a, $b) {
                return strcmp($a['mulai'], $b['mulai']);
            });
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'children' => $children->map(function($c) {
                    return ['id' => $c->id, 'name' => $c->user ? $c->user->name : '', 'classroom_name' => $c->classroom ? $c->classroom->name : ''];
                })->toArray(),
                'selected_child' => ['id' => $selectedChild->id, 'name' => $selectedChild->user ? $selectedChild->user->name : '', 'classroom_name' => $classroom->name],
                'schedule' => $formatted
            ]
        ]);
    }

    /**
     * Complete dashboard overview for Parent (Jadwal Anak Saya).
     */
    public function parentDashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->hasRole('orang_tua') && !$user->hasRole('siswa')) {
                return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
            }

            if ($user->hasRole('siswa')) {
                $studentProfile = $user->studentProfile;
                if (!$studentProfile) {
                    return response()->json(['status' => 'error', 'message' => 'Student profile not found.'], 404);
                }
                $children = collect([$studentProfile]);
            } else {
                $parentProfile = $user->parentProfile;
                if (!$parentProfile) {
                    return response()->json(['status' => 'error', 'message' => 'Parent profile not found.'], 404);
                }
                $children = $parentProfile->children()->with(['user', 'classroom'])->get();
            }
            if ($children->isEmpty()) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'children'        => [],
                        'selected_child'  => null,
                        'weekly_schedule' => [],
                        'upcoming_exams'  => [],
                        'upcoming_events' => []
                    ]
                ]);
            }

            $childId = $request->query('child_id');
            $selectedChild = $childId ? $children->firstWhere('id', (int)$childId) : $children->first();

            if (!$selectedChild) {
                $selectedChild = $children->first();
            }

            $childrenList = $children->map(function($c) {
                return [
                    'id'             => $c->id,
                    'name'           => $c->user ? $c->user->name : '',
                    'classroom_name' => $c->classroom ? $c->classroom->name : '',
                    'grade'          => $c->classroom ? $c->classroom->grade : null,
                ];
            })->toArray();

            $classroomId = $selectedChild->classroom_id;
            $classroom = $classroomId ? Classroom::find($classroomId) : null;
            $schoolId = $classroom ? $classroom->school_id : ($selectedChild->user ? $selectedChild->user->school_id : null);
            $grade = $classroom ? $classroom->grade : null;

            $selectedChildData = [
                'id'             => $selectedChild->id,
                'name'           => $selectedChild->user ? $selectedChild->user->name : '',
                'classroom_name' => $classroom ? $classroom->name : 'Belum Ada Kelas',
                'grade'          => $grade,
                'room'           => $classroom ? $classroom->room : '',
            ];

            // 1. Weekly Schedule
            $weeklySchedule = [];
            for ($i = 1; $i <= 7; $i++) {
                $weeklySchedule[$i] = [];
            }

            if ($classroom) {
                $schedules = Schedule::with(['subject', 'teacher', 'timeSlot'])
                    ->where('academic_year_id', $classroom->academic_year_id)
                    ->where('classroom_id', $classroom->id)
                    ->published()
                    ->get();

                if ($schedules->isNotEmpty()) {
                    $daysWithLessons = $schedules->pluck('day_of_week')->map(fn($d) => (int)$d)->unique()->toArray();
                    $timeSlots = TimeSlot::where('school_id', $classroom->school_id)->orderBy('slot_number')->get();

                    foreach ($timeSlots as $slot) {
                        for ($day = 1; $day <= 7; $day++) {
                            $sched = $schedules->first(function($s) use ($slot, $day) {
                                return $s->time_slot_id === $slot->id && (int)$s->day_of_week === $day;
                            });

                            if ($sched) {
                                $weeklySchedule[$day][] = [
                                    'id'       => $sched->id,
                                    'mulai'    => substr($slot->start_time, 0, 5),
                                    'selesai'  => substr($slot->end_time, 0, 5),
                                    'mapel'    => $sched->subject ? $sched->subject->name : '',
                                    'code'     => $sched->subject ? $sched->subject->code : '',
                                    'kelas'    => $classroom->name,
                                    'ruang'    => $classroom->room,
                                    'guru'     => $sched->teacher ? $sched->teacher->name : '',
                                    'is_break' => (bool)$slot->is_break
                                ];
                            } elseif ($slot->is_break && in_array($day, $daysWithLessons)) {
                                $weeklySchedule[$day][] = [
                                    'id'       => 'break-' . $slot->id,
                                    'mulai'    => substr($slot->start_time, 0, 5),
                                    'selesai'  => substr($slot->end_time, 0, 5),
                                    'mapel'    => $slot->label ?: 'Istirahat',
                                    'code'     => '',
                                    'kelas'    => '',
                                    'ruang'    => '',
                                    'guru'     => '',
                                    'is_break' => true
                                ];
                            }
                        }
                    }

                    foreach ($weeklySchedule as $day => &$lessons) {
                        usort($lessons, function($a, $b) {
                            return strcmp($a['mulai'], $b['mulai']);
                        });
                    }
                }
            }

            // 2. Upcoming Exams
            $upcomingExams = [];
            if ($schoolId && $grade) {
                $examSessions = ExamSession::with(['calendarEvent', 'sessionSubjects.subject'])
                    ->published()
                    ->where('school_id', $schoolId)
                    ->whereHas('sessionSubjects', function($q) use ($grade) {
                        $q->where('grade', $grade);
                    })
                    ->where('exam_date', '>=', now()->toDateString())
                    ->orderBy('exam_date')
                    ->orderBy('session_number')
                    ->get();

                $groupedExams = [];
                foreach ($examSessions as $session) {
                    $eventId = $session->academic_calendar_event_id;
                    if (!isset($groupedExams[$eventId])) {
                        $startDateStr = $session->calendarEvent?->start_date 
                            ? (is_string($session->calendarEvent->start_date) ? $session->calendarEvent->start_date : $session->calendarEvent->start_date->format('Y-m-d'))
                            : (is_string($session->exam_date) ? $session->exam_date : $session->exam_date->format('Y-m-d'));

                        $endDateStr = $session->calendarEvent?->end_date 
                            ? (is_string($session->calendarEvent->end_date) ? $session->calendarEvent->end_date : $session->calendarEvent->end_date->format('Y-m-d'))
                            : (is_string($session->exam_date) ? $session->exam_date : $session->exam_date->format('Y-m-d'));

                        $groupedExams[$eventId] = [
                            'id'          => $eventId,
                            'event_title' => $session->calendarEvent?->title ?? 'Ujian',
                            'start_date'  => $startDateStr,
                            'end_date'    => $endDateStr,
                            'description' => $session->calendarEvent?->description,
                            'sessions'    => []
                        ];
                    }

                    $gradeSubjects = $session->sessionSubjects->where('grade', $grade);
                    foreach ($gradeSubjects as $ss) {
                        $examDateStr = is_string($session->exam_date) ? $session->exam_date : $session->exam_date->format('Y-m-d');
                        $groupedExams[$eventId]['sessions'][] = [
                            'id'             => $session->id,
                            'exam_date'      => $examDateStr,
                            'session_number' => $session->session_number,
                            'start_time'     => substr($session->start_time, 0, 5),
                            'end_time'       => substr($session->end_time, 0, 5),
                            'subject_name'   => $ss->subject?->name ?? 'Mata Pelajaran',
                            'subject_code'   => $ss->subject?->code ?? '',
                            'notes'          => $session->notes ?? 'Ruang Ujian'
                        ];
                    }
                }
                $upcomingExams = array_values($groupedExams);
            }

            // 3. Upcoming Events & Holidays
            $upcomingEvents = [];
            if ($schoolId) {
                $events = AcademicCalendarEvent::where(function($q) use ($schoolId) {
                    $q->where('school_id', $schoolId)->orWhereNull('school_id');
                })
                ->where('end_date', '>=', now()->toDateString())
                ->orderBy('start_date')
                ->get();

                $upcomingEvents = $events->map(function($e) {
                    $sDateStr = is_string($e->start_date) ? $e->start_date : $e->start_date->format('Y-m-d');
                    $eDateStr = is_string($e->end_date) ? $e->end_date : $e->end_date->format('Y-m-d');
                    return [
                        'id'          => $e->id,
                        'title'       => $e->title,
                        'type'        => $e->type,
                        'start_date'  => $sDateStr,
                        'end_date'    => $eDateStr,
                        'description' => $e->description,
                        'is_holiday'  => in_array($e->type, ['libur_nasional', 'libur_semester', 'libur_khusus', 'tanggal_merah'])
                    ];
                })->toArray();
            }

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'children'        => $childrenList,
                    'selected_child'  => $selectedChildData,
                    'weekly_schedule' => $weeklySchedule,
                    'upcoming_exams'  => $upcomingExams,
                    'upcoming_events' => $upcomingEvents
                ]
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error in parentDashboard: ' . $e->getMessage() . ' line ' . $e->getLine());
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memuat data jadwal anak: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset calendar events and delete academic year records.
     */
    public function reset(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $academicYearId = $request->input('academic_year_id');

        try {
            $this->calendarService->resetCalendar($schoolId, $academicYearId);
            return response()->json([
                'status' => 'success',
                'message' => 'Kalender akademik berhasil dihapus.'
            ]);
        } catch (\Exception $e) {
            $code = $e->getCode() === 422 ? 422 : 404;
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $code);
        }
    }
}
