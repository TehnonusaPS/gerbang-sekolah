<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aset extends Model
{
    protected $fillable = [
        'school_id',
        'code',
        'name',
        'category',
        'brand',
        'building',
        'floor',
        'room',
        'condition',
        'date',
        'value',
        'foto'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
