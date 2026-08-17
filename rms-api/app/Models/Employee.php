<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_NONE = 'none';

    public const STATUS_PRESENT = 'present';

    public const STATUS_BREAK = 'break';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LEAVE = 'leave';

    protected $fillable = [

        'user_id',

        'phone',

        'joining_date',

        'hourly_rate',

        'current_status',

        'status_updated_at',

        'created_by',

        'updated_by',

    ];

    protected $casts = [

        'joining_date' =>
            'date',

        'hourly_rate' =>
            'decimal:2',

        'status_updated_at' =>
            'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Allowed Working Statuses
    |--------------------------------------------------------------------------
    */

    public static function allowedStatuses(): array
    {
        return [

            self::STATUS_NONE,

            self::STATUS_PRESENT,

            self::STATUS_BREAK,

            self::STATUS_ABSENT,

            self::STATUS_LEAVE,

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | User Account
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this
            ->belongsTo(
                User::class,
                'user_id'
            )
            ->with('role');
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

    /*
    |--------------------------------------------------------------------------
    | Compatibility Relationships
    |--------------------------------------------------------------------------
    */

    public function createdBy(): BelongsTo
    {
        return $this->creator();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->updater();
    }

    /*
    |--------------------------------------------------------------------------
    | Current Status Label
    |--------------------------------------------------------------------------
    */

    public function currentStatusLabel(): string
    {
        return match ($this->current_status) {

            self::STATUS_NONE =>
                'Not Checked In',

            self::STATUS_PRESENT =>
                'Working',

            self::STATUS_BREAK =>
                'On Break',

            self::STATUS_ABSENT =>
                'Absent',

            self::STATUS_LEAVE =>
                'On Leave',

            default =>
                ucfirst(
                    (string) $this->current_status
                ),

        };
    }
    /*
    |--------------------------------------------------------------------------
    | Shift Schedules
    |--------------------------------------------------------------------------
    */

    public function shiftSchedules(): HasMany
    {
        return $this->hasMany(
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
}