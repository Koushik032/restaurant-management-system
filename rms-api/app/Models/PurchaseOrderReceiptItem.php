<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;


class PurchaseOrderReceiptItem extends Model
{
    use HasFactory;


    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'purchase_order_receipt_id',

        'purchase_order_item_id',

        'raw_material_id',

        'item_name',

        'unit',

        'quantity',

        'unit_cost',

        'total_cost',

        'notes',

    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'purchase_order_receipt_id' =>
            'integer',

        'purchase_order_item_id' =>
            'integer',

        'raw_material_id' =>
            'integer',

        'quantity' =>
            'decimal:4',

        'unit_cost' =>
            'decimal:4',

        'total_cost' =>
            'decimal:4',

    ];


    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    |
    | Receipt items are historical GRN snapshots.
    |
    | Create:
    |     Allowed
    |
    | Update:
    |     Blocked
    |
    | Delete:
    |     Blocked
    |
    */

    protected static function booted(): void
    {
        static::creating(

            function (
                PurchaseOrderReceiptItem $item
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Receipt ID
                |--------------------------------------------------------------------------
                */

                $receiptId =
                    (int) (
                        $item->purchase_order_receipt_id
                        ?? 0
                    );


                if ($receiptId <= 0) {

                    throw ValidationException::withMessages([

                        'purchase_order_receipt_id' => [
                            'A valid purchase receipt is required.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Purchase Order Item ID
                |--------------------------------------------------------------------------
                */

                $purchaseOrderItemId =
                    (int) (
                        $item->purchase_order_item_id
                        ?? 0
                    );


                if ($purchaseOrderItemId <= 0) {

                    throw ValidationException::withMessages([

                        'purchase_order_item_id' => [
                            'A valid purchase order item is required.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Raw Material ID
                |--------------------------------------------------------------------------
                */

                $rawMaterialId =
                    (int) (
                        $item->raw_material_id
                        ?? 0
                    );


                if ($rawMaterialId <= 0) {

                    throw ValidationException::withMessages([

                        'raw_material_id' => [
                            'A valid raw material is required.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Snapshot Item Name
                |--------------------------------------------------------------------------
                */

                $itemName =
                    trim(
                        (string) (
                            $item->item_name
                            ?? ''
                        )
                    );


                if ($itemName === '') {

                    throw ValidationException::withMessages([

                        'item_name' => [
                            'Receipt item name is required.',
                        ],

                    ]);
                }


                $item->item_name =
                    $itemName;


                /*
                |--------------------------------------------------------------------------
                | Snapshot Unit
                |--------------------------------------------------------------------------
                */

                $unit =
                    strtolower(
                        trim(
                            (string) (
                                $item->unit
                                ?? ''
                            )
                        )
                    );


                if ($unit === '') {

                    throw ValidationException::withMessages([

                        'unit' => [
                            'Receipt item unit is required.',
                        ],

                    ]);
                }


                $item->unit =
                    $unit;


                /*
                |--------------------------------------------------------------------------
                | Quantity
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


                if ($quantity <= 0) {

                    throw ValidationException::withMessages([

                        'quantity' => [
                            'Receipt item quantity must be greater than zero.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Unit Cost
                |--------------------------------------------------------------------------
                */

                $unitCost =
                    round(
                        (float) (
                            $item->unit_cost
                            ?? 0
                        ),
                        4
                    );


                if ($unitCost < 0) {

                    throw ValidationException::withMessages([

                        'unit_cost' => [
                            'Receipt item unit cost cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Calculate Total Cost
                |--------------------------------------------------------------------------
                */

                $totalCost =
                    round(
                        $quantity
                        *
                        $unitCost,
                        4
                    );


                /*
                |--------------------------------------------------------------------------
                | Normalize Notes
                |--------------------------------------------------------------------------
                */

                if ($item->notes !== null) {

                    $notes =
                        trim(
                            (string) $item->notes
                        );


                    $item->notes =
                        $notes !== ''
                            ? $notes
                            : null;
                }


                /*
                |--------------------------------------------------------------------------
                | Store Normalized Values
                |--------------------------------------------------------------------------
                */

                $item->quantity =
                    $quantity;


                $item->unit_cost =
                    $unitCost;


                $item->total_cost =
                    $totalCost;
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Receipt Item Modification
        |--------------------------------------------------------------------------
        */

        static::updating(

            function (): void {

                throw ValidationException::withMessages([

                    'receipt_item' => [
                        'Purchase receipt item history cannot be modified.',
                    ],

                ]);
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Receipt Item Deletion
        |--------------------------------------------------------------------------
        */

        static::deleting(

            function (): void {

                throw ValidationException::withMessages([

                    'receipt_item' => [
                        'Purchase receipt item history cannot be deleted.',
                    ],

                ]);
            }

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    public function receipt(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrderReceipt::class,
            'purchase_order_receipt_id'
        );
    }


    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrderItem::class,
            'purchase_order_item_id'
        );
    }


    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(
            RawMaterial::class,
            'raw_material_id'
        )->withTrashed();
    }
}