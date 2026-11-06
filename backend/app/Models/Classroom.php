<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    protected $fillable = [
        'school_id',
        'academic_year_id',
        'name',
        'grade',
        'major',
        'room',
        'status',
        'homeroom_teacher_id',
        'capacity',
    ];

    protected function casts(): array
    {
        return [
            'grade'    => 'integer',
            'capacity' => 'integer',
        ];
    }

    /**
     * Get the school this classroom belongs to.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the academic year this classroom belongs to.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the homeroom teacher (wali kelas) for this classroom.
     */
    public function homeroomTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    /**
     * Get all students directly assigned to this classroom.
     */
    public function students(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }

    /**
     * Get all classroom membership records (history & active).
     */
    public function classroomStudents(): HasMany
    {
        return $this->hasMany(ClassroomStudent::class);
    }

    /**
     * Get active classroom membership records.
     */
    public function activeClassroomStudents(): HasMany
    {
        return $this->hasMany(ClassroomStudent::class)->where('status', 'active');
    }

    /**
     * Get active students via pivot.
     */
    public function activeStudents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'classroom_students')
            ->wherePivot('status', 'active')
            ->withPivot('status', 'notes', 'start_date', 'end_date')
            ->withTimestamps();
    }

    /**
     * Get all students who ever attended this classroom (history).
     */
    public function historyStudents(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(StudentProfile::class, 'classroom_students')
            ->withPivot('status', 'notes', 'start_date', 'end_date')
            ->withTimestamps();
    }

    public function materials(): HasMany
    {
        return $this->hasMany(SubjectMaterial::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class);
    }

    /**
     * Get all schedules for this classroom.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
