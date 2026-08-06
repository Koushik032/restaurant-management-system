<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddOnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'serial_id' => $this->id,

            'add_on_name' =>
                $this->add_on_name,

            'price' =>
                (float) $this->price,

            'formatted_price' =>
                number_format(
                    (float) $this->price,
                    2
                ),

            'description' =>
                $this->description,

            'is_available' =>
                (bool) $this->is_available,

            'status_label' =>
                $this->is_available
                    ? 'Available'
                    : 'Unavailable',

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}