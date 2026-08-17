<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;


class PurchaseOrderItem extends Model
{
    use HasFactory;


    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'purchase_order_id',
        'raw_material_id',
        'item_name',
        'unit',
        'quantity',
        'received_quantity',
        'unit_price',
        'total_price',
    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'purchase_order_id' =>
            'integer',

        'raw_material_id' =>
            'integer',

        'quantity' =>
            'decimal:4',

        'received_quantity' =>
            'decimal:4',

        'unit_price' =>
            'decimal:2',

        'total_price' =>
            'decimal:2',

    ];


    /*
    |--------------------------------------------------------------------------
    | Appended Attributes
    |--------------------------------------------------------------------------
    */

    protected $appends = [
        'remaining_quantity',
        'is_fully_received',
        'is_partially_received',
    ];


    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::saving(

            function (
                PurchaseOrderItem $item
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Normalize Numeric Values
                |--------------------------------------------------------------------------
                */

                $quantity =
                    round(
                        (float) (
                            $item->quantity
                            ?? 0
                        ),
                        4
                    );


                $receivedQuantity =
                    round(
                        (float) (
                            $item->received_quantity
                            ?? 0
                        ),
                        4
                    );


                $unitPrice =
                    round(
                        (float) (
                            $item->unit_price
                            ?? 0
                        ),
                        2
                    );


                /*
                |--------------------------------------------------------------------------
                | Ordered Quantity Protection
                |--------------------------------------------------------------------------
                */

                if ($quantity <= 0) {

                    throw ValidationException::withMessages([

                        'quantity' => [
                            'Purchase order item quantity must be greater than zero.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Received Quantity Protection
                |--------------------------------------------------------------------------
                */

                if ($receivedQuantity < 0) {

                    throw ValidationException::withMessages([

                        'received_quantity' => [
                            'Received quantity cannot be negative.',
                        ],

                    ]);
                }


                if (
                    $receivedQuantity
                    >
                    $quantity
                ) {

                    throw ValidationException::withMessages([

                        'received_quantity' => [
                            'Received quantity cannot exceed ordered quantity.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Unit Price Protection
                |--------------------------------------------------------------------------
                */

                if ($unitPrice < 0) {

                    throw ValidationException::withMessages([

                        'unit_price' => [
                            'Unit price cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Calculate Line Total
                |--------------------------------------------------------------------------
                |
                | total_price must always match:
                |
                | quantity × unit_price
                |
                */

                $totalPrice =
                    round(
                        $quantity
                        *
                        $unitPrice,
                        2
                    );


                /*
                |--------------------------------------------------------------------------
                | Store Normalized Values
                |--------------------------------------------------------------------------
                */

                $item->quantity =
                    $quantity;


                $item->received_quantity =
                    $receivedQuantity;


                $item->unit_price =
                    $unitPrice;


                $item->total_price =
                    $totalPrice;
            }

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Purchase Order
    |--------------------------------------------------------------------------
    */

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrder::class,
            'purchase_order_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Raw Material
    |--------------------------------------------------------------------------
    */

    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(
            RawMaterial::class,
            'raw_material_id'
        )->withTrashed();
    }


    /*
    |--------------------------------------------------------------------------
    | Receipt Item History
    |--------------------------------------------------------------------------
    */

    public function receiptItems(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderReceiptItem::class,
            'purchase_order_item_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Remaining Quantity
    |--------------------------------------------------------------------------
    */

    public function getRemainingQuantityAttribute(): float
    {
        $orderedQuantity =
            round(
                (float) (
                    $this->quantity
                    ?? 0
                ),
                4
            );


        $receivedQuantity =
            round(
                (float) (
                    $this->received_quantity
                    ?? 0
                ),
                4
            );


        return round(
            max(
                0,
                $orderedQuantity
                -
                $receivedQuantity
            ),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Fully Received
    |--------------------------------------------------------------------------
    */

    public function getIsFullyReceivedAttribute(): bool
    {
        $orderedQuantity =
            round(
                (float) (
                    $this->quantity
                    ?? 0
                ),
                4
            );


        $receivedQuantity =
            round(
                (float) (
                    $this->received_quantity
                    ?? 0
                ),
                4
            );


        if ($orderedQuantity <= 0) {
            return false;
        }


        return
            $receivedQuantity
            >=
            $orderedQuantity;
    }


    /*
    |--------------------------------------------------------------------------
    | Partially Received
    |--------------------------------------------------------------------------
    */

    public function getIsPartiallyReceivedAttribute(): bool
    {
        $orderedQuantity =
            round(
                (float) (
                    $this->quantity
                    ?? 0
                ),
                4
            );


        $receivedQuantity =
            round(
                (float) (
                    $this->received_quantity
                    ?? 0
                ),
                4
            );


        return
            $receivedQuantity > 0
            &&
            $receivedQuantity < $orderedQuantity;
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Has Raw Material
    |--------------------------------------------------------------------------
    */

    public function hasRawMaterial(): bool
    {
        return $this->raw_material_id !== null;
    }


    /*
    |--------------------------------------------------------------------------
    | Can Receive Quantity
    |--------------------------------------------------------------------------
    */

    public function canReceive(
        float $quantity
    ): bool {

        $quantity =
            round(
                $quantity,
                4
            );


        if ($quantity <= 0) {
            return false;
        }


        $remainingQuantity =
            round(
                (float) $this->remaining_quantity,
                4
            );


        return
            $quantity
            <=
            $remainingQuantity;
    }
}