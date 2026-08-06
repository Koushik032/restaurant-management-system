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
        | Filtered Summary
        |--------------------------------------------------------------------------
        */

        $summaryQuery =
            clone $query;

        $summary = [
            'total_details' =>
                (clone $summaryQuery)
                    ->count(),

            'full_salary_count' =>
                (clone $summaryQuery)
                    ->whereIn(
                        'salary_type',
                        [
                            SalaryDetail::TYPE_FULL,
                            SalaryDetail::TYPE_FULL_OVERTIME,
                        ]
                    )
                    ->count(),

            'half_salary_count' =>
                (clone $summaryQuery)
                    ->whereIn(
                        'salary_type',
                        [
                            SalaryDetail::TYPE_HALF,
                            SalaryDetail::TYPE_HALF_OVERTIME,
                        ]
                    )
                    ->count(),

            'overtime_only_count' =>
                (clone $summaryQuery)
                    ->where(
                        'salary_type',
                        SalaryDetail::TYPE_OVERTIME_ONLY
                    )
                    ->count(),

            'no_salary_count' =>
                (clone $summaryQuery)
                    ->where(
                        'salary_type',
                        SalaryDetail::TYPE_NO_SALARY
                    )
                    ->count(),

            'regular_salary' =>
                (float) (
                    (clone $summaryQuery)
                        ->sum(
                            'regular_salary'
                        )
                ),

            'overtime_salary' =>
                (float) (
                    (clone $summaryQuery)
                        ->sum(
                            'overtime_salary'
                        )
                ),

            'total_amount' =>
                (float) (
                    (clone $summaryQuery)
                        ->sum(
                            'total_amount'
                        )
                ),
        ];

        $salaryDetails =
            $query
                ->orderByDesc(
                    'salary_date'
                )
                ->orderByDesc('id')
                ->paginate(
                    (int) (
                        $validated['per_page']
                        ??
                        10
                    )
                )
                ->withQueryString();

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