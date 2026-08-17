<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class WarehouseStockResource extends JsonResource
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
        | Quantity
        |--------------------------------------------------------------------------
        |
        | Inventory quantities use 4-decimal precision.
        |
        */

        $quantity = round(
            (float) $this->quantity,
            4
        );


        $minimumQuantity =
            $rawMaterialLoaded
            &&
            $rawMaterial !== null
                ? round(
                    (float) $rawMaterial
                        ->warehouse_minimum_quantity,
                    4
                )
                : null;


        /*
        |--------------------------------------------------------------------------
        | Average Cost
        |--------------------------------------------------------------------------
        */

        $averageUnitCost = round(
            (float) $this->average_unit_cost,
            4
        );


        /*
        |--------------------------------------------------------------------------
        | Stock Value
        |--------------------------------------------------------------------------
        |
        | Keep 4-decimal internal precision.
        |
        */

        $stockValue = round(
            (float) $this->stockValue(),
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


                            'warehouse_minimum_quantity' =>
                                round(
                                    (float) $rawMaterial
                                        ->warehouse_minimum_quantity,
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
            | Raw Material Display Fields
            |--------------------------------------------------------------------------
            |
            | If rawMaterial was not loaded, return null instead of pretending
            | that the material is unknown.
            |
            */

            'material_name' =>
                $rawMaterialLoaded
                    ? $rawMaterial?->material_name
                    : null,


            'category' =>
                $rawMaterialLoaded
                    ? $rawMaterial?->category
                    : null,


            'unit' =>
                $rawMaterialLoaded
                    ? $rawMaterial?->base_unit
                    : null,


            'unit_label' =>
                $rawMaterialLoaded
                    ? $rawMaterial?->base_unit_label
                    : null,


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


            'minimum_quantity' =>
                $minimumQuantity,


            'minimum_quantity_formatted' =>
                $minimumQuantity !== null
                    ? $this->formatQuantity(
                        quantity: $minimumQuantity,
                        unit: $rawMaterial?->base_unit
                    )
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Automatic Status
            |--------------------------------------------------------------------------
            |
            | Status itself remains model-authoritative.
            |
            */

            'status' =>
                $this->status,


            'status_label' =>
                $this->status_label,


            'status_color' =>
                $this->status_color,


            'is_available' =>
                $this->isAvailable(),


            'is_limited' =>
                $this->isLimited(),


            'is_out_of_stock' =>
                $this->isOutOfStock(),


            /*
            |--------------------------------------------------------------------------
            | Cost
            |--------------------------------------------------------------------------
            |
            | Inventory average cost uses 4-decimal precision.
            |
            */

            'average_unit_cost' =>
                $averageUnitCost,


            'average_unit_cost_formatted' =>
                $this->formatCost(
                    $averageUnitCost
                ),


            'stock_value' =>
                $stockValue,


            'stock_value_formatted' =>
                $this->money(
                    $stockValue
                ),


            /*
            |--------------------------------------------------------------------------
            | Receive Information
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
            | Delete Hint
            |--------------------------------------------------------------------------
            |
            | This only describes whether this stock snapshot currently has
            | quantity. It is NOT authorization to delete the RawMaterial.
            |
            */

            'can_delete' =>
                $quantity <= 0,


            /*
            |--------------------------------------------------------------------------
            | Audit Users
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
    | Average unit cost keeps up to 4 decimals.
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