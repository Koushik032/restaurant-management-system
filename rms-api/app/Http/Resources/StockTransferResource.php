<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class StockTransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Items
        |--------------------------------------------------------------------------
        */

        $itemsLoaded =
            $this->relationLoaded(
                'items'
            );


        $items =
            $itemsLoaded
                ? $this->items
                : collect();


        /*
        |--------------------------------------------------------------------------
        | Total Quantity
        |--------------------------------------------------------------------------
        |
        | Inventory quantity uses 4-decimal precision.
        |
        */

        $totalQuantity =
            $itemsLoaded
                ? round(
                    (float) $items->sum(
                        'quantity'
                    ),
                    4
                )
                : 0.0;


        /*
        |--------------------------------------------------------------------------
        | Quantity By Unit
        |--------------------------------------------------------------------------
        |
        | A transfer can contain materials with different units.
        |
        | Example:
        |
        | 5 kg + 10 pcs
        |
        | A single total of 15 is not meaningful, therefore this unit-aware
        | summary should be preferred for reporting/display.
        |
        */

        $totalQuantityByUnit =
            $itemsLoaded
                ? $items
                    ->groupBy(
                        static function ($item): string {

                            $unit =
                                strtolower(
                                    trim(
                                        (string) (
                                            $item->unit
                                            ?? ''
                                        )
                                    )
                                );


                            return $unit !== ''
                                ? $unit
                                : 'unknown';
                        }
                    )
                    ->map(
                        static function (
                            $group,
                            string $unit
                        ): array {

                            return [

                                'unit' =>
                                    $unit,


                                'quantity' =>
                                    round(
                                        (float) $group->sum(
                                            'quantity'
                                        ),
                                        4
                                    ),

                            ];
                        }
                    )
                    ->values()
                    ->all()
                : [];


        /*
        |--------------------------------------------------------------------------
        | Transfer Value
        |--------------------------------------------------------------------------
        |
        | Inventory costing uses 4-decimal precision.
        |
        */

        $totalValue =
            $itemsLoaded
                ? round(

                    (float) $items->sum(

                        static function (
                            $item
                        ): float {

                            $quantity =
                                round(
                                    (float) $item->quantity,
                                    4
                                );


                            $unitCost =
                                round(
                                    (float) $item->unit_cost,
                                    4
                                );


                            return
                                $quantity
                                *
                                $unitCost;
                        }

                    ),

                    4
                )
                : 0.0;


        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,


            'transfer_no' =>
                $this->transfer_no,


            /*
            |--------------------------------------------------------------------------
            | Transfer Date
            |--------------------------------------------------------------------------
            */

            'transferred_at' =>
                $this->transferred_at
                    ?->toISOString(),


            'transferred_at_label' =>
                $this->transferred_at
                    ?->format(
                        'd M Y, h:i A'
                    ),


            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' =>
                $this->when(

                    $itemsLoaded,

                    fn () =>
                        StockTransferItemResource::collection(
                            $items
                        ),

                    []
                ),


            'total_items' =>
                $itemsLoaded
                    ? $items->count()
                    : 0,


            /*
            |--------------------------------------------------------------------------
            | Quantity Summary
            |--------------------------------------------------------------------------
            */

            'total_quantity' =>
                $totalQuantity,


            'total_quantity_by_unit' =>
                $totalQuantityByUnit,


            /*
            |--------------------------------------------------------------------------
            | Transfer Value
            |--------------------------------------------------------------------------
            */

            'total_value' =>
                $totalValue,


            'total_value_formatted' =>
                $this->money(
                    $totalValue
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
            | Transferred By
            |--------------------------------------------------------------------------
            |
            | Do not force lazy loading.
            |
            */

            'transferred_by' =>
                $this->whenLoaded(

                    'transferredBy',

                    fn (): ?array =>
                        $this->userSummary(
                            $this->transferredBy
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