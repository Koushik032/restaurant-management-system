<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class RawMaterialResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Warehouse Stock
        |--------------------------------------------------------------------------
        */

        $warehouseStockLoaded =
            $this->relationLoaded(
                'warehouseStock'
            );


        $warehouseStock =
            $warehouseStockLoaded
                ? $this->warehouseStock
                : null;


        $warehouseQuantity = round(
            (float) (
                $warehouseStock?->quantity
                ?? 0
            ),
            4
        );


        $warehouseStockValue = round(
            $warehouseStock
                ? (float) $warehouseStock->stockValue()
                : 0,
            4
        );


        /*
        |--------------------------------------------------------------------------
        | Restaurant Stock
        |--------------------------------------------------------------------------
        */

        $restaurantStockLoaded =
            $this->relationLoaded(
                'restaurantStock'
            );


        $restaurantStock =
            $restaurantStockLoaded
                ? $this->restaurantStock
                : null;


        $restaurantQuantity = round(
            (float) (
                $restaurantStock?->quantity
                ?? 0
            ),
            4
        );


        $restaurantStockValue = round(
            $restaurantStock
                ? (float) $restaurantStock->stockValue()
                : 0,
            4
        );


        /*
        |--------------------------------------------------------------------------
        | Minimum Quantities
        |--------------------------------------------------------------------------
        */

        $warehouseMinimumQuantity = round(
            (float) $this->warehouse_minimum_quantity,
            4
        );


        $restaurantMinimumQuantity = round(
            (float) $this->restaurant_minimum_quantity,
            4
        );


        /*
        |--------------------------------------------------------------------------
        | Delete Hint
        |--------------------------------------------------------------------------
        |
        | This is only a frontend hint.
        |
        | Backend InventoryService remains authoritative because deletion can
        | also be blocked by purchase/order/history dependencies.
        |
        | We therefore only return:
        |
        | false => stock definitely prevents deletion
        | null  => Resource cannot guarantee deletion is allowed
        |
        */

        $canDelete = null;


        if (
            (
                $warehouseStockLoaded
                &&
                $warehouseQuantity > 0
            )
            ||
            (
                $restaurantStockLoaded
                &&
                $restaurantQuantity > 0
            )
        ) {
            $canDelete = false;
        }


        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,


            'material_name' =>
                $this->material_name,


            'category' =>
                $this->category,


            /*
            |--------------------------------------------------------------------------
            | Unit
            |--------------------------------------------------------------------------
            */

            'base_unit' =>
                $this->base_unit,


            'base_unit_label' =>
                $this->base_unit_label,


            /*
            |--------------------------------------------------------------------------
            | Minimum Stock Levels
            |--------------------------------------------------------------------------
            */

            'warehouse_minimum_quantity' =>
                $warehouseMinimumQuantity,


            'restaurant_minimum_quantity' =>
                $restaurantMinimumQuantity,


            /*
            |--------------------------------------------------------------------------
            | Warehouse Stock Summary
            |--------------------------------------------------------------------------
            */

            'warehouse_quantity' =>
                $warehouseQuantity,


            'warehouse_quantity_formatted' =>
                $this->formatQuantity(
                    quantity: $warehouseQuantity,
                    unit: $this->base_unit
                ),


            'warehouse_status' =>
                $warehouseStock?->status,


            'warehouse_status_label' =>
                $warehouseStock?->status_label,


            'warehouse_status_color' =>
                $warehouseStock?->status_color,


            'warehouse_stock_value' =>
                $warehouseStockValue,


            'warehouse_stock_value_formatted' =>
                $this->money(
                    $warehouseStockValue
                ),


            /*
            |--------------------------------------------------------------------------
            | Full Warehouse Stock
            |--------------------------------------------------------------------------
            */

            'warehouse_stock' =>
                $this->whenLoaded(

                    'warehouseStock',

                    fn () =>
                        $warehouseStock
                            ? new WarehouseStockResource(
                                $warehouseStock
                            )
                            : null,

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Restaurant Stock Summary
            |--------------------------------------------------------------------------
            */

            'restaurant_quantity' =>
                $restaurantQuantity,


            'restaurant_quantity_formatted' =>
                $this->formatQuantity(
                    quantity: $restaurantQuantity,
                    unit: $this->base_unit
                ),


            'restaurant_status' =>
                $restaurantStock?->status,


            'restaurant_status_label' =>
                $restaurantStock?->status_label,


            'restaurant_status_color' =>
                $restaurantStock?->status_color,


            'restaurant_stock_value' =>
                $restaurantStockValue,


            'restaurant_stock_value_formatted' =>
                $this->money(
                    $restaurantStockValue
                ),


            /*
            |--------------------------------------------------------------------------
            | Full Restaurant Stock
            |--------------------------------------------------------------------------
            */

            'restaurant_stock' =>
                $this->whenLoaded(

                    'restaurantStock',

                    fn () =>
                        $restaurantStock
                            ? new RestaurantStockResource(
                                $restaurantStock
                            )
                            : null,

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_active' =>
                (bool) $this->is_active,


            'status_label' =>
                (bool) $this->is_active
                    ? 'Active'
                    : 'Inactive',


            /*
            |--------------------------------------------------------------------------
            | Delete Hint
            |--------------------------------------------------------------------------
            */

            'can_delete' =>
                $canDelete,


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


        return $unit
            ? "{$formatted} {$unit}"
            : $formatted;
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