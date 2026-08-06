<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalaryDetailResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {

        $attendance =
            $this->attendance;

        $payroll =
            $this->salaryPayroll;

        return [
            'id' =>
                (int) $this->id,

            'salary_payroll_id' =>
                (int) $this->salary_payroll_id,

            'attendance_id' =>
                $this->attendance_id
                    ? (int) $this->attendance_id
                    : null,

            'employee_id' =>
                (int) $this->employee_id,

            /*
            |--------------------------------------------------------------------------
            | Employee
            |--------------------------------------------------------------------------
            */

            'employee_name' =>
                $payroll?->employee_name
                ??
                $this->employee
                    ?->user
                    ?->name
                ??
                'Unknown Staff',

            'employee_phone' =>
                $payroll?->employee_phone
                ??
                $this->employee
                    ?->phone,

            'employee_email' =>
                $payroll?->employee_email
                ??
                $this->employee
                    ?->user
                    ?->email,

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'salary_date' =>
                $this->salary_date
                    ?->format('Y-m-d'),

            'salary_date_label' =>
                $this->salary_date
                    ?->format('d M Y'),

            'day_label' =>
                $this->salary_date
                    ?->format('l'),

            /*
            |--------------------------------------------------------------------------
            | Attendance
            |--------------------------------------------------------------------------
            */

            'attendance_status' =>
                $attendance?->status,

            'attendance_status_label' =>
                $attendance
                    ? $attendance->statusLabel()
                    : 'No Attendance',

            'scheduled_start_at' =>
                $attendance
                    ?->scheduled_start_at
                    ?->toISOString(),

            'scheduled_start_time_label' =>
                $attendance
                    ?->scheduled_start_at
                    ?->format('h:i A'),

            'scheduled_end_at' =>
                $attendance
                    ?->scheduled_end_at
                    ?->toISOString(),

            'scheduled_end_time_label' =>
                $attendance
                    ?->scheduled_end_at
                    ?->format('h:i A'),

            'scheduled_shift_label' =>
                $this->scheduledShiftLabel(),

            'check_in_at' =>
                $attendance
                    ?->check_in_at
                    ?->toISOString(),

            'check_in_time_label' =>
                $attendance
                    ?->check_in_at
                    ?->format('h:i A'),

            'check_out_at' =>
                $attendance
                    ?->check_out_at
                    ?->toISOString(),

            'check_out_time_label' =>
                $attendance
                    ?->check_out_at
                    ?->format('h:i A'),

            'auto_checked_out' =>
                (bool) (
                    $attendance
                        ?->auto_checked_out
                    ??
                    false
                ),

            /*
            |--------------------------------------------------------------------------
            | Time Calculations
            |--------------------------------------------------------------------------
            */

            'scheduled_minutes' =>
                (int) $this->scheduled_minutes,

            'scheduled_duration_label' =>
                $this->durationLabel(
                    $this->scheduled_minutes
                ),

            'worked_minutes' =>
                (int) $this->worked_minutes,

            'worked_duration_label' =>
                $this->durationLabel(
                    $this->worked_minutes
                ),

            'late_minutes' =>
                (int) $this->late_minutes,

            'late_duration_label' =>
                $this->durationLabel(
                    $this->late_minutes
                ),

            'break_minutes' =>
                (int) $this->break_minutes,

            'break_duration_label' =>
                $this->durationLabel(
                    $this->break_minutes
                ),

            'overtime_minutes' =>
                (int) $this->overtime_minutes,

            'overtime_duration_label' =>
                $this->durationLabel(
                    $this->overtime_minutes
                ),

            /*
            |--------------------------------------------------------------------------
            | Salary
            |--------------------------------------------------------------------------
            */

            'hourly_rate' =>
                (float) $this->hourly_rate,

            'hourly_rate_formatted' =>
                $this->money(
                    $this->hourly_rate
                )
                .
                ' / hour',

            'salary_type' =>
                $this->salary_type,

            'salary_type_label' =>
                $this->salaryTypeLabel(),

            'regular_salary' =>
                (float) $this->regular_salary,

            'regular_salary_formatted' =>
                $this->money(
                    $this->regular_salary
                ),

            'overtime_salary' =>
                (float) $this->overtime_salary,

            'overtime_salary_formatted' =>
                $this->money(
                    $this->overtime_salary
                ),

            'total_amount' =>
                (float) $this->total_amount,

            'total_amount_formatted' =>
                $this->money(
                    $this->total_amount
                ),

            'calculation_source' =>
                $this->calculation_source,

            'calculation_source_label' =>
                $this->calculation_source ===
                    'manual'
                    ? 'Manual'
                    : 'Automatic',

            'notes' =>
                $this->notes,

            /*
            |--------------------------------------------------------------------------
            | Payroll Status
            |--------------------------------------------------------------------------
            */

            'payment_status' =>
                $payroll?->payment_status,

            'payment_status_label' =>
                $payroll
                    ?->paymentStatusLabel(),

            'can_update' =>
                $payroll
                    ? ! $payroll->isPaid()
                    : false,

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }


    private function scheduledShiftLabel(): string
    {
        $attendance =
            $this->attendance;

        if (
            ! $attendance
            ||
            ! $attendance->scheduled_start_at
            ||
            ! $attendance->scheduled_end_at
        ) {
            return '—';
        }

        return $attendance
            ->scheduled_start_at
            ->format('h:i A')
            .
            ' — '
            .
            $attendance
                ->scheduled_end_at
                ->format('h:i A');
    }


    private function durationLabel(
        mixed $minutes
    ): string {

        $totalMinutes =
            max(
                0,
                (int) $minutes
            );

        $hours =
            intdiv(
                $totalMinutes,
                60
            );

        $remainingMinutes =
            $totalMinutes % 60;

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


    private function money(
        mixed $amount
    ): string {

        return '৳ '
            .
            number_format(
                (float) $amount,
                2
            );
    }
}