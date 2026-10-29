<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TeacherProfile extends Model
{
    protected $fillable = [
        'user_id',
        'nik',
        'nip_nuptk',
        'birth_place',
        'birth_date',
        'gender',
        'religion',
        'marital_status',
        'last_education',
        'front_title',
        'back_title',
        'address',
        'position',
        'employment_status',
        'unit_ids',
        'join_date',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'join_date'  => 'date',
            'unit_ids'   => 'array',
        ];
    }

    /**
     * Get the user account for this teacher profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(
            Subject::class,
            'teacher_subject_assignments',
            'teacher_id',
            'subject_id',
            'user_id',
            'id'
        );
    }
}
