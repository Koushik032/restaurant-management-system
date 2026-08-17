<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Validation\ValidationException;


class StockMovement extends Model
{
    use HasFactory;


    /*
    |--------------------------------------------------------------------------
    | Location Constants
    |--------------------------------------------------------------------------
    */

    public const LOCATION_WAREHOUSE =
        'warehouse';

    public const LOCATION_RESTAURANT =
        'restaurant';


    /*
    |--------------------------------------------------------------------------
    | Movement Type Constants
    |--------------------------------------------------------------------------
    */

    public const TYPE_OPENING_STOCK =
        'opening_stock';

    public const TYPE_PURCHASE_RECEIVE =
        'purchase_receive';

    public const TYPE_WAREHOUSE_ADJUSTMENT_IN =
        'warehouse_adjustment_in';

    public const TYPE_WAREHOUSE_ADJUSTMENT_OUT =
        'warehouse_adjustment_out';

    public const TYPE_TRANSFER_OUT =
        'transfer_out';

    public const TYPE_TRANSFER_IN =
        'transfer_in';

    public const TYPE_RESTAURANT_ADJUSTMENT_IN =
        'restaurant_adjustment_in';

    public const TYPE_RESTAURANT_ADJUSTMENT_OUT =
        'restaurant_adjustment_out';
    public const TYPE_RECIPE_CONSUMPTION =
        'recipe_consumption';

    /*
    |--------------------------------------------------------------------------
    | Direction Constants
    |--------------------------------------------------------------------------
    */

    public const DIRECTION_IN =
        'in';

    public const DIRECTION_OUT =
        'out';


    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'raw_material_id',

        'location',

        'movement_type',

        'quantity',

        'quantity_before',

        'quantity_after',

        'unit_cost',

        'reference_type',

        'reference_id',

        'unit',

        'notes',

        'created_by',

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

        'quantity_before' =>
            'decimal:4',

        'quantity_after' =>
            'decimal:4',

        'unit_cost' =>
            'decimal:4',

        'reference_id' =>
            'integer',

