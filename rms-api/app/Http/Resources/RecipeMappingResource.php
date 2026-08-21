<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class RecipeMappingResource extends JsonResource
{
    /*
    |--------------------------------------------------------------------------
    | Resource
    |--------------------------------------------------------------------------
    */

    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Loaded Relations Only
        |--------------------------------------------------------------------------
        |
        | এই resource কোনো hidden lazy query trigger করবে না।
        |
        */

        $menuItem =
            $this->relationLoaded(
                'menuItem'
            )
                ? $this->menuItem
                : null;


        $addOn =
            $this->relationLoaded(
                'addOn'
            )
                ? $this->addOn
                : null;


        $variant =
            $this->relationLoaded(
                'variant'
            )
                ? $this->variant
                : null;


        $rawMaterial =
            $this->relationLoaded(
                'rawMaterial'
            )
                ? $this->rawMaterial
                : null;


        $restaurantStock =
            $rawMaterial
            &&
            $rawMaterial->relationLoaded(
                'restaurantStock'
            )
                ? $rawMaterial->restaurantStock
                : null;


        $creator =
            $this->relationLoaded(
                'creator'
            )
                ? $this->creator
                : null;


        $updater =
            $this->relationLoaded(
                'updater'
            )
                ? $this->updater
                : null;


        /*
        |--------------------------------------------------------------------------
        | Target IDs
        |--------------------------------------------------------------------------
        */

        $menuItemId =
            $this->menu_item_id !==
            null

                ? (int)
                    $this->menu_item_id

                : null;


        $addOnId =
            $this->add_on_id !==
            null

                ? (int)
                    $this->add_on_id

                : null;


        $variantId =
            $this->variant_id !==
            null

                ? (int)
                    $this->variant_id

                : null;


        /*
        |--------------------------------------------------------------------------
        | Target Type
        |--------------------------------------------------------------------------
        */

        $targetType =
            $addOnId !== null

                ? 'add_on'

                : 'menu_item';


        /*
        |--------------------------------------------------------------------------
        | Target ID
        |--------------------------------------------------------------------------
        */

        $targetId =
            $targetType ===
            'add_on'

                ? $addOnId

                : $menuItemId;


        /*
        |--------------------------------------------------------------------------
        | Target Name
        |--------------------------------------------------------------------------
        */

        $targetName =
            $targetType ===
            'add_on'

                ? $addOn?->add_on_name

                : $menuItem?->menu_name;


        /*
        |--------------------------------------------------------------------------
        | Target Image
        |--------------------------------------------------------------------------
        |
        | Add-on has no image in current system.
        |
        */

        $imageUrl =
            $targetType ===
            'menu_item'

                ? (
                    $menuItem?->image_url
                    ??
                    null
                )

                : null;


        /*
        |--------------------------------------------------------------------------
        | Variant Name
        |--------------------------------------------------------------------------
        */

        $variantName =
            $variant?->variant_name;


        /*
        |--------------------------------------------------------------------------
        | Quantity / Unit
        |--------------------------------------------------------------------------
        */

        $quantity =
            round(
                (float)
                    $this->quantity,
                4
            );


        $unit =
            strtolower(
                trim(
                    (string)
                        $this->unit
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Restaurant Stock Quantity
        |--------------------------------------------------------------------------
        */

        $restaurantQuantity =
            $restaurantStock

                ? round(
                    (float)
                        $restaurantStock->quantity,
                    4
                )

                : null;


        return [

            /*
            |--------------------------------------------------------------------------
            | Mapping ID
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int)
                    $this->id,


            /*
            |--------------------------------------------------------------------------
            | Unified Target
            |--------------------------------------------------------------------------
            */

            'target_type' =>
                $targetType,


            'target_id' =>
                $targetId,


            'target_name' =>
                $targetName,


            /*
            |--------------------------------------------------------------------------
            | Image
            |--------------------------------------------------------------------------
            */

            'image_url' =>
                $imageUrl,


            /*
            |--------------------------------------------------------------------------
            | Variant
            |--------------------------------------------------------------------------
            */

            'variant_id' =>
                $variantId,


            'variant_name' =>
                $variantName,


            /*
            |--------------------------------------------------------------------------
            | Availability
            |--------------------------------------------------------------------------
            */

            'is_available' =>
                $targetType ===
                'add_on'

                    ? (bool)
                        $addOn?->is_available

                    : (bool)
                        $menuItem?->is_available,


            /*
            |--------------------------------------------------------------------------
            | Legacy Target IDs
            |--------------------------------------------------------------------------
            */

            'menu_item_id' =>
                $menuItemId,


            'add_on_id' =>
                $addOnId,


            /*
            |--------------------------------------------------------------------------
            | Menu Item Details
            |--------------------------------------------------------------------------
            */

            'menu_item' =>
                $this->when(

                    $this->relationLoaded(
                        'menuItem'
                    ),

                    function () use (
                        $menuItem
                    ): ?array {

                        if (
                            ! $menuItem
                        ) {

                            return null;
                        }


                        return [

                            'id' =>
                                (int)
                                    $menuItem->id,


                            'menu_name' =>
                                $menuItem->menu_name,


                            'item_type' =>
                                $menuItem->item_type,


                            'is_available' =>
                                (bool)
                                    $menuItem->is_available,


                            'image_url' =>
                                $menuItem->image_url
                                ??
                                null,

                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Add-on Details
            |--------------------------------------------------------------------------
            */

            'add_on' =>
                $this->when(

                    $this->relationLoaded(
                        'addOn'
                    ),

                    function () use (
                        $addOn
                    ): ?array {

                        if (
                            ! $addOn
                        ) {

                            return null;
                        }


                        return [

                            'id' =>
                                (int)
                                    $addOn->id,


                            'add_on_name' =>
                                $addOn->add_on_name,


                            'is_available' =>
                                (bool)
                                    $addOn->is_available,

                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Variant Details
            |--------------------------------------------------------------------------
            */

            'variant' =>
                $this->when(

                    $this->relationLoaded(
                        'variant'
                    ),

                    function () use (
                        $variant
                    ): ?array {

                        if (
                            ! $variant
                        ) {

                            return null;
                        }


                        return [

                            'id' =>
                                (int)
                                    $variant->id,


                            'variant_name' =>
                                $variant->variant_name,


                            'price' =>
                                round(
                                    (float)
                                        $variant->price,
                                    2
                                ),


                            'is_available' =>
                                (bool)
                                    $variant->is_available,


                            'menu_item_id' =>
                                (int)
                                    $variant->menu_item_id,

                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Raw Material
            |--------------------------------------------------------------------------
            */

            'raw_material_id' =>
                (int)
                    $this->raw_material_id,


            'quantity' =>
                $quantity,


            'quantity_formatted' =>
                $this->formatQuantity(
                    $quantity,
                    $unit
                ),


            'unit' =>
                $unit,


            'notes' =>
                $this->notes,


            /*
            |--------------------------------------------------------------------------
            | Raw Material Details
            |--------------------------------------------------------------------------
            */

            'raw_material' =>
                $this->when(

                    $rawMaterial !==
                    null,

                    function () use (
                        $rawMaterial
                    ): array {

                        return [

                            'id' =>
                                (int)
                                    $rawMaterial->id,


                            'material_name' =>
                                $rawMaterial->material_name,


                            'category' =>
                                $rawMaterial->category,


                            'base_unit' =>
                                $rawMaterial->base_unit,


                            'restaurant_minimum_quantity' =>
                                round(
                                    (float)
                                        $rawMaterial
                                            ->restaurant_minimum_quantity,
                                    4
                                ),


                            'is_active' =>
                                (bool)
                                    $rawMaterial->is_active,


                            'is_archived' =>
                                $rawMaterial->deleted_at !==
                                null,

                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Restaurant Stock
            |--------------------------------------------------------------------------
            */

            'restaurant_stock' =>
                $this->when(

                    $rawMaterial !== null

                    &&

                    $rawMaterial->relationLoaded(
                        'restaurantStock'
                    ),

                    function () use (
                        $restaurantStock,
                        $restaurantQuantity,
                        $quantity,
                        $unit
                    ): array {

                        if (
                            ! $restaurantStock
                        ) {

                            return [

                                'exists' =>
                                    false,


                                'quantity' =>
                                    0.0,


                                'quantity_formatted' =>
                                    $this->formatQuantity(
                                        0,
                                        $unit
                                    ),


                                'status' =>
                                    'out_of_stock',


                                'sufficient_for_one' =>
                                    false,

                            ];
                        }


                        return [

                            'exists' =>
                                true,


                            'quantity' =>
                                $restaurantQuantity,


                            'quantity_formatted' =>
                                $this->formatQuantity(
                                    $restaurantQuantity,
                                    $unit
                                ),


                            'average_unit_cost' =>
                                round(
                                    (float)
                                        $restaurantStock
                                            ->average_unit_cost,
                                    4
                                ),


                            'status' =>
                                $restaurantStock->status,


                            'sufficient_for_one' =>
                                $restaurantQuantity >=
                                $quantity,

                        ];
                    },

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'created_by' =>
                $this->created_by
                    ? (int)
                        $this->created_by

                    : null,


            'creator' =>
                $this->when(

                    $creator !==
                    null,

                    [

                        'id' =>
                            (int)
                                $creator?->id,


                        'name' =>
                            $creator?->name,

                    ],

                    null
                ),


            'updated_by' =>
                $this->updated_by
                    ? (int)
                        $this->updated_by

                    : null,


            'updater' =>
                $this->when(

                    $updater !==
                    null,

                    [

                        'id' =>
                            (int)
                                $updater?->id,


                        'name' =>
                            $updater?->name,

                    ],

                    null
                ),


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
                (float)
                    $quantity,
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
            $formatted
            . ' '
            . trim(
                (string)
                    $unit
            )
        );
    }
}