<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuCategoryResource extends JsonResource
{
    public function toArray(
    $request
): array {
    return [
        'id' => $this->id,

        'category_name' =>
            $this->category_name,

        'description' =>
            $this->description,

        'display_order' =>
            (int) $this->display_order,

        'is_available' =>
            (bool) $this->is_available,

        'created_at' =>
            $this->created_at?->toISOString(),

        'updated_at' =>
            $this->updated_at?->toISOString(),
    ];
}
}