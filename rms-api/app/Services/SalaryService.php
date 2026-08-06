<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\SalaryDetail;
use App\Models\SalaryPayroll;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryService
{
    public function __construct(
        private readonly SalaryCalculatorService $calculator
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Generate or Recalculate Payroll
    |--------------------------------------------------------------------------
    */

    public function generatePayrolls(
        CarbonInterface|string $fromDate,
        CarbonInterface|string $toDate,
        ?int $employeeId,
        User $admin
    ): array {

        $periodStart =
            $fromDate instanceof CarbonInterface
                ? $fromDate
                    ->copy()
                    ->startOfDay()
                : Carbon::parse(
                    $fromDate
                )->startOfDay();

        $periodEnd =
            $toDate instanceof CarbonInterface
                ? $toDate
                    ->copy()
                    ->startOfDay()
                : Carbon::parse(
                    $toDate
                )->startOfDay();

        if (
            $periodEnd->lt(
                $periodStart
            )
        ) {
            throw ValidationException::withMessages([
                'to_date' =>
                    'The end date cannot be before the start date.',
            ]);
        }

        if (
            $periodEnd->gt(
                today()
            )
        ) {
            throw ValidationException::withMessages([
                'to_date' =>
                    'Salary cannot be generated for a future date.',
            ]);
        }

        $employeeQuery =
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
                );

        if ($employeeId) {
            $employeeQuery->where(
                'id',
                $employeeId
            );
        }

        $employees =
            $employeeQuery->get();

        if ($employees->isEmpty()) {
            throw ValidationException::withMessages([
                'employee_id' =>
                    'No eligible employee was found.',
            ]);
        }

        $created = 0;

        $recalculated = 0;

        $skippedPaid = 0;

        $detailsProcessed = 0;

        DB::transaction(
            function () use (
                $employees,
                $periodStart,
                $periodEnd,
                $admin,
                &$created,
                &$recalculated,
                &$skippedPaid,
                &$detailsProcessed
            ) {

                foreach ($employees as $employee) {
                    $payroll =
                        SalaryPayroll::query()
                            ->where(
                                'employee_id',
                                $employee->id
                            )
                            ->whereDate(
                                'period_start',
                                $periodStart
                            )
                            ->whereDate(
                                'period_end',
                                $periodEnd
                            )
                            ->first();

                    if (
                        $payroll
                        &&
                        $payroll->isPaid()
                    ) {
                        $skippedPaid++;

                        continue;
                    }

                    $payroll =
                        $payroll
                        ??
                        new SalaryPayroll();

                    $isNew =
                        ! $payroll->exists;

                    $payroll->employee_id =
                        $employee->id;

                    $payroll->employee_name =
                        $employee->user
                            ?->name
                        ??
                        'Unknown Staff';

                    $payroll->employee_phone =
                        $employee->phone;

                    $payroll->employee_email =
                        $employee->user
                            ?->email;

                    $payroll->period_start =
                        $periodStart
                            ->format('Y-m-d');

                    $payroll->period_end =
                        $periodEnd
                            ->format('Y-m-d');

                    $payroll->hourly_rate =
                        round(
                            (float) $employee->hourly_rate,
                            2
                        );

                    if ($isNew) {
                        $payroll->payment_status =
                            SalaryPayroll::STATUS_UNPAID;

                        $payroll->adjustment_amount =
                            0;

                        $payroll->created_by =
                            $admin->id;
                    }

                    $payroll->updated_by =
                        $admin->id;

                    $payroll->save();

                    /*
                    |--------------------------------------------------------------------------
                    | Existing Details
                    |--------------------------------------------------------------------------
                    */

                    $existingDetails =
                        $payroll
                            ->salaryDetails()
                            ->get()
                            ->keyBy(
                                fn (SalaryDetail $detail) =>
                                    (string) $detail->attendance_id
                            );

                    $attendances =
                        Attendance::query()
                            ->where(
                                'employee_id',
                                $employee->id
                            )
                            ->whereBetween(
                                'attendance_date',
                                [
                                    $periodStart
                                        ->format('Y-m-d'),

                                    $periodEnd
                                        ->format('Y-m-d'),
                                ]
                            )
                            ->orderBy(
                                'attendance_date'
                            )
                            ->get();

                    $processedAttendanceIds = [];

                    foreach ($attendances as $attendance) {
                        $existingDetail =
                            $existingDetails->get(
                                (string) $attendance->id
                            );

                        $preserveManual =
                            $existingDetail
                            &&
                            $existingDetail->calculation_source ===
                                SalaryDetail::SOURCE_MANUAL;

                        if ($preserveManual) {
                            $calculation =
                                $this->calculator
                                    ->calculateByType(
                                        (int) $attendance->scheduled_minutes,
                                        (int) $attendance->overtime_minutes,
                                        (float) $payroll->hourly_rate,
                                        $existingDetail->salary_type
                                    );

                            $calculation['late_minutes'] =
                                $existingDetail->late_minutes;

                            $source =
                                SalaryDetail::SOURCE_MANUAL;
                        }
                        else {
                            $calculation =
                                $this->calculator
                                    ->calculateAutomatic(
                                        $attendance,
                                        (float) $payroll->hourly_rate
                                    );

                            $source =
                                SalaryDetail::SOURCE_AUTOMATIC;
                        }

                        $detail =
                            $existingDetail
                            ??
                            new SalaryDetail();

                        $detail->salary_payroll_id =
                            $payroll->id;

                        $detail->attendance_id =
                            $attendance->id;

                        $detail->employee_id =
                            $employee->id;

                        $detail->salary_date =
                            $attendance
                                ->attendance_date
                                ->format('Y-m-d');

                        $detail->scheduled_minutes =
                            (int) $attendance->scheduled_minutes;

                        $detail->worked_minutes =
                            (int) $attendance->worked_minutes;

                        $detail->late_minutes =
                            (int) $calculation['late_minutes'];

                        $detail->break_minutes =
                            (int) $attendance->break_minutes;

                        $detail->overtime_minutes =
                            (int) $attendance->overtime_minutes;

                        $detail->hourly_rate =
                            $payroll->hourly_rate;

                        $detail->salary_type =
                            $calculation['salary_type'];

                        $detail->regular_salary =
                            $calculation['regular_salary'];

                        $detail->overtime_salary =
                            $calculation['overtime_salary'];

                        $detail->total_amount =
                            $calculation['total_amount'];

                        $detail->calculation_source =
                            $source;

                        $detail->updated_by =
                            $admin->id;

                        $detail->save();

                        $processedAttendanceIds[] =
                            $attendance->id;

                        $detailsProcessed++;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Remove Stale Details
                    |--------------------------------------------------------------------------
                    */

                    if (
                        count(
                            $processedAttendanceIds
                        ) > 0
                    ) {
                        $payroll
                            ->salaryDetails()
                            ->whereNotIn(
                                'attendance_id',
                                $processedAttendanceIds
                            )
                            ->delete();
                    }
                    else {
                        $payroll
                            ->salaryDetails()
                            ->delete();
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Payroll Total
                    |--------------------------------------------------------------------------
                    */

                    $regularSalary =
                        (float) $payroll
                            ->salaryDetails()
                            ->sum(
                                'regular_salary'
                            );

                    $overtimeSalary =
                        (float) $payroll
                            ->salaryDetails()
                            ->sum(
                                'overtime_salary'
                            );

                    $adjustmentAmount =
                        (float) $payroll
                            ->adjustment_amount;

                    $payroll->regular_salary =
                        round(
                            $regularSalary,
                            2
                        );

                    $payroll->overtime_salary =
                        round(
                            $overtimeSalary,
                            2
                        );

                    $payroll->total_amount =
                        round(
                            $regularSalary
                            +
                            $overtimeSalary
                            +
                            $adjustmentAmount,
                            2
                        );

                    $payroll->updated_by =
                        $admin->id;

                    $payroll->save();

                    if ($isNew) {
                        $created++;
                    }
                    else {
                        $recalculated++;
                    }
                }
            }
        );

        return [
            'period_start' =>
                $periodStart
                    ->format('Y-m-d'),

            'period_end' =>
                $periodEnd
                    ->format('Y-m-d'),

            'created' =>
                $created,

            'recalculated' =>
                $recalculated,

            'skipped_paid' =>
                $skippedPaid,

            'details_processed' =>
                $detailsProcessed,

            'employees_processed' =>
                $created
                +
                $recalculated,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Update Payroll
    |--------------------------------------------------------------------------
    */

    public function updatePayroll(
        SalaryPayroll $payroll,
        array $data,
        User $admin
    ): SalaryPayroll {

        if ($payroll->isPaid()) {
            throw ValidationException::withMessages([
                'payment_status' =>
                    'A paid salary cannot be edited. Change it to unpaid first.',
            ]);
        }

        $adjustmentAmount =
            round(
                (float) (
                    $data['adjustment_amount']
                    ??
                    $payroll->adjustment_amount
                ),
                2
            );

        $payroll->update([
            'adjustment_amount' =>
                $adjustmentAmount,

            'total_amount' =>
                round(
                    (float) $payroll->regular_salary
                    +
                    (float) $payroll->overtime_salary
                    +
                    $adjustmentAmount,
                    2
                ),

            'notes' =>
                $data['notes']
                ??
                $payroll->notes,

            'updated_by' =>
                $admin->id,
        ]);

        return $payroll->fresh([
            'payer',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    public function updatePaymentStatus(
        SalaryPayroll $payroll,
        string $status,
        User $admin
    ): SalaryPayroll {

        if (
            ! in_array(
                $status,
                SalaryPayroll::allowedPaymentStatuses(),
                true
            )
        ) {
            throw ValidationException::withMessages([
                'payment_status' =>
                    'The selected payment status is invalid.',
            ]);
        }

        if (
            $status ===
            SalaryPayroll::STATUS_PAID
        ) {
            $payroll->update([
                'payment_status' =>
                    SalaryPayroll::STATUS_PAID,

                'paid_at' =>
                    now(),

                'paid_by' =>
                    $admin->id,

                'updated_by' =>
                    $admin->id,
            ]);
        }
        else {
            $payroll->update([
                'payment_status' =>
                    SalaryPayroll::STATUS_UNPAID,

                'paid_at' =>
                    null,

                'paid_by' =>
                    null,

                'updated_by' =>
                    $admin->id,
            ]);
        }

        return $payroll->fresh([
            'payer',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Payroll
    |--------------------------------------------------------------------------
    */

    public function deletePayroll(
        SalaryPayroll $payroll
    ): void {

        if ($payroll->isPaid()) {
            throw ValidationException::withMessages([
                'payment_status' =>
                    'A paid salary cannot be deleted. Change it to unpaid first.',
            ]);
        }

        DB::transaction(
            function () use ($payroll) {
                $payroll
                    ->salaryDetails()
                    ->delete();

                $payroll->delete();
            }
        );
    }
}