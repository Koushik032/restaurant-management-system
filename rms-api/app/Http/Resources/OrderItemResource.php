<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' => (int) $this->id,

            'menu_item_id' =>
                $this->menu_item_id,

            'menu_item_variant_id' =>
                $this->menu_item_variant_id,

            'item_name' =>
                $this->item_name,

            'variant_name' =>
                $this->variant_name,

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            'unit_price' =>
                (float) $this->unit_price,

            'unit_price_formatted' =>
                $this->money(
                    $this->unit_price
                ),

            'quantity' =>
                (int) $this->quantity,

            'addon_total' =>
                (float) $this->addon_total,

            'addon_total_formatted' =>
                $this->money(
                    $this->addon_total
                ),

            'line_total' =>
                (float) $this->line_total,

            'line_total_formatted' =>
                $this->money(
                    $this->line_total
                ),

            /*
            |--------------------------------------------------------------------------
            | Kitchen
            |--------------------------------------------------------------------------
            */

            'status' =>
                $this->status,

            'kitchen_note' =>
                $this->kitchen_note,

            /*
            |--------------------------------------------------------------------------
            | Addons
            |--------------------------------------------------------------------------
            */

            'addons' =>
                $this->whenLoaded(
                    'addons',
                    function () {

                        return $this->addons
                            ->map(
                                function (
                                    $addon
                                ) {

                                    return [

                                        'id' =>
                                            (int) $addon->id,

                                        'menu_addon_id' =>
                                            $addon->menu_addon_id,

                                        'addon_name' =>
                                            $addon->addon_name,

                                        'unit_price' =>
                                            (float) $addon->unit_price,

                                        'unit_price_formatted' =>
                                            $this->money(
                                                $addon->unit_price
                                            ),

                                        'quantity' =>
                                            (int) $addon->quantity,

                                        'total_price' =>
                                            (float) $addon->total_price,

                                        'total_price_formatted' =>
                                            $this->money(
                                                $addon->total_price
                                            ),

                                    ];

                                }
                            )
                            ->values();

                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | Summary
            |--------------------------------------------------------------------------
            */

            'addon_count' =>
                $this->whenLoaded(
                    'addons',
                    fn () =>
                        $this->addons->count()
                ),

        ];

    }

    /**
     * Money Formatter
     */
    private function money(
        mixed $amount
    ): string {

        return '৳ ' .
            number_format(
                (float) $amount,
                2
            );

    }
}