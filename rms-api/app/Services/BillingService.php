<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BillingService
{
    /*
    |--------------------------------------------------------------------------
    | Billing Summary
    |--------------------------------------------------------------------------
    |
    | Accounting calculations:
    |
    | Gross Sales       = SUM(subtotal)
    | Discount          = SUM(discount_amount)
    | Net Sales         = Gross Sales - Discount
    | Tax               = SUM(tax_amount)
    | Service Charge    = SUM(service_charge)
    | Total Billed      = Net Sales + Tax + Service Charge
    | Collected Amount  = SUM(order_payments.amount)
    | Cash Collection   = Cash payments only
    | Outstanding Due   = SUM(due_amount)
    |
    | Canceled orders order-based calculations থেকে বাদ যাবে।
    |
    */

    public function getSummary(
        array $filters = []
    ): array {
        [
            $startDate,
            $endDate,
        ] = $this->resolveDateRange(
            $filters
        );

        /*
        |--------------------------------------------------------------------------
        | Valid Orders Query
        |--------------------------------------------------------------------------
        */

        $ordersQuery = Order::query()
            ->whereBetween(
                'created_at',
                [
                    $startDate,
                    $endDate,
                ]
            )
            ->where(
                'status',
                '!=',
                Order::STATUS_CANCELED
            );

        /*
        |--------------------------------------------------------------------------
        | Payment Transactions Query
        |--------------------------------------------------------------------------
        |
        | Collection payment transaction-এর created_at date অনুযায়ী হবে।
        |
        */

        $paymentsQuery = OrderPayment::query()
            ->whereBetween(
                'created_at',
                [
                    $startDate,
                    $endDate,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Order-Based Calculations
        |--------------------------------------------------------------------------
        */

        $grossSales = (float) (
            clone $ordersQuery
        )->sum(
            'subtotal'
        );

        $discountAmount = (float) (
            clone $ordersQuery
        )->sum(
            'discount_amount'
        );

        $netSales = max(
            0.0,
            $grossSales - $discountAmount
        );

        $tax = (float) (
            clone $ordersQuery
        )->sum(
            'tax_amount'
        );

        $serviceCharge = (float) (
            clone $ordersQuery
        )->sum(
            'service_charge'
        );

        $totalBilled =
            $netSales
            + $tax
            + $serviceCharge;

        $outstandingDue = (float) (
            clone $ordersQuery
        )
            ->where(
                'due_amount',
                '>',
                0
            )
            ->sum(
                'due_amount'
            );

        $totalOrders = (int) (
            clone $ordersQuery
        )->count();

        /*
        |--------------------------------------------------------------------------
        | Payment-Based Calculations
        |--------------------------------------------------------------------------
        */

        $collectedAmount = (float) (
            clone $paymentsQuery
        )->sum(
            'amount'
        );

        $cashCollection = (float) (
            clone $paymentsQuery
        )
            ->where(
                'payment_method',
                Order::METHOD_CASH
            )
            ->sum(
                'amount'
            );

        /*
        |--------------------------------------------------------------------------
        | Expenses
        |--------------------------------------------------------------------------
        |
        | Expense module এখনো connect হয়নি।
        |
        */

        $expenses = 0.0;

        return [
            /*
            |--------------------------------------------------------------------------
            | Raw Values
            |--------------------------------------------------------------------------
            */

            'gross_sales' =>
                $grossSales,

            'discount_amount' =>
                $discountAmount,

            'net_sales' =>
                $netSales,

            'tax' =>
                $tax,

            'service_charge' =>
                $serviceCharge,

            'total_billed' =>
                $totalBilled,

            'collected_amount' =>
                $collectedAmount,

            'expenses' =>
                $expenses,

            'cash_collection' =>
                $cashCollection,

            'outstanding_due' =>
                $outstandingDue,

            'total_orders' =>
                $totalOrders,

            /*
            |--------------------------------------------------------------------------
            | Formatted Values
            |--------------------------------------------------------------------------
            */

            'gross_sales_formatted' =>
                $this->formatMoney(
                    $grossSales
                ),

            'discount_amount_formatted' =>
                $this->formatMoney(
                    $discountAmount
                ),

            'net_sales_formatted' =>
                $this->formatMoney(
                    $netSales
                ),

            'tax_formatted' =>
                $this->formatMoney(
                    $tax
                ),

            'service_charge_formatted' =>
                $this->formatMoney(
                    $serviceCharge
                ),

            'total_billed_formatted' =>
                $this->formatMoney(
                    $totalBilled
                ),

            'collected_amount_formatted' =>
                $this->formatMoney(
                    $collectedAmount
                ),

            'expenses_formatted' =>
                $this->formatMoney(
                    $expenses
                ),

            'cash_collection_formatted' =>
                $this->formatMoney(
                    $cashCollection
                ),

            'outstanding_due_formatted' =>
                $this->formatMoney(
                    $outstandingDue
                ),

            /*
            |--------------------------------------------------------------------------
            | Active Date Range
            |--------------------------------------------------------------------------
            */

            'date_range' =>
                $this->buildDateRange(
                    $startDate,
                    $endDate
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Settlement Orders
    |--------------------------------------------------------------------------
    |
    | Supports:
    |
    | Payment statuses:
    | - due
    | - partially_paid
    | - paid
    |
    | Order statuses:
    | - pending
    | - preparing
    | - ready
    | - served
    | - completed
    | - canceled
    |
    */

    public function getSettlements(
        array $filters = []
    ): array {
        [
            $startDate,
            $endDate,
        ] = $this->resolveDateRange(
            $filters
        );

        $status = trim(
            (string) (
                $filters['status']
                ?? ''
            )
        );

        $query = Order::query()
            ->with([
                'customer:id,name',
            ])
            ->whereBetween(
                'created_at',
                [
                    $startDate,
                    $endDate,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $status,
                Order::allowedPaymentStatuses(),
                true
            )
        ) {
            $query->where(
                'payment_status',
                $status
            );
        } elseif (
            in_array(
                $status,
                Order::allowedStatuses(),
                true
            )
        ) {
            $query->where(
                'status',
                $status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filtered Total
        |--------------------------------------------------------------------------
        |
        | Pagination apply করার আগে total calculate করা হবে।
        |
        */

        $totalAmount = (float) (
            clone $query
        )->sum(
            'total_amount'
        );

        $orders = $query
            ->orderByDesc(
                'created_at'
            )
            ->orderByDesc(
                'id'
            )
            ->paginate(
                $this->resolvePerPage(
                    $filters
                )
            );

        return [
            'orders' =>
                $orders,

            'totals' =>
                $this->buildTotals(
                    $totalAmount
                ),

            'date_range' =>
                $this->buildDateRange(
                    $startDate,
                    $endDate
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Mode Report
    |--------------------------------------------------------------------------
    |
    | একই order-এর একাধিক payment transaction আলাদা row হিসেবে থাকবে।
    |
    */

    public function getPaymentModes(
        array $filters = []
    ): array {
        [
            $startDate,
            $endDate,
        ] = $this->resolveDateRange(
            $filters
        );

        $paymentMethod = trim(
            (string) (
                $filters['payment_method']
                ?? ''
            )
        );

        $query = OrderPayment::query()
            ->with([
                'order.customer:id,name',
            ])
            ->whereBetween(
                'created_at',
                [
                    $startDate,
                    $endDate,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | Payment Method Filter
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $paymentMethod,
                Order::allowedPaymentMethods(),
                true
            )
        ) {
            $query->where(
                'payment_method',
                $paymentMethod
            );
        }

        $totalAmount = (float) (
            clone $query
        )->sum(
            'amount'
        );

        $payments = $query
            ->orderByDesc(
                'created_at'
            )
            ->orderByDesc(
                'id'
            )
            ->paginate(
                $this->resolvePerPage(
                    $filters
                )
            );

        return [
            'payments' =>
                $payments,

            'totals' =>
                $this->buildTotals(
                    $totalAmount
                ),

            'date_range' =>
                $this->buildDateRange(
                    $startDate,
                    $endDate
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Payment and Staff Activity
    |--------------------------------------------------------------------------
    |
    | Staff mapping:
    |
    | - Order Creator = orders.created_by
    | - Chef          = orders.chef_id
    | - Receiver      = order_payments.received_by
    |
    */

    public function getPaymentActivities(
        array $filters = []
    ): array {
        [
            $startDate,
            $endDate,
        ] = $this->resolveDateRange(
            $filters
        );

        $userId = $this->resolveUserId(
            $filters['user_id']
            ?? null
        );

        $userType = strtolower(
            trim(
                (string) (
                    $filters['user_type']
                    ?? 'all'
                )
            )
        );

        $query = OrderPayment::query()
            ->with([
                'order.customer:id,name',

                'order.creator:id,username,name',

                'order.chef:id,username,name',

                'receiver:id,username,name',
            ])
            ->whereBetween(
                'created_at',
                [
                    $startDate,
                    $endDate,
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | User Filter
        |--------------------------------------------------------------------------
        */

        if ($userId !== null) {
            $this->applyActivityUserFilter(
                query: $query,
                userId: $userId,
                userType: $userType
            );
        }

        $totalAmount = (float) (
            clone $query
        )->sum(
            'amount'
        );

        $activities = $query
            ->orderByDesc(
                'created_at'
            )
            ->orderByDesc(
                'id'
            )
            ->paginate(
                $this->resolvePerPage(
                    $filters
                )
            );

        return [
            'activities' =>
                $activities,

            'totals' =>
                $this->buildTotals(
                    $totalAmount
                ),

            'date_range' =>
                $this->buildDateRange(
                    $startDate,
                    $endDate
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Users
    |--------------------------------------------------------------------------
    |
    | Payment activity filter dropdown-এর জন্য active users return করবে।
    |
    */

    public function getUsers(): Collection
    {
        return User::query()
            ->select([
                'id',
                'username',
                'name',
            ])
            ->where(
                'is_active',
                true
            )
            ->whereNull(
                'blocked_at'
            )
            ->orderByRaw(
                'CASE
                    WHEN username IS NULL
                    OR username = ""
                    THEN 1
                    ELSE 0
                END'
            )
            ->orderBy(
                'username'
            )
            ->orderBy(
                'name'
            )
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Activity User Filter
    |--------------------------------------------------------------------------
    */

    private function applyActivityUserFilter(
        Builder $query,
        int $userId,
        string $userType
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Order Creator / Waiter
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $userType,
                [
                    'waiter',
                    'creator',
                ],
                true
            )
        ) {
            $query->whereHas(
                'order',
                static function (
                    Builder $orderQuery
                ) use (
                    $userId
                ): void {
                    $orderQuery->where(
                        'created_by',
                        $userId
                    );
                }
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Chef
        |--------------------------------------------------------------------------
        */

        if ($userType === 'chef') {
            $query->whereHas(
                'order',
                static function (
                    Builder $orderQuery
                ) use (
                    $userId
                ): void {
                    $orderQuery->where(
                        'chef_id',
                        $userId
                    );
                }
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Payment Receiver
        |--------------------------------------------------------------------------
        */

        if ($userType === 'receiver') {
            $query->where(
                'received_by',
                $userId
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | All Roles
        |--------------------------------------------------------------------------
        */

        $query->where(
            static function (
                Builder $activityQuery
            ) use (
                $userId
            ): void {
                $activityQuery
                    ->where(
                        'received_by',
                        $userId
                    )
                    ->orWhereHas(
                        'order',
                        static function (
                            Builder $orderQuery
                        ) use (
                            $userId
                        ): void {
                            $orderQuery
                                ->where(
                                    'created_by',
                                    $userId
                                )
                                ->orWhere(
                                    'chef_id',
                                    $userId
                                );
                        }
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Billing Date Range
    |--------------------------------------------------------------------------
    |
    | Behaviour:
    |
    | - date_from + date_to = সম্পূর্ণ range
    | - date_from only      = ওই একদিন
    | - date_to only        = ওই একদিন
    | - both empty          = আজকের দিন
    |
    */

    private function resolveDateRange(
        array $filters
    ): array {
        $dateFrom = trim(
            (string) (
                $filters['date_from']
                ?? ''
            )
        );

        $dateTo = trim(
            (string) (
                $filters['date_to']
                ?? ''
            )
        );

        /*
        |--------------------------------------------------------------------------
        | From and To
        |--------------------------------------------------------------------------
        */

        if (
            $dateFrom !== ''
            && $dateTo !== ''
        ) {
            $fromDate = $this->safeParseDate(
                $dateFrom
            );

            $toDate = $this->safeParseDate(
                $dateTo
            );

            /*
            |--------------------------------------------------------------------------
            | Reverse Date Protection
            |--------------------------------------------------------------------------
            */

            if (
                $fromDate->greaterThan(
                    $toDate
                )
            ) {
                return [
                    $toDate
                        ->copy()
                        ->startOfDay(),

                    $fromDate
                        ->copy()
                        ->endOfDay(),
                ];
            }

            return [
                $fromDate
                    ->copy()
                    ->startOfDay(),

                $toDate
                    ->copy()
                    ->endOfDay(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | From Only
        |--------------------------------------------------------------------------
        */

        if ($dateFrom !== '') {
            return $this->singleDayRange(
                $this->safeParseDate(
                    $dateFrom
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | To Only
        |--------------------------------------------------------------------------
        */

        if ($dateTo !== '') {
            return $this->singleDayRange(
                $this->safeParseDate(
                    $dateTo
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Default Today
        |--------------------------------------------------------------------------
        */

        return $this->singleDayRange(
            now()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Build Single-Day Range
    |--------------------------------------------------------------------------
    */

    private function singleDayRange(
        Carbon $date
    ): array {
        return [
            $date
                ->copy()
                ->startOfDay(),

            $date
                ->copy()
                ->endOfDay(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Safe Date Parser
    |--------------------------------------------------------------------------
    |
    | Invalid date হলে আজকের date fallback হবে।
    |
    */

    private function safeParseDate(
        string $date
    ): Carbon {
        try {
            return Carbon::createFromFormat(
                'Y-m-d',
                $date
            )->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Build Filtered Totals
    |--------------------------------------------------------------------------
    */

    private function buildTotals(
        mixed $amount
    ): array {
        $resolvedAmount =
            is_numeric($amount)
                ? (float) $amount
                : 0.0;

        return [
            'amount' =>
                $resolvedAmount,

            'amount_formatted' =>
                $this->formatMoney(
                    $resolvedAmount
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Build Date Range Response
    |--------------------------------------------------------------------------
    */

    private function buildDateRange(
        Carbon $startDate,
        Carbon $endDate
    ): array {
        return [
            'from' =>
                $startDate->format(
                    'Y-m-d'
                ),

            'to' =>
                $endDate->format(
                    'Y-m-d'
                ),

            'from_label' =>
                $startDate->format(
                    'd M Y'
                ),

            'to_label' =>
                $endDate->format(
                    'd M Y'
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Pagination Size
    |--------------------------------------------------------------------------
    */

    private function resolvePerPage(
        array $filters
    ): int {
        $perPage = (int) (
            $filters['per_page']
            ?? 5
        );

        if ($perPage <= 0) {
            return 5;
        }

        return min(
            $perPage,
            100
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve User ID
    |--------------------------------------------------------------------------
    */

    private function resolveUserId(
        mixed $userId
    ): ?int {
        $resolvedUserId =
            (int) $userId;

        return $resolvedUserId > 0
            ? $resolvedUserId
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Format Money
    |--------------------------------------------------------------------------
    */

    private function formatMoney(
        mixed $amount
    ): string {
        return '৳ '
            . number_format(
                is_numeric($amount)
                    ? (float) $amount
                    : 0.0,
                2
            );
    }
}