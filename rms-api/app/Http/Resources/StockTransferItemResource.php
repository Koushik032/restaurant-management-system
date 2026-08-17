<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class StockTransferItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Raw Material
        |--------------------------------------------------------------------------
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


        $unitCost = round(
            (float) $this->unit_cost,
            4
        );


        $warehouseQuantityBefore = round(
            (float) $this->warehouse_quantity_before,
            4
        );


        $warehouseQuantityAfter = round(
            (float) $this->warehouse_quantity_after,
            4
        );


        $restaurantQuantityBefore = round(
            (float) $this->restaurant_quantity_before,
            4
        );


        $restaurantQuantityAfter = round(
            (float) $this->restaurant_quantity_after,
            4
        );


        $transferValue = round(
            $quantity * $unitCost,
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


            'stock_transfer_id' =>
                (int) $this->stock_transfer_id,


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


                            'is_active' =>
                                (bool) $rawMaterial->is_active,

                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Historical Snapshot
            |--------------------------------------------------------------------------
            |
            | item_name and unit belong to the transfer snapshot.
            |
            */

            'item_name' =>
                $this->item_name,


            'unit' =>
                $this->unit,


            /*
            |--------------------------------------------------------------------------
            | Transfer Quantity
            |--------------------------------------------------------------------------
            */

            'quantity' =>
                $quantity,


            'quantity_formatted' =>
                $this->formatQuantity(
                    quantity: $quantity,
                    unit: $this->unit
                ),


            /*
            |--------------------------------------------------------------------------
            | Unit Cost
            |--------------------------------------------------------------------------
            */

            'unit_cost' =>
                $unitCost,


            'unit_cost_formatted' =>
                $this->formatCost(
                    $unitCost
                ),


            /*
            |--------------------------------------------------------------------------
            | Transfer Value
            |--------------------------------------------------------------------------
            */

            'transfer_value' =>
                $transferValue,


            'transfer_value_formatted' =>
                $this->money(
                    $transferValue
                ),


            /*
            |--------------------------------------------------------------------------
            | Warehouse Snapshot
            |--------------------------------------------------------------------------
            */

            'warehouse_quantity_before' =>
                $warehouseQuantityBefore,


            'warehouse_quantity_before_formatted' =>
                $this->formatQuantity(
                    quantity: $warehouseQuantityBefore,
                    unit: $this->unit
                ),


            'warehouse_quantity_after' =>
                $warehouseQuantityAfter,


            'warehouse_quantity_after_formatted' =>
                $this->formatQuantity(
                    quantity: $warehouseQuantityAfter,
                    unit: $this->unit
                ),


            /*
            |--------------------------------------------------------------------------
            | Restaurant Snapshot
            |--------------------------------------------------------------------------
            */

            'restaurant_quantity_before' =>
                $restaurantQuantityBefore,


            'restaurant_quantity_before_formatted' =>
                $this->formatQuantity(
                    quantity: $restaurantQuantityBefore,
                    unit: $this->unit
                ),


            'restaurant_quantity_after' =>
                $restaurantQuantityAfter,


            'restaurant_quantity_after_formatted' =>
                $this->formatQuantity(
                    quantity: $restaurantQuantityAfter,
                    unit: $this->unit
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
            | Date
            |--------------------------------------------------------------------------
            */

            'created_at' =>
                $this->created_at
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
    | Inventory Cost Formatter
    |--------------------------------------------------------------------------
    |
    | Unit cost keeps up to 4-decimal precision.
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
    | Standard Money Formatter
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