<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Employee;
use App\Models\ShiftSchedule;
use App\Models\ShiftScheduleOverride;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    /*
    |--------------------------------------------------------------------------
    | Sync Attendance for Selected Date
    |--------------------------------------------------------------------------
    */

    public function syncForDate(
        CarbonInterface|string $date,
        ?User $authUser = null
    ): array {

        $selectedDate =
            $date instanceof CarbonInterface
                ? $date->copy()->startOfDay()
                : Carbon::parse($date)->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | Future Attendance Protection
        |--------------------------------------------------------------------------
        */

        if (
            config(
                'attendance.prevent_future_sync',
                true
            )
            &&
            $selectedDate->gt(
                today()
            )
        ) {
            return [

                'date' =>
                    $selectedDate
                        ->format('Y-m-d'),

                'created' =>
                    0,

                'updated' =>
                    0,

                'removed' =>
                    0,

                'skipped' =>
                    0,

                'conflicts' =>
                    0,

                'auto_checked_out' =>
                    0,

                'employee_status_updates' =>
                    0,

                'total' =>
                    0,

                'future_date_skipped' =>
                    true,

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Auto-close Very Old Open Attendance
        |--------------------------------------------------------------------------
        */

        $autoCheckedOut =
            $selectedDate->isToday()
                ? $this
                    ->finalizeExpiredOpenAttendances(
                        null,
                        $authUser
                    )
                : 0;


        $resolvedSchedules =
            $this->resolveAllSchedulesForDate(
                $selectedDate
            );


        $created = 0;

        $updated = 0;

        $skipped = 0;

        $conflicts = 0;


        /*
        |--------------------------------------------------------------------------
        | Used to Detect Duplicate Employee Schedules
        |--------------------------------------------------------------------------
        */

        $seenEmployees = [];


        /*
        |--------------------------------------------------------------------------
        | Used to Remove Obsolete Auto-generated Attendance
        |--------------------------------------------------------------------------
        */

        $resolvedEmployeeIds =
            $resolvedSchedules
                ->map(
                    fn (array $resolved) =>
                        (int) $resolved[
                            'schedule'
                        ]->employee_id
                )
                ->unique()
                ->values()
                ->all();


        foreach ($resolvedSchedules as $resolved) {

            /** @var ShiftSchedule $schedule */
            $schedule =
                $resolved['schedule'];

            $employee =
                $schedule->employee;


            if (
                ! $employee
                ||
                ! $employee->user
                ||
                ! $employee->user->is_active
                ||
                $employee->user->blocked_at !== null
            ) {
                $skipped++;

                continue;
            }


            if (
                isset(
                    $seenEmployees[
                        $employee->id
                    ]
                )
            ) {
                $conflicts++;

                continue;
            }


            $seenEmployees[
                $employee->id
            ] = true;


            $attendance =
                $this->createOrUpdateAttendance(

                    employee:
                        $employee,

                    schedule:
                        $schedule,

                    override:
                        $resolved['override'],

                    attendanceDate:
                        $selectedDate,

                    scheduledStartAt:
                        $resolved[
                            'scheduled_start_at'
                        ],

                    scheduledEndAt:
                        $resolved[
                            'scheduled_end_at'
                        ],

                    graceMinutes:
                        $resolved[
                            'grace_minutes'
                        ],

                    authUser:
                        $authUser

                );


            if ($attendance->wasRecentlyCreated) {
                $created++;
            }
            else {
                $updated++;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Remove Schedule Rows No Longer Applicable
        |--------------------------------------------------------------------------
        |
        | Examples:
        | - Schedule became inactive
        | - Schedule deleted
        | - One-day Day Off added
        | - Working day removed
        |
        | Only untouched system-generated rows are removed.
        |
        */

        $removed =
            $this
                ->removeObsoleteGeneratedAttendances(
                    $selectedDate,
                    $resolvedEmployeeIds
                );


        /*
        |--------------------------------------------------------------------------
        | Sync Employee Current Status
        |--------------------------------------------------------------------------
        */

        $employeeStatusUpdates =
            $selectedDate->isToday()
                ? $this
                    ->syncEmployeeCurrentStatuses(
                        $authUser
                    )
                : 0;


        return [

            'date' =>
                $selectedDate
                    ->format('Y-m-d'),

            'created' =>
                $created,

            'updated' =>
                $updated,

            'removed' =>
                $removed,

            'skipped' =>
                $skipped,

            'conflicts' =>
                $conflicts,

            'auto_checked_out' =>
                $autoCheckedOut,

            'employee_status_updates' =>
                $employeeStatusUpdates,

            'total' =>
                $created + $updated,

            'future_date_skipped' =>
                false,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Employee Status Change
    |--------------------------------------------------------------------------
    */

    public function handleEmployeeStatusChange(
        Employee $employee,
        string $newStatus,
        User $authUser
    ): ?Attendance {

        return DB::transaction(
            function () use (
                $employee,
                $newStatus,
                $authUser
            ) {

                $employee->loadMissing(
                    'user.role'
                );


                if (
                    ! $employee->user
                    ||
                    ! $employee->user->is_active
                    ||
                    $employee->user->blocked_at !== null
                ) {
                    throw ValidationException::withMessages([

                        'current_status' =>
                            'A blocked or inactive employee cannot update attendance status.',

                    ]);
                }


                if (
                    ! in_array(
                        $newStatus,
                        Employee::allowedStatuses(),
                        true
                    )
                ) {
                    throw ValidationException::withMessages([

                        'current_status' =>
                            'The selected employee status is invalid.',

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Close Any Very Old Open Attendance First
                |--------------------------------------------------------------------------
                */

                $this->finalizeExpiredOpenAttendances(
                    $employee,
                    $authUser
                );


                $attendance =
                    match ($newStatus) {

                        Employee::STATUS_PRESENT =>
                            $this->checkInOrResume(
                                $employee,
                                $authUser
                            ),

                        Employee::STATUS_BREAK =>
                            $this->startBreak(
                                $employee,
                                $authUser
                            ),

                        Employee::STATUS_NONE =>
                            $this->checkOutIfRequired(
                                $employee,
                                $authUser
                            ),

                        Employee::STATUS_ABSENT =>
                            $this->markAbsent(
                                $employee,
                                $authUser
                            ),

                        Employee::STATUS_LEAVE =>
                            $this->markLeave(
                                $employee,
                                $authUser
                            ),

                        default =>
                            null,

                    };


                $employee->update([

                    'current_status' =>
                        $newStatus,

                    'status_updated_at' =>
                        now(),

                    'updated_by' =>
                        $authUser->id,

                ]);


                return $attendance
                    ?->fresh([

                        'employee.user.role',

                        'shiftSchedule',

                        'shiftScheduleOverride',

                        'breaks',

                    ]);

            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Check-in or Resume Break
    |--------------------------------------------------------------------------
    */

    private function checkInOrResume(
        Employee $employee,
        User $authUser
    ): Attendance {

        $now = now();


        /*
        |--------------------------------------------------------------------------
        | Already Checked In
        |--------------------------------------------------------------------------
        */

        $openAttendance =
            $this->findOpenAttendance(
                $employee
            );


        if ($openAttendance) {

            $openBreak =
                $openAttendance
                    ->breaks()
                    ->whereNull('break_end_at')
                    ->latest('break_start_at')
                    ->first();


            if ($openBreak) {

                $this->closeBreak(
                    $openBreak,
                    $authUser,
                    $now
                );

            }


            $this->refreshBreakMinutes(
                $openAttendance,
                $authUser
            );


            $openAttendance->update([

                'status' =>
                    Attendance::STATUS_PRESENT,

                'is_manual' =>
                    true,

                'updated_by' =>
                    $authUser->id,

            ]);


            return $openAttendance;
        }


        /*
        |--------------------------------------------------------------------------
        | Scheduled Attendance for Current Shift
        |--------------------------------------------------------------------------
        */

        $attendance =
            $this->findAttendanceForCheckIn(
                $employee,
                $now
            );


        if (! $attendance) {

            $attendance =
                $this->ensureAttendanceForEmployeeDate(
                    $employee,
                    $now->copy()->startOfDay(),
                    $authUser
                );

        }


        $this->validateCheckInWindow(
            $attendance,
            $now
        );


        if (
            $attendance->status
            ===
            Attendance::STATUS_LEAVE
        ) {
            throw ValidationException::withMessages([

                'current_status' =>
                    'This employee is marked as on leave for this date.',

            ]);
        }


        if ($attendance->check_out_at) {
            throw ValidationException::withMessages([

                'current_status' =>
                    'This attendance record has already been checked out.',

            ]);
        }


        if (! $attendance->check_in_at) {

            $lateMinutes =
                $this->calculateLateMinutes(
                    $attendance,
                    $now
                );


            $attendance->update([

                'check_in_at' =>
                    $now,

                'status' =>
                    Attendance::STATUS_PRESENT,

                'late_minutes' =>
                    $lateMinutes,

                'is_manual' =>
                    true,

                'auto_checked_out' =>
                    false,

                'auto_checkout_reason' =>
                    null,

                'updated_by' =>
                    $authUser->id,

            ]);

        }
        else {

            $attendance->update([

                'status' =>
                    Attendance::STATUS_PRESENT,

                'is_manual' =>
                    true,

                'updated_by' =>
                    $authUser->id,

            ]);

        }


        return $attendance;
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Check-in Time
    |--------------------------------------------------------------------------
    */

    private function validateCheckInWindow(
        Attendance $attendance,
        Carbon $checkInTime
    ): void {

        $earlyCheckInMinutes =
            max(
                0,
                (int) config(
                    'attendance.early_check_in_minutes',
                    120
                )
            );


        $earliestAllowedTime =
            $attendance
                ->scheduled_start_at
                ->copy()
                ->subMinutes(
                    $earlyCheckInMinutes
                );


        if (
            $checkInTime->lt(
                $earliestAllowedTime
            )
        ) {
            throw ValidationException::withMessages([

                'current_status' =>
                    "Check-in is only allowed {$earlyCheckInMinutes} minutes before the scheduled shift.",

            ]);
        }


        if (
            $checkInTime->gt(
                $attendance->scheduled_end_at
            )
        ) {
            throw ValidationException::withMessages([

                'current_status' =>
                    'This shift has already ended. A new check-in is no longer allowed.',

            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Start Break
    |--------------------------------------------------------------------------
    */

    private function startBreak(
        Employee $employee,
        User $authUser
    ): Attendance {

        $attendance =
            $this->findOpenAttendance(
                $employee
            );


        if (
            ! $attendance
            ||
            ! $attendance->check_in_at
        ) {
            throw ValidationException::withMessages([

                'current_status' =>
                    'The employee must check in before starting a break.',

            ]);
        }


        $openBreak =
            $attendance
                ->breaks()
                ->whereNull('break_end_at')
                ->latest('break_start_at')
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Idempotent Break Request
        |--------------------------------------------------------------------------
        */

        if ($openBreak) {

            $attendance->update([

                'status' =>
                    Attendance::STATUS_BREAK,

                'updated_by' =>
                    $authUser->id,

            ]);


            return $attendance;
        }


        AttendanceBreak::create([

            'attendance_id' =>
                $attendance->id,

            'break_start_at' =>
                now(),

            'duration_minutes' =>
                0,

            'created_by' =>
                $authUser->id,

            'updated_by' =>
                $authUser->id,

        ]);


        $attendance->update([

            'status' =>
                Attendance::STATUS_BREAK,

            'is_manual' =>
                true,

            'updated_by' =>
                $authUser->id,

        ]);


        return $attendance;
    }


    /*
    |--------------------------------------------------------------------------
    | Check-out
    |--------------------------------------------------------------------------
    */

    private function checkOutIfRequired(
        Employee $employee,
        User $authUser
    ): ?Attendance {

        $attendance =
            $this->findOpenAttendance(
                $employee
            );


        if (! $attendance) {

            return $employee
                ->attendances()
                ->whereDate(
                    'attendance_date',
                    today()
                )
                ->latest('id')
                ->first();

        }


        $now = now();


        $openBreak =
            $attendance
                ->breaks()
                ->whereNull('break_end_at')
                ->latest('break_start_at')
                ->first();


        if ($openBreak) {

            $this->closeBreak(
                $openBreak,
                $authUser,
                $now
            );

        }


        $attendance->update([

            'check_out_at' =>
                $now,

            'status' =>
                Attendance::STATUS_COMPLETED,

            'is_manual' =>
                true,

            'auto_checked_out' =>
                false,

            'auto_checkout_reason' =>
                null,

            'updated_by' =>
                $authUser->id,

        ]);


        $this->recalculateAttendance(
            $attendance,
            $authUser
        );


        return $attendance;
    }


    /*
    |--------------------------------------------------------------------------
    | Manual Absent
    |--------------------------------------------------------------------------
    */

    private function markAbsent(
        Employee $employee,
        User $authUser
    ): Attendance {

        $attendance =
            $this->ensureAttendanceForEmployeeDate(
                $employee,
                today(),
                $authUser
            );


        if (
            $attendance->check_in_at
            ||
            $attendance->check_out_at
        ) {
            throw ValidationException::withMessages([

                'current_status' =>
                    'A checked-in employee cannot be marked absent.',

            ]);
        }


        $attendance->update([

            'status' =>
                Attendance::STATUS_ABSENT,

            'is_manual' =>
                true,

            'updated_by' =>
                $authUser->id,

        ]);


        return $attendance;
    }


    /*
    |--------------------------------------------------------------------------
    | Manual Leave
    |--------------------------------------------------------------------------
    */

    private function markLeave(
        Employee $employee,
        User $authUser
    ): Attendance {

        $attendance =
            $this->ensureAttendanceForEmployeeDate(
                $employee,
                today(),
                $authUser
            );


        if (
            $attendance->check_in_at
            ||
            $attendance->check_out_at
        ) {
            throw ValidationException::withMessages([

                'current_status' =>
                    'A checked-in employee cannot be marked on leave.',

            ]);
        }


        $attendance->update([

            'status' =>
                Attendance::STATUS_LEAVE,

            'is_manual' =>
                true,

            'updated_by' =>
                $authUser->id,

        ]);


        return $attendance;
    }


    /*
    |--------------------------------------------------------------------------
    | Ensure Attendance Exists
    |--------------------------------------------------------------------------
    */

    private function ensureAttendanceForEmployeeDate(
        Employee $employee,
        CarbonInterface|string $date,
        ?User $authUser = null
    ): Attendance {

        $selectedDate =
            $date instanceof CarbonInterface
                ? $date->copy()->startOfDay()
                : Carbon::parse($date)->startOfDay();


        if (
            config(
                'attendance.prevent_future_sync',
                true
            )
            &&
            $selectedDate->gt(
                today()
            )
        ) {
            throw ValidationException::withMessages([

                'current_status' =>
                    'Attendance cannot be created for a future date.',

            ]);
        }


        $existing =
            Attendance::query()
                ->where(
                    'employee_id',
                    $employee->id
                )
                ->whereDate(
                    'attendance_date',
                    $selectedDate
                )
                ->first();


        if ($existing) {
            return $existing;
        }


        $resolved =
            $this->resolveEmployeeScheduleForDate(
                $employee,
                $selectedDate
            );


        if (! $resolved) {
            throw ValidationException::withMessages([

                'current_status' =>
                    'No active shift schedule was found for this employee and date.',

            ]);
        }


        return $this->createOrUpdateAttendance(

            employee:
                $employee,

            schedule:
                $resolved['schedule'],

            override:
                $resolved['override'],

            attendanceDate:
                $selectedDate,

            scheduledStartAt:
                $resolved['scheduled_start_at'],

            scheduledEndAt:
                $resolved['scheduled_end_at'],

            graceMinutes:
                $resolved['grace_minutes'],

            authUser:
                $authUser

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve All Effective Schedules
    |--------------------------------------------------------------------------
    */

    private function resolveAllSchedulesForDate(
        Carbon $selectedDate
    ): Collection {

        $dateString =
            $selectedDate
                ->format('Y-m-d');

        $dayName =
            strtolower(
                $selectedDate
                    ->format('l')
            );


        $schedules =
            ShiftSchedule::query()
                ->with([

                    'employee.user.role',

                    'overrides' =>
                        function ($query) use (
                            $dateString
                        ) {

                            $query->whereDate(
                                'override_date',
                                $dateString
                            );

                        },

                ])
                ->where(
                    'is_active',
                    true
                )
                ->whereDate(
                    'start_date',
                    '<=',
                    $dateString
                )
                ->whereDate(
                    'end_date',
                    '>=',
                    $dateString
                )
                ->whereHas(
                    'employee.user.role',
                    function ($query) {

                        $query->where(
                            'name',
                            '!=',
                            'admin'
                        );

                    }
                )
                ->where(
                    function ($query) use (
                        $dayName,
                        $dateString
                    ) {

                        $query
                            ->whereJsonContains(
                                'working_days',
                                $dayName
                            )
                            ->orWhereHas(
                                'overrides',
                                function ($overrideQuery) use (
                                    $dateString
                                ) {

                                    $overrideQuery
                                        ->whereDate(
                                            'override_date',
                                            $dateString
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


        return $schedules
            ->map(
                fn (ShiftSchedule $schedule) =>
                    $this->buildEffectiveScheduleData(
                        $schedule,
                        $selectedDate
                    )
            )
            ->filter()
            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve One Employee
    |--------------------------------------------------------------------------
    */

    private function resolveEmployeeScheduleForDate(
        Employee $employee,
        Carbon $selectedDate
    ): ?array {

        $resolvedSchedules =
            $this
                ->resolveAllSchedulesForDate(
                    $selectedDate
                )
                ->filter(
                    fn (array $resolved) =>
                        (int) $resolved[
                            'schedule'
                        ]->employee_id
                        ===
                        (int) $employee->id
                )
                ->values();


        if (
            $resolvedSchedules->count() > 1
        ) {
            throw ValidationException::withMessages([

                'current_status' =>
                    'Multiple active schedules were found for this employee and date.',

            ]);
        }


        return $resolvedSchedules->first();
    }


    /*
    |--------------------------------------------------------------------------
    | Build Effective Schedule
    |--------------------------------------------------------------------------
    */

    private function buildEffectiveScheduleData(
        ShiftSchedule $schedule,
        Carbon $selectedDate
    ): ?array {

        $override =
            $schedule
                ->overrides
                ->first();


        if (
            $override?->override_type
            ===
            ShiftScheduleOverride::TYPE_DAY_OFF
        ) {
            return null;
        }


        $dayName =
            strtolower(
                $selectedDate
                    ->format('l')
            );


        $isRegularDay =
            in_array(
                $dayName,
                $schedule->working_days
                ??
                [],
                true
            );


        $isModifiedOverride =
            $override?->override_type
            ===
            ShiftScheduleOverride::TYPE_MODIFIED;


        if (
            ! $isRegularDay
            &&
            ! $isModifiedOverride
        ) {
            return null;
        }


        $startTime =
            $isModifiedOverride
                ? $override->start_time
                : $schedule->start_time;


        $endTime =
            $isModifiedOverride
                ? $override->end_time
                : $schedule->end_time;


        if (
            ! $startTime
            ||
            ! $endTime
        ) {
            return null;
        }


        $graceMinutes =
            $isModifiedOverride
            &&
            $override->grace_minutes !== null
                ? (int) $override->grace_minutes
                : (int) $schedule->grace_minutes;


        $scheduledStartAt =
            Carbon::parse(
                $selectedDate
                    ->format('Y-m-d')
                .
                ' '
                .
                substr(
                    (string) $startTime,
                    0,
                    5
                )
            );


        $scheduledEndAt =
            Carbon::parse(
                $selectedDate
                    ->format('Y-m-d')
                .
                ' '
                .
                substr(
                    (string) $endTime,
                    0,
                    5
                )
            );


        if (
            $scheduledEndAt
                ->lessThanOrEqualTo(
                    $scheduledStartAt
                )
        ) {
            $scheduledEndAt->addDay();
        }


        return [

            'schedule' =>
                $schedule,

            'override' =>
                $isModifiedOverride
                    ? $override
                    : null,

            'scheduled_start_at' =>
                $scheduledStartAt,

            'scheduled_end_at' =>
                $scheduledEndAt,

            'grace_minutes' =>
                $graceMinutes,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Create or Update Attendance
    |--------------------------------------------------------------------------
    */

    private function createOrUpdateAttendance(
        Employee $employee,
        ShiftSchedule $schedule,
        ?ShiftScheduleOverride $override,
        Carbon $attendanceDate,
        Carbon $scheduledStartAt,
        Carbon $scheduledEndAt,
        int $graceMinutes,
        ?User $authUser = null
    ): Attendance {

        $attendance =
            Attendance::firstOrNew([

                'employee_id' =>
                    $employee->id,

                'attendance_date' =>
                    $attendanceDate
                        ->format('Y-m-d'),

            ]);


        $isNew =
            ! $attendance->exists;


        /*
        |--------------------------------------------------------------------------
        | Historical Snapshot Protection
        |--------------------------------------------------------------------------
        |
        | Checked-in অথবা old historical attendance-এর scheduled snapshot
        | আর পরিবর্তন হবে না।
        |
        */

        if (
            $isNew
            ||
            (
                ! $attendance->check_in_at
                &&
                ! $attendance->check_out_at
                &&
                $attendanceDate->isToday()
            )
        ) {

            $attendance->shift_schedule_id =
                $schedule->id;

            $attendance->shift_schedule_override_id =
                $override?->id;

            $attendance->scheduled_start_at =
                $scheduledStartAt;

            $attendance->scheduled_end_at =
                $scheduledEndAt;

            $attendance->grace_minutes =
                $graceMinutes;

            $attendance->scheduled_minutes =
                $this->minutesBetween(
                    $scheduledStartAt,
                    $scheduledEndAt
                );

        }


        if ($isNew) {

            $attendance->status =
                Attendance::STATUS_SCHEDULED;

            $attendance->is_manual =
                false;

            $attendance->auto_checked_out =
                false;

            $attendance->created_by =
                $authUser?->id;

        }


        if ($authUser) {

            $attendance->updated_by =
                $authUser->id;

        }


        $attendance->save();


        $this->syncAttendanceStatus(
            $attendance,
            now(),
            $authUser
        );


        return $attendance;
    }


    /*
    |--------------------------------------------------------------------------
    | Scheduled / Absent / Present Sync
    |--------------------------------------------------------------------------
    */

    private function syncAttendanceStatus(
        Attendance $attendance,
        Carbon $currentTime,
        ?User $authUser = null
    ): void {

        if ($attendance->check_out_at) {

            $status =
                Attendance::STATUS_COMPLETED;

        }
        elseif ($attendance->check_in_at) {

            $status =
                $attendance->hasOpenBreak()
                    ? Attendance::STATUS_BREAK
                    : Attendance::STATUS_PRESENT;

        }
        elseif (
            $attendance->is_manual
            &&
            in_array(
                $attendance->status,
                [
                    Attendance::STATUS_ABSENT,
                    Attendance::STATUS_LEAVE,
                ],
                true
            )
        ) {

            /*
            | Preserve manually assigned absent or leave.
            */

            $status =
                $attendance->status;

        }
        elseif (
            $currentTime->greaterThan(
                $attendance->scheduled_start_at
            )
        ) {

            /*
            | Exact start time-এ scheduled থাকবে।
            | Start time পার হওয়ার পর absent হবে।
            */

            $status =
                Attendance::STATUS_ABSENT;

        }
        else {

            $status =
                Attendance::STATUS_SCHEDULED;

        }


        $data = [

            'status' =>
                $status,

        ];


        if ($authUser) {

            $data['updated_by'] =
                $authUser->id;

        }


        $attendance->update(
            $data
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Remove Obsolete Generated Attendance
    |--------------------------------------------------------------------------
    */

    private function removeObsoleteGeneratedAttendances(
        Carbon $selectedDate,
        array $resolvedEmployeeIds
    ): int {

        $query =
            Attendance::query()
                ->whereDate(
                    'attendance_date',
                    $selectedDate
                )
                ->where(
                    'is_manual',
                    false
                )
                ->whereNull(
                    'check_in_at'
                )
                ->whereNull(
                    'check_out_at'
                )
                ->whereIn(
                    'status',
                    [
                        Attendance::STATUS_SCHEDULED,
                        Attendance::STATUS_ABSENT,
                    ]
                );


        if (count($resolvedEmployeeIds) > 0) {

            $query->whereNotIn(
                'employee_id',
                $resolvedEmployeeIds
            );

        }


        $attendances =
            $query->get();


        $removed =
            $attendances->count();


        foreach ($attendances as $attendance) {

            $attendance->delete();

        }


        return $removed;
    }


    /*
    |--------------------------------------------------------------------------
    | Find Attendance for Check-in
    |--------------------------------------------------------------------------
    */

    private function findAttendanceForCheckIn(
        Employee $employee,
        Carbon $currentTime
    ): ?Attendance {

        $earlyCheckInMinutes =
            max(
                0,
                (int) config(
                    'attendance.early_check_in_minutes',
                    120
                )
            );


        return $employee
            ->attendances()
            ->whereNull(
                'check_out_at'
            )
            ->where(
                'scheduled_start_at',
                '<=',
                $currentTime
                    ->copy()
                    ->addMinutes(
                        $earlyCheckInMinutes
                    )
            )
            ->where(
                'scheduled_end_at',
                '>=',
                $currentTime
            )
            ->whereDate(
                'attendance_date',
                '>=',
                $currentTime
                    ->copy()
                    ->subDay()
                    ->format('Y-m-d')
            )
            ->orderBy(
                'scheduled_start_at'
            )
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | Find Open Attendance
    |--------------------------------------------------------------------------
    */

    private function findOpenAttendance(
        Employee $employee
    ): ?Attendance {

        return $employee
            ->attendances()
            ->whereNotNull(
                'check_in_at'
            )
            ->whereNull(
                'check_out_at'
            )
            ->latest(
                'check_in_at'
            )
            ->first();
    }


    /*
    |--------------------------------------------------------------------------
    | Auto-close Expired Open Attendance
    |--------------------------------------------------------------------------
    */

    private function finalizeExpiredOpenAttendances(
        ?Employee $employee = null,
        ?User $authUser = null
    ): int {

        $afterMinutes =
            max(
                0,
                (int) config(
                    'attendance.auto_checkout_after_minutes',
                    360
                )
            );


        $expirationThreshold =
            now()
                ->subMinutes(
                    $afterMinutes
                );


        $query =
            Attendance::query()
                ->with([
                    'employee',
                    'breaks',
                ])
                ->whereNotNull(
                    'check_in_at'
                )
                ->whereNull(
                    'check_out_at'
                )
                ->where(
                    'scheduled_end_at',
                    '<=',
                    $expirationThreshold
                );


        if ($employee) {

            $query->where(
                'employee_id',
                $employee->id
            );

        }


        $attendances =
            $query->get();


        foreach ($attendances as $attendance) {

            $checkoutTime =
                $attendance
                    ->scheduled_end_at
                    ->copy();


            if (
                $attendance->check_in_at
                    ->gt(
                        $checkoutTime
                    )
            ) {

                $checkoutTime =
                    $attendance
                        ->check_in_at
                        ->copy();

            }


            $openBreak =
                $attendance
                    ->breaks
                    ->first(
                        fn (AttendanceBreak $break) =>
                            $break->break_end_at === null
                    );


            if ($openBreak) {

                $breakEndTime =
                    $checkoutTime->copy();


                if (
                    $openBreak->break_start_at
                        ->gt(
                            $breakEndTime
                        )
                ) {

                    $breakEndTime =
                        $openBreak
                            ->break_start_at
                            ->copy();

                }


                $this->closeBreak(
                    $openBreak,
                    $authUser,
                    $breakEndTime
                );

            }


            $data = [

                'check_out_at' =>
                    $checkoutTime,

                'status' =>
                    Attendance::STATUS_COMPLETED,

                'auto_checked_out' =>
                    true,

                'auto_checkout_reason' =>
                    "No checkout was recorded within {$afterMinutes} minutes after the scheduled shift end.",

            ];


            if ($authUser) {

                $data['updated_by'] =
                    $authUser->id;

            }


            $attendance->update(
                $data
            );


            $this->recalculateAttendance(
                $attendance,
                $authUser
            );

        }


        return $attendances->count();
    }


    /*
    |--------------------------------------------------------------------------
    | Synchronize Employee Current Status
    |--------------------------------------------------------------------------
    */

    private function syncEmployeeCurrentStatuses(
        ?User $authUser = null
    ): int {

        $employees =
            Employee::query()
                ->with([
                    'user.role',
                ])
                ->whereHas(
                    'user.role',
                    function ($query) {

                        $query->where(
                            'name',
                            '!=',
                            'admin'
                        );

                    }
                )
                ->get();


        $updatedCount = 0;


        foreach ($employees as $employee) {

            $desiredStatus =
                Employee::STATUS_NONE;


            if (
                ! $employee->user
                ||
                ! $employee->user->is_active
                ||
                $employee->user->blocked_at !== null
            ) {

                $desiredStatus =
                    Employee::STATUS_NONE;

            }
            else {

                /*
                |--------------------------------------------------------------------------
                | Open Attendance Has Highest Priority
                |--------------------------------------------------------------------------
                */

                $openAttendance =
                    $employee
                        ->attendances()
                        ->whereNotNull(
                            'check_in_at'
                        )
                        ->whereNull(
                            'check_out_at'
                        )
                        ->latest(
                            'check_in_at'
                        )
                        ->first();


                if ($openAttendance) {

                    $hasOpenBreak =
                        $openAttendance
                            ->breaks()
                            ->whereNull(
                                'break_end_at'
                            )
                            ->exists();


                    $desiredStatus =
                        $hasOpenBreak
                            ? Employee::STATUS_BREAK
                            : Employee::STATUS_PRESENT;

                }
                else {

                    $todayAttendance =
                        $employee
                            ->attendances()
                            ->whereDate(
                                'attendance_date',
                                today()
                            )
                            ->latest('id')
                            ->first();


                    $desiredStatus =
                        match (
                            $todayAttendance?->status
                        ) {

                            Attendance::STATUS_ABSENT =>
                                Employee::STATUS_ABSENT,

                            Attendance::STATUS_LEAVE =>
                                Employee::STATUS_LEAVE,

                            default =>
                                Employee::STATUS_NONE,

                        };

                }

            }


            if (
                $employee->current_status
                !==
                $desiredStatus
            ) {

                $data = [

                    'current_status' =>
                        $desiredStatus,

                    'status_updated_at' =>
                        now(),

                ];


                if ($authUser) {

                    $data['updated_by'] =
                        $authUser->id;

                }


                $employee->update(
                    $data
                );


                $updatedCount++;
            }

        }


        return $updatedCount;
    }


    /*
    |--------------------------------------------------------------------------
    | Close Break
    |--------------------------------------------------------------------------
    */

    private function closeBreak(
        AttendanceBreak $break,
        ?User $authUser,
        Carbon $endTime
    ): void {

        $durationMinutes =
            $this->minutesBetween(
                $break->break_start_at,
                $endTime
            );


        $data = [

            'break_end_at' =>
                $endTime,

            'duration_minutes' =>
                $durationMinutes,

        ];


        if ($authUser) {

            $data['updated_by'] =
                $authUser->id;

        }


        $break->update(
            $data
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Refresh Total Break
    |--------------------------------------------------------------------------
    */

    private function refreshBreakMinutes(
        Attendance $attendance,
        ?User $authUser = null
    ): void {

        $breakMinutes =
            (int) $attendance
                ->breaks()
                ->whereNotNull(
                    'break_end_at'
                )
                ->sum(
                    'duration_minutes'
                );


        $data = [

            'break_minutes' =>
                $breakMinutes,

        ];


        if ($authUser) {

            $data['updated_by'] =
                $authUser->id;

        }


        $attendance->update(
            $data
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Final Attendance Calculation
    |--------------------------------------------------------------------------
    */

    private function recalculateAttendance(
        Attendance $attendance,
        ?User $authUser = null
    ): void {

        $breakMinutes =
            (int) $attendance
                ->breaks()
                ->whereNotNull(
                    'break_end_at'
                )
                ->sum(
                    'duration_minutes'
                );


        $totalAttendanceMinutes =
            $attendance->check_in_at
            &&
            $attendance->check_out_at
                ? $this->minutesBetween(
                    $attendance->check_in_at,
                    $attendance->check_out_at
                )
                : 0;


        $workedMinutes =
            max(
                0,
                $totalAttendanceMinutes
                -
                $breakMinutes
            );


        $overtimeMinutes =
            max(
                0,
                $workedMinutes
                -
                (int) $attendance
                    ->scheduled_minutes
            );


        $earlyLeaveMinutes = 0;


        if (
            $attendance->check_out_at
            &&
            $attendance->check_out_at
                ->lt(
                    $attendance->scheduled_end_at
                )
        ) {

            $earlyLeaveMinutes =
                $this->minutesBetween(
                    $attendance->check_out_at,
                    $attendance->scheduled_end_at
                );

        }


        $data = [

            'worked_minutes' =>
                $workedMinutes,

            'break_minutes' =>
                $breakMinutes,

            'overtime_minutes' =>
                $overtimeMinutes,

            'early_leave_minutes' =>
                $earlyLeaveMinutes,

        ];


        if ($authUser) {

            $data['updated_by'] =
                $authUser->id;

        }


        $attendance->update(
            $data
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Late Calculation
    |--------------------------------------------------------------------------
    */

    private function calculateLateMinutes(
        Attendance $attendance,
        Carbon $checkInTime
    ): int {

        $lateThreshold =
            $attendance
                ->scheduled_start_at
                ->copy()
                ->addMinutes(
                    (int) $attendance
                        ->grace_minutes
                );


        if (
            $checkInTime
                ->lessThanOrEqualTo(
                    $lateThreshold
                )
        ) {
            return 0;
        }


        return $this->minutesBetween(
            $lateThreshold,
            $checkInTime
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Minute Difference
    |--------------------------------------------------------------------------
    */

    private function minutesBetween(
        CarbonInterface $start,
        CarbonInterface $end
    ): int {

        return max(
            0,
            intdiv(
                $end->getTimestamp()
                -
                $start->getTimestamp(),
                60
            )
        );
    }
}