<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Extracurricular extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'description',
        'advisor_id',
        'coach_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the school this extracurricular activity belongs to.
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the teacher/staff assigned as advisor/pembina for this extracurricular.
     */
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    /**
     * Get all students enrolled in this extracurricular.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(
            StudentProfile::class,
            'extracurricular_student',
            'extracurricular_id',
            'student_profile_id'
        )->withTimestamps();
    }
}
