<?php

namespace App\Http\Traits;

use App\Models\School;
use App\Models\Subject;
use App\Models\SubjectGrade;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;

trait HasCurriculumMappingTrait
{
    /**
     * Auto-populate school subjects from the active curriculum assigned to the school.
     */
    public function syncSchoolSubjectsFromCurriculum(School $school): void
    {
        if (!$school->curriculum_id) {
            return;
        }

        $curriculum = Curriculum::with('curriculumSubjects')->find($school->curriculum_id);
        if (!$curriculum) {
            return;
        }

        $levelMap = [
            'JP001' => 'SD',
            'TK'    => 'SD',
            'JP002' => 'SD',
            'SD'    => 'SD',
            'JP003' => 'SMP',
            'SMP'   => 'SMP',
            'SMA'   => 'SMP',
            'SMK'   => 'SMP',
        ];
        $normalizedLevel = $levelMap[strtoupper(trim((string) $school->level))] ?? 'SD';

        $currSubjects = $curriculum->curriculumSubjects;
        if ($curriculum->level === 'ALL' && $currSubjects->isNotEmpty()) {
            $currSubjects = $currSubjects->where('level', $normalizedLevel)->values();
        }

        if ($currSubjects->isEmpty()) {
            $specificCode = $normalizedLevel === 'SMP' ? 'KUR-MERDEKA-SMP' : 'KUR-MERDEKA-SD';
            $specificCurriculum = Curriculum::with('curriculumSubjects')->where('code', $specificCode)->first();
            $currSubjects = $specificCurriculum ? $specificCurriculum->curriculumSubjects : collect();
        }

        if ($currSubjects->isEmpty()) {
            return;
        }

        $activeSubjectIds = [];

        foreach ($currSubjects as $currSubject) {
            $cleanCode = preg_replace('/-(SD|SMP)$/', '', (string) $currSubject->code);
            $subject = Subject::where('school_id', $school->id)
                ->where('name', $currSubject->name)
                ->orderBy('id')
                ->first();

            if (!$subject) {
                $subject = Subject::create([
                    'school_id'   => $school->id,
                    'name'        => $currSubject->name,
                    'code'        => $cleanCode,
                    'description' => "Mata Pelajaran Standar " . $curriculum->name . " (" . $currSubject->level . ")",
                    'is_active'   => true,
                ]);
            } else {
                $subject->update([
                    'code'        => $cleanCode,
                    'description' => "Mata Pelajaran Standar " . $curriculum->name . " (" . $currSubject->level . ")",
                    'is_active'   => true,
                ]);
            }

            $activeSubjectIds[] = $subject->id;

            // Default grade mappings based on curriculum subject configuration or level fallback
            if (!empty($currSubject->default_grades) && is_array($currSubject->default_grades)) {
                $grades = $currSubject->default_grades;
            } elseif ($normalizedLevel === 'SD') {
                $grades = [1, 2, 3, 4, 5, 6];
            } elseif ($normalizedLevel === 'SMP') {
                $grades = [7, 8, 9];
            } else {
                $grades = [];
            }

            foreach ($grades as $grade) {
                SubjectGrade::firstOrCreate([
                    'subject_id' => $subject->id,
                    'grade'      => $grade,
                ]);
            }
        }

        // Deactivate subjects in this school that do not belong to the active curriculum (or are duplicates)
        Subject::where('school_id', $school->id)
            ->whereNotIn('id', $activeSubjectIds)
            ->update(['is_active' => false]);
    }

    /**
     * Sync grade levels for a specific subject.
     */
    public function syncSubjectGrades(Subject $subject, array $grades): void
    {
        SubjectGrade::where('subject_id', $subject->id)->delete();

        foreach ($grades as $grade) {
            SubjectGrade::create([
                'subject_id' => $subject->id,
                'grade'      => (int) $grade,
            ]);
        }
    }
}
