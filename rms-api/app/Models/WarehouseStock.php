<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;


class WarehouseStock extends Model
{
    use HasFactory;
    use SoftDeletes;


    /*
    |--------------------------------------------------------------------------
    | Stock Status Constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_AVAILABLE =
        'available';

    public const STATUS_LIMITED =
        'limited';

    public const STATUS_OUT_OF_STOCK =
        'out_of_stock';


    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'raw_material_id',

        'quantity',

        'average_unit_cost',

        'last_received_at',

        'created_by',

        'updated_by',

    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'raw_material_id' =>
            'integer',

        'quantity' =>
            'decimal:4',

        'average_unit_cost' =>
            'decimal:4',

        'last_received_at' =>
            'datetime',

        'created_by' =>
            'integer',

        'updated_by' =>
            'integer',

        'deleted_at' =>
            'datetime',

    ];


    /*
    |--------------------------------------------------------------------------
    | Appended Attributes
    |--------------------------------------------------------------------------
    */

    protected $appends = [

        'status',

        'status_label',

        'status_color',

        'quantity_formatted',

    ];


    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    |
    | WarehouseStock represents the current stock snapshot.
    |
    | Unlike StockMovement history, this model MUST remain mutable.
    |
    */

    protected static function booted(): void
    {
        static::saving(

            function (
                WarehouseStock $warehouseStock
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Raw Material Protection
                |--------------------------------------------------------------------------
                */

                $rawMaterialId =
                    (int) (
                        $warehouseStock->raw_material_id
                        ?? 0
                    );


                if ($rawMaterialId <= 0) {

                    throw ValidationException::withMessages([

                        'raw_material_id' => [
                            'A valid raw material is required for warehouse stock.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Quantity Protection
                |--------------------------------------------------------------------------
                */

                $quantity =
                    round(
                        (float) (
                            $warehouseStock->quantity
                            ?? 0
                        ),
                        4
                    );


                if ($quantity < 0) {

                    throw ValidationException::withMessages([

                        'quantity' => [
                            'Warehouse stock quantity cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Average Unit Cost Protection
                |--------------------------------------------------------------------------
                */

                $averageUnitCost =
                    round(
                        (float) (
                            $warehouseStock->average_unit_cost
                            ?? 0
                        ),
                        4
                    );


                if ($averageUnitCost < 0) {

                    throw ValidationException::withMessages([

                        'average_unit_cost' => [
                            'Average unit cost cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Store Normalized Values
                |--------------------------------------------------------------------------
                */

                $warehouseStock->quantity =
                    $quantity;


                $warehouseStock->average_unit_cost =
                    $averageUnitCost;
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
    | Creator
    |--------------------------------------------------------------------------
    */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Updater
    |--------------------------------------------------------------------------
    */

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Status Accessors
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Status
    |--------------------------------------------------------------------------
    */

    public function getStatusAttribute(): string
    {
        $quantity =
            $this->currentQuantity();


        if ($quantity <= 0) {

            return self::STATUS_OUT_OF_STOCK;
        }


        $minimumQuantity =
            $this->minimumQuantity();


        if (
            $minimumQuantity > 0
            &&
            $quantity <= $minimumQuantity
        ) {

            return self::STATUS_LIMITED;
        }


        return self::STATUS_AVAILABLE;
    }


    /*
    |--------------------------------------------------------------------------
    | Status Label
    |--------------------------------------------------------------------------
    */

    public function getStatusLabelAttribute(): string
    {
        return match (
            $this->status
        ) {

            self::STATUS_AVAILABLE =>
                'Available',

            self::STATUS_LIMITED =>
                'Limited',

            self::STATUS_OUT_OF_STOCK =>
                'Out of Stock',

            default =>
                'Unknown',

        };
    }


    /*
    |--------------------------------------------------------------------------
    | Status Color
    |--------------------------------------------------------------------------
    */

    public function getStatusColorAttribute(): string
    {
        return match (
            $this->status
        ) {

            self::STATUS_AVAILABLE =>
                'green',

            self::STATUS_LIMITED =>
                'orange',

            self::STATUS_OUT_OF_STOCK =>
                'red',

            default =>
                'gray',

        };
    }


    /*
    |--------------------------------------------------------------------------
    | Formatted Quantity
    |--------------------------------------------------------------------------
    */

    public function getQuantityFormattedAttribute(): string
    {
        $quantity =
            number_format(
                $this->currentQuantity(),
                4,
                '.',
                ''
            );


        $quantity =
            rtrim(
                rtrim(
                    $quantity,
                    '0'
                ),
                '.'
            );


        $unit =
            $this->baseUnit();


        return trim(
            "{$quantity} {$unit}"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Quantity Helpers
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Current Quantity
    |--------------------------------------------------------------------------
    */

    public function currentQuantity(): float
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
    | Minimum Quantity
    |--------------------------------------------------------------------------
    */

    public function minimumQuantity(): float
    {
        $rawMaterial =
            $this->getRawMaterialForHelpers();


        return round(
            (float) (
                $rawMaterial
                    ?->warehouse_minimum_quantity
                ??
                0
            ),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Base Unit
    |--------------------------------------------------------------------------
    */

    public function baseUnit(): string
    {
        $rawMaterial =
            $this->getRawMaterialForHelpers();


        return trim(
            (string) (
                $rawMaterial
                    ?->base_unit
                ??
                ''
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Has Stock
    |--------------------------------------------------------------------------
    */

    public function hasStock(): bool
    {
        return $this->currentQuantity() > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Can Deduct
    |--------------------------------------------------------------------------
    */

    public function canDeduct(
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


        return
            $this->currentQuantity()
            >=
            $quantity;
    }


    /*
    |--------------------------------------------------------------------------
    | Quantity After Deduction
    |--------------------------------------------------------------------------
    */

    public function quantityAfterDeduction(
        float $quantity
    ): float {

        $quantity =
            round(
                $quantity,
                4
            );


        if ($quantity <= 0) {

            throw ValidationException::withMessages([

                'quantity' => [
                    'Deduction quantity must be greater than zero.',
                ],

            ]);
        }


        if (
            ! $this->canDeduct(
                $quantity
            )
        ) {

            throw ValidationException::withMessages([

                'quantity' => [
                    'Insufficient warehouse stock.',
                ],

            ]);
        }


        return round(
            $this->currentQuantity()
            -
            $quantity,
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Quantity After Addition
    |--------------------------------------------------------------------------
    */

    public function quantityAfterAddition(
        float $quantity
    ): float {

        $quantity =
            round(
                $quantity,
                4
            );


        if ($quantity <= 0) {

            throw ValidationException::withMessages([

                'quantity' => [
                    'Addition quantity must be greater than zero.',
                ],

            ]);
        }


        return round(
            $this->currentQuantity()
            +
            $quantity,
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Cost Helpers
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Average Unit Cost
    |--------------------------------------------------------------------------
    */

    public function averageUnitCost(): float
    {
        return round(
            (float) (
                $this->average_unit_cost
                ?? 0
            ),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Stock Value
    |--------------------------------------------------------------------------
    */

    public function stockValue(): float
    {
        return round(
            $this->currentQuantity()
            *
            $this->averageUnitCost(),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public static function allowedStatuses(): array
    {
        return [

            self::STATUS_AVAILABLE,

            self::STATUS_LIMITED,

            self::STATUS_OUT_OF_STOCK,

        ];
    }


    public function isAvailable(): bool
    {
        return
            $this->status
            ===
            self::STATUS_AVAILABLE;
    }


    public function isLimited(): bool
    {
        return
            $this->status
            ===
            self::STATUS_LIMITED;
    }


    public function isOutOfStock(): bool
    {
        return
            $this->status
            ===
            self::STATUS_OUT_OF_STOCK;
    }


    /*
    |--------------------------------------------------------------------------
    | Internal Helpers
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Raw Material For Helpers
    |--------------------------------------------------------------------------
    |
    | Avoid unnecessary duplicate queries when the rawMaterial relationship
    | has already been eager loaded.
    |
    */

    private function getRawMaterialForHelpers(): ?RawMaterial
    {
        if (
            $this->relationLoaded(
                'rawMaterial'
            )
        ) {

            return $this->rawMaterial;
        }


        $this->loadMissing(
            'rawMaterial'
        );


        return $this->rawMaterial;
    }
}