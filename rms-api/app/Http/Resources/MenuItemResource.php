<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class MenuItemResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {

        $variants =
            $this->relationLoaded(
                'variants'
            )
                ? $this->variants
                : collect();


        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Identity
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,


            'menu_category_id' =>
                $this->menu_category_id !== null
                    ? (int) $this->menu_category_id
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category_name' =>
                $this->whenLoaded(
                    'category',
                    fn (): ?string =>
                        $this->category?->category_name
                ),


            'category' =>
                $this->when(
                    $this->relationLoaded(
                        'category'
                    ),

                    fn () =>
                        $this->category
                            ? new MenuCategoryResource(
                                $this->category
                            )
                            : null
                ),


            /*
            |--------------------------------------------------------------------------
            | Menu Item
            |--------------------------------------------------------------------------
            */

            'menu_name' =>
                $this->menu_name,


            'item_type' =>
                $this->item_type,


            'item_type_label' =>
                $this->item_type_label,


            'price' =>
                (float) $this->price,


            'formatted_price' =>
                number_format(
                    (float) $this->price,
                    2,
                    '.',
                    ''
                ),


            'ingredients' =>
                $this->ingredients,


            'description' =>
                $this->description,


            /*
            |--------------------------------------------------------------------------
            | Image
            |--------------------------------------------------------------------------
            */

            'image_path' =>
                $this->image_path,


            'image_url' =>
                $this->image_url,


            /*
            |--------------------------------------------------------------------------
            | Preparation
            |--------------------------------------------------------------------------
            */

            'preparation_time' =>
                $this->preparation_time !== null
                    ? (int) $this->preparation_time
                    : null,


            'preparation_time_label' =>
                $this->preparation_time !== null

                    ? $this->preparation_time
                        . ' minutes'

                    : null,


            /*
            |--------------------------------------------------------------------------
            | Availability
            |--------------------------------------------------------------------------
            */

            'is_available' =>
                (bool) $this->is_available,


            'status_label' =>
                (bool) $this->is_available
                    ? 'Available'
                    : 'Unavailable',


            'is_featured' =>
                (bool) $this->is_featured,


            /*
            |--------------------------------------------------------------------------
            | Variants
            |--------------------------------------------------------------------------
            */

            'variants_count' =>
                $this->whenCounted(
                    'variants'
                ),


            'has_variants' =>
                $variants->isNotEmpty(),


            'variants' =>
                $this->when(

                    $this->relationLoaded(
                        'variants'
                    ),

                    MenuItemVariantResource::collection(
                        $variants
                    ),

                    []
                ),


            /*
            |--------------------------------------------------------------------------
            | Timestamps
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
}