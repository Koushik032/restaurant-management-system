<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class PurchaseOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Loaded Relations
        |--------------------------------------------------------------------------
        |
        | Resources must not force lazy loading.
        |
        */

        $supplierLoaded =
            $this->relationLoaded(
                'supplier'
            );

        $supplier =
            $supplierLoaded
                ? $this->supplier
                : null;


        $itemsLoaded =
            $this->relationLoaded(
                'items'
            );

        $items =
            $itemsLoaded
                ? $this->items
                : collect();


        $paymentsLoaded =
            $this->relationLoaded(
                'payments'
            );

        $payments =
            $paymentsLoaded
                ? $this->payments
                : collect();


        $receiptsLoaded =
            $this->relationLoaded(
                'receipts'
            );

        $receipts =
            $receiptsLoaded
                ? $this->receipts
                : collect();


        /*
        |--------------------------------------------------------------------------
        | Money Summary
        |--------------------------------------------------------------------------
        |
        | Purchase order money uses 2-decimal precision.
        |
        */

        $subtotal = round(
            (float) $this->subtotal,
            2
        );

        $tax = round(
            (float) $this->tax,
            2
        );

        $serviceCharge = round(
            (float) $this->service_charge,
            2
        );

        $totalAmount = round(
            (float) $this->total_amount,
            2
        );

        $paidAmount = round(
            (float) $this->paid_amount,
            2
        );

        $dueAmount = round(
            max(
                0,
                (float) $this->due_amount
            ),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Quantity Summary
        |--------------------------------------------------------------------------
        |
        | Inventory quantities use 4-decimal precision.
        |
        */

        $totalQuantity =
            $itemsLoaded
                ? round(
                    (float) $items->sum(
                        'quantity'
                    ),
                    4
                )
                : 0.0;

        $totalReceivedQuantity =
            $itemsLoaded
                ? round(
                    (float) $items->sum(
                        'received_quantity'
                    ),
                    4
                )
                : 0.0;

        $totalRemainingQuantity =
            $itemsLoaded
                ? round(
                    max(
                        0,
                        $totalQuantity
                        -
                        $totalReceivedQuantity
                    ),
                    4
                )
                : 0.0;


        /*
        |--------------------------------------------------------------------------
        | Quantity By Unit
        |--------------------------------------------------------------------------
        |
        | A purchase order can contain different units, therefore a single
        | aggregate such as "10 kg + 5 pcs = 15" is not meaningful by itself.
        |
        | Existing total_quantity fields are preserved for compatibility, while
        | quantity_by_unit provides the correct unit-aware summary.
        |
        */

        $quantityByUnit =
            $itemsLoaded
                ? $items
                    ->groupBy(
                        static function ($item): string {
                            $unit = strtolower(
                                trim(
                                    (string) (
                                        $item->unit
                                        ?? ''
                                    )
                                )
                            );

                            return $unit !== ''
                                ? $unit
                                : 'unknown';
                        }
                    )
                    ->map(
                        static function (
                            $group,
                            string $unit
                        ): array {

                            $ordered = round(
                                (float) $group->sum(
                                    'quantity'
                                ),
                                4
                            );

                            $received = round(
                                (float) $group->sum(
                                    'received_quantity'
                                ),
                                4
                            );

                            $remaining = round(
                                max(
                                    0,
                                    $ordered - $received
                                ),
                                4
                            );

                            return [
                                'unit' =>
                                    $unit,

                                'ordered_quantity' =>
                                    $ordered,

                                'received_quantity' =>
                                    $received,

                                'remaining_quantity' =>
                                    $remaining,
                            ];
                        }
                    )
                    ->values()
                    ->all()
                : [];


        /*
        |--------------------------------------------------------------------------
        | Payment Summary
        |--------------------------------------------------------------------------
        */

        $paymentMethod =
            $this->normalizePaymentMethod(
                $this->payment_method
            );

        $hasPayment =
            $paidAmount > 0;

        $isFullyPaid =
            $totalAmount > 0
            &&
            $dueAmount <= 0;


        /*
        |--------------------------------------------------------------------------
        | Receive Summary
        |--------------------------------------------------------------------------
        */

        $status =
            strtolower(
                trim(
                    (string) $this->status
                )
            );

        $isPartiallyReceived =
            $status === 'partially_received';

        $isFullyReceived =
            $status === 'received';

        $canReceive =
            in_array(
                $status,
                [
                    'ordered',
                    'partially_received',
                ],
                true
            )
            &&
            $itemsLoaded
            &&
            $items->contains(
                static function ($item): bool {
                    $ordered = round(
                        (float) $item->quantity,
                        4
                    );

                    $received = round(
                        (float) $item->received_quantity,
                        4
                    );

                    return
                        $item->raw_material_id !== null
                        &&
                        $ordered > 0
                        &&
                        $received < $ordered;
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Latest Receipt
        |--------------------------------------------------------------------------
        |
        | Controller/service normally loads receipts newest-first. This local
        | selection is kept deterministic even if another caller loads them in
        | a different order.
        |
        */

        $latestReceipt = null;

        if (
            $receiptsLoaded
            &&
            $receipts->isNotEmpty()
        ) {
            $latestReceipt =
                $receipts
                    ->sort(
                        static function (
                            $a,
                            $b
                        ): int {

                            $aTimestamp =
                                $a->received_at
                                    ?->timestamp
                                ?? 0;

                            $bTimestamp =
                                $b->received_at
                                    ?->timestamp
                                ?? 0;

                            $dateComparison =
                                $bTimestamp
                                <=>
                                $aTimestamp;

                            if ($dateComparison !== 0) {
                                return $dateComparison;
                            }

                            return
                                (int) $b->id
                                <=>
                                (int) $a->id;
                        }
                    )
                    ->first();
        }


        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,

            'supplier_id' =>
                $this->supplier_id !== null
                    ? (int) $this->supplier_id
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Supplier
            |--------------------------------------------------------------------------
            */

            'supplier' =>
                $this->when(
                    $supplierLoaded,
                    function () use (
                        $supplier
                    ): ?array {

                        if (!$supplier) {
                            return null;
                        }

                        return [
                            'id' =>
                                (int) $supplier->id,

                            'name' =>
                                $supplier->supplier_name,

                            'supplier_name' =>
                                $supplier->supplier_name,

                            'phone' =>
                                $supplier->phone,

                            'email' =>
                                $supplier->email,

                            'address' =>
                                $supplier->address,
                        ];
                    },
                    null
                ),

            'supplier_name' =>
                $supplierLoaded
                    ? (
                        $supplier?->supplier_name
                        ?? 'Unknown Supplier'
                    )
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Order Information
            |--------------------------------------------------------------------------
            */

            'order_date' =>
                $this->order_date
                    ?->toISOString(),

            'order_date_value' =>
                $this->order_date
                    ?->format(
                        'Y-m-d'
                    ),

            'order_date_label' =>
                $this->order_date
                    ?->format(
                        'd M Y, h:i A'
                    ),

            'delivery_date' =>
                $this->delivery_date
                    ?->format(
                        'Y-m-d'
                    ),

            'delivery_date_label' =>
                $this->delivery_date
                    ?->format(
                        'd M Y'
                    ),


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' =>
                $status,

            'status_label' =>
                $this->getStatusLabel(
                    $status
                ),


            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' =>
                $this->when(
                    $itemsLoaded,
                    fn () =>
                        PurchaseOrderItemResource::collection(
                            $items
                        ),
                    []
                ),

            'purchase_items' =>
                $itemsLoaded
                    ? $items
                        ->pluck(
                            'item_name'
                        )
                        ->filter(
                            static fn ($value): bool =>
                                is_string($value)
                                &&
                                trim($value) !== ''
                        )
                        ->implode(
                            ', '
                        )
                    : '',

            'total_items' =>
                $itemsLoaded
                    ? $items->count()
                    : 0,


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'total_quantity' =>
                $totalQuantity,

            'total_received_quantity' =>
                $totalReceivedQuantity,

            'total_remaining_quantity' =>
                $totalRemainingQuantity,

            'quantity_by_unit' =>
                $quantityByUnit,


            /*
            |--------------------------------------------------------------------------
            | Receiving Summary
            |--------------------------------------------------------------------------
            */

            'is_partially_received' =>
                $isPartiallyReceived,

            'is_fully_received' =>
                $isFullyReceived,

            'can_receive' =>
                $canReceive,


            /*
            |--------------------------------------------------------------------------
            | Amounts
            |--------------------------------------------------------------------------
            */

            'subtotal' =>
                $subtotal,

            'subtotal_formatted' =>
                $this->money(
                    $subtotal
                ),

            'tax' =>
                $tax,

            'tax_formatted' =>
                $this->money(
                    $tax
                ),

            'service_charge' =>
                $serviceCharge,

            'service_charge_formatted' =>
                $this->money(
                    $serviceCharge
                ),

            'total_amount' =>
                $totalAmount,

            'total_amount_formatted' =>
                $this->money(
                    $totalAmount
                ),

            'paid_amount' =>
                $paidAmount,

            'paid_amount_formatted' =>
                $this->money(
                    $paidAmount
                ),

            'due_amount' =>
                $dueAmount,

            'due_amount_formatted' =>
                $this->money(
                    $dueAmount
                ),


            /*
            |--------------------------------------------------------------------------
            | Payment Summary
            |--------------------------------------------------------------------------
            */

            'payment_method' =>
                $paymentMethod,

            'payment_method_label' =>
                $this->getPaymentMethodLabel(
                    $paymentMethod
                ),

            'has_payment' =>
                $hasPayment,

            'has_due' =>
                $dueAmount > 0,

            'is_fully_paid' =>
                $isFullyPaid,


            /*
            |--------------------------------------------------------------------------
            | Payment History
            |--------------------------------------------------------------------------
            */

            'payments' =>
                $this->when(
                    $paymentsLoaded,
                    fn () =>
                        PurchaseOrderPaymentResource::collection(
                            $payments
                        ),
                    []
                ),

            'total_payments' =>
                $paymentsLoaded
                    ? $payments->count()
                    : 0,


            /*
            |--------------------------------------------------------------------------
            | GRN / Receive History
            |--------------------------------------------------------------------------
            */

            'receipts' =>
                $this->when(
                    $receiptsLoaded,
                    fn () =>
                        PurchaseOrderReceiptResource::collection(
                            $receipts
                        ),
                    []
                ),

            'total_receipts' =>
                $receiptsLoaded
                    ? $receipts->count()
                    : 0,

            'latest_receipt' =>
                $this->when(
                    $receiptsLoaded,
                    fn () =>
                        $latestReceipt
                            ? new PurchaseOrderReceiptResource(
                                $latestReceipt
                            )
                            : null,
                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Ordered User
            |--------------------------------------------------------------------------
            */

            'ordered_by' =>
                $this->whenLoaded(
                    'orderedBy',
                    fn (): ?array =>
                        $this->userSummary(
                            $this->orderedBy
                        ),
                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' =>
                $this->notes,


            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'created_by' =>
                $this->whenLoaded(
                    'creator',
                    fn (): ?array =>
                        $this->userSummary(
                            $this->creator
                        ),
                    null
                ),

            'updated_by' =>
                $this->whenLoaded(
                    'updater',
                    fn (): ?array =>
                        $this->userSummary(
                            $this->updater
                        ),
                    null
                ),

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | User Summary
    |--------------------------------------------------------------------------
    */

    private function userSummary(
        mixed $user
    ): ?array {

        if (!$user) {
            return null;
        }

        return [
            'id' =>
                (int) $user->id,

            'name' =>
                $user->name,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Status Label
    |--------------------------------------------------------------------------
    */

    private function getStatusLabel(
        ?string $status
    ): string {

        return match ($status) {

            'ordered' =>
                'Ordered',

            'partially_received' =>
                'Partially Received',

            'received' =>
                'Received',

            'cancelled' =>
                'Cancelled',

            null, '' =>
                'Unknown',

            default =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $status
                    )
                ),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Payment Method
    |--------------------------------------------------------------------------
    */

    private function normalizePaymentMethod(
        mixed $method
    ): ?string {

        if ($method === null) {
            return null;
        }

        $method =
            strtolower(
                trim(
                    (string) $method
                )
            );

        return $method !== ''
            ? $method
            : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Method Label
    |--------------------------------------------------------------------------
    */

    private function getPaymentMethodLabel(
        ?string $method
    ): string {

        return match ($method) {

            'cash' =>
                'Cash',

            'card' =>
                'Card',

            'bkash' =>
                'bKash',

            'nagad' =>
                'Nagad',

            'bank_transfer' =>
                'Bank Transfer',

            'mixed' =>
                'Mixed Payment',

            'other' =>
                'Other',

            null, '' =>
                'Not Selected',

            default =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $method
                    )
                ),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Money Formatter
    |--------------------------------------------------------------------------
    */

    private function money(
        mixed $amount
    ): string {

        return '৳ '
            .
            number_format(
                (float) $amount,
                2
            );
    }
}