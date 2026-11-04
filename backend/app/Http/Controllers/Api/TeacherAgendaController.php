<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\HasSchoolScope;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\TeacherAgenda;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherAgendaController extends Controller
{
    use HasSchoolScope;

    /**
     * Get list of teacher agendas based on role.
     */
    public function index(Request $request): JsonResponse
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

        if (!$schoolId) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $query = TeacherAgenda::with(['classroom', 'subject', 'teacher'])
            ->where('school_id', $schoolId);

        // Filter based on role
        if ($user->hasRole('siswa')) {
            $profile = $user->studentProfile;
            if ($profile && $profile->classroom_id) {
                $query->where(function ($q) use ($profile) {
                    $q->whereNull('classroom_id')
                      ->orWhere('classroom_id', $profile->classroom_id);
                });
            }
        } elseif ($user->hasRole('orang_tua')) {
            $parentProfile = $user->parentProfile;
            if ($parentProfile) {
                $childId = $request->query('child_id');
                if ($childId) {
                    $child = $parentProfile->children()->where('student_profiles.id', $childId)->first();
                    $classroomIds = $child && $child->classroom_id ? [$child->classroom_id] : [];
                } else {
                    $classroomIds = $parentProfile->children()->pluck('classroom_id')->filter()->toArray();
                }
                if (!empty($classroomIds)) {
                    $query->where(function ($q) use ($classroomIds) {
                        $q->whereNull('classroom_id')
                          ->orWhereIn('classroom_id', $classroomIds);
                    });
                }
            }
        } elseif ($user->hasRole('guru')) {
            // Show agendas created by this teacher or assigned to their school
            $query->where('teacher_id', $user->id);
        }

        $agendas = $query->orderBy('date', 'asc')->get();

        $formatted = $agendas->map(function ($item) {
            $dateStr = is_string($item->getRawOriginal('date')) ? substr($item->getRawOriginal('date'), 0, 10) : $item->date->format('Y-m-d');
            $endDateStr = $item->end_date
                ? (is_string($item->getRawOriginal('end_date')) ? substr($item->getRawOriginal('end_date'), 0, 10) : $item->end_date->format('Y-m-d'))
                : $dateStr;

            return [
                'id'               => $item->id,
                'title'            => $item->title,
                'type'             => $item->type,
                'date'             => $dateStr,
                'end_date'         => $endDateStr,
                'time'             => $item->time,
                'description'      => $item->description,
                'classroom_id'     => $item->classroom_id,
                'classroom_name'   => $item->classroom ? $item->classroom->name : 'Semua Kelas Saya',
                'subject_id'       => $item->subject_id,
                'subject_name'     => $item->subject ? $item->subject->name : null,
                'teacher_id'       => $item->teacher_id,
                'teacher_name'     => $item->teacher ? $item->teacher->name : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $formatted
        ]);
    }

    /**
     * Get classrooms and subjects taught by teacher (for dropdown options).
     */
    public function myClassrooms(Request $request): JsonResponse
    {
        $user = $request->user();
        $schoolId = $this->resolveSchoolId($request);

        $activeYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();

        // Get classrooms taught by teacher or all classrooms in school
        $assignments = TeacherSubjectAssignment::with(['classroom', 'subject'])
            ->where('school_id', $schoolId)
            ->where('teacher_id', $user->id)
            ->where('is_active', true)
            ->get();

        $classrooms = [];
        $subjects = [];

        foreach ($assignments as $ass) {
            if ($ass->classroom) {
                $classrooms[$ass->classroom->id] = [
                    'id'   => $ass->classroom->id,
                    'name' => $ass->classroom->name
                ];
            }
            if ($ass->subject) {
                $subjects[$ass->subject->id] = [
                    'id'   => $ass->subject->id,
                    'name' => $ass->subject->name,
                    'code' => $ass->subject->code
                ];
            }
        }

        // If teacher has no specific assignments yet, fetch all school classrooms and subjects as fallback
        if (empty($classrooms)) {
            $allClassrooms = Classroom::where('school_id', $schoolId)
                ->when($activeYear, fn($q) => $q->where('academic_year_id', $activeYear->id))
                ->orderBy('name')
                ->get();

            foreach ($allClassrooms as $c) {
                $classrooms[$c->id] = ['id' => $c->id, 'name' => $c->name];
            }
        }

        if (empty($subjects)) {
            $allSubjects = Subject::where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get();
            foreach ($allSubjects as $s) {
                $subjects[$s->id] = ['id' => $s->id, 'name' => $s->name, 'code' => $s->code];
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'classrooms' => array_values($classrooms),
                'subjects'   => array_values($subjects)
            ]
        ]);
    }

    /**
     * Store a new teacher agenda.
     */
    public function store(Request $request): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'type'         => 'required|in:tugas,ujian_harian,kegiatan',
            'date'         => 'required|date',
            'end_date'     => 'nullable|date|after_or_equal:date',
            'time'         => 'nullable|string|max:100',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'subject_id'   => 'nullable|exists:subjects,id',
            'description'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $activeYear = AcademicYear::where('school_id', $schoolId)->where('is_active', true)->first();
        if (!$activeYear) {
            $activeYear = AcademicYear::where('school_id', $schoolId)->latest()->first();
        }

        if (!$activeYear) {
            return response()->json(['status' => 'error', 'message' => 'Tahun ajaran aktif tidak ditemukan.'], 422);
        }

        $agenda = TeacherAgenda::create([
            'school_id'        => $schoolId,
            'teacher_id'       => $user->id,
            'classroom_id'     => $request->classroom_id ?: null,
            'subject_id'       => $request->subject_id ?: null,
            'academic_year_id' => $activeYear->id,
            'title'            => $request->title,
            'type'             => $request->type,
            'date'             => $request->date,
            'end_date'         => $request->end_date ?: $request->date,
            'time'             => $request->time,
            'description'      => $request->description,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Agenda guru berhasil ditambahkan.',
            'data'    => $agenda
        ], 201);
    }

    /**
     * Update an existing teacher agenda.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $user = $request->user();

        $agenda = TeacherAgenda::where('id', $id)->where('school_id', $schoolId)->first();

        if (!$agenda) {
            return response()->json(['status' => 'error', 'message' => 'Agenda tidak ditemukan.'], 404);
        }

        if ($agenda->teacher_id !== $user->id && !$user->hasRole('admin_sekolah')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk mengubah agenda ini.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:255',
            'type'         => 'required|in:tugas,ujian_harian,kegiatan',
            'date'         => 'required|date',
            'end_date'     => 'nullable|date|after_or_equal:date',
            'time'         => 'nullable|string|max:100',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'subject_id'   => 'nullable|exists:subjects,id',
            'description'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $agenda->update([
            'title'        => $request->title,
            'type'         => $request->type,
            'date'         => $request->date,
            'end_date'     => $request->end_date ?: $request->date,
            'time'         => $request->time,
            'classroom_id' => $request->classroom_id ?: null,
            'subject_id'   => $request->subject_id ?: null,
            'description'  => $request->description,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Agenda guru berhasil diperbarui.',
            'data'    => $agenda
        ]);
    }

    /**
     * Delete a teacher agenda.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $schoolId = $this->resolveSchoolId($request);
        $user = $request->user();

        $agenda = TeacherAgenda::where('id', $id)->where('school_id', $schoolId)->first();

        if (!$agenda) {
            return response()->json(['status' => 'error', 'message' => 'Agenda tidak ditemukan.'], 404);
        }

        if ($agenda->teacher_id !== $user->id && !$user->hasRole('admin_sekolah')) {
            return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk menghapus agenda ini.'], 403);
        }

        $agenda->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Agenda guru berhasil dihapus.'
        ]);
    }
}
