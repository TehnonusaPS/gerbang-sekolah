<?php

namespace App\Models;

use App\Models\Concerns\NormalizesProfileValues;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class StudentProfile extends Model
{
    use NormalizesProfileValues;

    protected $fillable = [
        'user_id',
        'classroom_id',
        'nisn',
        'nik',
        'rfid_number',
        'birth_place',
        'birth_date',
        'gender',
        'religion',
        'address',
        'enrollment_date',
        'status',
        'is_face_registered',
        'embedding',
    ];

    protected function status(): Attribute
    {
        return Attribute::make(
            set: fn (?string $val) => self::normalizeStudentStatusToDb($val),
        );
    }

    protected $appends = ['is_rfid_registered'];

    public function getIsRfidRegisteredAttribute(): bool
    {
        return !empty($this->rfid_number);
    }

    protected function casts(): array
    {
        return [
            'birth_date'          => 'date',
            'enrollment_date'     => 'date',
            'is_face_registered'  => 'boolean',
            'embedding'           => 'array',
        ];
    }

    /**
     * Get the student attendances.
     */
    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentAttendance::class);
    }

    /**
     * Get the student biometric samples.
     */
    public function biometrics(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StudentBiometric::class);
    }

    /**
     * Get the user account for this student profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the classroom this student is assigned to.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get all parents/guardians of this student.
     */
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            ParentProfile::class,
            'parent_student',
            'student_profile_id',
            'parent_profile_id'
        )->withTimestamps();
    }
}
