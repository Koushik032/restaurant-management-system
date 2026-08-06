<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
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

            'id' =>
                (int) $this->id,


            /*
            |--------------------------------------------------------------------------
            | Date Information
            |--------------------------------------------------------------------------
            */

            'expense_date' =>
                $this->expense_date
                    ?->toISOString(),


            'expense_date_label' =>
                $this->expense_date
                    ?->format(
                        'd M Y, h:i A'
                    ),


            'expense_day' =>
                $this->expense_date
                    ?->format(
                        'l'
                    ),



            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category' =>
                $this->whenLoaded(
                    'category',
                    function () {

                        return [

                            'id' =>
                                (int)
                                $this->category->id,


                            'name' =>
                                $this->category->name,

                        ];

                    }
                ),


            'category_name' =>
                $this->category?->name
                ??
                'Uncategorized',



            /*
            |--------------------------------------------------------------------------
            | Amount
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
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment_method' =>
                $this->payment_method,


            'payment_method_label' =>
                $this->paymentMethodLabel(),



            /*
            |--------------------------------------------------------------------------
            | Paid By
            |--------------------------------------------------------------------------
            */

            'paid_by' =>
                $this->whenLoaded(
                    'creator',
                    function () {

                        return [

                            'id' =>
                                (int)
                                $this->creator->id,


                            'name' =>
                                $this->creator->name,


                            'email' =>
                                $this->creator->email,

                        ];

                    }
                ),


            'paid_by_name' =>
                $this->creator?->name
                ??
                'System',



            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' =>
                $this->notes,



            /*
            |--------------------------------------------------------------------------
            | Audit Information
            |--------------------------------------------------------------------------
            */

            'created_at' =>
                $this->created_at
                    ?->toISOString(),


            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
                    
            'updated_by_name' =>
                $this->updater?->name
                ??
                null,

        ];
    }



    /**
     * Money Formatter
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