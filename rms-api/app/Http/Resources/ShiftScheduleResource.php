<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftScheduleResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {

        $selectedDate =
            Carbon::parse(
                $request->input(
                    'schedule_date',
                    now()->format('Y-m-d')
                )
            );

        $override =
            $this->overrideForDate(
                $selectedDate
            );

        $isDayOff =
            $override?->override_type
            ===
            'day_off';

        $hasModifiedOverride =
            $override?->override_type
            ===
            'modified';

        $effectiveStartTime =
            $isDayOff
                ? null
                : (
                    $hasModifiedOverride
                        ? $override->start_time
                        : $this->start_time
                );

        $effectiveEndTime =
            $isDayOff
                ? null
                : (
                    $hasModifiedOverride
                        ? $override->end_time
                        : $this->end_time
                );

        $effectiveGraceMinutes =
            $isDayOff
                ? 0
                : (
                    $hasModifiedOverride
                    &&
                    $override->grace_minutes !== null
                        ? (int) $override->grace_minutes
                        : (int) $this->grace_minutes
                );

        $scheduledMinutes =
            $isDayOff
                ? 0
                : $this->scheduledMinutesFor(
                    $effectiveStartTime,
                    $effectiveEndTime
                );

        $employeeAccountActive =
            (
                $this->employee
                    ?->user
                    ?->is_active
                ??
                false
            )
            &&
            $this->employee
                ?->user
                ?->blocked_at === null;

        $effectiveStatus =
            ! $this->is_active
                ? 'inactive'
                : (
                    $isDayOff
                        ? 'day_off'
                        : 'active'
                );

        return [

            /*
            |--------------------------------------------------------------------------
            | Basic
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,

            'employee_id' =>
                (int) $this->employee_id,

            /*
            |--------------------------------------------------------------------------
            | Employee
            |--------------------------------------------------------------------------
            */

            'staff_name' =>
                $this->employee
                    ?->user
                    ?->name
                ??
                'Unknown Staff',

            'username' =>
                $this->employee
                    ?->user
                    ?->username,

            'phone' =>
                $this->employee
                    ?->phone,

            'email' =>
                $this->employee
                    ?->user
                    ?->email,

            'role_id' =>
                $this->employee
                    ?->user
                    ?->role_id
                        ? (int)
                            $this->employee
                                ->user
                                ->role_id
                        : null,

            'role_name' =>
                $this->employee
                    ?->user
                    ?->role
                    ?->name,

            'role_label' =>
                $this->roleLabel(),

            'employee_account_active' =>
                $employeeAccountActive,

            /*
            |--------------------------------------------------------------------------
            | Recurring Schedule Range
            |--------------------------------------------------------------------------
            */

            'start_date' =>
                $this->start_date
                    ?->format('Y-m-d'),

            'start_date_label' =>
                $this->start_date
                    ?->format('d M Y'),

            'end_date' =>
                $this->end_date
                    ?->format('Y-m-d'),

            'end_date_label' =>
                $this->end_date
                    ?->format('d M Y'),

            'date_range_label' =>
                $this->dateRangeLabel(),

            'working_days' =>
                $this->working_days
                ??
                [],

            'working_day_labels' =>
                collect(
                    $this->working_days
                    ??
                    []
                )
                    ->map(
                        fn ($day) =>
                            ucfirst($day)
                    )
                    ->values(),

            /*
            |--------------------------------------------------------------------------
            | Base Schedule Time
            |--------------------------------------------------------------------------
            */

            'base_start_time' =>
                $this->normalizeTime(
                    $this->start_time
                ),

            'base_start_time_label' =>
                $this->formatTime(
                    $this->start_time
                ),

            'base_end_time' =>
                $this->normalizeTime(
                    $this->end_time
                ),

            'base_end_time_label' =>
                $this->formatTime(
                    $this->end_time
                ),

            'base_grace_minutes' =>
                (int) $this->grace_minutes,

            /*
            |--------------------------------------------------------------------------
            | Selected Day
            |--------------------------------------------------------------------------
            */

            'schedule_date' =>
                $selectedDate->format('Y-m-d'),

            'schedule_date_label' =>
                $selectedDate->format('d M Y'),

            'day_name' =>
                $selectedDate->format('l'),

            'is_regular_working_day' =>
                $this->appliesToDate(
                    $selectedDate
                ),

            /*
            |--------------------------------------------------------------------------
            | Effective Time for Selected Day
            |--------------------------------------------------------------------------
            */

            'start_time' =>
                $this->normalizeTime(
                    $effectiveStartTime
                ),

            'start_time_label' =>
                $this->formatTime(
                    $effectiveStartTime
                ),

            'end_time' =>
                $this->normalizeTime(
                    $effectiveEndTime
                ),

            'end_time_label' =>
                $this->formatTime(
                    $effectiveEndTime
                ),

            'grace_minutes' =>
                $effectiveGraceMinutes,

            'scheduled_minutes' =>
                $scheduledMinutes,

            'scheduled_hours' =>
                round(
                    $scheduledMinutes / 60,
                    2
                ),

            'scheduled_duration_label' =>
                $this->durationLabel(
                    $scheduledMinutes
                ),

            'is_overnight' =>
                ! $isDayOff
                &&
                $this->isOvernightFor(
                    $effectiveStartTime,
                    $effectiveEndTime
                ),

            /*
            |--------------------------------------------------------------------------
            | Override
            |--------------------------------------------------------------------------
            */

            'has_override' =>
                $override !== null,

            'override_id' =>
                $override
                    ? (int) $override->id
                    : null,

            'override_type' =>
                $override?->override_type,

            'override_type_label' =>
                match (
                    $override?->override_type
                ) {

                    'modified' =>
                        'Modified Shift',

                    'day_off' =>
                        'Day Off',

                    default =>
                        'Regular Schedule',

                },

            'is_day_off' =>
                $isDayOff,

            'override' =>
                $override
                    ? new ShiftScheduleOverrideResource(
                        $override
                    )
                    : null,

            /*
            |--------------------------------------------------------------------------
            | Effective Status
            |--------------------------------------------------------------------------
            */

            'is_active' =>
                (bool) $this->is_active,

            'status' =>
                $effectiveStatus,

            'status_label' =>
                match ($effectiveStatus) {

                    'active' =>
                        'Active',

                    'day_off' =>
                        'Day Off',

                    default =>
                        'Inactive',

                },

            'can_generate_attendance' =>
                (
                    $this->is_active
                    &&
                    ! $isDayOff
                    &&
                    $employeeAccountActive
                ),

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' =>
                $override?->notes
                ??
                $this->notes,

            'base_notes' =>
                $this->notes,

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),

        ];
    }


    private function roleLabel(): string
    {
        $roleName =
            $this->employee
                ?->user
                ?->role
                ?->name;

        return match ($roleName) {

            'manager' =>
                'Manager',

            'waiter' =>
                'Waiter',

            'chef' =>
                'Chef',

            null, '' =>
                'No Role',

            default =>
                ucwords(
                    str_replace(
                        [
                            '_',
                            '-',
                        ],
                        ' ',
                        (string) $roleName
                    )
                ),

        };
    }


    private function normalizeTime(
        mixed $time
    ): ?string {

        if (! $time) {
            return null;
        }

        return Carbon::parse(
            (string) $time
        )->format('H:i');
    }


    private function formatTime(
        mixed $time
    ): ?string {

        if (! $time) {
            return null;
        }

        return Carbon::parse(
            (string) $time
        )->format('h:i A');
    }


    private function dateRangeLabel(): string
    {
        if (
            ! $this->start_date
            ||
            ! $this->end_date
        ) {
            return '-';
        }

        if (
            $this->start_date
                ->isSameDay(
                    $this->end_date
                )
        ) {
            return $this->start_date
                ->format('d M Y');
        }

        return (
            $this->start_date
                ->format('d M Y')
            .
            ' - '
            .
            $this->end_date
                ->format('d M Y')
        );
    }


    private function durationLabel(
        int $minutes
    ): string {

        if ($minutes <= 0) {
            return '0m';
        }

        $hours =
            intdiv(
                $minutes,
                60
            );

        $remainingMinutes =
            $minutes % 60;

        if (
            $hours > 0
            &&
            $remainingMinutes > 0
        ) {
            return "{$hours}h {$remainingMinutes}m";
        }

        if ($hours > 0) {
            return "{$hours}h";
        }

        return "{$remainingMinutes}m";
    }
}