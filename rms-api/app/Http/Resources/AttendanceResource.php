<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {

        $openBreak =
            $this->relationLoaded('breaks')
                ? $this->breaks
                    ->first(
                        fn ($break) =>
                            $break->break_end_at === null
                    )
                : null;

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

            'shift_schedule_id' =>
                $this->shift_schedule_id
                    ? (int) $this->shift_schedule_id
                    : null,

            'shift_schedule_override_id' =>
                $this->shift_schedule_override_id
                    ? (int) $this->shift_schedule_override_id
                    : null,

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

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'attendance_date' =>
                $this->attendance_date
                    ?->format('Y-m-d'),

            'attendance_date_label' =>
                $this->attendance_date
                    ?->format('d M Y'),

            'day_name' =>
                $this->attendance_date
                    ?->format('l'),

            /*
            |--------------------------------------------------------------------------
            | Scheduled Time
            |--------------------------------------------------------------------------
            */

            'scheduled_start_at' =>
                $this->scheduled_start_at
                    ?->toISOString(),

            'scheduled_start_time' =>
                $this->scheduled_start_at
                    ?->format('H:i'),

            'scheduled_start_time_label' =>
                $this->scheduled_start_at
                    ?->format('h:i A'),

            'scheduled_end_at' =>
                $this->scheduled_end_at
                    ?->toISOString(),

            'scheduled_end_time' =>
                $this->scheduled_end_at
                    ?->format('H:i'),

            'scheduled_end_time_label' =>
                $this->scheduled_end_at
                    ?->format('h:i A'),

            'is_overnight' =>
                $this->scheduled_start_at
                &&
                $this->scheduled_end_at
                    ? ! $this->scheduled_start_at
                        ->isSameDay(
                            $this->scheduled_end_at
                        )
                    : false,

            'grace_minutes' =>
                (int) $this->grace_minutes,

            'scheduled_minutes' =>
                (int) $this->scheduled_minutes,

            'scheduled_duration_label' =>
                $this->durationLabel(
                    (int) $this->scheduled_minutes
                ),

            /*
            |--------------------------------------------------------------------------
            | Actual Attendance
            |--------------------------------------------------------------------------
            */

            'check_in_at' =>
                $this->check_in_at
                    ?->toISOString(),

            'check_in_time' =>
                $this->check_in_at
                    ?->format('H:i'),

            'check_in_time_label' =>
                $this->check_in_at
                    ?->format('h:i A'),

            'check_out_at' =>
                $this->check_out_at
                    ?->toISOString(),

            'check_out_time' =>
                $this->check_out_at
                    ?->format('H:i'),

            'check_out_time_label' =>
                $this->check_out_at
                    ?->format('h:i A'),
                    
            'auto_checked_out' =>
                (bool) $this->auto_checked_out,

            'auto_checkout_reason' =>
                $this->auto_checkout_reason,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' =>
                $this->status,

            'status_label' =>
                $this->statusLabel(),

            'is_checked_in' =>
                $this->isCheckedIn(),

            'is_checked_out' =>
                $this->isCheckedOut(),

            'is_late' =>
                $this->late_minutes > 0,

            'is_on_break' =>
                $openBreak !== null,

            'current_break_started_at' =>
                $openBreak
                    ?->break_start_at
                    ?->toISOString(),

            'current_break_started_time_label' =>
                $openBreak
                    ?->break_start_at
                    ?->format('h:i A'),

            /*
            |--------------------------------------------------------------------------
            | Calculations
            |--------------------------------------------------------------------------
            */

            'late_minutes' =>
                (int) $this->late_minutes,

            'late_duration_label' =>
                $this->durationLabel(
                    (int) $this->late_minutes
                ),

            'worked_minutes' =>
                (int) $this->worked_minutes,

            'worked_duration_label' =>
                $this->durationLabel(
                    (int) $this->worked_minutes
                ),

            'break_minutes' =>
                (int) $this->break_minutes,

            'break_duration_label' =>
                $this->durationLabel(
                    (int) $this->break_minutes
                ),

            'overtime_minutes' =>
                (int) $this->overtime_minutes,

            'overtime_duration_label' =>
                $this->durationLabel(
                    (int) $this->overtime_minutes
                ),

            'early_leave_minutes' =>
                (int) $this->early_leave_minutes,

            'early_leave_duration_label' =>
                $this->durationLabel(
                    (int) $this->early_leave_minutes
                ),

            /*
            |--------------------------------------------------------------------------
            | Break Records
            |--------------------------------------------------------------------------
            */

            'breaks' =>
                $this->whenLoaded(
                    'breaks',
                    function () {

                        return $this->breaks
                            ->map(
                                function ($break) {

                                    return [

                                        'id' =>
                                            (int) $break->id,

                                        'break_start_at' =>
                                            $break->break_start_at
                                                ?->toISOString(),

                                        'break_start_time_label' =>
                                            $break->break_start_at
                                                ?->format('h:i A'),

                                        'break_end_at' =>
                                            $break->break_end_at
                                                ?->toISOString(),

                                        'break_end_time_label' =>
                                            $break->break_end_at
                                                ?->format('h:i A'),

                                        'duration_minutes' =>
                                            (int)
                                            $break->duration_minutes,

                                        'duration_label' =>
                                            $this->durationLabel(
                                                (int)
                                                $break->duration_minutes
                                            ),

                                    ];

                                }
                            )
                            ->values();

                    },
                    []
                ),

            /*
            |--------------------------------------------------------------------------
            | Other
            |--------------------------------------------------------------------------
            */

            'is_manual' =>
                (bool) $this->is_manual,

            'notes' =>
                $this->notes,

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