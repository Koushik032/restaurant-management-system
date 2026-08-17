<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class PurchaseOrderReceiptItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Precision
        |--------------------------------------------------------------------------
        |
        | GRN quantities and inventory costing use 4-decimal precision.
        |
        */

        $quantity = round(
            (float) $this->quantity,
            4
        );


        $unitCost = round(
            (float) $this->unit_cost,
            4
        );


        $totalCost = round(
            (float) $this->total_cost,
            4
        );


        /*
        |--------------------------------------------------------------------------
        | Raw Material
        |--------------------------------------------------------------------------
        */

        $rawMaterial =
            $this->relationLoaded('rawMaterial')
                ? $this->rawMaterial
                : null;


        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,


            'purchase_order_receipt_id' =>
                (int) $this->purchase_order_receipt_id,


            'purchase_order_item_id' =>
                (int) $this->purchase_order_item_id,


            'raw_material_id' =>
                (int) $this->raw_material_id,


            /*
            |--------------------------------------------------------------------------
            | Raw Material
            |--------------------------------------------------------------------------
            |
            | Never force lazy loading from a Resource.
            |
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


                            'base_unit' =>
                                $rawMaterial->base_unit,


                            'base_unit_label' =>
                                $rawMaterial->base_unit_label,

                        ];
                    }
                ),


            /*
            |--------------------------------------------------------------------------
            | Historical Snapshot
            |--------------------------------------------------------------------------
            |
            | item_name and unit belong to the GRN snapshot.
            | They must not depend on the current RawMaterial values.
            |
            */

            'item_name' =>
                $this->item_name,


            'unit' =>
                $this->unit,


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


            /*
            |--------------------------------------------------------------------------
            | Cost
            |--------------------------------------------------------------------------
            */

            'unit_cost' =>
                $unitCost,


            'unit_cost_formatted' =>
                $this->formatCost(
                    $unitCost
                ),


            'total_cost' =>
                $totalCost,


            'total_cost_formatted' =>
                $this->formatCost(
                    $totalCost
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
            | Timestamp
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
    |
    | Examples:
    |
    | 10.0000  => 10
    | 10.5000  => 10.5
    | 10.1250  => 10.125
    | 10.1234  => 10.1234
    |
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
    | Costing values retain up to 4 decimal places because warehouse average
    | cost and GRN costing use 4-decimal precision.
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
    | Trim Decimal
    |--------------------------------------------------------------------------
    */

    private function trimDecimal(
        mixed $value,
        int $decimals,
        int $minimumDecimals = 0
    ): string {

        $formatted = number_format(
            (float) $value,
            $decimals,
            '.',
            ''
        );


        if ($minimumDecimals >= $decimals) {
            return $formatted;
        }


        $parts = explode(
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