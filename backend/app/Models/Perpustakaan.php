<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perpustakaan extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'isbn',
        'category',
        'stock',
        'rack_location',
        'publication_year',
        'author',
        'publisher',
        'description',
        'foto',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
