<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nisn',
        'name',
        'class_name',
        'photo_path',
        'face_embedding',
    ];

    protected $casts = [
        'face_embedding' => 'array',
    ];

    public function attendances(): HasMany
    {
        return $table = $this->hasMany(Attendance::class);
    }
}
