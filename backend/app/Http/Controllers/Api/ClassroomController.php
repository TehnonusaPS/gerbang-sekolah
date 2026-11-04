<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClassroomController extends Controller
{
    /**
     * Helper to get school scope for the current user.
     */
    private function getSchoolScope(Request $request, $user)
    {
        if ($user->isSuperAdmin()) {
            return $request->input('school_id');
        } elseif ($user->hasRole('admin_yayasan')) {
            $schoolId = $request->input('school_id');
            if ($schoolId) {
                // Ensure school belongs to the foundation
                $belongs = \App\Models\School::where('id', $schoolId)
                    ->where('foundation_id', $user->foundation_id)
                    ->exists();
                return $belongs ? $schoolId : -1; // -1 means invalid/no access
            }
            return null; // yayasan wide query
        }
        return $user->school_id;
    }

    /**
     * Get available teacher options for homeroom teacher selection.
     * Filtered by school and role (guru), sorted by availability (available first).
     */
    public function getTeacherOptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $this->getSchoolScope($request, $user);

        if ($schoolId === -1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Sekolah tidak termasuk dalam naungan yayasan Anda.',
            ], 403);
        }

        if (!$schoolId) {
            $schoolId = $user->school_id;
        }

        if (!$schoolId) {
            return response()->json([
                'status' => 'success',
                'data'   => [],
            ]);
        }

        $academicYearId = $request->input('academic_year_id');
        if (!$academicYearId) {
            $activeYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();
            $academicYearId = $activeYear?->id;
        }

        $currentClassroomId = $request->input('current_classroom_id');

        // Ambil pemetaan wali kelas di tahun ajaran yang bersangkutan
        $assignedClasses = collect();
        if ($academicYearId) {
            $targetYear = AcademicYear::find($academicYearId);
            $yearIds = $targetYear
                ? AcademicYear::where('school_id', $schoolId)->where('name', $targetYear->name)->pluck('id')
                : [$academicYearId];

            $assignedClasses = Classroom::where('school_id', $schoolId)
                ->whereIn('academic_year_id', $yearIds)
                ->whereNotNull('homeroom_teacher_id')
                ->get(['id', 'name', 'homeroom_teacher_id'])
                ->keyBy('homeroom_teacher_id');
        }

        // Hanya ambil user guru dari sekolah tersebut
        $teachers = User::where('school_id', $schoolId)
            ->whereHas('role', function ($q) {
                $q->where('name', 'guru');
            })
            ->with('teacherProfile')
            ->get();

        $options = $teachers->map(function ($teacher) use ($assignedClasses, $currentClassroomId) {
            $assigned = $assignedClasses->get($teacher->id);
            $frontTitle = $teacher->teacherProfile?->front_title ? $teacher->teacherProfile->front_title . ' ' : '';
            $backTitle = $teacher->teacherProfile?->back_title ? ', ' . $teacher->teacherProfile->back_title : '';
            $fullName = $frontTitle . $teacher->name . $backTitle;

            $isCurrentClass = $assigned && $currentClassroomId && ((string) $assigned->id === (string) $currentClassroomId);
            $isAssignedOther = $assigned && !$isCurrentClass;

            if ($isCurrentClass) {
                $statusText = "(Wali Kelas Saat Ini)";
                $disabled = false;
                $isAvailable = true;
            } elseif ($isAssignedOther) {
                $statusText = "(Wali Kelas {$assigned->name})";
                $disabled = true;
                $isAvailable = false;
            } else {
                $statusText = "(Tersedia)";
                $disabled = false;
                $isAvailable = true;
            }

            return [
                'id'             => (string) $teacher->id,
                'value'          => (string) $teacher->id,
                'name'           => $fullName,
                'assigned_class' => $assigned?->name,
                'is_available'   => $isAvailable,
                'disabled'       => $disabled,
                'label'          => "{$fullName} — {$statusText}",
            ];
        })
        ->sortBy([
            ['disabled', 'asc'], // false (tersedia) diletakkan di paling atas!
            ['name', 'asc'],
        ])
        ->values();

        return response()->json([
            'status' => 'success',
            'data'   => $options,
        ]);
    }

    /**
     * Display a listing of classrooms.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Classroom::with(['homeroomTeacher', 'academicYear'])->withCount('students');

        if ($user->isSuperAdmin()) {
            if ($request->has('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            }
        } elseif ($user->hasRole('admin_yayasan')) {
            $query->whereHas('school', function ($q) use ($user) {
                $q->where('foundation_id', $user->foundation_id);
            });
            if ($request->has('school_id')) {
                $query->where('school_id', $request->input('school_id'));
            }
        } else { // admin_sekolah, kepala_sekolah, tata_usaha, guru
            $query->where('school_id', $user->school_id);
        }

        // Apply filters
        if ($request->has('grade')) {
            $query->where('grade', $request->input('grade'));
        }
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('academic_year_id') && $request->academic_year_id !== 'all') {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $classrooms = $query->get()->sortBy([
            ['academicYear.name', 'desc'],
            ['name', 'asc'],
        ])->values();

        $formatted = $classrooms->map(function ($item) {
            return [
                'id'                     => $item->id,
                'name'                   => $item->name,
                'grade'                  => $item->grade,
                'major'                  => $item->major,
                'room'                   => $item->room,
                'capacity'               => $item->capacity,
                'students_count'         => $item->students_count,
                'status'                 => $item->status,
                'homeroom_teacher'       => $item->homeroomTeacher ? $item->homeroomTeacher->name : null,
                'homeroom_teacher_id'    => $item->homeroom_teacher_id,
                'school_id'              => $item->school_id,
                'academic_year_id'       => $item->academic_year_id,
                'academic_year_name'     => $item->academicYear ? $item->academicYear->name : null,
                'academic_year_semester' => $item->academicYear ? $item->academicYear->semester : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $formatted,
        ]);
    }

    /**
     * Store a newly created classroom.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $this->getSchoolScope($request, $user);

        if ($schoolId === -1) {
            return response()->json([
                'status'  => 'error',
                'message' => 'The selected school does not belong to your foundation.',
            ], 403);
        }

        if (!$schoolId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'School ID is required.',
            ], 400);
        }

        if ($request->input('homeroom_teacher_id') === 'none') {
            $request->merge(['homeroom_teacher_id' => null]);
        }

        $validator = Validator::make($request->all(), [
            'name'                => 'required|string|max:100',
            'grade'               => 'required|integer',
            'major'               => 'nullable|string|max:100',
            'room'                => 'nullable|string|max:100',
            'status'              => 'nullable|string|in:active,full,no_teacher',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'capacity'            => 'nullable|integer',
            'academic_year_id'    => 'nullable|exists:academic_years,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $academicYearId = $request->input('academic_year_id');
        if (!$academicYearId) {
            $activeYear = AcademicYear::where('school_id', $schoolId)
                ->where('is_active', true)
                ->first() ?: AcademicYear::where('school_id', $schoolId)->latest('id')->first();
            if (!$activeYear) {
                $activeYear = AcademicYear::create(array_merge(
                    AcademicYear::defaultAttributesForDate(now()),
                    ['school_id' => $schoolId]
                ));
            }
            $academicYearId = $activeYear->id;
        }

        $homeroomTeacherId = $request->input('homeroom_teacher_id');
        if (!$request->has('homeroom_teacher_id') && $request->filled('homeroom_teacher')) {
            $teacherName = $request->input('homeroom_teacher');
            $teacher = \App\Models\User::where('name', 'like', "%{$teacherName}%")
                ->whereHas('role', function($q) {
                    $q->where('name', 'guru');
                })
                ->first();
            if ($teacher) {
                $homeroomTeacherId = $teacher->id;
            }
        }

        // Cegah guru ditugaskan jika sudah menjadi wali kelas lain di tahun ajaran ini
        if ($homeroomTeacherId) {
            $targetYear = AcademicYear::find($academicYearId);
            $yearIds = $targetYear
                ? AcademicYear::where('school_id', $schoolId)->where('name', $targetYear->name)->pluck('id')
                : [$academicYearId];

            $conflict = Classroom::where('school_id', $schoolId)
                ->whereIn('academic_year_id', $yearIds)
                ->where('homeroom_teacher_id', $homeroomTeacherId)
                ->first();

            if ($conflict) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Guru ini sudah menjadi wali kelas di kelas \"{$conflict->name}\" pada tahun ajaran ini.",
                    'errors'  => ['homeroom_teacher_id' => ["Guru ini sudah menjadi wali kelas di kelas \"{$conflict->name}\"."]],
                ], 422);
            }
        }

        $existing = Classroom::where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->input('name')))])
            ->first();

        if ($existing) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kelas dengan nama "' . $request->input('name') . '" sudah ada di tahun ajaran ini.',
                'errors'  => ['name' => ['Kelas dengan nama ini sudah terdaftar pada tahun ajaran yang dipilih.']],
            ], 422);
        }

        $classroom = Classroom::create([
            'school_id'           => $schoolId,
            'academic_year_id'    => $academicYearId,
            'name'                => $request->input('name'),
            'grade'               => $request->input('grade'),
            'major'               => $request->input('major'),
            'room'                => $request->input('room'),
            'status'              => $request->input('status', 'active'),
            'homeroom_teacher_id' => $homeroomTeacherId,
            'capacity'            => $request->input('capacity', 36),
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Classroom created successfully.',
            'data'    => $classroom,
        ], 201);
    }

    /**
     * Display the specified classroom.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $classroom = Classroom::with(['homeroomTeacher'])->withCount('students')->find($id);

        if (!$classroom) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Classroom not found.',
            ], 404);
        }

        // Authority scope check
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($classroom->school->foundation_id != $user->foundation_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($classroom->school_id != $user->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'               => $classroom->id,
                'name'             => $classroom->name,
                'grade'            => $classroom->grade,
                'major'            => $classroom->major,
                'room'             => $classroom->room,
                'capacity'         => $classroom->capacity,
                'students_count'   => $classroom->students_count,
                'status'           => $classroom->status,
                'homeroom_teacher' => $classroom->homeroomTeacher ? $classroom->homeroomTeacher->name : null,
                'homeroom_teacher_id' => $classroom->homeroom_teacher_id,
                'school_id'              => $classroom->school_id,
                'academic_year_id'       => $classroom->academic_year_id,
                'academic_year_name'     => $classroom->academicYear ? $classroom->academicYear->name : null,
                'academic_year_semester' => $classroom->academicYear ? $classroom->academicYear->semester : null,
            ],
        ]);
    }

    /**
     * Update the specified classroom.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Classroom not found.',
            ], 404);
        }

        // Authority scope check
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($classroom->school->foundation_id != $user->foundation_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($classroom->school_id != $user->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        if ($request->input('homeroom_teacher_id') === 'none') {
            $request->merge(['homeroom_teacher_id' => null]);
        }

        $validator = Validator::make($request->all(), [
            'name'                => 'sometimes|required|string|max:100',
            'grade'               => 'sometimes|required|integer',
            'major'               => 'nullable|string|max:100',
            'room'                => 'nullable|string|max:100',
            'status'              => 'nullable|string|in:active,full,no_teacher',
            'homeroom_teacher_id' => 'nullable|exists:users,id',
            'capacity'            => 'nullable|integer',
            'academic_year_id'    => 'nullable|exists:academic_years,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $homeroomTeacherId = $request->input('homeroom_teacher_id');
        if (!$request->has('homeroom_teacher_id') && $request->filled('homeroom_teacher')) {
            $teacherName = $request->input('homeroom_teacher');
            $teacher = \App\Models\User::where('name', 'like', "%{$teacherName}%")
                ->whereHas('role', function($q) {
                    $q->where('name', 'guru');
                })
                ->first();
            if ($teacher) {
                $homeroomTeacherId = $teacher->id;
            }
        }

        // Cegah guru ditugaskan jika sudah menjadi wali kelas lain di tahun ajaran ini
        if ($homeroomTeacherId) {
            $academicYearId = $request->input('academic_year_id', $classroom->academic_year_id);
            $targetYear = AcademicYear::find($academicYearId);
            $yearIds = $targetYear
                ? AcademicYear::where('school_id', $classroom->school_id)->where('name', $targetYear->name)->pluck('id')
                : [$academicYearId];

            $conflict = Classroom::where('school_id', $classroom->school_id)
                ->whereIn('academic_year_id', $yearIds)
                ->where('homeroom_teacher_id', $homeroomTeacherId)
                ->where('id', '!=', $classroom->id)
                ->first();

            if ($conflict) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Guru ini sudah menjadi wali kelas di kelas \"{$conflict->name}\" pada tahun ajaran ini.",
                    'errors'  => ['homeroom_teacher_id' => ["Guru ini sudah menjadi wali kelas di kelas \"{$conflict->name}\"."]],
                ], 422);
            }
        }

        $classroom->update(array_merge(
            $request->only(['name', 'grade', 'major', 'room', 'status', 'capacity', 'academic_year_id']),
            ['homeroom_teacher_id' => $homeroomTeacherId]
        ));

        return response()->json([
            'status'  => 'success',
            'message' => 'Classroom updated successfully.',
            'data'    => $classroom,
        ]);
    }

    /**
     * Remove the specified classroom from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Classroom not found.',
            ], 404);
        }

        // Authority scope check
        if (!$user->isSuperAdmin()) {
            if ($user->hasRole('admin_yayasan')) {
                if ($classroom->school->foundation_id != $user->foundation_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            } else {
                if ($classroom->school_id != $user->school_id) {
                    return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                }
            }
        }

        if ($classroom->students()->count() > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kelas tidak dapat dihapus karena masih memiliki ' . $classroom->students()->count() . ' siswa di dalamnya. Pindahkan siswa terlebih dahulu.',
            ], 422);
        }

        try {
            $classroom->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data kelas berhasil dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus kelas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menempatkan atau memindahkan siswa ke kelas secara transaksional dengan locking.
     */
    public function assignStudents(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'required|uuid|distinct',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Validasi gagal.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $rawIds = $request->input('student_ids');

        return DB::transaction(function () use ($user, $id, $rawIds) {
            // Pessimistic lock pada kelas tujuan
            $classroom = Classroom::lockForUpdate()->find($id);
            if (!$classroom) {
                return response()->json(['status' => 'error', 'message' => 'Kelas tidak ditemukan.'], 404);
            }

            // Otorisasi Scope Sekolah
            if (!$user->isSuperAdmin()) {
                if ($user->hasRole('admin_yayasan')) {
                    if (!$classroom->school || $classroom->school->foundation_id != $user->foundation_id) {
                        return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                    }
                } else {
                    if ($classroom->school_id != $user->school_id) {
                        return response()->json(['status' => 'error', 'message' => 'Unauthorized.'], 403);
                    }
                }
            }

            // Verifikasi profil siswa: harus role siswa, sekolah yang sama, akun aktif, dan status aktif
            $validProfiles = StudentProfile::whereHas('user', function ($q) use ($classroom) {
                $q->where('school_id', $classroom->school_id)
                  ->where('is_active', true)
                  ->whereHas('role', fn($rq) => $rq->where('name', 'siswa'));
            })
            ->whereIn('user_id', $rawIds)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();

            if ($validProfiles->count() !== count($rawIds)) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Satu atau lebih siswa tidak valid, tidak aktif, atau tidak terdaftar di sekolah ini.',
                ], 422);
            }

            // Sisihkan siswa yang belum terdaftar di kelas tujuan
            $studentsToMove = $validProfiles->filter(fn($sp) => $sp->classroom_id != $classroom->id);
            if ($studentsToMove->isEmpty()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Semua siswa yang dipilih sudah berada di kelas ini.',
                ], 422);
            }

            // Validasi Kapasitas
            $currentCount = StudentProfile::where('classroom_id', $classroom->id)->where('status', 'active')->count();
            $incomingCount = $studentsToMove->count();
            if ($classroom->capacity && ($currentCount + $incomingCount) > $classroom->capacity) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "Kapasitas kelas tidak mencukupi. Kapasitas: {$classroom->capacity}, Terisi: {$currentCount}, Penambahan: {$incomingCount}.",
                ], 422);
            }

            // Eksekusi pemindahan siswa
            StudentProfile::whereIn('id', $studentsToMove->pluck('id'))
                ->update(['classroom_id' => $classroom->id]);

            return response()->json([
                'status'  => 'success',
                'message' => "{$incomingCount} siswa berhasil ditempatkan ke kelas {$classroom->name}.",
            ]);
        });
    }
}
