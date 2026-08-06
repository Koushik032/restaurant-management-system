<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceBreak extends Model
{
    use HasFactory;


    protected $fillable = [

        'attendance_id',

        'break_start_at',

        'break_end_at',

        'duration_minutes',

        'notes',

        'created_by',

        'updated_by',

    ];


    protected $casts = [

        'break_start_at' =>
            'datetime',

        'break_end_at' =>
            'datetime',

        'duration_minutes' =>
            'integer',

    ];


    public function attendance(): BelongsTo
    {
        return $this->belongsTo(
            Attendance::class
        );
    }


    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }


    public function isOpen(): bool
    {
        return (
            $this->break_end_at === null
        );
    }
}