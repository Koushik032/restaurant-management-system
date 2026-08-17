<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class StockMovementResource extends JsonResource
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
        | Direction
        |--------------------------------------------------------------------------
        */

        $direction =
            strtolower(
                trim(
                    (string) $this->direction
                )
            );


        $direction =
            $direction !== ''
                ? $direction
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


        $quantityBefore = round(
            (float) $this->quantity_before,
            4
        );


        $quantityAfter = round(
            (float) $this->quantity_after,
            4
        );


        $signedQuantity =
            round(
                $direction === 'out'
                    ? -$quantity
                    : $quantity,
                4
            );


        /*
        |--------------------------------------------------------------------------
        | Cost
        |--------------------------------------------------------------------------
        |
        | Inventory unit cost uses 4-decimal precision.
        |
        */

        $unitCost =
            $this->unit_cost !== null
                ? round(
                    (float) $this->unit_cost,
                    4
                )
                : null;


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

                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Material Display Name
            |--------------------------------------------------------------------------
            |
            | If relation was not loaded we do not pretend the material is
            | unknown. The caller simply did not request that relationship.
            |
            */

            'material_name' =>
                $rawMaterialLoaded
                    ? $rawMaterial?->material_name
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            */

            'location' =>
                $this->location,


            'location_label' =>
                $this->location_label,


            /*
            |--------------------------------------------------------------------------
            | Movement
            |--------------------------------------------------------------------------
            */

            'movement_type' =>
                $this->movement_type,


            'movement_type_label' =>
                $this->movement_type_label,


            'direction' =>
                $direction,


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
                    unit: $this->unit
                ),


            'signed_quantity' =>
                $signedQuantity,


            'signed_quantity_formatted' =>
                $this->formatSignedQuantity(
                    quantity: $signedQuantity,
                    unit: $this->unit
                ),


            'quantity_before' =>
                $quantityBefore,


            'quantity_before_formatted' =>
                $this->formatQuantity(
                    quantity: $quantityBefore,
                    unit: $this->unit
                ),


            'quantity_after' =>
                $quantityAfter,


            'quantity_after_formatted' =>
                $this->formatQuantity(
                    quantity: $quantityAfter,
                    unit: $this->unit
                ),


            /*
            |--------------------------------------------------------------------------
            | Historical Unit Snapshot
            |--------------------------------------------------------------------------
            */

            'unit' =>
                $this->unit,


            /*
            |--------------------------------------------------------------------------
            | Cost
            |--------------------------------------------------------------------------
            */

            'unit_cost' =>
                $unitCost,


            'unit_cost_formatted' =>
                $unitCost !== null
                    ? $this->formatCost(
                        $unitCost
                    )
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Reference
            |--------------------------------------------------------------------------
            |
            | reference_type/reference_id identify the immutable business event
            | responsible for this stock movement.
            |
            */

            'reference_type' =>
                $this->reference_type,


            'reference_id' =>
                $this->reference_id !== null
                    ? (int) $this->reference_id
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' =>
                $this->notes,


            /*
            |--------------------------------------------------------------------------
            | Created User
            |--------------------------------------------------------------------------
            */

            'created_by' =>
                $this->whenLoaded(

                    'creator',

                    function (): ?array {

                        if (!$this->creator) {
                            return null;
                        }


                        return [

                            'id' =>
                                (int) $this->creator->id,


                            'name' =>
                                $this->creator->name,


                            'email' =>
                                $this->creator->email,

                        ];
                    },

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


            'created_at_label' =>
                $this->created_at
                    ?->format(
                        'd M Y, h:i A'
                    ),


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
    | Signed Quantity Formatter
    |--------------------------------------------------------------------------
    */

    private function formatSignedQuantity(
        mixed $quantity,
        ?string $unit
    ): string {

        $numericQuantity =
            round(
                (float) $quantity,
                4
            );


        $formatted =
            $this->trimDecimal(
                value: abs(
                    $numericQuantity
                ),
                decimals: 4
            );


        $prefix =
            $numericQuantity > 0
                ? '+'
                : (
                    $numericQuantity < 0
                        ? '-'
                        : ''
                );


        $value =
            $prefix
            .
            $formatted;


        return $unit
            ? "{$value} {$unit}"
            : $value;
    }


    /*
    |--------------------------------------------------------------------------
    | Cost Formatter
    |--------------------------------------------------------------------------
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