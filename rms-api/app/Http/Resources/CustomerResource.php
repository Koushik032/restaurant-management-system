<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /*
    |--------------------------------------------------------------------------
    | Transform Resource
    |--------------------------------------------------------------------------
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

            'name' =>
                $this->display_name,

            'phone' =>
                $this->phone,

            'email' =>
                $this->email,

            'notes' =>
                $this->notes,

            /*
            |--------------------------------------------------------------------------
            | Display Helpers
            |--------------------------------------------------------------------------
            */

            'initial' =>
                $this->initial,

            'customer_code' =>
                $this->customerCode(),

            /*
            |--------------------------------------------------------------------------
            | Customer Statistics
            |--------------------------------------------------------------------------
            */

            'visit_count' =>
                $this->visitCount(),

            'total_orders' =>
                $this->visitCount(),

            'total_spent' =>
                $this->totalSpentValue(),

            'total_spent_formatted' =>
                $this->formatMoney(
                    $this->totalSpentValue()
                ),

            /*
            |--------------------------------------------------------------------------
            | Last Visit
            |--------------------------------------------------------------------------
            */

            'last_visit_at' =>
                $this->last_visit_at
                    ?->toISOString(),

            'last_visit_date' =>
                $this->last_visit_at
                    ?->format(
                        'd M Y'
                    ),

            'last_visit_time' =>
                $this->last_visit_at
                    ?->format(
                        'h:i A'
                    ),

            'last_visit_label' =>
                $this->last_visit_at
                    ?->format(
                        'd M Y, h:i A'
                    )
                ?? 'Never',

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_active' =>
                (bool) $this->is_active,

            'status' =>
                $this->is_active
                    ? 'active'
                    : 'inactive',

            'status_label' =>
                $this->status_label,

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'created_date' =>
                $this->created_at
                    ?->format(
                        'd M Y'
                    ),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Code
    |--------------------------------------------------------------------------
    |
    | Example:
    |
    | Customer ID 7 => CUST-0007
    |
    */

    private function customerCode(): string
    {
        return 'CUST-'
            . str_pad(
                (string) $this->id,
                4,
                '0',
                STR_PAD_LEFT
            );
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