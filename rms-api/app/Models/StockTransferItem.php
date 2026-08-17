<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;


class StockTransferItem extends Model
{
    use HasFactory;


    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'stock_transfer_id',

        'raw_material_id',

        'item_name',

        'unit',

        'quantity',

        'unit_cost',

        'warehouse_quantity_before',

        'warehouse_quantity_after',

        'restaurant_quantity_before',

        'restaurant_quantity_after',

        'notes',

    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'stock_transfer_id' =>
            'integer',

        'raw_material_id' =>
            'integer',

        'quantity' =>
            'decimal:4',

        'unit_cost' =>
            'decimal:4',

        'warehouse_quantity_before' =>
            'decimal:4',

        'warehouse_quantity_after' =>
            'decimal:4',

        'restaurant_quantity_before' =>
            'decimal:4',

        'restaurant_quantity_after' =>
            'decimal:4',

    ];


    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    |
    | Stock transfer items are historical stock snapshots.
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
        /*
        |--------------------------------------------------------------------------
        | Validate Transfer Item Creation
        |--------------------------------------------------------------------------
        */

        static::creating(

            function (
                StockTransferItem $item
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Stock Transfer ID
                |--------------------------------------------------------------------------
                */

                $stockTransferId =
                    (int) (
                        $item->stock_transfer_id
                        ?? 0
                    );


                if ($stockTransferId <= 0) {

                    throw ValidationException::withMessages([

                        'stock_transfer_id' => [
                            'A valid stock transfer is required.',
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
                            'Transfer item name is required.',
                        ],

                    ]);
                }


                $item->item_name =
                    $itemName;


                /*
                |--------------------------------------------------------------------------
                | Unit
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


                if (
                    ! in_array(
                        $unit,
                        RawMaterial::allowedUnits(),
                        true
                    )
                ) {

                    throw ValidationException::withMessages([

                        'unit' => [
                            'The selected transfer unit is invalid.',
                        ],

                    ]);
                }


                $item->unit =
                    $unit;


                /*
                |--------------------------------------------------------------------------
                | Transfer Quantity
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
                            'Transfer quantity must be greater than zero.',
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
                            'Transfer unit cost cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Warehouse Quantity Before
                |--------------------------------------------------------------------------
                */

                $warehouseBefore =
                    round(
                        (float) (
                            $item->warehouse_quantity_before
                            ?? 0
                        ),
                        4
                    );


                if ($warehouseBefore < 0) {

                    throw ValidationException::withMessages([

                        'warehouse_quantity_before' => [
                            'Warehouse quantity before transfer cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Warehouse Quantity After
                |--------------------------------------------------------------------------
                */

                $warehouseAfter =
                    round(
                        (float) (
                            $item->warehouse_quantity_after
                            ?? 0
                        ),
                        4
                    );


                if ($warehouseAfter < 0) {

                    throw ValidationException::withMessages([

                        'warehouse_quantity_after' => [
                            'Warehouse quantity after transfer cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Restaurant Quantity Before
                |--------------------------------------------------------------------------
                */

                $restaurantBefore =
                    round(
                        (float) (
                            $item->restaurant_quantity_before
                            ?? 0
                        ),
                        4
                    );


                if ($restaurantBefore < 0) {

                    throw ValidationException::withMessages([

                        'restaurant_quantity_before' => [
                            'Restaurant quantity before transfer cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Restaurant Quantity After
                |--------------------------------------------------------------------------
                */

                $restaurantAfter =
                    round(
                        (float) (
                            $item->restaurant_quantity_after
                            ?? 0
                        ),
                        4
                    );


                if ($restaurantAfter < 0) {

                    throw ValidationException::withMessages([

                        'restaurant_quantity_after' => [
                            'Restaurant quantity after transfer cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Warehouse Stock Protection
                |--------------------------------------------------------------------------
                */

                if ($quantity > $warehouseBefore) {

                    throw ValidationException::withMessages([

                        'quantity' => [
                            'Transfer quantity cannot exceed available warehouse stock.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Warehouse Snapshot Consistency
                |--------------------------------------------------------------------------
                |
                | warehouse_before - transfer_quantity = warehouse_after
                |
                */

                $expectedWarehouseAfter =
                    round(
                        $warehouseBefore
                        -
                        $quantity,
                        4
                    );


                if (
                    abs(
                        $expectedWarehouseAfter
                        -
                        $warehouseAfter
                    )
                    >
                    0.00005
                ) {

                    throw ValidationException::withMessages([

                        'warehouse_quantity_after' => [

                            sprintf(
                                'Warehouse stock snapshot mismatch. Expected quantity after transfer is %.4f.',
                                $expectedWarehouseAfter
                            ),

                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Restaurant Snapshot Consistency
                |--------------------------------------------------------------------------
                |
                | restaurant_before + transfer_quantity = restaurant_after
                |
                */

                $expectedRestaurantAfter =
                    round(
                        $restaurantBefore
                        +
                        $quantity,
                        4
                    );


                if (
                    abs(
                        $expectedRestaurantAfter
                        -
                        $restaurantAfter
                    )
                    >
                    0.00005
                ) {

                    throw ValidationException::withMessages([

                        'restaurant_quantity_after' => [

                            sprintf(
                                'Restaurant stock snapshot mismatch. Expected quantity after transfer is %.4f.',
                                $expectedRestaurantAfter
                            ),

                        ],

                    ]);
                }


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


                $item->warehouse_quantity_before =
                    $warehouseBefore;


                $item->warehouse_quantity_after =
                    $warehouseAfter;


                $item->restaurant_quantity_before =
                    $restaurantBefore;


                $item->restaurant_quantity_after =
                    $restaurantAfter;
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Transfer Item Modification
        |--------------------------------------------------------------------------
        */

        static::updating(

            function (): void {

                throw ValidationException::withMessages([

                    'stock_transfer_item' => [
                        'Stock transfer item history cannot be modified.',
                    ],

                ]);
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Transfer Item Deletion
        |--------------------------------------------------------------------------
        */

        static::deleting(

            function (): void {

                throw ValidationException::withMessages([

                    'stock_transfer_item' => [
                        'Stock transfer item history cannot be deleted.',
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


    /*
    |--------------------------------------------------------------------------
    | Stock Transfer
    |--------------------------------------------------------------------------
    */

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(
            StockTransfer::class,
            'stock_transfer_id'
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
    | Helpers
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Transfer Quantity
    |--------------------------------------------------------------------------
    */

    public function transferQuantity(): float
    {
        return round(
            (float) (
                $this->quantity
                ?? 0
            ),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Unit Cost
    |--------------------------------------------------------------------------
    */

    public function unitCost(): float
    {
        return round(
            (float) (
                $this->unit_cost
                ?? 0
            ),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Transfer Value
    |--------------------------------------------------------------------------
    */

    public function transferValue(): float
    {
        return round(
            $this->transferQuantity()
            *
            $this->unitCost(),
            4
        );
    }
}