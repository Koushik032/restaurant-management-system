<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class OrderRecipeConsumptionItemResource extends JsonResource
{
    /**
     * Transform the immutable recipe-consumption item snapshot.
     */
    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Loaded Raw Material
        |--------------------------------------------------------------------------
        |
        | Never trigger hidden/lazy queries from the resource.
        |
        */

        $rawMaterial =
            $this->relationLoaded(
                'rawMaterial'
            )
                ? $this->rawMaterial
                : null;


        /*
        |--------------------------------------------------------------------------
        | Numeric Snapshots
        |--------------------------------------------------------------------------
        */

        $quantity =
            round(
                (float) $this->quantity,
                4
            );


        $unitCost =
            round(
                (float) $this->unit_cost,
                4
            );


        $quantityBefore =
            round(
                (float) $this
                    ->restaurant_quantity_before,
                4
            );


        $quantityAfter =
            round(
                (float) $this
                    ->restaurant_quantity_after,
                4
            );


        $totalCost =
            round(
                $quantity
                *
                $unitCost,
                4
            );


        return [

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,


            'order_recipe_consumption_id' =>
                (int) $this
                    ->order_recipe_consumption_id,


            'raw_material_id' =>
                (int) $this
                    ->raw_material_id,


            /*
            |--------------------------------------------------------------------------
            | Raw Material Snapshot
            |--------------------------------------------------------------------------
            */

            'material_name' =>
                $this->material_name,


            'unit' =>
                $this->unit,


            /*
            |--------------------------------------------------------------------------
            | Current Raw Material Relation
            |--------------------------------------------------------------------------
            */

            'raw_material' =>
                $this->when(
                    $rawMaterial !== null,

                    function () use (
                        $rawMaterial
                    ): array {

                        return [

                            'id' =>
                                (int) $rawMaterial
                                    ->id,


                            'material_name' =>
                                $rawMaterial
                                    ->material_name,


                            'category' =>
                                $rawMaterial
                                    ->category,


                            'base_unit' =>
                                $rawMaterial
                                    ->base_unit,


                            'is_active' =>
                                (bool) $rawMaterial
                                    ->is_active,


                            'is_archived' =>
                                method_exists(
                                    $rawMaterial,
                                    'trashed'
                                )
                                    ? (bool) $rawMaterial
                                        ->trashed()
                                    : false,
                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Consumption
            |--------------------------------------------------------------------------
            */

            'quantity' =>
                $quantity,


            'quantity_formatted' =>
                $this->formatQuantity(
                    $quantity,
                    $this->unit
                ),


            /*
            |--------------------------------------------------------------------------
            | Cost Snapshot
            |--------------------------------------------------------------------------
            */

            'unit_cost' =>
                $unitCost,


            'unit_cost_formatted' =>
                $this->money(
                    $unitCost
                ),


            'total_cost' =>
                $totalCost,


            'total_cost_formatted' =>
                $this->money(
                    $totalCost
                ),


            /*
            |--------------------------------------------------------------------------
            | Restaurant Stock Snapshot
            |--------------------------------------------------------------------------
            */

            'restaurant_quantity_before' =>
                $quantityBefore,


            'restaurant_quantity_before_formatted' =>
                $this->formatQuantity(
                    $quantityBefore,
                    $this->unit
                ),


            'restaurant_quantity_after' =>
                $quantityAfter,


            'restaurant_quantity_after_formatted' =>
                $this->formatQuantity(
                    $quantityAfter,
                    $this->unit
                ),


            /*
            |--------------------------------------------------------------------------
            | Recipe Source Breakdown
            |--------------------------------------------------------------------------
            */

            'source_breakdown' =>
                is_array(
                    $this->source_breakdown
                )
                    ? $this->source_breakdown
                    : [],


            /*
            |--------------------------------------------------------------------------
            | Notes / Audit Time
            |--------------------------------------------------------------------------
            */

            'notes' =>
                $this->notes,


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
            number_format(
                (float) $quantity,
                4,
                '.',
                ''
            );


        $formatted =
            rtrim(
                rtrim(
                    $formatted,
                    '0'
                ),
                '.'
            );


        return trim(
            "{$formatted} {$unit}"
        );
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