        'created_by' =>
            'integer',

    ];


    /*
    |--------------------------------------------------------------------------
    | Appended Attributes
    |--------------------------------------------------------------------------
    */

    protected $appends = [

        'direction',

        'movement_type_label',

        'location_label',

        'quantity_formatted',

    ];


    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    |
    | Stock movement is an audit/history record.
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
        | Validate New Stock Movement
        |--------------------------------------------------------------------------
        */

        static::creating(

            function (
                StockMovement $movement
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Raw Material
                |--------------------------------------------------------------------------
                */

                $rawMaterialId =
                    (int) (
                        $movement->raw_material_id
                        ?? 0
                    );


                if ($rawMaterialId <= 0) {

                    throw ValidationException::withMessages([

                        'raw_material_id' => [
                            'A valid raw material is required for stock movement.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Location
                |--------------------------------------------------------------------------
                */

                $location =
                    strtolower(
                        trim(
                            (string) (
                                $movement->location
                                ?? ''
                            )
                        )
                    );


                if (
                    ! in_array(
                        $location,
                        self::allowedLocations(),
                        true
                    )
                ) {

                    throw ValidationException::withMessages([

                        'location' => [
                            'The selected stock location is invalid.',
                        ],

                    ]);
                }


                $movement->location =
                    $location;


                /*
                |--------------------------------------------------------------------------
                | Movement Type
                |--------------------------------------------------------------------------
                */

                $movementType =
                    strtolower(
                        trim(
                            (string) (
                                $movement->movement_type
                                ?? ''
                            )
                        )
                    );


                if (
                    ! in_array(
                        $movementType,
                        self::allowedMovementTypes(),
                        true
                    )
                ) {

                    throw ValidationException::withMessages([

                        'movement_type' => [
                            'The selected stock movement type is invalid.',
                        ],

                    ]);
                }


                $movement->movement_type =
                    $movementType;


                /*
                |--------------------------------------------------------------------------
                | Unit
                |--------------------------------------------------------------------------
                */

                $unit =
                    strtolower(
                        trim(
                            (string) (
                                $movement->unit
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
                            'The selected stock movement unit is invalid.',
                        ],

                    ]);
                }


                $movement->unit =
                    $unit;


                /*
                |--------------------------------------------------------------------------
                | Movement Quantity
                |--------------------------------------------------------------------------
                */

                $quantity =
                    round(
                        (float) (
                            $movement->quantity
                            ?? 0
                        ),
                        4
                    );


                if ($quantity <= 0) {

                    throw ValidationException::withMessages([

                        'quantity' => [
                            'Stock movement quantity must be greater than zero.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Quantity Before
                |--------------------------------------------------------------------------
                */

                $quantityBefore =
                    round(
                        (float) (
                            $movement->quantity_before
                            ?? 0
                        ),
                        4
                    );


                if ($quantityBefore < 0) {

                    throw ValidationException::withMessages([

                        'quantity_before' => [
                            'Previous stock quantity cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Quantity After
                |--------------------------------------------------------------------------
                */

                $quantityAfter =
                    round(
                        (float) (
                            $movement->quantity_after
                            ?? 0
                        ),
                        4
                    );


                if ($quantityAfter < 0) {

                    throw ValidationException::withMessages([

                        'quantity_after' => [
                            'Updated stock quantity cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Quantity Consistency
                |--------------------------------------------------------------------------
                |
                | Stock IN:
                |
                | quantity_before + quantity = quantity_after
                |
                | Stock OUT:
                |
                | quantity_before - quantity = quantity_after
                |
                */

                $expectedQuantityAfter =
                    self::calculateExpectedQuantityAfter(
                        movementType:
                            $movementType,

                        quantityBefore:
                            $quantityBefore,

                        quantity:
                            $quantity
                    );


                if ($expectedQuantityAfter < 0) {

                    throw ValidationException::withMessages([

                        'quantity' => [
                            'Stock movement would result in negative stock.',
                        ],

                    ]);
                }


                if (
                    abs(
                        $expectedQuantityAfter
                        -
                        $quantityAfter
                    )
                    >
                    0.00005
                ) {

                    throw ValidationException::withMessages([

                        'quantity_after' => [

                            sprintf(
                                'Stock quantity mismatch. Expected quantity after movement is %.4f.',
                                $expectedQuantityAfter
                            ),

                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Unit Cost
                |--------------------------------------------------------------------------
                */

                if (
                    $movement->unit_cost
                    !==
                    null
                ) {

                    $unitCost =
                        round(
                            (float) $movement
                                ->unit_cost,
                            4
                        );


                    if ($unitCost < 0) {

                        throw ValidationException::withMessages([

                            'unit_cost' => [
                                'Stock movement unit cost cannot be negative.',
                            ],

                        ]);
                    }


                    $movement->unit_cost =
                        $unitCost;
                }


                /*
                |--------------------------------------------------------------------------
                | Reference Integrity
                |--------------------------------------------------------------------------
                |
                | Both reference_type and reference_id must either exist
                | together or both remain null.
                |
                */

                $referenceType =
                    trim(
                        (string) (
                            $movement->reference_type
                            ?? ''
                        )
                    );


                $referenceId =
                    (int) (
                        $movement->reference_id
                        ?? 0
                    );


                $hasReferenceType =
                    $referenceType !== '';


                $hasReferenceId =
                    $referenceId > 0;


                if (
                    $hasReferenceType
                    !==
                    $hasReferenceId
                ) {

                    throw ValidationException::withMessages([

                        'reference' => [
                            'Stock movement reference type and reference ID must be provided together.',
                        ],

                    ]);
                }


                if ($hasReferenceType) {

                    $movement->reference_type =
                        $referenceType;

                    $movement->reference_id =
                        $referenceId;

                } else {

                    $movement->reference_type =
                        null;

                    $movement->reference_id =
                        null;
                }


                /*
                |--------------------------------------------------------------------------
                | Normalize Notes
                |--------------------------------------------------------------------------
                */

                if ($movement->notes !== null) {

                    $notes =
                        trim(
                            (string) $movement->notes
                        );


                    $movement->notes =
                        $notes !== ''
                            ? $notes
                            : null;
                }


                /*
                |--------------------------------------------------------------------------
                | Store Normalized Quantities
                |--------------------------------------------------------------------------
                */

                $movement->quantity =
                    $quantity;


                $movement->quantity_before =
                    $quantityBefore;


                $movement->quantity_after =
                    $quantityAfter;
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Stock Movement Modification
        |--------------------------------------------------------------------------
        */

        static::updating(

            function (): void {

                throw ValidationException::withMessages([

                    'stock_movement' => [
                        'Stock movement history cannot be modified.',
                    ],

                ]);
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Stock Movement Deletion
        |--------------------------------------------------------------------------
        */

        static::deleting(

            function (): void {

                throw ValidationException::withMessages([

                    'stock_movement' => [
                        'Stock movement history cannot be deleted.',
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
    | Reference
    |--------------------------------------------------------------------------
    |
    | Supports references such as:
    |
    | PurchaseOrderReceipt
    | StockTransfer
    | Other future stock transaction records
    |
    */

    public function reference(): MorphTo
    {
        return $this->morphTo(
            'reference'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Warehouse
    |--------------------------------------------------------------------------
    */

    public function scopeWarehouse(
        Builder $query
    ): Builder {

        return $query->where(
            'location',
            self::LOCATION_WAREHOUSE
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurant
    |--------------------------------------------------------------------------
    */

    public function scopeRestaurant(
        Builder $query
    ): Builder {

        return $query->where(
            'location',
            self::LOCATION_RESTAURANT
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Location
    |--------------------------------------------------------------------------
    */

    public function scopeOfLocation(
        Builder $query,
        ?string $location
    ): Builder {

        $location =
            strtolower(
                trim(
                    (string) $location
                )
            );


        if ($location === '') {
            return $query;
        }


        return $query->where(
            'location',
            $location
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Raw Material
    |--------------------------------------------------------------------------
    */

    public function scopeOfMaterial(
        Builder $query,
        int|string|null $materialId
    ): Builder {

        $materialId =
            (int) $materialId;


        if ($materialId <= 0) {
            return $query;
        }


        return $query->where(
            'raw_material_id',
            $materialId
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Movement Type
    |--------------------------------------------------------------------------
    */

    public function scopeOfType(
        Builder $query,
        ?string $movementType
    ): Builder {

        $movementType =
            strtolower(
                trim(
                    (string) $movementType
                )
            );


        if ($movementType === '') {
            return $query;
        }


        return $query->where(
            'movement_type',
            $movementType
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Direction
    |--------------------------------------------------------------------------
    */

    public function getDirectionAttribute(): string
    {
        if ($this->isStockOut()) {

            return self::DIRECTION_OUT;
        }


        if ($this->isStockIn()) {

            return self::DIRECTION_IN;
        }


        return '';
    }


    /*
    |--------------------------------------------------------------------------
    | Movement Type Label
    |--------------------------------------------------------------------------
    */

    public function getMovementTypeLabelAttribute(): string
    {
        return match (
            $this->movement_type
        ) {

            self::TYPE_OPENING_STOCK =>
                'Opening Stock',

            self::TYPE_PURCHASE_RECEIVE =>
                'Purchase Receive',

            self::TYPE_WAREHOUSE_ADJUSTMENT_IN =>
                'Warehouse Adjustment In',

            self::TYPE_WAREHOUSE_ADJUSTMENT_OUT =>
                'Warehouse Adjustment Out',

            self::TYPE_TRANSFER_OUT =>
                'Transfer to Restaurant',

            self::TYPE_TRANSFER_IN =>
                'Transfer Received',

            self::TYPE_RESTAURANT_ADJUSTMENT_IN =>
                'Restaurant Adjustment In',

            self::TYPE_RESTAURANT_ADJUSTMENT_OUT =>
                'Restaurant Adjustment Out',

            self::TYPE_RECIPE_CONSUMPTION =>
                'Recipe Consumption',

            default =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        (string) $this
                            ->movement_type
                    )
                ),

        };
    }


    /*
    |--------------------------------------------------------------------------
    | Location Label
    |--------------------------------------------------------------------------
    */

    public function getLocationLabelAttribute(): string
    {
        return match (
            $this->location
        ) {

            self::LOCATION_WAREHOUSE =>
                'Warehouse',

            self::LOCATION_RESTAURANT =>
                'Restaurant',

            default =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        (string) $this
                            ->location
                    )
                ),

        };
    }


    /*
    |--------------------------------------------------------------------------
    | Quantity Formatted
    |--------------------------------------------------------------------------
    */

    public function getQuantityFormattedAttribute(): string
    {
        $quantity =
            number_format(
                (float) $this->quantity,
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


        return trim(
            "{$quantity} {$this->unit}"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Allowed Locations
    |--------------------------------------------------------------------------
    */

    public static function allowedLocations(): array
    {
        return [

            self::LOCATION_WAREHOUSE,

            self::LOCATION_RESTAURANT,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Allowed Movement Types
    |--------------------------------------------------------------------------
    */

    public static function allowedMovementTypes(): array
    {
        return [

            self::TYPE_OPENING_STOCK,

            self::TYPE_PURCHASE_RECEIVE,

            self::TYPE_WAREHOUSE_ADJUSTMENT_IN,

            self::TYPE_WAREHOUSE_ADJUSTMENT_OUT,

            self::TYPE_TRANSFER_OUT,

            self::TYPE_TRANSFER_IN,

            self::TYPE_RESTAURANT_ADJUSTMENT_IN,

            self::TYPE_RESTAURANT_ADJUSTMENT_OUT,

            self::TYPE_RECIPE_CONSUMPTION,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Stock In Types
    |--------------------------------------------------------------------------
    */

    public static function stockInTypes(): array
    {
        return [

            self::TYPE_OPENING_STOCK,

            self::TYPE_PURCHASE_RECEIVE,

            self::TYPE_WAREHOUSE_ADJUSTMENT_IN,

            self::TYPE_TRANSFER_IN,

            self::TYPE_RESTAURANT_ADJUSTMENT_IN,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Stock Out Types
    |--------------------------------------------------------------------------
    */

    public static function stockOutTypes(): array
    {
        return [
            self::TYPE_WAREHOUSE_ADJUSTMENT_OUT,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_RESTAURANT_ADJUSTMENT_OUT,
            self::TYPE_RECIPE_CONSUMPTION,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Is Stock In
    |--------------------------------------------------------------------------
    */

    public function isStockIn(): bool
    {
        return in_array(
            $this->movement_type,
            self::stockInTypes(),
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Is Stock Out
    |--------------------------------------------------------------------------
    */

    public function isStockOut(): bool
    {
        return in_array(
            $this->movement_type,
            self::stockOutTypes(),
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Calculate Expected Quantity After
    |--------------------------------------------------------------------------
    */

    public static function calculateExpectedQuantityAfter(
        string $movementType,
        float $quantityBefore,
        float $quantity
    ): float {

        $quantityBefore =
            round(
                $quantityBefore,
                4
            );


        $quantity =
            round(
                $quantity,
                4
            );


        if (
            in_array(
                $movementType,
                self::stockInTypes(),
                true
            )
        ) {

            return round(
                $quantityBefore
                +
                $quantity,
                4
            );
        }


        if (
            in_array(
                $movementType,
                self::stockOutTypes(),
                true
            )
        ) {

            return round(
                $quantityBefore
                -
                $quantity,
                4
            );
        }


        throw ValidationException::withMessages([

            'movement_type' => [
                'The stock movement type does not have a valid direction.',
            ],

        ]);
    }
}