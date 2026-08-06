<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftSchedule extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [

        'employee_id',

        'start_date',

        'end_date',

        'working_days',

        'start_time',

        'end_time',

        'grace_minutes',

        'is_active',

        'notes',

        'created_by',

        'updated_by',

    ];


    protected $casts = [

        'start_date' =>
            'date',

        'end_date' =>
            'date',

        'working_days' =>
            'array',

        'grace_minutes' =>
            'integer',

        'is_active' =>
            'boolean',

    ];


    /*
    |--------------------------------------------------------------------------
    | Employee
    |--------------------------------------------------------------------------
    */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | One-Day Overrides
    |--------------------------------------------------------------------------
    */

    public function overrides(): HasMany
    {
        return $this->hasMany(
            ShiftScheduleOverride::class
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


    /*
    |--------------------------------------------------------------------------
    | Applies to Selected Date
    |--------------------------------------------------------------------------
    */

    public function appliesToDate(
        CarbonInterface|string $date
    ): bool {

        $selectedDate =
            $date instanceof CarbonInterface
                ? $date->copy()
                : Carbon::parse($date);

        if (
            ! $this->start_date
            ||
            ! $this->end_date
        ) {
            return false;
        }

        $startBoundary =
    $this->start_date
        ->copy()
        ->startOfDay();

$endBoundary =
    $this->end_date
        ->copy()
        ->endOfDay();


if (
    $selectedDate->lt(
        $startBoundary
    )
    ||
    $selectedDate->gt(
        $endBoundary
    )
) {
    return false;
}

        $dayName = strtolower(
            $selectedDate->format('l')
        );

        return in_array(
            $dayName,
            $this->working_days ?? [],
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Override for Date
    |--------------------------------------------------------------------------
    */

    public function overrideForDate(
        CarbonInterface|string $date
    ): ?ShiftScheduleOverride {

        $dateString =
            $date instanceof CarbonInterface
                ? $date->format('Y-m-d')
                : Carbon::parse($date)
                    ->format('Y-m-d');

        if ($this->relationLoaded('overrides')) {

            return $this->overrides
                ->first(
                    function (
                        ShiftScheduleOverride $override
                    ) use ($dateString) {

                        return (
                            $override->override_date
                                ?->format('Y-m-d')
                            ===
                            $dateString
                        );

                    }
                );

        }

        return $this->overrides()
            ->whereDate(
                'override_date',
                $dateString
            )
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | Time Helpers
    |--------------------------------------------------------------------------
    */

    public function isOvernightFor(
        mixed $startTime = null,
        mixed $endTime = null
    ): bool {

        $start =
            $this->timeToMinutes(
                $startTime
                ??
                $this->start_time
            );

        $end =
            $this->timeToMinutes(
                $endTime
                ??
                $this->end_time
            );

        if (
            $start === null
            ||
            $end === null
        ) {
            return false;
        }

        return $end <= $start;
    }


    public function scheduledMinutesFor(
        mixed $startTime = null,
        mixed $endTime = null
    ): int {

        $start =
            $this->timeToMinutes(
                $startTime
                ??
                $this->start_time
            );

        $end =
            $this->timeToMinutes(
                $endTime
                ??
                $this->end_time
            );

        if (
            $start === null
            ||
            $end === null
            ||
            $start === $end
        ) {
            return 0;
        }

        if ($end < $start) {
            $end += 1440;
        }

        return $end - $start;
    }


    public function scheduledHoursFor(
        mixed $startTime = null,
        mixed $endTime = null
    ): float {

        return round(
            $this->scheduledMinutesFor(
                $startTime,
                $endTime
            ) / 60,
            2
        );
    }


    private function timeToMinutes(
        mixed $time
    ): ?int {

        if (! $time) {
            return null;
        }

        $timeString =
            substr(
                (string) $time,
                0,
                5
            );

        $parts =
            explode(
                ':',
                $timeString
            );

        if (count($parts) !== 2) {
            return null;
        }

        return (
            ((int) $parts[0]) * 60
            +
            (int) $parts[1]
        );
    }
}