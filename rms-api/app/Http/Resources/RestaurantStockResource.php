<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class RestaurantStockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Raw Material Relation
        |--------------------------------------------------------------------------
        |
        | Never force lazy loading from a Resource.
        |
        */

        $rawMaterialLoaded =
            $this->relationLoaded(
                'rawMaterial'
            );


        $rawMaterial =
            $rawMaterialLoaded
                ? $this->rawMaterial
                : null;


        /*
        |--------------------------------------------------------------------------
        | Quantity / Cost
        |--------------------------------------------------------------------------
        */

        $quantity = round(
            (float) $this->quantity,
            4
        );


        $averageUnitCost = round(
            (float) $this->average_unit_cost,
            4
        );


        $stockValue = round(
            $quantity
            *
            $averageUnitCost,
            4
        );


        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,


            'raw_material_id' =>
                $this->raw_material_id !== null
                    ? (int) $this->raw_material_id
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Raw Material
            |--------------------------------------------------------------------------
            */

            'raw_material' =>
                $this->whenLoaded(

                    'rawMaterial',

                    function () use (
                        $rawMaterial
                    ): ?array {

                        if (!$rawMaterial) {
                            return null;
                        }


                        return [

                            'id' =>
                                (int) $rawMaterial->id,


                            'material_name' =>
                                $rawMaterial->material_name,


                            'category' =>
                                $rawMaterial->category,


                            'base_unit' =>
                                $rawMaterial->base_unit,


                            'base_unit_label' =>
                                $rawMaterial->base_unit_label,


                            'restaurant_minimum_quantity' =>
                                round(
                                    (float) $rawMaterial
                                        ->restaurant_minimum_quantity,
                                    4
                                ),


                            'is_active' =>
                                (bool) $rawMaterial->is_active,

                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'quantity' =>
                $quantity,


            'quantity_formatted' =>
                $this->formatQuantity(
                    quantity: $quantity,
                    unit: $rawMaterial?->base_unit
                ),


            /*
            |--------------------------------------------------------------------------
            | Average Unit Cost
            |--------------------------------------------------------------------------
            |
            | Inventory costing uses 4-decimal precision.
            |
            */

            'average_unit_cost' =>
                $averageUnitCost,


            'average_unit_cost_formatted' =>
                $this->formatCost(
                    $averageUnitCost
                ),


            /*
            |--------------------------------------------------------------------------
            | Stock Value
            |--------------------------------------------------------------------------
            */

            'stock_value' =>
                $stockValue,


            'stock_value_formatted' =>
                $this->money(
                    $stockValue
                ),


            /*
            |--------------------------------------------------------------------------
            | Stock Status
            |--------------------------------------------------------------------------
            */

            'status' =>
                $this->status,


            'status_label' =>
                $this->status_label,


            'status_color' =>
                $this->status_color,


            /*
            |--------------------------------------------------------------------------
            | Last Received
            |--------------------------------------------------------------------------
            */

            'last_received_at' =>
                $this->last_received_at
                    ?->toISOString(),


            'last_received_at_label' =>
                $this->last_received_at
                    ?->format(
                        'd M Y, h:i A'
                    ),


            /*
            |--------------------------------------------------------------------------
            | Dates
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
    | Quantity Formatter
    |--------------------------------------------------------------------------
    */

    private function formatQuantity(
        mixed $quantity,
        ?string $unit
    ): string {

        $formatted =
            $this->trimDecimal(
                value: $quantity,
                decimals: 4
            );


        return $unit
            ? "{$formatted} {$unit}"
            : $formatted;
    }


    /*
    |--------------------------------------------------------------------------
    | Cost Formatter
    |--------------------------------------------------------------------------
    |
    | Keep up to 4 decimal places for inventory unit cost.
    |
    */

    private function formatCost(
        mixed $amount
    ): string {

        return '৳ '
            .
            $this->trimDecimal(
                value: $amount,
                decimals: 4,
                minimumDecimals: 2
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Money Formatter
    |--------------------------------------------------------------------------
    |
    | Final stock value is displayed as standard 2-decimal money.
    |
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


    /*
    |--------------------------------------------------------------------------
    | Trim Decimal
    |--------------------------------------------------------------------------
    */

    private function trimDecimal(
        mixed $value,
        int $decimals,
        int $minimumDecimals = 0
    ): string {

        $formatted =
            number_format(
                (float) $value,
                $decimals,
                '.',
                ''
            );


        $parts =
            explode(
                '.',
                $formatted,
                2
            );


        if (count($parts) === 1) {
            return $parts[0];
        }


        $integerPart =
            $parts[0];


        $decimalPart =
            rtrim(
                $parts[1],
                '0'
            );


        if (
            strlen($decimalPart)
            <
            $minimumDecimals
        ) {
            $decimalPart =
                str_pad(
                    $decimalPart,
                    $minimumDecimals,
                    '0'
                );
        }


        return $decimalPart !== ''
            ? "{$integerPart}.{$decimalPart}"
            : $integerPart;
    }
}