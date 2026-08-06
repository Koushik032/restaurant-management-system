<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SalaryPayrollResource;
use App\Models\Employee;
use App\Models\SalaryPayroll;
use App\Services\SalaryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryController extends Controller
{
    public function __construct(
        private readonly SalaryService $salaryService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Salary List
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


        $query =
            SalaryPayroll::query()
                ->with([
                    'payer',
                ])
                ->withCount(
                    'salaryDetails'
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


            $query->where(
                function ($searchQuery) use ($search) {

                    $searchQuery
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
        | Employee Filter
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
        | Period Filter
        |--------------------------------------------------------------------------
        */

        if (
            ! empty(
                $validated['from_date']
                ??
                null
            )
        ) {

            $query->whereDate(
                'period_start',
                '>=',
                $validated['from_date']
            );

        }


        if (
            ! empty(
                $validated['to_date']
                ??
                null
            )
        ) {

            $query->whereDate(
                'period_end',
                '<=',
                $validated['to_date']
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Payment Filter
        |--------------------------------------------------------------------------
        */

        if (
            ! empty(
                $validated['payment_status']
                ??
                null
            )
        ) {

            $query->where(
                'payment_status',
                $validated['payment_status']
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Summary
        |--------------------------------------------------------------------------
        */

        $summaryQuery =
            clone $query;


        $summary = [

            'total_payrolls' =>
                (clone $summaryQuery)
                    ->count(),

            'paid_count' =>
                (clone $summaryQuery)
                    ->where(
                        'payment_status',
                        SalaryPayroll::STATUS_PAID
                    )
                    ->count(),

            'unpaid_count' =>
                (clone $summaryQuery)
                    ->where(
                        'payment_status',
                        SalaryPayroll::STATUS_UNPAID
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

            'adjustment_amount' =>
                (float) (
                    (clone $summaryQuery)
                        ->sum(
                            'adjustment_amount'
                        )
                ),

            'total_amount' =>
                (float) (
                    (clone $summaryQuery)
                        ->sum(
                            'total_amount'
                        )
                ),

            'paid_amount' =>
                (float) (
                    (clone $summaryQuery)
                        ->where(
                            'payment_status',
                            SalaryPayroll::STATUS_PAID
                        )
                        ->sum(
                            'total_amount'
                        )
                ),

            'unpaid_amount' =>
                (float) (
                    (clone $summaryQuery)
                        ->where(
                            'payment_status',
                            SalaryPayroll::STATUS_UNPAID
                        )
                        ->sum(
                            'total_amount'
                        )
                ),

        ];


        $payrolls =
            $query
                ->latest(
                    'period_end'
                )
                ->latest('id')
                ->paginate(
                    (int) (
                        $validated['per_page']
                        ??
                        10
                    )
                )
                ->withQueryString();


        return SalaryPayrollResource::collection(
            $payrolls
        )->additional([

            'success' =>
                true,

            'message' =>
                'Salary payrolls loaded successfully.',

            'summary' =>
                $summary,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Employee Options
    |--------------------------------------------------------------------------
    */

    public function employees(Request $request)
    {
        $this->ensureAdmin(
            $request
        );


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
                ->orderBy('id')
                ->get()
                ->map(
                    function (Employee $employee) {

                        return [

                            'id' =>
                                (int) $employee->id,

                            'employee_name' =>
                                $employee->user
                                    ?->name
                                ??
                                'Unknown Staff',

                            'phone' =>
                                $employee->phone,

                            'email' =>
                                $employee->user
                                    ?->email,

                            'hourly_rate' =>
                                (float)
                                $employee->hourly_rate,

                            'hourly_rate_formatted' =>
                                '৳ '
                                .
                                number_format(
                                    (float)
                                    $employee
                                        ->hourly_rate,
                                    2
                                )
                                .
                                ' / hour',

                            'role_name' =>
                                $employee->user
                                    ?->role
                                    ?->name,

                        ];

                    }
                )
                ->values();


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Salary employee options loaded successfully.',

            'data' =>
                $employees,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Generate or Recalculate
    |--------------------------------------------------------------------------
    */

    public function generate(Request $request)
    {
        $this->ensureAdmin(
            $request
        );


        $validated =
            $request->validate([

                'from_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],

                'to_date' => [
                    'required',
                    'date',
                    'after_or_equal:from_date',
                    'before_or_equal:today',
                ],

                'employee_id' => [
                    'nullable',
                    'integer',
                    'exists:employees,id',
                ],

            ]);


        $result =
            $this->salaryService
                ->generatePayrolls(

                    $validated['from_date'],

                    $validated['to_date'],

                    isset(
                        $validated['employee_id']
                    )
                        ? (int)
                            $validated['employee_id']
                        : null,

                    $request->user()

                );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Salary generation and recalculation completed successfully.',

            'data' =>
                $result,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Update Payroll
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        SalaryPayroll $salary
    ) {
        $this->ensureAdmin(
            $request
        );


        $validated =
            $request->validate([

                'adjustment_amount' => [
                    'nullable',
                    'numeric',
                    'between:-99999999,99999999',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

            ]);


        $salary =
            $this->salaryService
                ->updatePayroll(

                    $salary,

                    $validated,

                    $request->user()

                );


        return (
            new SalaryPayrollResource(
                $salary
            )
        )->additional([

            'success' =>
                true,

            'message' =>
                'Salary payroll updated successfully.',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    public function paymentStatus(
        Request $request,
        SalaryPayroll $salary
    ) {
        $this->ensureAdmin(
            $request
        );


        $validated =
            $request->validate([

                'payment_status' => [
                    'required',

                    Rule::in(
                        SalaryPayroll::allowedPaymentStatuses()
                    ),
                ],

            ]);


        $salary =
            $this->salaryService
                ->updatePaymentStatus(

                    $salary,

                    $validated[
                        'payment_status'
                    ],

                    $request->user()

                );


        return (
            new SalaryPayrollResource(
                $salary
            )
        )->additional([

            'success' =>
                true,

            'message' =>
                'Salary payment status updated successfully.',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Payroll
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request,
        SalaryPayroll $salary
    ) {
        $this->ensureAdmin(
            $request
        );


        $this->salaryService
            ->deletePayroll(
                $salary
            );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Salary payroll deleted successfully.',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Admin Protection
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
            'Only administrators can manage employee salaries.'
        );
    }
}