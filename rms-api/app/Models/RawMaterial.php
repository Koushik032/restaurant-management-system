<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;


class RawMaterial extends Model
{
    use HasFactory;
    use SoftDeletes;


    /*
    |--------------------------------------------------------------------------
    | Unit Constants
    |--------------------------------------------------------------------------
    */

    public const UNIT_KILOGRAM =
        'kg';

    public const UNIT_GRAM =
        'gram';

    public const UNIT_LITRE =
        'litre';

    public const UNIT_MILLILITRE =
        'ml';

    public const UNIT_PIECE =
        'pcs';

    public const UNIT_PACKET =
        'packet';

    public const UNIT_BOTTLE =
        'bottle';

    public const UNIT_BOX =
        'box';

    public const UNIT_BAG =
        'bag';

    public const UNIT_CAN =
        'can';

    public const UNIT_JAR =
        'jar';

    public const UNIT_DOZEN =
        'dozen';

    public const UNIT_TRAY =
        'tray';

    public const UNIT_SLICE =
        'slice';


    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'material_name',

        'category',

        'base_unit',

        'warehouse_minimum_quantity',

        'restaurant_minimum_quantity',

        'is_active',

        'created_by',

        'updated_by',

    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'warehouse_minimum_quantity' =>
            'decimal:4',

        'restaurant_minimum_quantity' =>
            'decimal:4',

        'is_active' =>
            'boolean',

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
        'base_unit_label',
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
                RawMaterial $rawMaterial
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Material Name
                |--------------------------------------------------------------------------
                */

                $materialName =
                    trim(
                        (string) (
                            $rawMaterial->material_name
                            ?? ''
                        )
                    );


                if ($materialName === '') {

                    throw ValidationException::withMessages([

                        'material_name' => [
                            'Raw material name is required.',
                        ],

                    ]);
                }


                $rawMaterial->material_name =
                    $materialName;


                /*
                |--------------------------------------------------------------------------
                | Category
                |--------------------------------------------------------------------------
                */

                if (
                    $rawMaterial->category
                    !==
                    null
                ) {

                    $category =
                        trim(
                            (string) $rawMaterial
                                ->category
                        );


                    $rawMaterial->category =
                        $category !== ''
                            ? $category
                            : null;
                }


                /*
                |--------------------------------------------------------------------------
                | Base Unit
                |--------------------------------------------------------------------------
                */

                $baseUnit =
                    strtolower(
                        trim(
                            (string) (
                                $rawMaterial->base_unit
                                ?? ''
                            )
                        )
                    );


                if (
                    ! in_array(
                        $baseUnit,
                        self::allowedUnits(),
                        true
                    )
                ) {

                    throw ValidationException::withMessages([

                        'base_unit' => [
                            'The selected base unit is invalid.',
                        ],

                    ]);
                }


                $rawMaterial->base_unit =
                    $baseUnit;


                /*
                |--------------------------------------------------------------------------
                | Warehouse Minimum Quantity
                |--------------------------------------------------------------------------
                */

                $warehouseMinimum =
                    round(
                        (float) (
                            $rawMaterial
                                ->warehouse_minimum_quantity
                            ?? 0
                        ),
                        4
                    );


