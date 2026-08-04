<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'check_in_start',
        'check_in_end',
        'check_out_start',
        'check_out_end',
    ];

    /**
     * Get or create default settings singleton.
     */
    public static function getSettings(): self
    {
        return static::firstOrCreate([], [
            'check_in_start' => '06:00:00',
            'check_in_end' => '07:30:00',
            'check_out_start' => '15:00:00',
            'check_out_end' => '17:00:00',
        ]);
    }
}
