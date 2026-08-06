<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\ShiftSchedule;
use App\Models\ShiftScheduleOverride;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftScheduleService
{
    /*
    |--------------------------------------------------------------------------
    | Create Recurring Schedule
    |--------------------------------------------------------------------------
    */

    public function createSchedule(
        array $data,
        User $authUser
    ): ShiftSchedule {

        return DB::transaction(
            function () use (
                $data,
                $authUser
            ) {

                $employee =
                    $this->getValidEmployee(
                        (int) $data['employee_id']
                    );

                $workingDays =
                    $this->normalizeWorkingDays(
                        $data['working_days']
                    );

                $this->validateDateRange(
                    $data['start_date'],
                    $data['end_date']
                );

                $this->validateTimes(
                    $data['start_time'],
                    $data['end_time']
                );

                $isActive =
                    array_key_exists(
                        'is_active',
                        $data
                    )
                        ? (bool) $data['is_active']
                        : true;

                if ($isActive) {

                    $this->ensureNoScheduleConflict(

                        employeeId:
                            $employee->id,

                        startDate:
                            $data['start_date'],

                        endDate:
                            $data['end_date'],

                        workingDays:
                            $workingDays

                    );

                }

                $schedule =
                    ShiftSchedule::create([

                        'employee_id' =>
                            $employee->id,

                        'start_date' =>
                            $data['start_date'],

                        'end_date' =>
                            $data['end_date'],

                        'working_days' =>
                            $workingDays,

                        'start_time' =>
                            $data['start_time'],

                        'end_time' =>
                            $data['end_time'],

                        'grace_minutes' =>
                            $data['grace_minutes']
                            ??
                            0,

                        'is_active' =>
                            $isActive,

                        'notes' =>
                            $data['notes']
                            ??
                            null,

                        'created_by' =>
                            $authUser->id,

                        'updated_by' =>
                            $authUser->id,

                    ]);

                return $schedule->fresh([

                    'employee.user.role',

                    'overrides',

                    'creator',

                    'updater',

                ]);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update Recurring Schedule
    |--------------------------------------------------------------------------
    */

    public function updateSchedule(
        ShiftSchedule $schedule,
        array $data,
        User $authUser
    ): ShiftSchedule {

        return DB::transaction(
            function () use (
                $schedule,
                $data,
                $authUser
            ) {

                $employeeId =
                    array_key_exists(
                        'employee_id',
                        $data
                    )
                        ? (int) $data['employee_id']
                        : (int) $schedule->employee_id;

                $startDate =
                    $data['start_date']
                    ??
                    $schedule->start_date
                        ->format('Y-m-d');

                $endDate =
                    $data['end_date']
                    ??
                    $schedule->end_date
                        ->format('Y-m-d');

                $workingDays =
                    array_key_exists(
                        'working_days',
                        $data
                    )
                        ? $this->normalizeWorkingDays(
                            $data['working_days']
                        )
                        : $this->normalizeWorkingDays(
                            $schedule->working_days
                            ??
                            []
                        );

                $startTime =
                    $data['start_time']
                    ??
                    substr(
                        (string) $schedule->start_time,
                        0,
                        5
                    );

                $endTime =
                    $data['end_time']
                    ??
                    substr(
                        (string) $schedule->end_time,
                        0,
                        5
                    );

                $isActive =
                    array_key_exists(
                        'is_active',
                        $data
                    )
                        ? (bool) $data['is_active']
                        : (bool) $schedule->is_active;

                $this->getValidEmployee(
                    $employeeId
                );

                $this->validateDateRange(
                    $startDate,
                    $endDate
                );

                $this->validateTimes(
                    $startTime,
                    $endTime
                );

                $this->ensureOverridesInsideRange(
                    $schedule,
                    $startDate,
                    $endDate
                );

                $this->ensureDayOffOverridesRemainValid(
                    $schedule,
                    $workingDays
                );

                if ($isActive) {

                    $this->ensureNoScheduleConflict(

                        employeeId:
                            $employeeId,

                        startDate:
                            $startDate,

                        endDate:
                            $endDate,

                        workingDays:
                            $workingDays,

                        ignoreScheduleId:
                            $schedule->id

                    );

                }

                $scheduleData = [

                    'employee_id' =>
                        $employeeId,

                    'start_date' =>
                        $startDate,

                    'end_date' =>
                        $endDate,

                    'working_days' =>
                        $workingDays,

                    'start_time' =>
                        $startTime,

                    'end_time' =>
                        $endTime,

                    'is_active' =>
                        $isActive,

                    'updated_by' =>
                        $authUser->id,

                ];

                if (
                    array_key_exists(
                        'grace_minutes',
                        $data
                    )
                ) {

                    $scheduleData['grace_minutes'] =
                        $data['grace_minutes'];

                }

                if (
                    array_key_exists(
                        'notes',
                        $data
                    )
                ) {

                    $scheduleData['notes'] =
                        $data['notes'];

                }

                $schedule->update(
                    $scheduleData
                );

                return $schedule->fresh([

                    'employee.user.role',

                    'overrides',

                    'creator',

                    'updater',

                ]);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Activate / Deactivate
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        ShiftSchedule $schedule,
        bool $isActive,
        User $authUser
    ): ShiftSchedule {

        if ($isActive) {

            $this->ensureNoScheduleConflict(

                employeeId:
                    (int) $schedule->employee_id,

                startDate:
                    $schedule->start_date
                        ->format('Y-m-d'),

                endDate:
                    $schedule->end_date
                        ->format('Y-m-d'),

                workingDays:
                    $schedule->working_days
                    ??
                    [],

                ignoreScheduleId:
                    $schedule->id

            );

        }

        $schedule->update([

            'is_active' =>
                $isActive,

            'updated_by' =>
                $authUser->id,

        ]);

        return $schedule->fresh([

            'employee.user.role',

            'overrides',

            'creator',

            'updater',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Schedule
    |--------------------------------------------------------------------------
    */

    public function deleteSchedule(
        ShiftSchedule $schedule,
        User $authUser
    ): void {

        DB::transaction(
            function () use (
                $schedule,
                $authUser
            ) {

                $schedule->update([

                    'is_active' =>
                        false,

                    'updated_by' =>
                        $authUser->id,

                ]);

                $schedule->delete();

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Create One-Day Override
    |--------------------------------------------------------------------------
    */

    public function createOverride(
        ShiftSchedule $schedule,
        array $data,
        User $authUser
    ): ShiftScheduleOverride {

        return DB::transaction(
            function () use (
                $schedule,
                $data,
                $authUser
            ) {

                $overrideDate =
                    Carbon::parse(
                        $data['override_date']
                    )->format('Y-m-d');

                $overrideType =
                    $data['override_type'];

                $this->validateOverrideDate(
                    $schedule,
                    $overrideDate
                );

                $this->ensureNoDuplicateOverride(
                    $schedule->id,
                    $overrideDate
                );

                $this->validateOverrideTypeRules(
                    $schedule,
                    $overrideDate,
                    $overrideType,
                    $data['start_time']
                    ??
                    null,
                    $data['end_time']
                    ??
                    null
                );

                if (
                    $overrideType
                    ===
                    ShiftScheduleOverride::TYPE_MODIFIED
                ) {

                    $this->ensureNoOtherScheduleForDate(
                        $schedule,
                        $overrideDate
                    );

                }

                $override =
                    ShiftScheduleOverride::create([

                        'shift_schedule_id' =>
                            $schedule->id,

                        'override_date' =>
                            $overrideDate,

                        'override_type' =>
                            $overrideType,

                        'start_time' =>
                            $overrideType ===
                            ShiftScheduleOverride::TYPE_MODIFIED
                                ? $data['start_time']
                                : null,

                        'end_time' =>
                            $overrideType ===
                            ShiftScheduleOverride::TYPE_MODIFIED
                                ? $data['end_time']
                                : null,

                        'grace_minutes' =>
                            $overrideType ===
                            ShiftScheduleOverride::TYPE_MODIFIED
                                ? (
                                    $data['grace_minutes']
                                    ??
                                    null
                                )
                                : null,

                        'notes' =>
                            $data['notes']
                            ??
                            null,

                        'created_by' =>
                            $authUser->id,

                        'updated_by' =>
                            $authUser->id,

                    ]);

                return $override->fresh([

                    'shiftSchedule.employee.user.role',

                    'creator',

                    'updater',

                ]);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Update One-Day Override
    |--------------------------------------------------------------------------
    */

    public function updateOverride(
        ShiftScheduleOverride $override,
        array $data,
        User $authUser
    ): ShiftScheduleOverride {

        return DB::transaction(
            function () use (
                $override,
                $data,
                $authUser
            ) {

                $override->loadMissing(
                    'shiftSchedule'
                );

                $schedule =
                    $override->shiftSchedule;

                $overrideDate =
                    $data['override_date']
                    ??
                    $override->override_date
                        ->format('Y-m-d');

                $overrideType =
                    $data['override_type']
                    ??
                    $override->override_type;

                $startTime =
                    array_key_exists(
                        'start_time',
                        $data
                    )
                        ? $data['start_time']
                        : $override->start_time;

                $endTime =
                    array_key_exists(
                        'end_time',
                        $data
                    )
                        ? $data['end_time']
                        : $override->end_time;

                $this->validateOverrideDate(
                    $schedule,
                    $overrideDate
                );

                $this->ensureNoDuplicateOverride(
                    $schedule->id,
                    $overrideDate,
                    $override->id
                );

                $this->validateOverrideTypeRules(
                    $schedule,
                    $overrideDate,
                    $overrideType,
                    $startTime,
                    $endTime
                );

                if (
                    $overrideType
                    ===
                    ShiftScheduleOverride::TYPE_MODIFIED
                ) {

                    $this->ensureNoOtherScheduleForDate(
                        $schedule,
                        $overrideDate
                    );

                }

                $overrideData = [

                    'override_date' =>
                        $overrideDate,

                    'override_type' =>
                        $overrideType,

                    'start_time' =>
                        $overrideType ===
                        ShiftScheduleOverride::TYPE_MODIFIED
                            ? $startTime
                            : null,

                    'end_time' =>
                        $overrideType ===
                        ShiftScheduleOverride::TYPE_MODIFIED
                            ? $endTime
                            : null,

                    'grace_minutes' =>
                        $overrideType ===
                        ShiftScheduleOverride::TYPE_MODIFIED
                            ? (
                                array_key_exists(
                                    'grace_minutes',
                                    $data
                                )
                                    ? $data['grace_minutes']
                                    : $override->grace_minutes
                            )
                            : null,

                    'updated_by' =>
                        $authUser->id,

                ];

                if (
                    array_key_exists(
                        'notes',
                        $data
                    )
                ) {

                    $overrideData['notes'] =
                        $data['notes'];

                }

                $override->update(
                    $overrideData
                );

                return $override->fresh([

                    'shiftSchedule.employee.user.role',

                    'creator',

                    'updater',

                ]);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Override
    |--------------------------------------------------------------------------
    |
    | Override delete করলে ওই দিন আবার regular schedule follow করবে।
    |
    */

    public function deleteOverride(
        ShiftScheduleOverride $override,
        User $authUser
    ): void {

        DB::transaction(
            function () use (
                $override,
                $authUser
            ) {

                $override->update([

                    'updated_by' =>
                        $authUser->id,

                ]);

                $override->delete();

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Employee Validation
    |--------------------------------------------------------------------------
    */

    private function getValidEmployee(
        int $employeeId
    ): Employee {

        $employee =
            Employee::query()
                ->with([
                    'user.role',
                ])
                ->find($employeeId);

        if (
            ! $employee
            ||
            ! $employee->user
        ) {

            throw ValidationException::withMessages([

                'employee_id' =>
                    'The selected employee account could not be found.',

            ]);

        }

        if (
            ! $employee->user->role
            ||
            $employee->user->role->name === 'admin'
        ) {

            throw ValidationException::withMessages([

                'employee_id' =>
                    'Admin accounts cannot be assigned a staff schedule.',

            ]);

        }

        return $employee;
    }


    /*
    |--------------------------------------------------------------------------
    | Date and Time Validation
    |--------------------------------------------------------------------------
    */

    private function validateDateRange(
        string $startDate,
        string $endDate
    ): void {

        if (
            Carbon::parse($endDate)
                ->lt(
                    Carbon::parse($startDate)
                )
        ) {

            throw ValidationException::withMessages([

                'end_date' =>
                    'Schedule end date cannot be before the start date.',

            ]);

        }
    }


    private function validateTimes(
        mixed $startTime,
        mixed $endTime
    ): void {

        $normalizedStart =
            substr(
                (string) $startTime,
                0,
                5
            );

        $normalizedEnd =
            substr(
                (string) $endTime,
                0,
                5
            );

        if (
            ! $normalizedStart
            ||
            ! $normalizedEnd
            ||
            $normalizedStart === $normalizedEnd
        ) {

            throw ValidationException::withMessages([

                'end_time' =>
                    'Shift start time and end time cannot be the same.',

            ]);

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Recurring Schedule Conflict
    |--------------------------------------------------------------------------
    |
    | Date range overlap এবং working day overlap হলে conflict।
    |
    */

    private function ensureNoScheduleConflict(
        int $employeeId,
        string $startDate,
        string $endDate,
        array $workingDays,
        ?int $ignoreScheduleId = null
    ): void {

        $workingDays =
            $this->normalizeWorkingDays(
                $workingDays
            );

        $query =
            ShiftSchedule::query()
                ->where(
                    'employee_id',
                    $employeeId
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereDate(
                    'start_date',
                    '<=',
                    $endDate
                )
                ->whereDate(
                    'end_date',
                    '>=',
                    $startDate
                );

        if ($ignoreScheduleId !== null) {

            $query->where(
                'id',
                '!=',
                $ignoreScheduleId
            );

        }

        $conflictingSchedule =
            $query
                ->get()
                ->first(
                    function (
                        ShiftSchedule $existingSchedule
                    ) use ($workingDays) {

                        return count(
                            array_intersect(
                                $workingDays,
                                $existingSchedule
                                    ->working_days
                                ??
                                []
                            )
                        ) > 0;

                    }
                );

        if ($conflictingSchedule) {

            throw ValidationException::withMessages([

                'working_days' =>
                    'This employee already has an active schedule with overlapping dates and working days.',

                'start_date' =>
                    'The selected date range conflicts with another schedule.',

            ]);

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Override Validation
    |--------------------------------------------------------------------------
    */

    private function validateOverrideDate(
        ShiftSchedule $schedule,
        string $overrideDate
    ): void {

        $date =
            Carbon::parse(
                $overrideDate
            );

        if (
            $date->lt(
                $schedule->start_date
            )
            ||
            $date->gt(
                $schedule->end_date
            )
        ) {

            throw ValidationException::withMessages([

                'override_date' =>
                    'Override date must be inside the schedule date range.',

            ]);

        }
    }


    private function validateOverrideTypeRules(
        ShiftSchedule $schedule,
        string $overrideDate,
        string $overrideType,
        mixed $startTime,
        mixed $endTime
    ): void {

        if (
            $overrideType
            ===
            ShiftScheduleOverride::TYPE_DAY_OFF
        ) {

            if (
                ! $schedule->appliesToDate(
                    $overrideDate
                )
            ) {

                throw ValidationException::withMessages([

                    'override_date' =>
                        'Day-off override can only be added to a regular working day.',

                ]);

            }

            return;
        }

        if (
            $overrideType
            ===
            ShiftScheduleOverride::TYPE_MODIFIED
        ) {

            if (
                ! $startTime
                ||
                ! $endTime
            ) {

                throw ValidationException::withMessages([

                    'start_time' =>
                        'Modified shift start time is required.',

                    'end_time' =>
                        'Modified shift end time is required.',

                ]);

            }

            $this->validateTimes(
                $startTime,
                $endTime
            );

        }
    }


    private function ensureNoDuplicateOverride(
        int $scheduleId,
        string $overrideDate,
        ?int $ignoreOverrideId = null
    ): void {

        $query =
            ShiftScheduleOverride::query()
                ->where(
                    'shift_schedule_id',
                    $scheduleId
                )
                ->whereDate(
                    'override_date',
                    $overrideDate
                );

        if ($ignoreOverrideId !== null) {

            $query->where(
                'id',
                '!=',
                $ignoreOverrideId
            );

        }

        if ($query->exists()) {

            throw ValidationException::withMessages([

                'override_date' =>
                    'An override already exists for this schedule and date.',

            ]);

        }
    }


    /*
    |--------------------------------------------------------------------------
    | Extra / Modified Day Conflict
    |--------------------------------------------------------------------------
    */

    private function ensureNoOtherScheduleForDate(
        ShiftSchedule $schedule,
        string $overrideDate
    ): void {

        $date =
            Carbon::parse(
                $overrideDate
            );

        $dayName =
            strtolower(
                $date->format('l')
            );

        $otherSchedules =
            ShiftSchedule::query()
                ->with([
                    'overrides' =>
                        function ($query) use ($overrideDate) {

                            $query->whereDate(
                                'override_date',
                                $overrideDate
                            );

                        },
                ])
                ->where(
                    'employee_id',
                    $schedule->employee_id
                )
                ->where(
                    'id',
                    '!=',
                    $schedule->id
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereDate(
                    'start_date',
                    '<=',
                    $overrideDate
                )
                ->whereDate(
                    'end_date',
                    '>=',
                    $overrideDate
                )
                ->where(
                    function ($query) use (
                        $dayName,
                        $overrideDate
                    ) {

                        $query
                            ->whereJsonContains(
                                'working_days',
                                $dayName
                            )
                            ->orWhereHas(
                                'overrides',
                                function ($overrideQuery) use (
                                    $overrideDate
                                ) {

                                    $overrideQuery
                                        ->whereDate(
                                            'override_date',
                                            $overrideDate
                                        )
                                        ->where(
                                            'override_type',
                                            ShiftScheduleOverride::TYPE_MODIFIED
                                        );

                                }
                            );

                    }
                )
                ->get();

        foreach ($otherSchedules as $otherSchedule) {

            $otherOverride =
                $otherSchedule
                    ->overrides
                    ->first();

            if (
                $otherOverride?->override_type
                ===
                ShiftScheduleOverride::TYPE_DAY_OFF
            ) {
                continue;
            }

            throw ValidationException::withMessages([

                'override_date' =>
                    'This employee already has another active schedule for the selected date.',

            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Keep Existing Overrides Valid
    |--------------------------------------------------------------------------
    */

    private function ensureOverridesInsideRange(
        ShiftSchedule $schedule,
        string $startDate,
        string $endDate
    ): void {

        $invalidOverrideExists =
            $schedule
                ->overrides()
                ->where(
                    function ($query) use (
                        $startDate,
                        $endDate
                    ) {

                        $query
                            ->whereDate(
                                'override_date',
                                '<',
                                $startDate
                            )
                            ->orWhereDate(
                                'override_date',
                                '>',
                                $endDate
                            );

                    }
                )
                ->exists();

        if ($invalidOverrideExists) {

            throw ValidationException::withMessages([

                'start_date' =>
                    'The new date range would exclude one or more existing one-day overrides.',

                'end_date' =>
                    'Delete or update the affected overrides before changing this date range.',

            ]);

        }
    }


    private function ensureDayOffOverridesRemainValid(
        ShiftSchedule $schedule,
        array $workingDays
    ): void {

        $workingDays =
            $this->normalizeWorkingDays(
                $workingDays
            );

        $dayOffOverrides =
            $schedule
                ->overrides()
                ->where(
                    'override_type',
                    ShiftScheduleOverride::TYPE_DAY_OFF
                )
                ->get();

        foreach ($dayOffOverrides as $override) {

            $dayName =
                strtolower(
                    $override
                        ->override_date
                        ->format('l')
                );

            if (
                ! in_array(
                    $dayName,
                    $workingDays,
                    true
                )
            ) {

                throw ValidationException::withMessages([

                    'working_days' =>
                        'One or more existing day-off overrides would become invalid after changing the working days.',

                ]);

            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Working Day Normalizer
    |--------------------------------------------------------------------------
    */

    private function normalizeWorkingDays(
        array $workingDays
    ): array {

        $allowedDays = [

            'saturday',

            'sunday',

            'monday',

            'tuesday',

            'wednesday',

            'thursday',

            'friday',

        ];

        return array_values(
            array_filter(
                array_unique(
                    array_map(
                        fn ($day) =>
                            strtolower(
                                trim(
                                    (string) $day
                                )
                            ),
                        $workingDays
                    )
                ),
                fn ($day) =>
                    in_array(
                        $day,
                        $allowedDays,
                        true
                    )
            )
        );
    }
}