                if ($warehouseMinimum < 0) {

                    throw ValidationException::withMessages([

                        'warehouse_minimum_quantity' => [
                            'Warehouse minimum quantity cannot be negative.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Restaurant Minimum Quantity
                |--------------------------------------------------------------------------
                */

                $restaurantMinimum =
                    round(
                        (float) (
                            $rawMaterial
                                ->restaurant_minimum_quantity
                            ?? 0
                        ),
                        4
                    );


                if ($restaurantMinimum < 0) {

                    throw ValidationException::withMessages([

                        'restaurant_minimum_quantity' => [
                            'Restaurant minimum quantity cannot be negative.',
                        ],

                    ]);
                }
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
    | Warehouse Stock
    |--------------------------------------------------------------------------
    */

    public function warehouseStock(): HasOne
    {
        return $this->hasOne(
            WarehouseStock::class,
            'raw_material_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurant Stock
    |--------------------------------------------------------------------------
    */

    public function restaurantStock(): HasOne
    {
        return $this->hasOne(
            RestaurantStock::class,
            'raw_material_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Stock Movements
    |--------------------------------------------------------------------------
    */

    public function stockMovements(): HasMany
    {
        return $this->hasMany(
            StockMovement::class,
            'raw_material_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Stock Transfer Items
    |--------------------------------------------------------------------------
    */

    public function stockTransferItems(): HasMany
    {
        return $this->hasMany(
            StockTransferItem::class,
            'raw_material_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Order Items
    |--------------------------------------------------------------------------
    */

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderItem::class,
            'raw_material_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Receipt Item History
    |--------------------------------------------------------------------------
    */

    public function purchaseReceiptItems(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderReceiptItem::class,
            'raw_material_id'
        );
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
    | Query Scopes
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Active
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {

        return $query->where(
            'is_active',
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Inactive
    |--------------------------------------------------------------------------
    */

    public function scopeInactive(
        Builder $query
    ): Builder {

        return $query->where(
            'is_active',
            false
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {

        $search =
            trim(
                (string) $search
            );


        if ($search === '') {
            return $query;
        }


        return $query->where(

            function (
                Builder $builder
            ) use (
                $search
            ): void {

                $builder

                    ->where(
                        'material_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'category',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'base_unit',
                        'like',
                        "%{$search}%"
                    );
            }

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Category
    |--------------------------------------------------------------------------
    */

    public function scopeOfCategory(
        Builder $query,
        ?string $category
    ): Builder {

        $category =
            trim(
                (string) $category
            );


        if ($category === '') {
            return $query;
        }


        return $query->where(
            'category',
            $category
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Unit
    |--------------------------------------------------------------------------
    */

    public function scopeOfUnit(
        Builder $query,
        ?string $unit
    ): Builder {

        $unit =
            strtolower(
                trim(
                    (string) $unit
                )
            );


        if ($unit === '') {
            return $query;
        }


        return $query->where(
            'base_unit',
            $unit
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Base Unit Label
    |--------------------------------------------------------------------------
    */

    public function getBaseUnitLabelAttribute(): string
    {
        return self::unitLabels()[
            $this->base_unit
        ]
        ??
        ucwords(
            str_replace(
                '_',
                ' ',
                (string) $this->base_unit
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Unit Helpers
    |--------------------------------------------------------------------------
    */

    public static function allowedUnits(): array
    {
        return array_keys(
            self::unitLabels()
        );
    }


    public static function unitLabels(): array
    {
        return [

            self::UNIT_KILOGRAM =>
                'Kilogram (kg)',

            self::UNIT_GRAM =>
                'Gram',

            self::UNIT_LITRE =>
                'Litre',

            self::UNIT_MILLILITRE =>
                'Millilitre (ml)',

            self::UNIT_PIECE =>
                'Pieces',

            self::UNIT_PACKET =>
                'Packet',

            self::UNIT_BOTTLE =>
                'Bottle',

            self::UNIT_BOX =>
                'Box',

            self::UNIT_BAG =>
                'Bag',

            self::UNIT_CAN =>
                'Can',

            self::UNIT_JAR =>
                'Jar',

            self::UNIT_DOZEN =>
                'Dozen',

            self::UNIT_TRAY =>
                'Tray',

            self::UNIT_SLICE =>
                'Slice',

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Warehouse Helpers
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Has Warehouse Stock
    |--------------------------------------------------------------------------
    */

    public function hasWarehouseStock(): bool
    {
        if (
            $this->relationLoaded(
                'warehouseStock'
            )
        ) {

            return $this
                ->warehouseStock
                !==
                null;
        }


        return $this
            ->warehouseStock()
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Warehouse Quantity
    |--------------------------------------------------------------------------
    */

    public function warehouseQuantity(): float
    {
        if (
            $this->relationLoaded(
                'warehouseStock'
            )
        ) {

            return round(
                (float) (
                    $this
                        ->warehouseStock
                        ?->quantity
                    ??
                    0
                ),
                4
            );
        }


        return round(
            (float) (
                $this
                    ->warehouseStock()
                    ->value(
                        'quantity'
                    )
                ??
                0
            ),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Warehouse Minimum Quantity
    |--------------------------------------------------------------------------
    */

    public function warehouseMinimumQuantity(): float
    {
        return round(
            (float) (
                $this
                    ->warehouse_minimum_quantity
                ??
                0
            ),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurant Helpers
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Has Restaurant Stock
    |--------------------------------------------------------------------------
    */

    public function hasRestaurantStock(): bool
    {
        if (
            $this->relationLoaded(
                'restaurantStock'
            )
        ) {

            return $this
                ->restaurantStock
                !==
                null;
        }


        return $this
            ->restaurantStock()
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurant Quantity
    |--------------------------------------------------------------------------
    */

    public function restaurantQuantity(): float
    {
        if (
            $this->relationLoaded(
                'restaurantStock'
            )
        ) {

            return round(
                (float) (
                    $this
                        ->restaurantStock
                        ?->quantity
                    ??
                    0
                ),
                4
            );
        }


        return round(
            (float) (
                $this
                    ->restaurantStock()
                    ->value(
                        'quantity'
                    )
                ??
                0
            ),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurant Minimum Quantity
    |--------------------------------------------------------------------------
    */

    public function restaurantMinimumQuantity(): float
    {
        return round(
            (float) (
                $this
                    ->restaurant_minimum_quantity
                ??
                0
            ),
            4
        );
    }
}