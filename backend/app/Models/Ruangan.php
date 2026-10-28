<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruangan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'building',
        'capacity',
        'area',
        'facilities',
        'school_id',
        'foto',
    ];

    protected $casts = [
        'facilities' => 'array',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
