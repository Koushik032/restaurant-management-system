<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\PurchaseOrder;
use App\Models\RestaurantStock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportService
{
    /*
    |--------------------------------------------------------------------------
    | Attendance Report
    |--------------------------------------------------------------------------
    */

    
    /*
|--------------------------------------------------------------------------
| Attendance Report
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Attendance Report
|--------------------------------------------------------------------------
*/

public function getAttendance(
    array $filters = []
): LengthAwarePaginator {

    return Attendance::query()

        ->with([
            'employee.user.role',
            'salaryDetail',
        ])

        ->when(
            $filters['date_from'] ?? null,
            function (
                $query,
                $date
            ) {

                $query->whereDate(
                    'attendance_date',
                    '>=',
                    $date
                );

            }
        )

        ->when(
            $filters['date_to'] ?? null,
            function (
                $query,
                $date
            ) {

                $query->whereDate(
                    'attendance_date',
                    '<=',
                    $date
                );

            }
        )

        ->when(
            $filters['employee_id'] ?? null,
            function (
                $query,
                $employeeId
            ) {

                $query->where(
                    'employee_id',
                    (int) $employeeId
                );

            }
        )

        ->when(
            $filters['status'] ?? null,
            function (
                $query,
                $status
            ) {

                $query->where(
                    'status',
                    $status
                );

            }
        )

        ->latest(
            'attendance_date'
        )

        ->latest(
            'id'
        )

        ->paginate(
            (int) (
                $filters['per_page']
                ??
                10
            )
        );
}


/*
|--------------------------------------------------------------------------
| Attendance Report Employees
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Attendance Report Employees
|--------------------------------------------------------------------------
*/

public function getAttendanceEmployees()
{
    return Employee::query()

        ->with([
            'user.role',
        ])

        ->whereHas(
            'user.role',
            function (
                $query
            ) {

                $query->where(
                    'name',
                    '!=',
                    'admin'
                );

            }
        )

        ->orderBy(
            'id'
        )

        ->get()

        ->map(
            function (
                Employee $employee
            ) {

                return [

                    'id' =>
                        (int)
                        $employee->id,

                    'employee_name' =>
                        $employee
                            ->user
                            ?->name
                        ??
                        'Unknown Staff',

                    'phone' =>
                        $employee->phone,

                    'email' =>
                        $employee
                            ->user
                            ?->email,

                    'role_name' =>
                        $employee
                            ->user
                            ?->role
                            ?->name,

                    'hourly_rate' =>
                        (float)
                        $employee
                            ->hourly_rate,

                ];

            }
        )

        ->values();
}

    /*
    |--------------------------------------------------------------------------
    | Export Data
    |--------------------------------------------------------------------------
    */

    public function getExportData(
        string $type,
        array $filters = []
    ): array {

        return match ($type) {


            /*
            |--------------------------------------------------------------------------
            | Orders
            |--------------------------------------------------------------------------
            */

            'orders' =>

                collect(

                    $this->getOrders(

                        array_merge(
                            $filters,
                            [
                                'per_page' => 999999
                            ]
                        )

                    )->items()

                )

                ->map(
                    function ($order) {

                        return [

                            'Order ID' =>
                                $order->id,

                            'Customer' =>
                                $order->customer?->name
                                ??
                                'Walk In',

                            'Date' =>
                                $order->created_at,

                            'Amount' =>
                                $order->total_amount,

                            'Payment' =>
                                $order->payment_method
                                ??
                                '-',

                        ];

                    }
                )

                ->values()

                ->toArray(),



            /*
            |--------------------------------------------------------------------------
            | Expenses
            |--------------------------------------------------------------------------
            */

            'expenses' =>

                collect(

                    $this->getExpenses(

                        array_merge(
                            $filters,
                            [
                                'per_page' => 999999
                            ]
                        )

                    )->items()

                )

                ->map(
                    function ($expense) {

                        return [

                            'Category' =>
                                $expense->category?->name
                                ??
                                '-',

                            'Date' =>
                                $expense->expense_date,

                            'Amount' =>
                                $expense->amount,

                            'Payment' =>
                                $expense->payment_method
                                ??
                                '-',

                            'Note' =>
                                $expense->notes
                                ??
                                '-',

                        ];

                    }
                )

                ->values()

                ->toArray(),



            /*
            |--------------------------------------------------------------------------
            | Purchase
            |--------------------------------------------------------------------------
            */

            'purchase' =>

                collect(

                    $this->getPurchaseOrders(

                        array_merge(
                            $filters,
                            [
                                'per_page' => 999999
                            ]
                        )

                    )->items()

                )

                ->map(
                    function ($purchase) {

                        return [

                            'Purchase ID' =>
                                $purchase->id,

                            'Supplier' =>
                                $purchase->supplier?->supplier_name
                                ??
                                '-',

                            'Date' =>
                                $purchase->order_date,

                            'Total Amount' =>
                                $purchase->total_amount,

                            'Paid' =>
                                $purchase->paid_amount,

                            'Due' =>
                                $purchase->due_amount,

                            'Status' =>
                                $purchase->status,

                        ];

                    }
                )

                ->values()

                ->toArray(),



            /*
            |--------------------------------------------------------------------------
            | Restaurant Stock
            |--------------------------------------------------------------------------
            */

            'stock' =>

                $this->exportStock(
                    $filters
                ),



            /*
            |--------------------------------------------------------------------------
            | Attendance
            |--------------------------------------------------------------------------
            */

            'attendance' =>

                $this->exportAttendance(
                    $filters
                ),



            /*
            |--------------------------------------------------------------------------
            | Default
            |--------------------------------------------------------------------------
            */

            default => [],

        };
    }


    /*
    |--------------------------------------------------------------------------
    | Attendance Export
    |--------------------------------------------------------------------------
    */

   /*
|--------------------------------------------------------------------------
| Attendance Export
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Attendance Export
|--------------------------------------------------------------------------
*/

private function exportAttendance(
    array $filters = []
): array {

    $attendances =
        Attendance::query()

            ->with([
                'employee.user.role',
                'salaryDetail',
            ])

            ->when(
                $filters['date_from'] ?? null,
                function (
                    $query,
                    $date
                ) {

                    $query->whereDate(
                        'attendance_date',
                        '>=',
                        $date
                    );

                }
            )

            ->when(
                $filters['date_to'] ?? null,
                function (
                    $query,
                    $date
                ) {

                    $query->whereDate(
                        'attendance_date',
                        '<=',
                        $date
                    );

                }
            )

            ->when(
                $filters['employee_id'] ?? null,
                function (
                    $query,
                    $employeeId
                ) {

                    $query->where(
                        'employee_id',
                        (int) $employeeId
                    );

                }
            )

            ->when(
                $filters['status'] ?? null,
                function (
                    $query,
                    $status
                ) {

                    $query->where(
                        'status',
                        $status
                    );

                }
            )

            ->latest(
                'attendance_date'
            )

            ->latest(
                'id'
            )

            ->get();


    return $attendances

        ->values()

        ->map(
            function (
                Attendance $attendance,
                int $index
            ) {

                $salaryDetail =
                    $attendance
                        ->salaryDetail;


                $hourlyRate =
                    $salaryDetail?->hourly_rate
                    ??
                    $attendance
                        ->employee
                        ?->hourly_rate
                    ??
                    0;


                $workedMinutes =
                    $salaryDetail
                        ?->worked_minutes
                    ??
                    $attendance
                        ->worked_minutes
                    ??
                    0;


                $overtimeMinutes =
                    $salaryDetail
                        ?->overtime_minutes
                    ??
                    $attendance
                        ->overtime_minutes
                    ??
                    0;


                return [

                    'SL' =>
                        $index + 1,

                    'Employee' =>
                        $attendance
                            ->employee
                            ?->user
                            ?->name
                        ??
                        'Unknown Staff',

                    'Date' =>
                        $attendance
                            ->attendance_date
                            ?->format(
                                'Y-m-d'
                            )
                        ??
                        '-',

                    'Status' =>
                        $attendance
                            ->statusLabel(),

                    'Check In' =>
                        $attendance
                            ->check_in_at
                            ?->format(
                                'Y-m-d H:i:s'
                            )
                        ??
                        '-',

                    'Check Out' =>
                        $attendance
                            ->check_out_at
                            ?->format(
                                'Y-m-d H:i:s'
                            )
                        ??
                        '-',

                    'Hourly Rate' =>
                        '৳ '
                        .
                        number_format(
                            (float)
                            $hourlyRate,
                            2
                        ),

                    'Worked Hour' =>
                        $this->formatMinutes(
                            $workedMinutes
                        ),

                    'Overtime' =>
                        $this->formatMinutes(
                            $overtimeMinutes
                        ),

                ];

            }
        )

        ->toArray();
}

    /*
    |--------------------------------------------------------------------------
    | Format Minutes
    |--------------------------------------------------------------------------
    */

    /*
|--------------------------------------------------------------------------
| Format Minutes
|--------------------------------------------------------------------------
*/

private function formatMinutes(
    mixed $minutes
): string {

    $minutes =
        max(
            0,
            (int) (
                $minutes
                ??
                0
            )
        );


    if (
        $minutes <= 0
    ) {

        return '0h';

    }


    $hours =
        intdiv(
            $minutes,
            60
        );


    $remainingMinutes =
        $minutes % 60;


    if (
        $remainingMinutes === 0
    ) {

        return "{$hours}h";

    }


    return
        $hours
        . 'h '
        . $remainingMinutes
        . 'm';
}


    /*
    |--------------------------------------------------------------------------
    | Restaurant Stock Export
    |--------------------------------------------------------------------------
    */

    private function exportStock(
        array $filters = []
    ): array {

        $stocks =

            RestaurantStock::query()

                ->with([
                    'rawMaterial'
                ])

                ->when(
                    $filters['date_from'] ?? null,
                    function ($query, $date) {

                        $query->whereDate(
                            'last_received_at',
                            '>=',
                            $date
                        );

                    }
                )

                ->when(
                    $filters['date_to'] ?? null,
                    function ($query, $date) {

                        $query->whereDate(
                            'last_received_at',
                            '<=',
                            $date
                        );

                    }
                )

                ->latest()

                ->get();


        return $stocks

            ->map(
                function ($stock) {

                    return [

                        'Material' =>

                            $stock
                                ->rawMaterial
                                ?->material_name
                            ??
                            $stock
                                ->rawMaterial
                                ?->name
                            ??
                            '-',

                        'Unit' =>

                            $stock
                                ->rawMaterial
                                ?->base_unit
                            ??
                            '-',

                        'Quantity' =>

                            $stock->quantity
                            ??
                            0,

                        'Unit Cost' =>

                            $stock->average_unit_cost
                            ??
                            0,

                        'Total Value' =>

                            (
                                (float)
                                ($stock->quantity ?? 0)
                            )
                            *
                            (
                                (float)
                                ($stock->average_unit_cost ?? 0)
                            ),

                        'Last Received' =>

                            $stock
                                ->last_received_at
                                ?->format(
                                    'Y-m-d H:i:s'
                                )
                            ??
                            '-',

                    ];

                }
            )

            ->values()

            ->toArray();
    }
}