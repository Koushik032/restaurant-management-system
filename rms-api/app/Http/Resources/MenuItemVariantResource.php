<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemVariantResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' =>
                $this->id,

            'menu_item_id' =>
                $this->menu_item_id,

            'variant_name' =>
                $this->variant_name,

            'price' =>
                $this->price,

            'is_available' =>
                (bool) $this->is_available,

            'menu_item' =>
                $this->whenLoaded(
                    'menuItem',
                    fn () =>
                    new MenuItemResource(
                        $this->menuItem
                    )
                ),

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}