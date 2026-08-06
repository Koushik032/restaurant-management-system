<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,

            'menu_category_id' => (int) $this->menu_category_id,

            'category_name' => $this->whenLoaded(
                'category',
                fn (): ?string => $this->category?->category_name
            ),

            'category' => $this->when(
                $this->relationLoaded('category'),
                fn () => $this->category
                    ? new MenuCategoryResource($this->category)
                    : null
            ),

            'menu_name' => $this->menu_name,

            'item_type' => $this->item_type,

            'item_type_label' => $this->item_type_label,

            'price' => (float) $this->price,

            'formatted_price' => number_format(
                (float) $this->price,
                2,
                '.',
                ''
            ),

            'ingredients' => $this->ingredients,

            'description' => $this->description,

            'image_path' => $this->image_path,

            'image_url' => $this->image_url,

            'preparation_time' => $this->preparation_time !== null
                ? (int) $this->preparation_time
                : null,

            'preparation_time_label' => $this->preparation_time !== null
                ? $this->preparation_time . ' minutes'
                : null,

            'is_available' => (bool) $this->is_available,

            'status_label' => (bool) $this->is_available
                ? 'Available'
                : 'Unavailable',

            'is_featured' => (bool) $this->is_featured,

            'variants_count' => $this->whenCounted(
                'variants'
            ),

            'variants' => MenuItemVariantResource::collection(
                $this->whenLoaded('variants')
            ),

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}