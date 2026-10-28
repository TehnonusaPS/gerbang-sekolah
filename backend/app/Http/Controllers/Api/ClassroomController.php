<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\StudentProfile;
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
     * Display a listing of classrooms.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Classroom::with(['homeroomTeacher'])->withCount('students');

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
        } else { // admin_sekolah, kepala_sekolah, tata_usaha, wali_kelas
            $query->where('school_id', $user->school_id);
        }

        // Apply filters
        if ($request->has('grade')) {
            $query->where('grade', $request->input('grade'));
        }
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        $classrooms = $query->latest()->get();

        $formatted = $classrooms->map(function ($item) {
            return [
                'id'               => $item->id,
                'name'             => $item->name,
                'grade'            => $item->grade,
                'major'            => $item->major,
                'room'             => $item->room,
                'capacity'         => $item->capacity,
                'students_count'   => $item->students_count,
                'status'           => $item->status,
                'homeroom_teacher' => $item->homeroomTeacher ? $item->homeroomTeacher->name : null,
                'homeroom_teacher_id' => $item->homeroom_teacher_id,
                'school_id'        => $item->school_id,
                'academic_year_id' => $item->academic_year_id,
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
                ->first();
            if (!$activeYear) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'No active academic year found for this school. Please specify academic_year_id.',
                ], 400);
            }
            $academicYearId = $activeYear->id;
        }

        $homeroomTeacherId = $request->input('homeroom_teacher_id');
        if (!$homeroomTeacherId && $request->filled('homeroom_teacher')) {
            $teacherName = $request->input('homeroom_teacher');
            $teacher = \App\Models\User::where('name', 'like', "%{$teacherName}%")
                ->whereHas('role', function($q) {
                    $q->whereIn('name', ['guru', 'wali_kelas']);
                })
                ->first();
            if ($teacher) {
                $homeroomTeacherId = $teacher->id;
            }
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
                'school_id'        => $classroom->school_id,
                'academic_year_id' => $classroom->academic_year_id,
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
        if (!$homeroomTeacherId && $request->filled('homeroom_teacher')) {
            $teacherName = $request->input('homeroom_teacher');
            $teacher = \App\Models\User::where('name', 'like', "%{$teacherName}%")
                ->whereHas('role', function($q) {
                    $q->whereIn('name', ['guru', 'wali_kelas']);
                })
                ->first();
            if ($teacher) {
                $homeroomTeacherId = $teacher->id;
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

        $classroom->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Classroom deleted successfully.',
        ]);
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
