<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class OrderRecipeConsumptionResource extends JsonResource
{
    /**
     * Transform the immutable order-level recipe-consumption audit record.
     */
    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Loaded Relations
        |--------------------------------------------------------------------------
        |
        | Never trigger hidden/lazy queries from the resource.
        |
        */

        $order =
            $this->relationLoaded(
                'order'
            )
                ? $this->order
                : null;


        $creator =
            $this->relationLoaded(
                'creator'
            )
                ? $this->creator
                : null;


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
        | Cost Summary
        |--------------------------------------------------------------------------
        |
        | Ingredient quantities are intentionally not summed because different
        | ingredients may use different units such as kg, litre, pcs, etc.
        |
        */

        $totalCost =
            $itemsLoaded
                ? round(
                    (float) $items->sum(
                        static function ($item): float {

                            return round(
                                (float) $item->quantity,
                                4
                            )
                            *
                            round(
                                (float) $item->unit_cost,
                                4
                            );
                        }
                    ),
                    4
                )
                : null;


        return [

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,


            'order_id' =>
                (int) $this->order_id,


            /*
            |--------------------------------------------------------------------------
            | Immutable Order Snapshot
            |--------------------------------------------------------------------------
            */

            'order_number' =>
                $this->order_number,


            'trigger' =>
                $this->trigger,


            'order_status_snapshot' =>
                $this->order_status_snapshot,


            /*
            |--------------------------------------------------------------------------
            | Consumption Audit
            |--------------------------------------------------------------------------
            */

            'consumed_at' =>
                $this->consumed_at
                    ?->toISOString(),


            'created_by' =>
                $this->created_by !== null
                    ? (int) $this->created_by
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Creator
            |--------------------------------------------------------------------------
            */

            'creator' =>
                $this->when(
                    $this->relationLoaded(
                        'creator'
                    ),

                    function () use (
                        $creator
                    ): ?array {

                        if (! $creator) {
                            return null;
                        }


                        return [
                            'id' =>
                                (int) $creator->id,

                            'name' =>
                                $creator->name,

                            'username' =>
                                $creator->username,
                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Current Order Context
            |--------------------------------------------------------------------------
            |
            | Snapshot fields above remain authoritative for audit history.
            | The current Order relation is supplementary only.
            |
            */

            'order' =>
                $this->when(
                    $this->relationLoaded(
                        'order'
                    ),

                    function () use (
                        $order
                    ): ?array {

                        if (! $order) {
                            return null;
                        }


                        return [
                            'id' =>
                                (int) $order->id,

                            'order_number' =>
                                $order->order_number,

                            'status' =>
                                $order->status,

                            'is_archived' =>
                                method_exists(
                                    $order,
                                    'trashed'
                                )
                                    ? (bool) $order
                                        ->trashed()
                                    : false,
                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Ingredient Consumption Items
            |--------------------------------------------------------------------------
            */

            'items_loaded' =>
                $itemsLoaded,


            'items' =>
                $this->when(
                    $itemsLoaded,

                    OrderRecipeConsumptionItemResource::collection(
                        $items
                    ),

                    []
                ),


            /*
            |--------------------------------------------------------------------------
            | Summary
            |--------------------------------------------------------------------------
            */

            'summary' =>
                $this->when(
                    $itemsLoaded,

                    function () use (
                        $items,
                        $totalCost
                    ): array {

                        return [
                            'ingredient_lines' =>
                                $items->count(),

                            'total_cost' =>
                                $totalCost,

                            'total_cost_formatted' =>
                                $this->money(
                                    $totalCost
                                ),
                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Record Timestamps
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