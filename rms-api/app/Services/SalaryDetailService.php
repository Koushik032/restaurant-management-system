<?php

namespace App\Services;

use App\Models\SalaryDetail;
use App\Models\SalaryPayroll;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalaryDetailService
{
    public function __construct(
        private readonly SalaryCalculatorService $calculator
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Update Daily Salary Type
    |--------------------------------------------------------------------------
    */

    public function updateSalaryDetail(
        SalaryDetail $salaryDetail,
        string $salaryType,
        ?string $notes,
        User $admin
    ): SalaryDetail {

        return DB::transaction(
            function () use (
                $salaryDetail,
                $salaryType,
                $notes,
                $admin
            ) {

                $salaryDetail->loadMissing(
                    'salaryPayroll'
                );

                $payroll =
                    $salaryDetail->salaryPayroll;

                if (! $payroll) {
                    throw ValidationException::withMessages([
                        'salary_type' =>
                            'The related salary payroll was not found.',
                    ]);
                }

                if ($payroll->isPaid()) {
                    throw ValidationException::withMessages([
                        'salary_type' =>
                            'A paid payroll is locked. Change the payroll to unpaid before updating salary details.',
                    ]);
                }

                $amounts =
                    $this->calculator
                        ->calculateByType(
                            (int) $salaryDetail->scheduled_minutes,
                            (int) $salaryDetail->overtime_minutes,
                            (float) $salaryDetail->hourly_rate,
                            $salaryType
                        );

                $salaryDetail->update([
                    'salary_type' =>
                        $amounts['salary_type'],

                    'regular_salary' =>
                        $amounts['regular_salary'],

                    'overtime_salary' =>
                        $amounts['overtime_salary'],

                    'total_amount' =>
                        $amounts['total_amount'],

                    'calculation_source' =>
                        SalaryDetail::SOURCE_MANUAL,

                    'notes' =>
                        $notes,

                    'updated_by' =>
                        $admin->id,
                ]);

                $this->recalculatePayroll(
                    $payroll,
                    $admin
                );

                return $salaryDetail->fresh([
                    'salaryPayroll.payer',
                    'attendance',
                    'employee.user',
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Recalculate Payroll Summary
    |--------------------------------------------------------------------------
    */

    public function recalculatePayroll(
        SalaryPayroll $payroll,
        User $admin
    ): SalaryPayroll {

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

        $payroll->update([
            'regular_salary' =>
                round(
                    $regularSalary,
                    2
                ),

            'overtime_salary' =>
                round(
                    $overtimeSalary,
                    2
                ),

            'total_amount' =>
                round(
                    $regularSalary
                    +
                    $overtimeSalary
                    +
                    $adjustmentAmount,
                    2
                ),

            'updated_by' =>
                $admin->id,
        ]);

        return $payroll->fresh([
            'payer',
        ]);
    }
}