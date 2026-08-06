<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerDetailsResource extends JsonResource
{
    /*
    |--------------------------------------------------------------------------
    | Transform Resource
    |--------------------------------------------------------------------------
    |
    | Expected structure:
    |
    | [
    |     'customer' => Customer,
    |     'summary' => [...],
    |     'orders' => LengthAwarePaginator,
    | ]
    |
    */

    public function toArray(
        Request $request
    ): array {
        $customer =
            $this->resource[
                'customer'
            ];

        $summary =
            $this->resource[
                'summary'
            ] ?? [];

        $orders =
            $this->resource[
                'orders'
            ] ?? null;

        return [
            /*
            |--------------------------------------------------------------------------
            | Customer Profile
            |--------------------------------------------------------------------------
            */

            'customer' =>
                new CustomerResource(
                    $customer
                ),

            /*
            |--------------------------------------------------------------------------
            | Live Customer Summary
            |--------------------------------------------------------------------------
            */

            'summary' =>
                $this->transformSummary(
                    $summary
                ),

            /*
            |--------------------------------------------------------------------------
            | Visit / Order History
            |--------------------------------------------------------------------------
            */

            'orders' =>
                $this->transformOrders(
                    $orders
                ),

            /*
            |--------------------------------------------------------------------------
            | Pagination Metadata
            |--------------------------------------------------------------------------
            */

            'meta' =>
                $this->transformPaginationMeta(
                    $orders
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Transform Summary
    |--------------------------------------------------------------------------
    */

    private function transformSummary(
        array $summary
    ): array {
        $totalOrderAmount =
            $this->toFloat(
                $summary[
                    'total_order_amount'
                ] ?? 0
            );

        $totalPaidAmount =
            $this->toFloat(
                $summary[
                    'total_paid_amount'
                ] ?? 0
            );

        $totalDueAmount =
            $this->toFloat(
                $summary[
                    'total_due_amount'
                ] ?? 0
            );

        return [
            'visit_count' =>
                max(
                    0,
                    (int) (
                        $summary[
                            'visit_count'
                        ] ?? 0
                    )
                ),

            'total_order_amount' =>
                $totalOrderAmount,

            'total_order_amount_formatted' =>
                $summary[
                    'total_order_amount_formatted'
                ]
                ?? $this->formatMoney(
                    $totalOrderAmount
                ),

            'total_paid_amount' =>
                $totalPaidAmount,

            'total_paid_amount_formatted' =>
                $summary[
                    'total_paid_amount_formatted'
                ]
                ?? $this->formatMoney(
                    $totalPaidAmount
                ),

            'total_due_amount' =>
                $totalDueAmount,

            'total_due_amount_formatted' =>
                $summary[
                    'total_due_amount_formatted'
                ]
                ?? $this->formatMoney(
                    $totalDueAmount
                ),

            'first_visit_at' =>
                $this->formatIsoDate(
                    $summary[
                        'first_visit_at'
                    ] ?? null
                ),

            'first_visit_label' =>
                $summary[
                    'first_visit_label'
                ]
                ?? $this->formatDateLabel(
                    $summary[
                        'first_visit_at'
                    ] ?? null,
                    'Never'
                ),

            'last_visit_at' =>
                $this->formatIsoDate(
                    $summary[
                        'last_visit_at'
                    ] ?? null
                ),

            'last_visit_label' =>
                $summary[
                    'last_visit_label'
                ]
                ?? $this->formatDateLabel(
                    $summary[
                        'last_visit_at'
                    ] ?? null,
                    'Never'
                ),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Transform Orders
    |--------------------------------------------------------------------------
    */

    private function transformOrders(
        mixed $orders
    ): array {
        if (
            ! $orders instanceof
            LengthAwarePaginator
        ) {
            return [];
        }

        return collect(
            $orders->items()
        )
            ->map(
                fn (
                    Order $order
                ): array =>
                    $this->transformOrder(
                        $order
                    )
            )
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Transform One Order
    |--------------------------------------------------------------------------
    */

    private function transformOrder(
        Order $order
    ): array {
        return [
            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $order->id,

            'order_number' =>
                $order->order_number,

            /*
            |--------------------------------------------------------------------------
            | Visit Date and Time
            |--------------------------------------------------------------------------
            */

            'created_at' =>
                $order->created_at
                    ?->toISOString(),

            'visit_date' =>
                $order->created_at
                    ?->format(
                        'd M Y'
                    ),

            'visit_time' =>
                $order->created_at
                    ?->format(
                        'h:i A'
                    ),

            'visit_day' =>
                $order->created_at
                    ?->format(
                        'l'
                    ),

            'visit_label' =>
                $order->created_at
                    ?->format(
                        'd M Y, h:i A'
                    )
                ?? 'Not Available',

            /*
            |--------------------------------------------------------------------------
            | Financial Information
            |--------------------------------------------------------------------------
            */

            'subtotal' =>
                $this->toFloat(
                    $order->subtotal
                ),

            'subtotal_formatted' =>
                $this->formatMoney(
                    $order->subtotal
                ),

            'discount_amount' =>
                $this->toFloat(
                    $order->discount_amount
                ),

            'discount_amount_formatted' =>
                $this->formatMoney(
                    $order->discount_amount
                ),

            'tax_amount' =>
                $this->toFloat(
                    $order->tax_amount
                ),

            'tax_amount_formatted' =>
                $this->formatMoney(
                    $order->tax_amount
                ),

            'service_charge' =>
                $this->toFloat(
                    $order->service_charge
                ),

            'service_charge_formatted' =>
                $this->formatMoney(
                    $order->service_charge
                ),

            'total_amount' =>
                $this->toFloat(
                    $order->total_amount
                ),

            'total_amount_formatted' =>
                $this->formatMoney(
                    $order->total_amount
                ),

            'paid_amount' =>
                $this->toFloat(
                    $order->paid_amount
                ),

            'paid_amount_formatted' =>
                $this->formatMoney(
                    $order->paid_amount
                ),

            'due_amount' =>
                $this->toFloat(
                    $order->due_amount
                ),

            'due_amount_formatted' =>
                $this->formatMoney(
                    $order->due_amount
                ),

            /*
            |--------------------------------------------------------------------------
            | Ordered Items
            |--------------------------------------------------------------------------
            */

            'items' =>
                $this->transformOrderItems(
                    $order
                ),

            'items_count' =>
                $order->relationLoaded(
                    'items'
                )
                    ? (int) $order
                        ->items
                        ->sum(
                            'quantity'
                        )
                    : 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Transform Order Items
    |--------------------------------------------------------------------------
    */

    private function transformOrderItems(
        Order $order
    ): array {
        if (
            ! $order->relationLoaded(
                'items'
            )
        ) {
            return [];
        }

        return $order
            ->items
            ->map(
                function (
                    $item
                ): array {
                    return [
                        'id' =>
                            (int) $item->id,

                        'item_name' =>
                            $item->item_name
                            ?: 'Unnamed Item',

                        'variant_name' =>
                            $item->variant_name,

                        'quantity' =>
                            max(
                                1,
                                (int) $item->quantity
                            ),

                        'unit_price' =>
                            $this->toFloat(
                                $item->unit_price
                            ),

                        'unit_price_formatted' =>
                            $this->formatMoney(
                                $item->unit_price
                            ),

                        'addon_total' =>
                            $this->toFloat(
                                $item->addon_total
                            ),

                        'addon_total_formatted' =>
                            $this->formatMoney(
                                $item->addon_total
                            ),

                        'line_total' =>
                            $this->toFloat(
                                $item->line_total
                            ),

                        'line_total_formatted' =>
                            $this->formatMoney(
                                $item->line_total
                            ),

                        'addons' =>
                            $this->transformItemAddons(
                                $item
                            ),
                    ];
                }
            )
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Transform Item Add-ons
    |--------------------------------------------------------------------------
    */

    private function transformItemAddons(
        mixed $item
    ): array {
        if (
            ! $item->relationLoaded(
                'addons'
            )
        ) {
            return [];
        }

        return $item
            ->addons
            ->map(
                function (
                    $addon
                ): array {
                    return [
                        'id' =>
                            (int) $addon->id,

                        'addon_name' =>
                            $addon->addon_name
                            ?: 'Unnamed Add-on',

                        'quantity' =>
                            max(
                                1,
                                (int) $addon->quantity
                            ),

                        'unit_price' =>
                            $this->toFloat(
                                $addon->unit_price
                            ),

                        'unit_price_formatted' =>
                            $this->formatMoney(
                                $addon->unit_price
                            ),

                        'total_price' =>
                            $this->toFloat(
                                $addon->total_price
                            ),

                        'total_price_formatted' =>
                            $this->formatMoney(
                                $addon->total_price
                            ),
                    ];
                }
            )
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination Metadata
    |--------------------------------------------------------------------------
    */

    private function transformPaginationMeta(
        mixed $orders
    ): array {
        if (
            ! $orders instanceof
            LengthAwarePaginator
        ) {
            return [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 10,
                'total' => 0,
                'from' => null,
                'to' => null,
            ];
        }

        return [
            'current_page' =>
                (int) $orders
                    ->currentPage(),

            'last_page' =>
                (int) $orders
                    ->lastPage(),

            'per_page' =>
                (int) $orders
                    ->perPage(),

            'total' =>
                (int) $orders
                    ->total(),

            'from' =>
                $orders
                    ->firstItem(),

            'to' =>
                $orders
                    ->lastItem(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Convert to Float
    |--------------------------------------------------------------------------
    */

    private function toFloat(
        mixed $value
    ): float {
        return is_numeric(
            $value
        )
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

    /*
    |--------------------------------------------------------------------------
    | Format ISO Date
    |--------------------------------------------------------------------------
    */

    private function formatIsoDate(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (
            method_exists(
                $value,
                'toISOString'
            )
        ) {
            return $value
                ->toISOString();
        }

        try {
            return \Illuminate\Support\Carbon::parse(
                $value
            )->toISOString();
        } catch (\Throwable) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Format Date Label
    |--------------------------------------------------------------------------
    */

    private function formatDateLabel(
        mixed $value,
        string $fallback
    ): string {
        if ($value === null) {
            return $fallback;
        }

        if (
            method_exists(
                $value,
                'format'
            )
        ) {
            return $value->format(
                'd M Y, h:i A'
            );
        }

        try {
            return \Illuminate\Support\Carbon::parse(
                $value
            )->format(
                'd M Y, h:i A'
            );
        } catch (\Throwable) {
            return $fallback;
        }
    }
}