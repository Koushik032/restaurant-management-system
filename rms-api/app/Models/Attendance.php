<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Attendance extends Model
{
    use HasFactory;


    public const STATUS_SCHEDULED =
        'scheduled';

    public const STATUS_ABSENT =
        'absent';

    public const STATUS_PRESENT =
        'present';

    public const STATUS_BREAK =
        'break';

    public const STATUS_COMPLETED =
        'completed';

    public const STATUS_LEAVE =
        'leave';


    protected $fillable = [

        'employee_id',

        'shift_schedule_id',

        'shift_schedule_override_id',

        'attendance_date',

        'scheduled_start_at',

        'scheduled_end_at',

        'grace_minutes',

        'scheduled_minutes',

        'check_in_at',

        'check_out_at',

        'auto_checked_out',

        'auto_checkout_reason',

        'status',

        'late_minutes',

        'worked_minutes',

        'break_minutes',

        'overtime_minutes',

        'early_leave_minutes',

        'is_manual',

        'notes',

        'created_by',

        'updated_by',

    ];


    protected $casts = [

        'attendance_date' =>
            'date',

        'scheduled_start_at' =>
            'datetime',

        'scheduled_end_at' =>
            'datetime',

        'check_in_at' =>
            'datetime',

        'check_out_at' =>
            'datetime',

        'auto_checked_out' =>
            'boolean',

        'grace_minutes' =>
            'integer',

        'scheduled_minutes' =>
            'integer',

        'late_minutes' =>
            'integer',

        'worked_minutes' =>
            'integer',

        'break_minutes' =>
            'integer',

        'overtime_minutes' =>
            'integer',

        'early_leave_minutes' =>
            'integer',

        'is_manual' =>
            'boolean',

    ];


    public static function allowedStatuses(): array
    {
        return [

            self::STATUS_SCHEDULED,

            self::STATUS_ABSENT,

            self::STATUS_PRESENT,

            self::STATUS_BREAK,

            self::STATUS_COMPLETED,

            self::STATUS_LEAVE,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Employee
    |--------------------------------------------------------------------------
    */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'employee_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Salary Detail
    |--------------------------------------------------------------------------
    */

    public function salaryDetail(): HasOne
    {
        return $this->hasOne(
            SalaryDetail::class,
            'attendance_id',
            'id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Recurring Schedule
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
    | One-Day Override
    |--------------------------------------------------------------------------
    */

    public function shiftScheduleOverride(): BelongsTo
    {
        return $this->belongsTo(
            ShiftScheduleOverride::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Breaks
    |--------------------------------------------------------------------------
    */

    public function breaks(): HasMany
    {
        return $this->hasMany(
            AttendanceBreak::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isCheckedIn(): bool
    {
        return (
            $this->check_in_at !== null
            &&
            $this->check_out_at === null
        );
    }


    public function isCheckedOut(): bool
    {
        return (
            $this->check_out_at !== null
        );
    }


    public function hasOpenBreak(): bool
    {
        if ($this->relationLoaded('breaks')) {

            return $this->breaks
                ->contains(
                    fn (AttendanceBreak $break) =>
                        $break->break_end_at === null
                );

        }

        return $this
            ->breaks()
            ->whereNull('break_end_at')
            ->exists();
    }


    public function statusLabel(): string
    {
        return match ($this->status) {

            self::STATUS_SCHEDULED =>
                'Scheduled',

            self::STATUS_ABSENT =>
                'Absent',

            self::STATUS_PRESENT =>
                'Present',

            self::STATUS_BREAK =>
                'On Break',

            self::STATUS_COMPLETED =>
                'Checked Out',

            self::STATUS_LEAVE =>
                'On Leave',

            default =>
                ucfirst(
                    (string) $this->status
                ),

        };
    }
}