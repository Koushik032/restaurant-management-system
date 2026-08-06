<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddOnCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'category_name' => $this->category_name,

            'description' => $this->description,

            'is_available' => (bool) $this->is_available,

            'status_label' => $this->is_available
                ? 'Available'
                : 'Unavailable',

            'display_order' => (int) $this->display_order,

            'add_ons_count' => $this->whenCounted(
                'addOns'
            ),

            'add_ons' => AddOnResource::collection(
                $this->whenLoaded('addOns')
            ),

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}