<?php

namespace App\Models;

use App\Models\Concerns\NormalizesProfileValues;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ParentProfile extends Model
{
    use NormalizesProfileValues;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'nik',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
        'last_education',
        'marital_status',
        'relationship',
        'occupation',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /**
     * Get the user account for this parent profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all children (students) of this parent.
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            StudentProfile::class,
            'parent_student',
            'parent_profile_id',
            'student_profile_id'
        )->withTimestamps();
    }

    /**
     * Get all students (alias for children relationship used by AuthController).
     */
    public function students(): BelongsToMany
    {
        return $this->children();
    }
}
