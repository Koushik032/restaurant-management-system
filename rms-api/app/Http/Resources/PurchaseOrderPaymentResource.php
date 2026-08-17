<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class PurchaseOrderPaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Amount
        |--------------------------------------------------------------------------
        */

        $amount = round(
            (float) $this->amount,
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Payment Method
        |--------------------------------------------------------------------------
        */

        $paymentMethod =
            $this->normalizePaymentMethod(
                $this->payment_method
            );


        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,


            'purchase_order_id' =>
                (int) $this->purchase_order_id,


            /*
            |--------------------------------------------------------------------------
            | Payment Date
            |--------------------------------------------------------------------------
            */

            'payment_date' =>
                $this->payment_date
                    ?->format(
                        'Y-m-d'
                    ),


            'payment_date_label' =>
                $this->payment_date
                    ?->format(
                        'd M Y'
                    ),


            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */

            'amount' =>
                $amount,


            'amount_formatted' =>
                $this->money(
                    $amount
                ),


            /*
            |--------------------------------------------------------------------------
            | Payment Method
            |--------------------------------------------------------------------------
            */

            'payment_method' =>
                $paymentMethod,


            'payment_method_label' =>
                $this->paymentMethodLabel(
                    $paymentMethod
                ),


            /*
            |--------------------------------------------------------------------------
            | Transaction Information
            |--------------------------------------------------------------------------
            */

            'transaction_reference' =>
                $this->transaction_reference,


            'notes' =>
                $this->notes,


            /*
            |--------------------------------------------------------------------------
            | Audit Users
            |--------------------------------------------------------------------------
            |
            | Relations are returned only when already loaded.
            | Resource must never force lazy loading.
            |
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


            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

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

    private function paymentMethodLabel(
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