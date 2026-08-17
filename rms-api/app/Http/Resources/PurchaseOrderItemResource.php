<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class PurchaseOrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        /*
        |--------------------------------------------------------------------------
        | Quantity
        |--------------------------------------------------------------------------
        |
        | Inventory quantities use 4-decimal precision throughout the system.
        |
        */

        $orderedQuantity = round(
            (float) $this->quantity,
            4
        );


        $receivedQuantity = round(
            (float) $this->received_quantity,
            4
        );


        $remainingQuantity = max(
            0,
            round(
                $orderedQuantity
                -
                $receivedQuantity,
                4
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Receiving Progress
        |--------------------------------------------------------------------------
        */

        $progressPercentage =
            $orderedQuantity > 0
                ? round(
                    min(
                        100,
                        max(
                            0,
                            (
                                $receivedQuantity
                                /
                                $orderedQuantity
                            )
                            *
                            100
                        )
                    ),
                    2
                )
                : 0;


        $isPartiallyReceived =
            $orderedQuantity > 0
            &&
            $receivedQuantity > 0
            &&
            $receivedQuantity < $orderedQuantity;


        $isFullyReceived =
            $orderedQuantity > 0
            &&
            $receivedQuantity >= $orderedQuantity;


        /*
        |--------------------------------------------------------------------------
        | Raw Material Relation
        |--------------------------------------------------------------------------
        |
        | Do not force lazy loading from a Resource.
        |
        */

        $rawMaterialLoaded =
            $this->relationLoaded(
                'rawMaterial'
            );


        $rawMaterial =
            $rawMaterialLoaded
                ? $this->rawMaterial
                : null;


        /*
        |--------------------------------------------------------------------------
        | Receive Availability
        |--------------------------------------------------------------------------
        |
        | Backend PurchaseReceiveService remains authoritative.
        |
        | When RawMaterial is loaded, frontend can also immediately disable
        | receiving for missing/inactive raw materials.
        |
        */

        $rawMaterialCanReceive =
            !$rawMaterialLoaded
            ||
            (
                $rawMaterial !== null
                &&
                (bool) $rawMaterial->is_active
            );


        $canReceive =
            !$isFullyReceived
            &&
            $remainingQuantity > 0
            &&
            $this->raw_material_id !== null
            &&
            $rawMaterialCanReceive;


        return [

            /*
            |--------------------------------------------------------------------------
            | Basic Information
            |--------------------------------------------------------------------------
            */

            'id' =>
                (int) $this->id,


            'purchase_order_id' =>
                (int) $this->purchase_order_id,


            'raw_material_id' =>
                $this->raw_material_id !== null
                    ? (int) $this->raw_material_id
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Raw Material
            |--------------------------------------------------------------------------
            */

            'raw_material' =>
                $this->whenLoaded(

                    'rawMaterial',

                    function () use (
                        $rawMaterial
                    ): ?array {

                        if (!$rawMaterial) {
                            return null;
                        }


                        return [

                            'id' =>
                                (int) $rawMaterial->id,


                            'material_name' =>
                                $rawMaterial->material_name,


                            'category' =>
                                $rawMaterial->category,


                            'base_unit' =>
                                $rawMaterial->base_unit,


                            'base_unit_label' =>
                                $rawMaterial->base_unit_label,


                            'is_active' =>
                                (bool) $rawMaterial->is_active,

                        ];
                    }
                ),


            /*
            |--------------------------------------------------------------------------
            | Item Snapshot
            |--------------------------------------------------------------------------
            */

            'item_name' =>
                $this->item_name,


            'unit' =>
                $this->unit,


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'quantity' =>
                $orderedQuantity,


            'received_quantity' =>
                $receivedQuantity,


            'remaining_quantity' =>
                $remainingQuantity,


            /*
            |--------------------------------------------------------------------------
            | Receiving Progress
            |--------------------------------------------------------------------------
            */

            'receive_progress_percentage' =>
                $progressPercentage,


            'is_partially_received' =>
                $isPartiallyReceived,


            'is_fully_received' =>
                $isFullyReceived,


            'can_receive' =>
                $canReceive,


            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            'unit_price' =>
                round(
                    (float) $this->unit_price,
                    2
                ),


            'total_price' =>
                round(
                    (float) $this->total_price,
                    2
                ),

        ];
    }
}