<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Models\Employee;
use App\Models\SalaryDetail;
use App\Models\SalaryPayroll;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;

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
            ->startOfMonth()
        : Carbon::parse(
            $fromDate
        )
        ->startOfMonth()
        ->startOfDay();



$periodEnd =
    $toDate instanceof CarbonInterface
        ? $toDate
            ->copy()
            ->startOfDay()
        : Carbon::parse(
            $toDate
        )
        ->startOfDay();

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
| Salary List
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Salary List
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Salary List
|--------------------------------------------------------------------------
| Current month-to-date payroll summary for each employee.
|--------------------------------------------------------------------------
*/

public function getSalaries(
    array $filters = []
): LengthAwarePaginator {

    $fromDate =
        Carbon::parse(
            $filters['from_date']
            ??
            now()
                ->startOfMonth()
                ->format('Y-m-d')
        )->startOfDay();

    $toDate =
        Carbon::parse(
            $filters['to_date']
            ??
            today()
                ->format('Y-m-d')
        )->startOfDay();

    /*
    |--------------------------------------------------------------------------
    | Employee Query
    |--------------------------------------------------------------------------
    */

    $employeeQuery =
        Employee::query()
            ->with([
                'user.role',
                'shiftSchedules',
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

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    if (
        ! empty(
            $filters['search']
            ??
            null
        )
    ) {

        $search =
            trim(
                (string)
                $filters['search']
            );

        $employeeQuery->where(
            function ($query) use (
                $search
            ) {

                $query
                    ->where(
                        'phone',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhereHas(
                        'user',
                        function ($userQuery) use (
                            $search
                        ) {

                            $userQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )

                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                )

                                ->orWhere(
                                    'username',
                                    'like',
                                    "%{$search}%"
                                );

                        }
                    );

            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Employee Filter
    |--------------------------------------------------------------------------
    */

    if (
        ! empty(
            $filters['employee_id']
            ??
            null
        )
    ) {

        $employeeQuery->where(
            'id',
            (int)
            $filters['employee_id']
        );
    }

    $employees =
        $employeeQuery
            ->orderBy(
                'id'
            )
            ->get();

    /*
    |--------------------------------------------------------------------------
    | Payroll Map
    |--------------------------------------------------------------------------
    |
    | Get latest current-month payroll for every employee.
    |
    */

    $payrolls =
        SalaryPayroll::query()
            ->with([
                'salaryDetails',
                'payer',
            ])
            ->whereDate(
                'period_start',
                $fromDate->format('Y-m-d')
            )
            ->whereDate(
                'period_end',
                '<=',
                $toDate->format('Y-m-d')
            )
            ->orderByDesc(
                'id'
            )
            ->get()
            ->unique(
                'employee_id'
            )
            ->keyBy(
                'employee_id'
            );

    /*
    |--------------------------------------------------------------------------
    | Build Employee Salary Rows
    |--------------------------------------------------------------------------
    */

    $rows =
        $employees
            ->map(
                function (
                    Employee $employee
                ) use (
                    $payrolls,
                    $fromDate,
                    $toDate
                ) {

                    $payroll =
                        $payrolls->get(
                            $employee->id
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Create Virtual Payroll When Not Generated
                    |--------------------------------------------------------------------------
                    */

                    if (! $payroll) {

                        $payroll =
                            new SalaryPayroll();

                        $payroll->exists =
                            false;

                        $payroll->employee_id =
                            $employee->id;

                        $payroll->employee_name =
                            $employee->user?->name
                            ??
                            'Unknown Staff';

                        $payroll->employee_phone =
                            $employee->phone;

                        $payroll->employee_email =
                            $employee->user?->email;

                        $payroll->period_start =
                            $fromDate->format(
                                'Y-m-d'
                            );

                        $payroll->period_end =
                            $toDate->format(
                                'Y-m-d'
                            );

                        $payroll->hourly_rate =
                            round(
                                (float)
                                $employee->hourly_rate,
                                2
                            );

                        $payroll->regular_salary =
                            0;

                        $payroll->overtime_salary =
                            0;

                        $payroll->adjustment_amount =
                            0;

                        $payroll->total_amount =
                            0;

                        $payroll->payment_status =
                            SalaryPayroll::STATUS_UNPAID;

                        $payroll->paid_at =
                            null;

                        $payroll->paid_by =
                            null;

                        $payroll->setRelation(
                            'salaryDetails',
                            collect()
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Existing Payroll Snapshot
                    |--------------------------------------------------------------------------
                    */

                    $payroll->setRelation(
                        'employee',
                        $employee
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Daily Details
                    |--------------------------------------------------------------------------
                    */

                    $details =
                        $payroll
                            ->salaryDetails
                            ?? collect();

                    /*
                    |--------------------------------------------------------------------------
                    | Aggregate Attendance
                    |--------------------------------------------------------------------------
                    */

                    $workedMinutes =
                        (int)
                        $details->sum(
                            'worked_minutes'
                        );

                    $overtimeMinutes =
                        (int)
                        $details->sum(
                            'overtime_minutes'
                        );

                    $scheduledMinutes =
                        (int)
                        $details->sum(
                            'scheduled_minutes'
                        );

                    $breakMinutes =
                        (int)
                        $details->sum(
                            'break_minutes'
                        );

                    $lateMinutes =
                        (int)
                        $details->sum(
                            'late_minutes'
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Attach Calculated Values
                    |--------------------------------------------------------------------------
                    */

                    $payroll->worked_minutes =
                        $workedMinutes;

                    $payroll->overtime_minutes =
                        $overtimeMinutes;

                    $payroll->scheduled_minutes =
                        $scheduledMinutes;

                    $payroll->break_minutes =
                        $breakMinutes;

                    $payroll->late_minutes =
                        $lateMinutes;

                    $payroll->salary_details_count =
                        $details->count();

                    /*
                    |--------------------------------------------------------------------------
                    | Payment Status Filter
                    |--------------------------------------------------------------------------
                    */

                    return $payroll;
                }
            );

    /*
    |--------------------------------------------------------------------------
    | Payment Status Filter
    |--------------------------------------------------------------------------
    */

    if (
        ! empty(
            $filters['payment_status']
            ??
            null
        )
    ) {

        $rows =
            $rows->filter(
                function (
                    SalaryPayroll $payroll
                ) use ($filters) {

                    return $payroll
                        ->payment_status
                        ===
                        $filters['payment_status'];

                }
            )
            ->values();
    }

    /*
    |--------------------------------------------------------------------------
    | Manual Pagination
    |--------------------------------------------------------------------------
    */

    $page =
        max(
            1,
            (int) (
                $filters['page']
                ??
                1
            )
        );

    $perPage =
        max(
            1,
            min(
                100,
                (int) (
                    $filters['per_page']
                    ??
                    10
                )
            )
        );

    $total =
        $rows->count();

    $items =
        $rows->forPage(
            $page,
            $perPage
        )->values();

    return new LengthAwarePaginator(
        $items,
        $total,
        $perPage,
        $page,
        [
            'path' =>
                request()->url(),

            'query' =>
                request()->query(),
        ]
    );
}
public function getSalarySummary(
    array $filters = []
): array {

    /*
    |--------------------------------------------------------------------------
    | Resolve Selected Period
    |--------------------------------------------------------------------------
    */

    $fromDate =
        Carbon::parse(
            $filters['from_date']
            ??
            now()
                ->startOfMonth()
                ->format('Y-m-d')
        )->startOfDay();

    $toDate =
        Carbon::parse(
            $filters['to_date']
            ??
            today()
                ->format('Y-m-d')
        )->startOfDay();


    /*
    |--------------------------------------------------------------------------
    | Load All Matching Employees
    |--------------------------------------------------------------------------
    */

    $filters['from_date'] =
        $fromDate->format('Y-m-d');

    $filters['to_date'] =
        $toDate->format('Y-m-d');

    $filters['page'] =
        1;

    $filters['per_page'] =
        100;


    $employees =
        $this->getSalaries(
            $filters
        );


    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */

    $rows =
        collect(
            $employees->items()
        );


    /*
    |--------------------------------------------------------------------------
    | Salary Summary
    |--------------------------------------------------------------------------
    */

    $regularSalary =
        (float)
        $rows->sum(
            fn (
                SalaryPayroll $payroll
            ) =>
                (float)
                $payroll->regular_salary
        );


    $overtimeSalary =
        (float)
        $rows->sum(
            fn (
                SalaryPayroll $payroll
            ) =>
                (float)
                $payroll->overtime_salary
        );


    $adjustmentAmount =
        (float)
        $rows->sum(
            fn (
                SalaryPayroll $payroll
            ) =>
                (float)
                $payroll->adjustment_amount
        );


    $totalAmount =
        (float)
        $rows->sum(
            fn (
                SalaryPayroll $payroll
            ) =>
                (float)
                $payroll->total_amount
        );


    /*
    |--------------------------------------------------------------------------
    | Payment Rows
    |--------------------------------------------------------------------------
    */

    $paidRows =
        $rows->filter(
            fn (
                SalaryPayroll $payroll
            ) =>
                $payroll->payment_status
                ===
                SalaryPayroll::STATUS_PAID
        );


    $unpaidRows =
        $rows->filter(
            fn (
                SalaryPayroll $payroll
            ) =>
                $payroll->payment_status
                ===
                SalaryPayroll::STATUS_UNPAID
        );


    /*
    |--------------------------------------------------------------------------
    | Paid / Unpaid Amount
    |--------------------------------------------------------------------------
    */

    $paidAmount =
        (float)
        $paidRows->sum(
            fn (
                SalaryPayroll $payroll
            ) =>
                (float)
                $payroll->total_amount
        );


    $unpaidAmount =
        (float)
        $unpaidRows->sum(
            fn (
                SalaryPayroll $payroll
            ) =>
                (float)
                $payroll->total_amount
        );


    /*
    |--------------------------------------------------------------------------
    | Working Time Summary
    |--------------------------------------------------------------------------
    */

    $workedMinutes =
        (int)
        $rows->sum(
            fn (
                SalaryPayroll $payroll
            ) =>
                (int)
                (
                    $payroll->worked_minutes
                    ??
                    0
                )
        );


    $overtimeMinutes =
        (int)
        $rows->sum(
            fn (
                SalaryPayroll $payroll
            ) =>
                (int)
                (
                    $payroll->overtime_minutes
                    ??
                    0
                )
        );


    $scheduledMinutes =
        (int)
        $rows->sum(
            fn (
                SalaryPayroll $payroll
            ) =>
                (int)
                (
                    $payroll->scheduled_minutes
                    ??
                    0
                )
        );


    $breakMinutes =
        (int)
        $rows->sum(
            fn (
                SalaryPayroll $payroll
            ) =>
                (int)
                (
                    $payroll->break_minutes
                    ??
                    0
                )
        );


    /*
    |--------------------------------------------------------------------------
    | Return Summary
    |--------------------------------------------------------------------------
    */

    return [

        'total_payrolls' =>
            $rows->count(),


        'paid_count' =>
            $paidRows->count(),


        'unpaid_count' =>
            $unpaidRows->count(),


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


        'adjustment_amount' =>
            round(
                $adjustmentAmount,
                2
            ),


        'total_amount' =>
            round(
                $totalAmount,
                2
            ),


        'paid_amount' =>
            round(
                $paidAmount,
                2
            ),


        'unpaid_amount' =>
            round(
                $unpaidAmount,
                2
            ),


        'scheduled_minutes' =>
            $scheduledMinutes,


        'worked_minutes' =>
            $workedMinutes,


        'break_minutes' =>
            $breakMinutes,


        'overtime_minutes' =>
            $overtimeMinutes,


        /*
        |--------------------------------------------------------------------------
        | Selected Period
        |--------------------------------------------------------------------------
        */

        'period_start' =>
            $fromDate
                ->format(
                    'Y-m-d'
                ),


        'period_end' =>
            $toDate
                ->format(
                    'Y-m-d'
                ),

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
| Process Daily Salary
|--------------------------------------------------------------------------
|
| One specific day's attendance থেকে salary detail তৈরি/update করবে
| এবং current month's payroll summary recalculate করবে।
|
*/

public function processDailySalary(
    CarbonInterface|string $salaryDate,
    User $admin,
    ?int $employeeId = null
): array {

    $date =
        $salaryDate instanceof CarbonInterface
            ? $salaryDate->copy()->startOfDay()
            : Carbon::parse($salaryDate)->startOfDay();

    if ($date->gt(today())) {
        throw ValidationException::withMessages([
            'salary_date' =>
                'Salary cannot be processed for a future date.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Attendance
    |--------------------------------------------------------------------------
    */

    $attendanceService =
        app(AttendanceService::class);

    $attendancePreparation =
        $attendanceService
            ->prepareForPayroll(
                $date,
                $admin
            );

    /*
    |--------------------------------------------------------------------------
    | Eligible Employees
    |--------------------------------------------------------------------------
    */

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

    if ($employeeId !== null) {
        $employeeQuery->where(
            'id',
            $employeeId
        );
    }

    $employees =
        $employeeQuery
            ->orderBy('id')
            ->get();

    if ($employees->isEmpty()) {
        throw ValidationException::withMessages([
            'employee_id' =>
                'No eligible employee was found.',
        ]);
    }

    $createdPayrolls = 0;
    $updatedPayrolls = 0;
    $skippedPaidPayrolls = 0;
    $detailsCreated = 0;
    $detailsUpdated = 0;

    DB::transaction(
        function () use (
            $employees,
            $date,
            $admin,
            &$createdPayrolls,
            &$updatedPayrolls,
            &$skippedPaidPayrolls,
            &$detailsCreated,
            &$detailsUpdated
        ) {

            foreach ($employees as $employee) {

                /*
                |--------------------------------------------------------------------------
                | Current Month Payroll
                |--------------------------------------------------------------------------
                */

                $periodStart =
                    $date
                        ->copy()
                        ->startOfMonth();

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
                        ->orderByDesc('id')
                        ->first();

                /*
                |--------------------------------------------------------------------------
                | Paid Payroll Protection
                |--------------------------------------------------------------------------
                */

                if (
                    $payroll
                    &&
                    $payroll->isPaid()
                ) {

                    $skippedPaidPayrolls++;

                    continue;
                }

                $isNew =
                    !$payroll;

                if (!$payroll) {
                    $payroll =
                        new SalaryPayroll();
                }

                /*
                |--------------------------------------------------------------------------
                | Payroll Snapshot
                |--------------------------------------------------------------------------
                */

                $payroll->employee_id =
                    $employee->id;

                $payroll->employee_name =
                    $employee->user?->name
                    ??
                    'Unknown Staff';

                $payroll->employee_phone =
                    $employee->phone;

                $payroll->employee_email =
                    $employee->user?->email;

                $payroll->period_start =
                    $periodStart->format('Y-m-d');

                /*
                | Important:
                | Period end = current processed date.
                */

                $payroll->period_end =
                    $date->format('Y-m-d');

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

                    $payroll->regular_salary =
                        0;

                    $payroll->overtime_salary =
                        0;

                    $payroll->total_amount =
                        0;

                    $payroll->created_by =
                        $admin->id;
                }

                $payroll->updated_by =
                    $admin->id;

                $payroll->save();

                if ($isNew) {
                    $createdPayrolls++;
                }
                else {
                    $updatedPayrolls++;
                }

                /*
                |--------------------------------------------------------------------------
                | Today's Attendance
                |--------------------------------------------------------------------------
                */

                $attendance =
                    Attendance::query()
                        ->where(
                            'employee_id',
                            $employee->id
                        )
                        ->whereDate(
                            'attendance_date',
                            $date
                        )
                        ->first();

                /*
                |--------------------------------------------------------------------------
                | No Attendance
                |--------------------------------------------------------------------------
                */

                if (!$attendance) {
                    $this->recalculatePayrollTotals(
                        $payroll,
                        $admin
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Existing Daily Detail
                |--------------------------------------------------------------------------
                */

                $detail =
                    SalaryDetail::query()
                        ->where(
                            'attendance_id',
                            $attendance->id
                        )
                        ->first();

                $isNewDetail =
                    !$detail;

                /*
                |--------------------------------------------------------------------------
                | Preserve Manual Calculation
                |--------------------------------------------------------------------------
                */

                $preserveManual =
                    $detail
                    &&
                    $detail->calculation_source ===
                        SalaryDetail::SOURCE_MANUAL;

                if ($preserveManual) {

                    $calculation =
                        $this->calculator
                            ->calculateByType(
                                (int) $attendance->scheduled_minutes,
                                (int) $attendance->overtime_minutes,
                                (float) $payroll->hourly_rate,
                                $detail->salary_type
                            );

                    $calculation['late_minutes'] =
                        (int) $detail->late_minutes;

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

                if (!$detail) {
                    $detail =
                        new SalaryDetail();
                }

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

                if ($isNewDetail) {
                    $detailsCreated++;
                }
                else {
                    $detailsUpdated++;
                }

                /*
                |--------------------------------------------------------------------------
                | Recalculate Monthly Payroll
                |--------------------------------------------------------------------------
                */

                $this->recalculatePayrollTotals(
                    $payroll,
                    $admin
                );
            }
        }
    );

    return [

        'salary_date' =>
            $date->format('Y-m-d'),

        'period_start' =>
            $date
                ->copy()
                ->startOfMonth()
                ->format('Y-m-d'),

        'period_end' =>
            $date->format('Y-m-d'),

        'employees_processed' =>
            $employees->count(),

        'payroll_created' =>
            $createdPayrolls,

        'payroll_updated' =>
            $updatedPayrolls,

        'skipped_paid_payrolls' =>
            $skippedPaidPayrolls,

        'details_created' =>
            $detailsCreated,

        'details_updated' =>
            $detailsUpdated,

        'attendance' =>
            $attendancePreparation,
    ];
}
/*
|--------------------------------------------------------------------------
| Recalculate Payroll Totals
|--------------------------------------------------------------------------
*/

private function recalculatePayrollTotals(
    SalaryPayroll $payroll,
    User $admin
): SalaryPayroll {

    $regularSalary =
        (float) $payroll
            ->salaryDetails()
            ->sum('regular_salary');

    $overtimeSalary =
        (float) $payroll
            ->salaryDetails()
            ->sum('overtime_salary');

    $adjustmentAmount =
        (float) $payroll->adjustment_amount;

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