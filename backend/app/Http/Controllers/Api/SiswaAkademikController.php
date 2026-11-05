<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\SubjectGrade;
use App\Models\Schedule;
use App\Models\SubjectMaterial;
use App\Models\Assessment;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiswaAkademikController extends Controller
{
    /**
     * Get classroom history/options for the authenticated student.
     */
    public function getMyClassrooms(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;
        if (!$student) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil siswa tidak ditemukan.'
            ], 404);
        }

        // Ambil riwayat kelas nyata siswa dari tabel pivot riwayat
        $classrooms = $student->classroomHistories()->with(['academicYear', 'homeroomTeacher'])->get();

        // Fallback jika belum tercatat di pivot tapi ada pointer kelas saat ini
        if ($classrooms->isEmpty() && $student->classroom_id) {
            $current = Classroom::with(['academicYear', 'homeroomTeacher'])->find($student->classroom_id);
            if ($current) {
                $classrooms = collect([$current]);
            }
        }

        $data = $classrooms->map(function ($c) use ($student) {
            $pivot = $c->pivot ?? null;
            $teacher = $c->homeroomTeacher;
            $teacherName = $teacher ? $teacher->name : 'Belum Ada';

            return [
                'classroom_id'          => $c->id,
                'classroom_name'        => $c->name,
                'grade'                 => $c->grade,
                'academic_year_id'      => $c->academic_year_id,
                'academic_year_name'    => $c->academicYear ? $c->academicYear->name : '-',
                'semester'              => $c->academicYear ? $c->academicYear->semester : 'odd',
                'semester_label'        => ($c->academicYear && $c->academicYear->semester === 'odd') ? 'Ganjil' : 'Genap',
                'homeroom_teacher_name' => $teacherName,
                'status_keanggotaan'    => $pivot ? $pivot->status : ($c->id === $student->classroom_id ? 'active' : 'archived'),
                'is_current'            => $c->id === $student->classroom_id,
            ];
        })->sortByDesc('academic_year_id')->values();

        return response()->json([
            'status' => 'success',
            'data'   => $data
        ]);
    }

    /**
     * Get subjects taught in a classroom.
     */
    public function getSubjects(Request $request): JsonResponse
    {
        $classroomId = $request->query('classroom_id');
        if (!$classroomId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Classroom ID wajib diisi.'
            ], 400);
        }

        $classroom = Classroom::find($classroomId);
        if (!$classroom) {
            return response()->json([
                'status' => 'success',
                'data'   => []
            ]);
        }

        // 1. Subjects from TeacherSubjectAssignment
        $assignmentSubjectIds = TeacherSubjectAssignment::where('classroom_id', $classroomId)
            ->pluck('subject_id')
            ->toArray();

        // 2. Subjects from Schedule (Jadwal Pelajaran Kelas)
        $scheduleSubjectIds = Schedule::where('classroom_id', $classroomId)
            ->pluck('subject_id')
            ->toArray();

        // 3. Subjects mapped by grade level (SubjectGrade)
        $gradeSubjectIds = SubjectGrade::where('grade', $classroom->grade)
            ->pluck('subject_id')
            ->toArray();

        $allSubjectIds = array_filter(array_unique(array_merge($assignmentSubjectIds, $scheduleSubjectIds, $gradeSubjectIds)));

        $subjects = Subject::whereIn('id', $allSubjectIds)
            ->where('is_active', true)
            ->get();

        // Fallback: If no specific subject assignment is found yet, return all active subjects of the school
        if ($subjects->isEmpty()) {
            $subjects = Subject::where('school_id', $classroom->school_id)
                ->where('is_active', true)
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data'   => $subjects
        ]);
    }

    /**
     * Get subject material & assessment overview.
     */
    public function getSubjectOverview(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;
        $subjectId = $request->query('subject_id');
        $classroomId = $request->query('classroom_id');

        if (!$student || !$subjectId || !$classroomId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter tidak lengkap.'
            ], 400);
        }

        // 1. Fetch materials
        $materials = SubjectMaterial::where('subject_id', $subjectId)
            ->where(function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId)
                  ->orWhereNull('classroom_id');
            })
            ->where('is_active', true)
            ->get();

        // 2. Fetch assessments with student score
        $assessments = Assessment::with(['material:id,title', 'scores' => function ($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->where('subject_id', $subjectId)
            ->where(function ($q) use ($classroomId) {
                $q->where('classroom_id', $classroomId)
                  ->orWhereNull('classroom_id');
            })
            ->where('is_active', true)
            ->get();

        $mappedAssessments = $assessments->map(function ($a) {
            $studentScore = $a->scores->first();
            return [
                'id'             => $a->id,
                'type'           => $a->type,
                'category'       => $a->category,
                'title'          => $a->title,
                'material_id'    => $a->material_id,
                'material_title' => $a->material ? $a->material->title : null,
                'score'          => $studentScore ? floatval($studentScore->score) : null
            ];
        });

        // 3. Compute stats
        $tugasAssessments = $mappedAssessments->filter(fn($a) => $a['category'] === 'tugas' && $a['score'] !== null);
        $ujianAssessments = $mappedAssessments->filter(fn($a) => $a['category'] === 'ujian' && $a['score'] !== null);

        $avgTugas = $tugasAssessments->count() > 0 ? round($tugasAssessments->avg('score'), 2) : 0;
        $avgUjian = $ujianAssessments->count() > 0 ? round($ujianAssessments->avg('score'), 2) : 0;

        $avgKeseluruhan = round(($avgTugas * 0.4) + ($avgUjian * 0.6), 2);

        $uts = $mappedAssessments->first(fn($a) => $a['type'] === 'uts');
        $uas = $mappedAssessments->first(fn($a) => $a['type'] === 'uas');

        $tugasDetails = $tugasAssessments->map(fn($a) => ['label' => $a['title'], 'value' => $a['score']])->values();
        $ujianDetails = $ujianAssessments->map(fn($a) => ['label' => $a['title'], 'value' => $a['score']])->values();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'materials'   => $materials,
                'assessments' => $mappedAssessments,
                'uts'         => $uts ? $uts['score'] : null,
                'uas'         => $uas ? $uas['score'] : null,
                'stats'       => [
                    'avg_tugas'       => $avgTugas,
                    'avg_ujian'       => $avgUjian,
                    'avg_keseluruhan' => $avgKeseluruhan,
                    'tugas_count'     => $tugasAssessments->count(),
                    'ujian_count'     => $ujianAssessments->count(),
                    'tugas_details'   => $tugasDetails,
                    'ujian_details'   => $ujianDetails
                ]
            ]
        ]);
    }

    /**
     * Get global stats across all subjects.
     */
    public function getGlobalStats(Request $request): JsonResponse
    {
        $student = $request->user()->studentProfile;
        $classroomId = $request->query('classroom_id');

        if (!$student || !$classroomId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter tidak lengkap.'
            ], 400);
        }

        $assessments = Assessment::with(['scores' => function ($q) use ($student) {
                $q->where('student_id', $student->id);
            }])
            ->where('classroom_id', $classroomId)
            ->where('is_active', true)
            ->get();

        $tugasScores = [];
        $ujianScores = [];
        $tugasCompletedCount = 0;

        foreach ($assessments as $assessment) {
            $studentScore = $assessment->scores->first();
            if ($studentScore !== null) {
                $scoreVal = floatval($studentScore->score);
                if ($assessment->category === 'tugas') {
                    $tugasScores[] = $scoreVal;
                    $tugasCompletedCount++;
                } elseif ($assessment->category === 'ujian') {
                    $ujianScores[] = $scoreVal;
                }
            }
        }

        $avgTugas = count($tugasScores) > 0 ? (array_sum($tugasScores) / count($tugasScores)) : 0;
        $avgUjian = count($ujianScores) > 0 ? (array_sum($ujianScores) / count($ujianScores)) : 0;
        $avgAll = (count($tugasScores) + count($ujianScores)) > 0
            ? (($avgTugas * 0.40) + ($avgUjian * 0.60))
            : 0;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'avg_all_subjects'      => round($avgAll, 2),
                'total_tugas_completed' => $tugasCompletedCount,
                'avg_ujian_all'         => round($avgUjian, 2),
                'avg_tugas_all'         => round($avgTugas, 2)
            ]
        ]);
    }
}
