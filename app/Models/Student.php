<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nisn
 * @property string|null $rfid_uid
 * @property string|null $nis
 * @property string $name
 * @property string $class_name
 * @property string|null $address
 * @property string|null $school_origin
 * @property string|null $photo_path
 * @property array<int, float>|null $face_embedding
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'nisn',
        'rfid_uid',
        'nis',
        'name',
        'class_name',
        'address',
        'school_origin',
        'photo_path',
        'face_embedding',
    ];

    protected $casts = [
        'face_embedding' => 'array',
    ];

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
