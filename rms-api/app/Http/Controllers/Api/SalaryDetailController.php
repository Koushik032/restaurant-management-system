<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalaryDetailResource;
use App\Models\Attendance;
use App\Models\SalaryDetail;
use App\Models\SalaryPayroll;
use App\Services\SalaryDetailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryDetailController extends Controller
{
    public function __construct(
        private readonly SalaryDetailService $salaryDetailService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Salary Detail List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
{
    $this->ensureAdmin(
        $request
    );

    $validated =
        $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:150',
            ],

            'employee_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
            ],

            'from_date' => [
                'nullable',
                'date',
            ],

            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],

            'salary_type' => [
                'nullable',

                Rule::in(
                    SalaryDetail::allowedSalaryTypes()
                ),
            ],

            'attendance_status' => [
                'nullable',

                Rule::in(
                    Attendance::allowedStatuses()
                ),
            ],

            'payment_status' => [
                'nullable',

                Rule::in(
                    SalaryPayroll::allowedPaymentStatuses()
                ),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

    /*
    |--------------------------------------------------------------------------
    | Current Month Default
    |--------------------------------------------------------------------------
    */

    $fromDate =
        Carbon::parse(
            $validated['from_date']
            ??
            now()
                ->startOfMonth()
                ->format('Y-m-d')
        )->startOfDay();

    $toDate =
        Carbon::parse(
            $validated['to_date']
            ??
            today()
                ->format('Y-m-d')
        )->startOfDay();

    if (
        $toDate->lt(
            $fromDate
        )
    ) {
        return response()->json([
            'success' => false,

            'message' =>
                'The end date cannot be before the start date.',
        ], 422);
    }

    /*
    |--------------------------------------------------------------------------
    | Base Query
    |--------------------------------------------------------------------------
    */

    $query =
        SalaryDetail::query()
            ->with([
                'salaryPayroll.payer',
                'attendance',
                'employee.user',
            ])
            ->whereBetween(
                'salary_date',
                [
                    $fromDate
                        ->format('Y-m-d'),

                    $toDate
                        ->format('Y-m-d'),
                ]
            );

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    if (
        ! empty(
            $validated['search']
            ??
            null
        )
    ) {
        $search =
            trim(
                $validated['search']
            );

        $query->whereHas(
            'salaryPayroll',
            function ($payrollQuery) use ($search) {
                $payrollQuery
                    ->where(
                        'employee_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'employee_phone',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'employee_email',
                        'like',
                        "%{$search}%"
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Employee
    |--------------------------------------------------------------------------
    */

    if (
        ! empty(
            $validated['employee_id']
            ??
            null
        )
    ) {
        $query->where(
            'employee_id',
            $validated['employee_id']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Salary Type
    |--------------------------------------------------------------------------
    */

    if (
        ! empty(
            $validated['salary_type']
            ??
            null
        )
    ) {
        $query->where(
            'salary_type',
            $validated['salary_type']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Attendance Status
    |--------------------------------------------------------------------------
    */

    if (
        ! empty(
            $validated['attendance_status']
            ??
            null
        )
    ) {
        $attendanceStatus =
            $validated['attendance_status'];

        $query->whereHas(
            'attendance',
            function ($attendanceQuery) use ($attendanceStatus) {
                $attendanceQuery->where(
                    'status',
                    $attendanceStatus
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    if (
        ! empty(
            $validated['payment_status']
            ??
            null
        )
    ) {
        $paymentStatus =
            $validated['payment_status'];

        $query->whereHas(
            'salaryPayroll',
            function ($payrollQuery) use ($paymentStatus) {
                $payrollQuery->where(
                    'payment_status',
                    $paymentStatus
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Full Period Summary
    |--------------------------------------------------------------------------
    |
    | Summary pagination-এর উপর নির্ভর করবে না।
    | অর্থাৎ page-এ 10/15 rows থাকলেও পুরো selected period-এর
    | worked time, overtime, attendance এবং salary দেখাবে।
    |
    */

    $summaryQuery =
        clone $query;

    /*
    |--------------------------------------------------------------------------
    | Salary Type Counts
    |--------------------------------------------------------------------------
    */

    $totalDetails =
        (clone $summaryQuery)
            ->count();

    $fullSalaryCount =
        (clone $summaryQuery)
            ->whereIn(
                'salary_type',
                [
                    SalaryDetail::TYPE_FULL,
                    SalaryDetail::TYPE_FULL_OVERTIME,
                ]
            )
            ->count();

    $halfSalaryCount =
        (clone $summaryQuery)
            ->whereIn(
                'salary_type',
                [
                    SalaryDetail::TYPE_HALF,
                    SalaryDetail::TYPE_HALF_OVERTIME,
                ]
            )
            ->count();

    $overtimeOnlyCount =
        (clone $summaryQuery)
            ->where(
                'salary_type',
                SalaryDetail::TYPE_OVERTIME_ONLY
            )
            ->count();

    $noSalaryCount =
        (clone $summaryQuery)
            ->where(
                'salary_type',
                SalaryDetail::TYPE_NO_SALARY
            )
            ->count();

    /*
    |--------------------------------------------------------------------------
    | Salary Summary
    |--------------------------------------------------------------------------
    */

    $regularSalary =
        (float) (
            (clone $summaryQuery)
                ->sum(
                    'regular_salary'
                )
        );

    $overtimeSalary =
        (float) (
            (clone $summaryQuery)
                ->sum(
                    'overtime_salary'
                )
        );

    $totalAmount =
        (float) (
            (clone $summaryQuery)
                ->sum(
                    'total_amount'
                )
        );

    /*
    |--------------------------------------------------------------------------
    | Time Summary
    |--------------------------------------------------------------------------
    */

    $scheduledMinutes =
        (int) (
            (clone $summaryQuery)
                ->sum(
                    'scheduled_minutes'
                )
        );

    $workedMinutes =
        (int) (
            (clone $summaryQuery)
                ->sum(
                    'worked_minutes'
                )
        );

    $lateMinutes =
        (int) (
            (clone $summaryQuery)
                ->sum(
                    'late_minutes'
                )
        );

    $breakMinutes =
        (int) (
            (clone $summaryQuery)
                ->sum(
                    'break_minutes'
                )
        );

    $overtimeMinutes =
        (int) (
            (clone $summaryQuery)
                ->sum(
                    'overtime_minutes'
                )
        );

    /*
    |--------------------------------------------------------------------------
    | Attendance Summary
    |--------------------------------------------------------------------------
    */

    $completedDays =
        (clone $summaryQuery)
            ->whereHas(
                'attendance',
                function ($attendanceQuery) {

                    $attendanceQuery->where(
                        'status',
                        Attendance::STATUS_COMPLETED
                    );

                }
            )
            ->count();

    $presentDays =
        (clone $summaryQuery)
            ->whereHas(
                'attendance',
                function ($attendanceQuery) {

                    $attendanceQuery->where(
                        'status',
                        Attendance::STATUS_PRESENT
                    );

                }
            )
            ->count();

    $absentDays =
        (clone $summaryQuery)
            ->whereHas(
                'attendance',
                function ($attendanceQuery) {

                    $attendanceQuery->where(
                        'status',
                        Attendance::STATUS_ABSENT
                    );

                }
            )
            ->count();

    $leaveDays =
        (clone $summaryQuery)
            ->whereHas(
                'attendance',
                function ($attendanceQuery) {

                    $attendanceQuery->where(
                        'status',
                        Attendance::STATUS_LEAVE
                    );

                }
            )
            ->count();

    /*
    |--------------------------------------------------------------------------
    | Final Summary Array
    |--------------------------------------------------------------------------
    */

    $summary = [

        'total_details' =>
            $totalDetails,

        'full_salary_count' =>
            $fullSalaryCount,

        'half_salary_count' =>
            $halfSalaryCount,

        'overtime_only_count' =>
            $overtimeOnlyCount,

        'no_salary_count' =>
            $noSalaryCount,

        /*
        |--------------------------------------------------------------------------
        | Time
        |--------------------------------------------------------------------------
        */

        'scheduled_minutes' =>
            $scheduledMinutes,

        'worked_minutes' =>
            $workedMinutes,

        'late_minutes' =>
            $lateMinutes,

        'break_minutes' =>
            $breakMinutes,

        'overtime_minutes' =>
            $overtimeMinutes,

        /*
        |--------------------------------------------------------------------------
        | Attendance
        |--------------------------------------------------------------------------
        */

        'completed_days' =>
            $completedDays,

        'present_days' =>
            $presentDays,

        'absent_days' =>
            $absentDays,

        'leave_days' =>
            $leaveDays,

        /*
        |--------------------------------------------------------------------------
        | Salary
        |--------------------------------------------------------------------------
        */

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
                $totalAmount,
                2
            ),
    ];

    /*
    |--------------------------------------------------------------------------
    | Paginated Salary Details
    |--------------------------------------------------------------------------
    |
    | শুধু table rows pagination হবে।
    | উপরের summary pagination-এর বাইরে already calculate হয়েছে।
    |
    */

    $salaryDetails =
        $query
            ->orderByDesc(
                'salary_date'
            )
            ->orderByDesc(
                'id'
            )
            ->paginate(
                (int) (
                    $validated['per_page']
                    ??
                    10
                )
            )
            ->withQueryString();

    /*
    |--------------------------------------------------------------------------
    | Response
    |--------------------------------------------------------------------------
    */

    return SalaryDetailResource::collection(
        $salaryDetails
    )->additional([
        'success' =>
            true,

        'message' =>
            'Salary details loaded successfully.',

        'selected_period' => [

            'from_date' =>
                $fromDate
                    ->format('Y-m-d'),

            'to_date' =>
                $toDate
                    ->format('Y-m-d'),
        ],

        'summary' =>
            $summary,
    ]);
}


    /*
    |--------------------------------------------------------------------------
    | Update Daily Salary Type
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        SalaryDetail $salaryDetail
    ) {
        $this->ensureAdmin(
            $request
        );

        $validated =
            $request->validate([
                'salary_type' => [
                    'required',

                    Rule::in(
                        SalaryDetail::allowedSalaryTypes()
                    ),
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);

        $salaryDetail =
            $this->salaryDetailService
                ->updateSalaryDetail(
                    $salaryDetail,
                    $validated['salary_type'],
                    $validated['notes']
                    ??
                    $salaryDetail->notes,
                    $request->user()
                );

        return (
            new SalaryDetailResource(
                $salaryDetail
            )
        )->additional([
            'success' =>
                true,

            'message' =>
                'Daily salary calculation updated successfully.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Admin Check
    |--------------------------------------------------------------------------
    */

    private function ensureAdmin(
        Request $request
    ): void {

        $user =
            $request->user();

        $user?->loadMissing(
            'role'
        );

        $roleName =
            strtolower(
                trim(
                    (string) (
                        $user
                            ?->role
                            ?->name
                        ??
                        ''
                    )
                )
            );

        abort_unless(
            $user
            &&
            $roleName === 'admin',
            403,
            'Only administrators can manage salary details.'
        );
    }
}