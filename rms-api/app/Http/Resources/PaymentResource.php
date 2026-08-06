<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
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

            'id' =>
                (int) $this->id,

            'order_id' =>
                (int) $this->order_id,

            /*
            |--------------------------------------------------------------------------
            | Payment Amount
            |--------------------------------------------------------------------------
            */

            'amount' =>
                (float) $this->amount,

            'amount_formatted' =>
                $this->money(
                    $this->amount
                ),

            /*
            |--------------------------------------------------------------------------
            | Payment Method
            |--------------------------------------------------------------------------
            */

            'payment_method' =>
                $this->payment_method,

            'payment_method_label' =>
                $this->paymentMethodLabel(),

            /*
            |--------------------------------------------------------------------------
            | Reference
            |--------------------------------------------------------------------------
            */

            'reference' =>
                $this->reference,

            /*
            |--------------------------------------------------------------------------
            | Note
            |--------------------------------------------------------------------------
            */

            'note' =>
                $this->note,

            /*
            |--------------------------------------------------------------------------
            | Receiver
            |--------------------------------------------------------------------------
            */

            'received_by' =>
                $this->received_by,

            'received_by_name' =>
                $this->whenLoaded(
                    'receiver',
                    fn () =>
                        $this->receiver?->name
                ),

            /*
            |--------------------------------------------------------------------------
            | Date & Time
            |--------------------------------------------------------------------------
            */

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'date' =>
                $this->created_at
                    ?->format('d M Y'),

            'time' =>
                $this->created_at
                    ?->format('h:i A'),

            'date_time' =>
                $this->created_at
                    ?->format('d M Y h:i A'),

        ];

    }

    /**
     * Format currency.
     */
    private function money(
        mixed $amount
    ): string {

        return '৳ ' .
            number_format(
                (float) $amount,
                2
            );

    }

    /**
     * Human readable payment method.
     */
    private function paymentMethodLabel(): ?string
    {
        if (! $this->payment_method) {
            return null;
        }

        return match ($this->payment_method) {

            'cash' => 'Cash',

            'card' => 'Card',

            'bkash' => 'bKash',

            'nagad' => 'Nagad',

            'bank_transfer' => 'Bank Transfer',

            'mixed' => 'Mixed Payment',

            default => ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $this->payment_method
                )
            ),
        };
    }
}