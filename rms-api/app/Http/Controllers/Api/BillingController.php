<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        private readonly BillingService $billingService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Summary
    |--------------------------------------------------------------------------
    |
    | Supported filters:
    |
    | - date_from only = ওই একদিন
    | - date_to only   = ওই একদিন
    | - both dates     = সম্পূর্ণ date range
    | - no date        = আজকের data
    |
    */

    public function summary(
        Request $request
    ): JsonResponse {
        $summary = $this->billingService
            ->getSummary(
                $request->only([
                    'date_from',
                    'date_to',
                ])
            );

        return response()->json([
            'success' => true,

            'message' =>
                'Billing summary loaded successfully.',

            'data' =>
                $summary,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Settlement Orders
    |--------------------------------------------------------------------------
    |
    | Selected date range এবং status filter অনুযায়ী:
    |
    | - Paginated orders
    | - Filtered total amount
    | - Active date range
    | - Status filter options
    |
    */

    public function settlements(
        Request $request
    ): JsonResponse {
        $result = $this->billingService
            ->getSettlements(
                $request->only([
                    'date_from',
                    'date_to',
                    'status',
                    'page',
                    'per_page',
                ])
            );

        $orders =
            $result['orders'];

        $data = $orders
            ->getCollection()
            ->map(
                fn ($order): array =>
                    $this->transformSettlementOrder(
                        $order
                    )
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'Settlement orders loaded successfully.',

            'data' =>
                $data,

            'meta' =>
                $this->paginationMeta(
                    $orders
                ),

            'totals' =>
                $this->normaliseTotals(
                    $result['totals']
                    ?? []
                ),

            'date_range' =>
                $this->normaliseDateRange(
                    $result['date_range']
                    ?? []
                ),

            'filters' => [
                'statuses' =>
                    $this->settlementStatusOptions(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Mode Report
    |--------------------------------------------------------------------------
    |
    | Selected date range এবং payment method অনুযায়ী:
    |
    | - Paginated payment transactions
    | - Filtered total amount
    | - Payment method options
    |
    */

    public function paymentModes(
        Request $request
    ): JsonResponse {
        $result = $this->billingService
            ->getPaymentModes(
                $request->only([
                    'date_from',
                    'date_to',
                    'payment_method',
                    'page',
                    'per_page',
                ])
            );

        $payments =
            $result['payments'];

        $data = $payments
            ->getCollection()
            ->map(
                fn ($payment): array =>
                    $this->transformPaymentMode(
                        $payment
                    )
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'Payment mode report loaded successfully.',

            'data' =>
                $data,

            'meta' =>
                $this->paginationMeta(
                    $payments
                ),

            'totals' =>
                $this->normaliseTotals(
                    $result['totals']
                    ?? []
                ),

            'date_range' =>
                $this->normaliseDateRange(
                    $result['date_range']
                    ?? []
                ),

            'filters' => [
                'payment_methods' =>
                    $this->paymentMethodOptions(),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Activity Report
    |--------------------------------------------------------------------------
    |
    | Staff mapping:
    |
    | - Order Creator = orders.created_by
    | - Chef          = orders.chef_id
    | - Receiver      = order_payments.received_by
    |
    */

    public function paymentActivities(
        Request $request
    ): JsonResponse {
        $result = $this->billingService
            ->getPaymentActivities(
                $request->only([
                    'date_from',
                    'date_to',
                    'user_id',
                    'user_type',
                    'page',
                    'per_page',
                ])
            );

        $activities =
            $result['activities'];

        $data = $activities
            ->getCollection()
            ->map(
                fn ($payment): array =>
                    $this->transformPaymentActivity(
                        $payment
                    )
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'Payment activity report loaded successfully.',

            'data' =>
                $data,

            'meta' =>
                $this->paginationMeta(
                    $activities
                ),

            'totals' =>
                $this->normaliseTotals(
                    $result['totals']
                    ?? []
                ),

            'date_range' =>
                $this->normaliseDateRange(
                    $result['date_range']
                    ?? []
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Billing User Filter Options
    |--------------------------------------------------------------------------
    */

    public function users(): JsonResponse
    {
        $users = $this->billingService
            ->getUsers()
            ->map(
                fn ($user): array =>
                    $this->transformUser(
                        $user
                    )
            )
            ->values();

        return response()->json([
            'success' => true,

            'message' =>
                'Billing users loaded successfully.',

            'data' =>
                $users,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Transform Settlement Order
    |--------------------------------------------------------------------------
    */

    private function transformSettlementOrder(
        mixed $order
    ): array {
        return [
            'id' =>
                (int) $order->id,

            'order_number' =>
                $order->order_number,

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer' =>
                $this->transformCustomer(
                    customerId:
                        $order->customer_id,

                    customerName:
                        $order->customer_name
                        ?: $order->customer?->name
                ),

            /*
            |--------------------------------------------------------------------------
            | Order Status
            |--------------------------------------------------------------------------
            */

            'order_status' =>
                $order->status,

            'order_status_label' =>
                $this->formatLabel(
                    $order->status
                ),

            /*
            |--------------------------------------------------------------------------
            | Payment Status
            |--------------------------------------------------------------------------
            */

            'payment_status' =>
                $order->payment_status,

            'payment_status_label' =>
                $this->formatLabel(
                    $order->payment_status
                ),

            /*
            |--------------------------------------------------------------------------
            | Financial Information
            |--------------------------------------------------------------------------
            */

            'total_amount' =>
                $this->toFloat(
                    $order->total_amount
                ),

            'paid_amount' =>
                $this->toFloat(
                    $order->paid_amount
                ),

            'due_amount' =>
                $this->toFloat(
                    $order->due_amount
                ),

            'total_amount_formatted' =>
                $this->formatMoney(
                    $order->total_amount
                ),

            'paid_amount_formatted' =>
                $this->formatMoney(
                    $order->paid_amount
                ),

            'due_amount_formatted' =>
                $this->formatMoney(
                    $order->due_amount
                ),

            /*
            |--------------------------------------------------------------------------
            | Date and Time
            |--------------------------------------------------------------------------
            */

            ...$this->transformDateTime(
                $order->created_at
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Transform Payment Mode Row
    |--------------------------------------------------------------------------
    */

    private function transformPaymentMode(
        mixed $payment
    ): array {
        $order =
            $payment->order;

        return [
            'id' =>
                (int) $payment->id,

            'order_id' =>
                (int) $payment->order_id,

            'order_number' =>
                $order?->order_number,

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer' =>
                $this->transformCustomer(
                    customerId:
                        $order?->customer_id,

                    customerName:
                        $order?->customer_name
                        ?: $order?->customer?->name
                ),

            /*
            |--------------------------------------------------------------------------
            | Payment Information
            |--------------------------------------------------------------------------
            */

            'payment_method' =>
                $payment->payment_method,

            'payment_method_label' =>
                $this->formatLabel(
                    $payment->payment_method
                ),

            'amount' =>
                $this->toFloat(
                    $payment->amount
                ),

            'amount_formatted' =>
                $this->formatMoney(
                    $payment->amount
                ),

            'reference' =>
                $payment->reference,

            'note' =>
                $payment->note,

            /*
            |--------------------------------------------------------------------------
            | Date and Time
            |--------------------------------------------------------------------------
            */

            ...$this->transformDateTime(
                $payment->created_at
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Transform Payment Activity Row
    |--------------------------------------------------------------------------
    */

    private function transformPaymentActivity(
        mixed $payment
    ): array {
        $order =
            $payment->order;

        return [
            'id' =>
                (int) $payment->id,

            'order_id' =>
                (int) $payment->order_id,

            'order_number' =>
                $order?->order_number,

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer' =>
                $this->transformCustomer(
                    customerId:
                        $order?->customer_id,

                    customerName:
                        $order?->customer_name
                        ?: $order?->customer?->name
                ),

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment_method' =>
                $payment->payment_method,

            'payment_method_label' =>
                $this->formatLabel(
                    $payment->payment_method
                ),

            'amount' =>
                $this->toFloat(
                    $payment->amount
                ),

            'amount_formatted' =>
                $this->formatMoney(
                    $payment->amount
                ),

            /*
            |--------------------------------------------------------------------------
            | Order Creator / Waiter
            |--------------------------------------------------------------------------
            */

            'waiter' =>
                $this->transformUser(
                    $order?->creator
                ),

            /*
            |--------------------------------------------------------------------------
            | Assigned Chef
            |--------------------------------------------------------------------------
            */

            'chef' =>
                $this->transformUser(
                    $order?->chef
                ),

            /*
            |--------------------------------------------------------------------------
            | Payment Receiver
            |--------------------------------------------------------------------------
            */

            'receiver' =>
                $this->transformUser(
                    $payment->receiver
                ),

            /*
            |--------------------------------------------------------------------------
            | Date and Time
            |--------------------------------------------------------------------------
            */

            ...$this->transformDateTime(
                $payment->created_at
            ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Transform Customer
    |--------------------------------------------------------------------------
    */

    private function transformCustomer(
        mixed $customerId,
        ?string $customerName
    ): array {
        return [
            'id' =>
                $customerId !== null
                    ? (int) $customerId
                    : null,

            'name' =>
                filled($customerName)
                    ? $customerName
                    : 'Walk-in Customer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Transform User
    |--------------------------------------------------------------------------
    */

    private function transformUser(
        mixed $user
    ): array {
        return [
            'id' =>
                $user?->id !== null
                    ? (int) $user->id
                    : null,

            'username' =>
                $user?->username,

            'name' =>
                $user?->name,

            'display_name' =>
                $this->resolveUserName(
                    $user
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Transform Date and Time
    |--------------------------------------------------------------------------
    */

    private function transformDateTime(
        mixed $dateTime
    ): array {
        return [
            'date' =>
                $dateTime?->format(
                    'd M Y'
                ),

            'time' =>
                $dateTime?->format(
                    'h:i A'
                ),

            'created_at' =>
                $dateTime?->toISOString(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Normalise Filtered Totals
    |--------------------------------------------------------------------------
    */

    private function normaliseTotals(
        array $totals
    ): array {
        $amount =
            $this->toFloat(
                $totals['amount']
                ?? 0
            );

        return [
            'amount' =>
                $amount,

            'amount_formatted' =>
                $totals['amount_formatted']
                ?? $this->formatMoney(
                    $amount
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Normalise Date Range
    |--------------------------------------------------------------------------
    */

    private function normaliseDateRange(
        array $dateRange
    ): array {
        return [
            'from' =>
                $dateRange['from']
                ?? '',

            'to' =>
                $dateRange['to']
                ?? '',

            'from_label' =>
                $dateRange['from_label']
                ?? '',

            'to_label' =>
                $dateRange['to_label']
                ?? '',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination Metadata
    |--------------------------------------------------------------------------
    */

    private function paginationMeta(
        mixed $paginator
    ): array {
        return [
            'current_page' =>
                (int) $paginator->currentPage(),

            'last_page' =>
                (int) $paginator->lastPage(),

            'per_page' =>
                (int) $paginator->perPage(),

            'total' =>
                (int) $paginator->total(),

            'from' =>
                $paginator->firstItem(),

            'to' =>
                $paginator->lastItem(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Settlement Status Options
    |--------------------------------------------------------------------------
    |
    | Payment status এবং order status—দুই ধরনের filter support করবে।
    |
    */

    private function settlementStatusOptions(): array
    {
        return [
            [
                'value' => '',
                'label' => 'All Statuses',
            ],

            [
                'value' => 'due',
                'label' => 'Due',
            ],

            [
                'value' => 'partially_paid',
                'label' => 'Partially Paid',
            ],

            [
                'value' => 'paid',
                'label' => 'Paid',
            ],

            [
                'value' => 'pending',
                'label' => 'Pending',
            ],

            [
                'value' => 'preparing',
                'label' => 'Preparing',
            ],

            [
                'value' => 'ready',
                'label' => 'Ready',
            ],

            [
                'value' => 'served',
                'label' => 'Served',
            ],

            [
                'value' => 'completed',
                'label' => 'Completed',
            ],

            [
                'value' => 'canceled',
                'label' => 'Canceled',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Method Options
    |--------------------------------------------------------------------------
    */

    private function paymentMethodOptions(): array
    {
        return [
            [
                'value' => '',
                'label' => 'All Methods',
            ],

            [
                'value' => 'cash',
                'label' => 'Cash',
            ],

            [
                'value' => 'card',
                'label' => 'Card',
            ],

            [
                'value' => 'bkash',
                'label' => 'bKash',
            ],

            [
                'value' => 'nagad',
                'label' => 'Nagad',
            ],

            [
                'value' => 'bank_transfer',
                'label' => 'Bank Transfer',
            ],

            [
                'value' => 'mixed',
                'label' => 'Mixed',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve User Display Name
    |--------------------------------------------------------------------------
    */

    private function resolveUserName(
        mixed $user
    ): string {
        return $user?->username
            ?: $user?->name
            ?: 'Not Assigned';
    }

    /*
    |--------------------------------------------------------------------------
    | Format Readable Label
    |--------------------------------------------------------------------------
    */

    private function formatLabel(
        ?string $value
    ): string {
        if (blank($value)) {
            return 'Not Available';
        }

        return ucwords(
            str_replace(
                '_',
                ' ',
                $value
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Convert to Float
    |--------------------------------------------------------------------------
    */

    private function toFloat(
        mixed $value
    ): float {
        return is_numeric($value)
            ? (float) $value
            : 0.0;
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
                $this->toFloat(
                    $amount
                ),
                2
            );
    }
}