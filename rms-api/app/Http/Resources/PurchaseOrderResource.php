<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    /**
     * Transform resource into array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' => (int) $this->id,


            /*
            |--------------------------------------------------------------------------
            | Supplier Information
            |--------------------------------------------------------------------------
            */

            'supplier' => $this->whenLoaded(
                'supplier',

                function () {

                    if (! $this->supplier) {
                        return null;
                    }

                    return [

                        'id' => (int) $this->supplier->id,

                        'name' => $this->supplier->supplier_name,

                        'supplier_name' => $this->supplier->supplier_name,

                        'phone' => $this->supplier->phone,

                        'email' => $this->supplier->email,

                        'address' => $this->supplier->address,

                    ];
                },

                null
            ),


            'supplier_name' => $this->supplier?->supplier_name
                ?? 'Unknown Supplier',


            /*
            |--------------------------------------------------------------------------
            | Order Information
            |--------------------------------------------------------------------------
            */

            'order_date' => $this->order_date
                ?->toISOString(),


            'order_date_value' => $this->order_date
                ?->format('Y-m-d'),


            'order_date_label' => $this->order_date
                ?->format('d M Y, h:i A'),


            'delivery_date' => $this->delivery_date
                ?->format('Y-m-d'),


            'delivery_date_label' => $this->delivery_date
                ?->format('d M Y'),


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => $this->status,


            'status_label' => $this->getStatusLabel(),


            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => $this->whenLoaded(
                'items',

                function () {
                    return PurchaseOrderItemResource::collection(
                        $this->items
                    );
                },

                []
            ),


            /*
            |--------------------------------------------------------------------------
            | Item Summary
            |--------------------------------------------------------------------------
            */

            'purchase_items' => $this->whenLoaded(
                'items',

                function () {

                    return $this->items
                        ->pluck('item_name')
                        ->filter()
                        ->implode(', ');
                },

                ''
            ),


            'total_items' => $this->whenLoaded(
                'items',

                function () {
                    return $this->items->count();
                },

                0
            ),


            'total_quantity' => $this->whenLoaded(
                'items',

                function () {
                    return (float) $this->items->sum('quantity');
                },

                0
            ),


            'total_received_quantity' => $this->whenLoaded(
                'items',

                function () {
                    return (float) $this->items->sum(
                        'received_quantity'
                    );
                },

                0
            ),


            /*
            |--------------------------------------------------------------------------
            | Amount Information
            |--------------------------------------------------------------------------
            */

            'subtotal' => (float) $this->subtotal,


            'subtotal_formatted' => $this->money(
                $this->subtotal
            ),


            'tax' => (float) $this->tax,


            'tax_formatted' => $this->money(
                $this->tax
            ),


            'service_charge' => (float) $this->service_charge,


            'service_charge_formatted' => $this->money(
                $this->service_charge
            ),


            'total_amount' => (float) $this->total_amount,


            'total_amount_formatted' => $this->money(
                $this->total_amount
            ),


            'paid_amount' => (float) $this->paid_amount,


            'paid_amount_formatted' => $this->money(
                $this->paid_amount
            ),


            'due_amount' => (float) $this->due_amount,


            'due_amount_formatted' => $this->money(
                $this->due_amount
            ),


            /*
            |--------------------------------------------------------------------------
            | Payment Information
            |--------------------------------------------------------------------------
            */

            'payment_method' => $this->payment_method,


            'payment_method_label' => $this->getPaymentMethodLabel(),


            /*
            |--------------------------------------------------------------------------
            | Ordered User
            |--------------------------------------------------------------------------
            */

            'ordered_by' => $this->whenLoaded(
                'orderedBy',

                function () {

                    if (! $this->orderedBy) {
                        return null;
                    }

                    return [

                        'id' => (int) $this->orderedBy->id,

                        'name' => $this->orderedBy->name,

                    ];
                },

                null
            ),


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => $this->notes,


            /*
            |--------------------------------------------------------------------------
            | Audit Information
            |--------------------------------------------------------------------------
            */

            'created_at' => $this->created_at
                ?->toISOString(),


            'updated_at' => $this->updated_at
                ?->toISOString(),

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Status Label
    |--------------------------------------------------------------------------
    */

    private function getStatusLabel(): string
    {
        return match ($this->status) {

            'ordered' => 'Ordered',

            'partially_received' => 'Partially Received',

            'received' => 'Received',

            'cancelled' => 'Cancelled',

            default => ucwords(
                str_replace(
                    '_',
                    ' ',
                    (string) $this->status
                )
            ),

        };
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Method Label
    |--------------------------------------------------------------------------
    */

    private function getPaymentMethodLabel(): string
    {
        return match ($this->payment_method) {

            'cash' => 'Cash',

            'card' => 'Card',

            'bkash' => 'Bkash',

            'nagad' => 'Nagad',

            'bank_transfer' => 'Bank Transfer',

            'mixed' => 'Mixed Payment',

            'other' => 'Other',

            null, '' => 'Not Selected',

            default => ucwords(
                str_replace(
                    '_',
                    ' ',
                    (string) $this->payment_method
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