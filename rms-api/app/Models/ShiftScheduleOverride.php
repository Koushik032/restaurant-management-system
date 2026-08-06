<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftScheduleOverride extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const TYPE_MODIFIED =
        'modified';

    public const TYPE_DAY_OFF =
        'day_off';


    protected $fillable = [

        'shift_schedule_id',

        'override_date',

        'override_type',

        'start_time',

        'end_time',

        'grace_minutes',

        'notes',

        'created_by',

        'updated_by',

    ];


    protected $casts = [

        'override_date' =>
            'date',

        'grace_minutes' =>
            'integer',

    ];


    public static function allowedTypes(): array
    {
        return [

            self::TYPE_MODIFIED,

            self::TYPE_DAY_OFF,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Parent Schedule
    |--------------------------------------------------------------------------
    */

    public function shiftSchedule(): BelongsTo
    {
        return $this->belongsTo(
            ShiftSchedule::class
        );
    }
    /*
|--------------------------------------------------------------------------
| Attendance Records
|--------------------------------------------------------------------------
*/

public function attendances(): HasMany
{
    return $this->hasMany(
        Attendance::class
    );
}


    /*
    |--------------------------------------------------------------------------
    | Created By
    |--------------------------------------------------------------------------
    */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Updated By
    |--------------------------------------------------------------------------
    */

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }


    public function isModified(): bool
    {
        return (
            $this->override_type
            ===
            self::TYPE_MODIFIED
        );
    }


    public function isDayOff(): bool
    {
        return (
            $this->override_type
            ===
            self::TYPE_DAY_OFF
        );
    }
}