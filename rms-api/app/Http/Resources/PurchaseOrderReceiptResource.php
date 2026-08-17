<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class PurchaseOrderReceiptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Items
        |--------------------------------------------------------------------------
        */

        $itemsLoaded =
            $this->relationLoaded(
                'items'
            );


        $items =
            $itemsLoaded
                ? $this->items
                : collect();


        /*
        |--------------------------------------------------------------------------
        | Totals
        |--------------------------------------------------------------------------
        */

        $totalQuantity =
            round(
                (float) $items->sum(
                    'quantity'
                ),
                4
            );


        $totalCost =
            round(
                (float) $items->sum(
                    'total_cost'
                ),
                4
            );


        /*
        |--------------------------------------------------------------------------
        | Quantity By Unit
        |--------------------------------------------------------------------------
        |
        | A GRN may contain different units:
        |
        | 10 kg
        | 5 pcs
        |
        | A single numeric total of 15 is not meaningful by itself.
        | This grouped summary preserves the real unit context.
        |
        */

        $quantityByUnit =
            $itemsLoaded
                ? $items
                    ->groupBy(
                        function ($item): string {
                            $unit = strtolower(
                                trim(
                                    (string) (
                                        $item->unit
                                        ?? ''
                                    )
                                )
                            );

                            return $unit !== ''
                                ? $unit
                                : 'unknown';
                        }
                    )
                    ->map(
                        static function (
                            $group,
                            string $unit
                        ): array {

                            return [
                                'unit' =>
                                    $unit,

                                'quantity' =>
                                    round(
                                        (float) $group->sum(
                                            'quantity'
                                        ),
                                        4
                                    ),
                            ];
                        }
                    )
                    ->values()
                    ->all()
                : [];


        /*
        |--------------------------------------------------------------------------
        | Loaded Relations
        |--------------------------------------------------------------------------
        */

        $receivedBy =
            $this->relationLoaded(
                'receivedBy'
            )
                ? $this->receivedBy
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


            /*
            |--------------------------------------------------------------------------
            | GRN
            |--------------------------------------------------------------------------
            */

            'receipt_no' =>
                $this->receipt_no,


            /*
            |--------------------------------------------------------------------------
            | Receive Date
            |--------------------------------------------------------------------------
            */

            'received_at' =>
                $this->received_at
                    ?->toISOString(),


            'received_at_label' =>
                $this->received_at
                    ?->format(
                        'd M Y, h:i A'
                    ),


            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' =>
                $this->whenLoaded(

                    'items',

                    fn () =>
                        PurchaseOrderReceiptItemResource::collection(
                            $this->items
                        ),

                    []
                ),


            'total_items' =>
                $itemsLoaded
                    ? $items->count()
                    : 0,


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'total_quantity' =>
                $totalQuantity,


            'total_quantity_by_unit' =>
                $quantityByUnit,


            /*
            |--------------------------------------------------------------------------
            | Cost
            |--------------------------------------------------------------------------
            */

            'total_cost' =>
                $totalCost,


            'total_cost_formatted' =>
                $this->formatCost(
                    $totalCost
                ),


            /*
            |--------------------------------------------------------------------------
            | Received By
            |--------------------------------------------------------------------------
            */

            'received_by' =>
                $this->whenLoaded(

                    'receivedBy',

                    fn (): ?array =>
                        $this->userSummary(
                            $receivedBy
                        ),

                    null
                ),


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' =>
                $this->notes,


            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            'created_by' =>
                $this->whenLoaded(

                    'creator',

                    fn (): ?array =>
                        $this->userSummary(
                            $creator
                        ),

                    null
                ),


            'updated_by' =>
                $this->whenLoaded(

                    'updater',

                    fn (): ?array =>
                        $this->userSummary(
                            $updater
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
    | Cost Formatter
    |--------------------------------------------------------------------------
    |
    | GRN / inventory costing uses 4-decimal precision.
    |
    */

    private function formatCost(
        mixed $amount
    ): string {

        return '৳ '
            .
            $this->trimDecimal(
                value: $amount,
                decimals: 4,
                minimumDecimals: 2
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Trim Decimal
    |--------------------------------------------------------------------------
    */

    private function trimDecimal(
        mixed $value,
        int $decimals,
        int $minimumDecimals = 0
    ): string {

        $formatted =
            number_format(
                (float) $value,
                $decimals,
                '.',
                ''
            );


        $parts =
            explode(
                '.',
                $formatted,
                2
            );


        if (count($parts) === 1) {
            return $parts[0];
        }


        $integerPart =
            $parts[0];


        $decimalPart =
            rtrim(
                $parts[1],
                '0'
            );


        if (
            strlen($decimalPart)
            <
            $minimumDecimals
        ) {

            $decimalPart =
                str_pad(
                    $decimalPart,
                    $minimumDecimals,
                    '0'
                );
        }


        return $decimalPart !== ''
            ? "{$integerPart}.{$decimalPart}"
            : $integerPart;
    }
}