<?php

namespace App\Services;

use App\Http\Requests\Api\AdjustWarehouseStockRequest;
use App\Models\PurchaseOrder;
use App\Models\RawMaterial;
use App\Models\RestaurantStock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /*
    |--------------------------------------------------------------------------
    | Raw Material List
    |--------------------------------------------------------------------------
    */

    public function getRawMaterials(
        array $filters = []
    ): LengthAwarePaginator {
        $query = RawMaterial::query()
            ->with([
                'warehouseStock.rawMaterial',
                'creator',
                'updater',
            ])
            ->withCount([
                'stockMovements',
                'purchaseOrderItems',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['search'])) {
            $query->search(
                trim((string) $filters['search'])
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['category'])) {
            $query->ofCategory(
                trim((string) $filters['category'])
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Unit
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['base_unit'])) {
            $query->ofUnit(
                trim((string) $filters['base_unit'])
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Active Status
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists('is_active', $filters)
            && $filters['is_active'] !== ''
            && $filters['is_active'] !== null
        ) {
            $query->where(
                'is_active',
                filter_var(
                    $filters['is_active'],
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Stock Status
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['stock_status'])) {
            $this->applyRawMaterialStockStatusFilter(
                $query,
                (string) $filters['stock_status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'id',
            'material_name',
            'category',
            'base_unit',
            'created_at',
            'updated_at',
        ];

        $sortBy = in_array(
            $filters['sort_by'] ?? '',
            $allowedSorts,
            true
        )
            ? $filters['sort_by']
            : 'id';

        $sortDirection = strtolower(
            (string) (
                $filters['sort_direction']
                ?? 'desc'
            )
        ) === 'asc'
            ? 'asc'
            : 'desc';

        $query->orderBy(
            $sortBy,
            $sortDirection
        );

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        return $query
            ->paginate(
                $this->resolvePerPage(
                    $filters['per_page'] ?? 10
                )
            )
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Warehouse Stock List
    |--------------------------------------------------------------------------
    */

    public function getWarehouseStocks(
        array $filters = []
    ): LengthAwarePaginator {
        $query = WarehouseStock::query()
            ->with([
                'rawMaterial',
                'creator',
                'updater',
            ])
            ->whereHas(
                'rawMaterial',
                function (Builder $materialQuery): void {
                    $materialQuery->whereNull('deleted_at');
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['search'])) {
            $search = trim(
                (string) $filters['search']
            );

            $query->whereHas(
                'rawMaterial',
                function (
                    Builder $materialQuery
                ) use ($search): void {
                    $materialQuery->where(
                        function (
                            Builder $builder
                        ) use ($search): void {
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
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Raw Material
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['raw_material_id'])) {
            $query->where(
                'raw_material_id',
                $filters['raw_material_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['category'])) {
            $category = trim(
                (string) $filters['category']
            );

            $query->whereHas(
                'rawMaterial',
                function (
                    Builder $materialQuery
                ) use ($category): void {
                    $materialQuery->where(
                        'category',
                        $category
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Unit
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['base_unit'])) {
            $unit = trim(
                (string) $filters['base_unit']
            );

            $query->whereHas(
                'rawMaterial',
                function (
                    Builder $materialQuery
                ) use ($unit): void {
                    $materialQuery->where(
                        'base_unit',
                        $unit
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Active Material
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists('is_active', $filters)
            && $filters['is_active'] !== ''
            && $filters['is_active'] !== null
        ) {
            $isActive = filter_var(
                $filters['is_active'],
                FILTER_VALIDATE_BOOLEAN
            );

            $query->whereHas(
                'rawMaterial',
                function (
                    Builder $materialQuery
                ) use ($isActive): void {
                    $materialQuery->where(
                        'is_active',
                        $isActive
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Stock Status
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['status'])) {
            $this->applyWarehouseStockStatusFilter(
                $query,
                (string) $filters['status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'id',
            'quantity',
            'average_unit_cost',
            'last_received_at',
            'created_at',
            'updated_at',
        ];

        $sortBy = in_array(
            $filters['sort_by'] ?? '',
            $allowedSorts,
            true
        )
            ? $filters['sort_by']
            : 'id';

        $sortDirection = strtolower(
            (string) (
                $filters['sort_direction']
                ?? 'desc'
            )
        ) === 'asc'
            ? 'asc'
            : 'desc';

        $query->orderBy(
            $sortBy,
            $sortDirection
        );

        return $query
            ->paginate(
                $this->resolvePerPage(
                    $filters['per_page'] ?? 10
                )
            )
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Stock Movement History
    |--------------------------------------------------------------------------
    */

    public function getStockMovements(
        array $filters = []
    ): LengthAwarePaginator {
        $query = StockMovement::query()
            ->with([
                'rawMaterial',
                'creator',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['search'])) {
            $search = trim(
                (string) $filters['search']
            );

            $query->where(
                function (
                    Builder $movementQuery
                ) use ($search): void {
                    $movementQuery
                        ->where(
                            'notes',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'rawMaterial',
                            function (
                                Builder $materialQuery
                            ) use ($search): void {
                                $materialQuery
                                    ->where(
                                        'material_name',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'category',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Raw Material
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['raw_material_id'])) {
            $query->where(
                'raw_material_id',
                $filters['raw_material_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Location
        |--------------------------------------------------------------------------
        */

        if (
            ! empty($filters['location'])
            && in_array(
                $filters['location'],
                StockMovement::allowedLocations(),
                true
            )
        ) {
            $query->where(
                'location',
                $filters['location']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Movement Type
        |--------------------------------------------------------------------------
        */

        if (
            ! empty($filters['movement_type'])
            && in_array(
                $filters['movement_type'],
                StockMovement::allowedMovementTypes(),
                true
            )
        ) {
            $query->where(
                'movement_type',
                $filters['movement_type']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date From
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['date_from'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['date_from']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Date To
        |--------------------------------------------------------------------------
        */

        if (! empty($filters['date_to'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['date_to']
            );
        }

        return $query
            ->latest('id')
            ->paginate(
                $this->resolvePerPage(
                    $filters['per_page'] ?? 10
                )
            )
            ->withQueryString();
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Summary
    |--------------------------------------------------------------------------
    */

    public function getInventorySummary(): array
    {
        /*
        |--------------------------------------------------------------------------
        | Important
        |--------------------------------------------------------------------------
        |
        | LEFT JOIN ব্যবহার করা হয়েছে যাতে কোনো active raw material-এর
        | warehouse_stock row না থাকলেও সেটি Out of Stock হিসেবে গণনা হয়।
        |
        */

        $summary = DB::table(
            'raw_materials as rm'
        )
            ->leftJoin(
                'warehouse_stocks as ws',
                function ($join): void {
                    $join
                        ->on(
                            'ws.raw_material_id',
                            '=',
                            'rm.id'
                        )
                        ->whereNull(
                            'ws.deleted_at'
                        );
                }
            )
            ->whereNull(
                'rm.deleted_at'
            )
            ->where(
                'rm.is_active',
                true
            )
            ->selectRaw(
                'COUNT(DISTINCT rm.id) AS total_raw_materials'
            )
            ->selectRaw(
                '
                COUNT(
                    DISTINCT CASE
                        WHEN COALESCE(ws.quantity, 0)
                            >
                            COALESCE(rm.warehouse_minimum_quantity, 0)
                        THEN rm.id
                    END
                ) AS available_count
                '
            )
            ->selectRaw(
                '
                COUNT(
                    DISTINCT CASE
                        WHEN COALESCE(ws.quantity, 0) > 0
                            AND COALESCE(rm.warehouse_minimum_quantity, 0) > 0
                            AND COALESCE(ws.quantity, 0)
                                <=
                                COALESCE(rm.warehouse_minimum_quantity, 0)
                        THEN rm.id
                    END
                ) AS limited_count
                '
            )
            ->selectRaw(
                '
                COUNT(
                    DISTINCT CASE
                        WHEN COALESCE(ws.quantity, 0) <= 0
                        THEN rm.id
                    END
                ) AS out_of_stock_count
                '
            )
            ->selectRaw(
                '
                COALESCE(
                    SUM(
                        COALESCE(ws.quantity, 0)
                        *
                        COALESCE(ws.average_unit_cost, 0)
                    ),
                    0
                ) AS total_stock_value
                '
            )
            ->first();

        $totalRawMaterials = (int) (
            $summary->total_raw_materials ?? 0
        );

        $availableCount = (int) (
            $summary->available_count ?? 0
        );

        $limitedCount = (int) (
            $summary->limited_count ?? 0
        );

        $outOfStockCount = (int) (
            $summary->out_of_stock_count ?? 0
        );

        $totalStockValue = (float) (
            $summary->total_stock_value ?? 0
        );

        $lowStockAlertCount =
            $limitedCount
            +
            $outOfStockCount;

        return [
            'total_raw_materials' =>
                $totalRawMaterials,

            'available_count' =>
                $availableCount,

            'limited_count' =>
                $limitedCount,

            'out_of_stock_count' =>
                $outOfStockCount,

            'low_stock_alert_count' =>
                $lowStockAlertCount,

            'total_stock_value' =>
                round(
                    $totalStockValue,
                    2
                ),

            'total_stock_value_formatted' =>
                $this->money(
                    $totalStockValue
                ),

            'has_low_stock_alert' =>
                $lowStockAlertCount > 0,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Options
    |--------------------------------------------------------------------------
    */

    public function getOptions(): array
    {
        $categories = RawMaterial::query()
            ->whereNotNull('category')
            ->where(
                'category',
                '!=',
                ''
            )
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values()
            ->all();

        $units = collect(
            RawMaterial::unitLabels()
        )
            ->map(
                function (
                    $label,
                    $value
                ): array {
                    return [
                        'value' => $value,
                        'label' => $label,
                    ];
                }
            )
            ->values()
            ->all();

        return [
            'categories' =>
                $categories,

            'units' =>
                $units,

            'warehouse_statuses' => [
                [
                    'value' =>
                        WarehouseStock::STATUS_AVAILABLE,

                    'label' =>
                        'Available',

                    'color' =>
                        'green',
                ],

                [
                    'value' =>
                        WarehouseStock::STATUS_LIMITED,

                    'label' =>
                        'Limited',

                    'color' =>
                        'orange',
                ],

                [
                    'value' =>
                        WarehouseStock::STATUS_OUT_OF_STOCK,

                    'label' =>
                        'Out of Stock',

                    'color' =>
                        'red',
                ],
            ],

            'adjustment_types' => [
                [
                    'value' =>
                        AdjustWarehouseStockRequest::TYPE_INCREASE,

                    'label' =>
                        'Increase Stock',
                ],

                [
                    'value' =>
                        AdjustWarehouseStockRequest::TYPE_DECREASE,

                    'label' =>
                        'Decrease Stock',
                ],
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Create Raw Material
    |--------------------------------------------------------------------------
    */

    public function createRawMaterial(
        array $data,
        User $user
    ): RawMaterial {
        $openingQuantity =
            $this->normalizeNonNegativeDecimal(
                value:
                    $data['opening_quantity']
                    ?? 0,

                field:
                    'opening_quantity',

                label:
                    'Opening quantity'
            );

        $openingUnitCost =
            $this->normalizeNonNegativeDecimal(
                value:
                    $data['opening_unit_cost']
                    ?? 0,

                field:
                    'opening_unit_cost',

                label:
                    'Opening unit cost'
            );

        $warehouseMinimumQuantity =
            $this->normalizeNonNegativeDecimal(
                value:
                    $data['warehouse_minimum_quantity']
                    ?? 0,

                field:
                    'warehouse_minimum_quantity',

                label:
                    'Warehouse minimum quantity'
            );

        $restaurantMinimumQuantity =
            $this->normalizeNonNegativeDecimal(
                value:
                    $data['restaurant_minimum_quantity']
                    ?? 0,

                field:
                    'restaurant_minimum_quantity',

                label:
                    'Restaurant minimum quantity'
            );

        $isActive =
            array_key_exists(
                'is_active',
                $data
            )
                ? $this->normalizeBoolean(
                    value:
                        $data['is_active'],

                    field:
                        'is_active'
                )
                : true;

        /*
        |--------------------------------------------------------------------------
        | Inactive Material Opening Stock Protection
        |--------------------------------------------------------------------------
        |
        | Inactive materials cannot be adjusted, received or transferred.
        | Therefore creating an inactive material with stock would immediately
        | strand that stock.
        |
        */

        if (
            ! $isActive
            &&
            $openingQuantity > 0
        ) {
            throw ValidationException::withMessages([
                'is_active' => [
                    'A raw material with opening stock must be active.',
                ],
            ]);
        }

        return DB::transaction(
            function () use (
                $data,
                $user,
                $openingQuantity,
                $openingUnitCost,
                $warehouseMinimumQuantity,
                $restaurantMinimumQuantity,
                $isActive
            ): RawMaterial {
                /*
                |--------------------------------------------------------------------------
                | Create Material
                |--------------------------------------------------------------------------
                */

                $rawMaterial = RawMaterial::create([
                    'material_name' =>
                        $data['material_name'],

                    'category' =>
                        $data['category']
                        ?? null,

                    'base_unit' =>
                        $data['base_unit'],

                    'warehouse_minimum_quantity' =>
                        $warehouseMinimumQuantity,

                    'restaurant_minimum_quantity' =>
                        $restaurantMinimumQuantity,

                    'is_active' =>
                        $isActive,

                    'created_by' =>
                        $user->id,

                    'updated_by' =>
                        $user->id,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Create Warehouse Stock
                |--------------------------------------------------------------------------
                */

                $warehouseStock = WarehouseStock::create([
                    'raw_material_id' =>
                        $rawMaterial->id,

                    'quantity' =>
                        $openingQuantity,

                    'average_unit_cost' =>
                        $openingUnitCost,

                    'last_received_at' =>
                        $openingQuantity > 0
                            ? now()
                            : null,

                    'created_by' =>
                        $user->id,

                    'updated_by' =>
                        $user->id,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Opening Stock Movement
                |--------------------------------------------------------------------------
                */

                if ($openingQuantity > 0) {
                    $this->createStockMovement([
                        'raw_material_id' =>
                            $rawMaterial->id,

                        'location' =>
                            StockMovement::LOCATION_WAREHOUSE,

                        'movement_type' =>
                            StockMovement::TYPE_OPENING_STOCK,

                        'quantity' =>
                            $openingQuantity,

                        'quantity_before' =>
                            0,

                        'quantity_after' =>
                            $openingQuantity,

                        'unit_cost' =>
                            $openingUnitCost,

                        'reference_type' =>
                            RawMaterial::class,

                        'reference_id' =>
                            $rawMaterial->id,

                        'unit' =>
                            $rawMaterial->base_unit,

                        'notes' =>
                            $data['opening_stock_notes']
                            ??
                            'Initial warehouse opening stock.',

                        'created_by' =>
                            $user->id,
                    ]);
                }

                return $rawMaterial->fresh([
                    'warehouseStock.rawMaterial',
                    'creator',
                    'updater',
                ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Update Raw Material
    |--------------------------------------------------------------------------
    */

    public function updateRawMaterial(
        RawMaterial $rawMaterial,
        array $data,
        User $user
    ): RawMaterial {
        return DB::transaction(
            function () use (
                $rawMaterial,
                $data,
                $user
            ): RawMaterial {
                /*
                |--------------------------------------------------------------------------
                | Lock Raw Material
                |--------------------------------------------------------------------------
                */

                $lockedMaterial = RawMaterial::query()
                    ->whereKey(
                        $rawMaterial->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | Normalize Requested Base Unit
                |--------------------------------------------------------------------------
                */

                $requestedBaseUnit = null;

                if (
                    array_key_exists(
                        'base_unit',
                        $data
                    )
                    &&
                    $data['base_unit'] !== null
                ) {
                    $requestedBaseUnit =
                        strtolower(
                            trim(
                                (string) $data['base_unit']
                            )
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Base Unit Change Protection
                |--------------------------------------------------------------------------
                |
                | Once stock or transaction history exists, changing the base
                | unit would make historical quantities ambiguous.
                |
                */

                if (
                    $requestedBaseUnit !== null
                    &&
                    $requestedBaseUnit
                    !==
                    strtolower(
                        trim(
                            (string) $lockedMaterial->base_unit
                        )
                    )
                ) {
                    $warehouseQuantity = round(
                        (float) (
                            WarehouseStock::withTrashed()
                                ->where(
                                    'raw_material_id',
                                    $lockedMaterial->id
                                )
                                ->value(
                                    'quantity'
                                )
                            ?? 0
                        ),
                        4
                    );

                    $restaurantQuantity = round(
                        (float) (
                            RestaurantStock::withTrashed()
                                ->where(
                                    'raw_material_id',
                                    $lockedMaterial->id
                                )
                                ->value(
                                    'quantity'
                                )
                            ?? 0
                        ),
                        4
                    );

                    $hasMovementHistory =
                        $lockedMaterial
                            ->stockMovements()
                            ->exists();

                    $hasPurchaseHistory =
                        $lockedMaterial
                            ->purchaseOrderItems()
                            ->exists();

                    $hasTransferHistory =
                        $lockedMaterial
                            ->stockTransferItems()
                            ->exists();

                    if (
                        $warehouseQuantity > 0
                        ||
                        $restaurantQuantity > 0
                        ||
                        $hasMovementHistory
                        ||
                        $hasPurchaseHistory
                        ||
                        $hasTransferHistory
                    ) {
                        throw ValidationException::withMessages([
                            'base_unit' => [
                                'Base unit cannot be changed because this material already has stock or transaction history.',
                            ],
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Prepare Update Data
                |--------------------------------------------------------------------------
                */

                $updateData = Arr::only(
                    $data,
                    [
                        'material_name',
                        'category',
                        'base_unit',
                        'warehouse_minimum_quantity',
                        'restaurant_minimum_quantity',
                        'is_active',
                    ]
                );

                /*
                |--------------------------------------------------------------------------
                | Normalize Base Unit
                |--------------------------------------------------------------------------
                */

                if (
                    array_key_exists(
                        'base_unit',
                        $updateData
                    )
                    &&
                    $updateData['base_unit'] !== null
                ) {
                    $updateData['base_unit'] =
                        $requestedBaseUnit;
                }

                /*
                |--------------------------------------------------------------------------
                | Normalize Minimum Quantities
                |--------------------------------------------------------------------------
                */

                if (
                    array_key_exists(
                        'warehouse_minimum_quantity',
                        $updateData
                    )
                ) {
                    $updateData['warehouse_minimum_quantity'] =
                        $this->normalizeNonNegativeDecimal(
                            value:
                                $updateData[
                                    'warehouse_minimum_quantity'
                                ],

                            field:
                                'warehouse_minimum_quantity',

                            label:
                                'Warehouse minimum quantity'
                        );
                }

                if (
                    array_key_exists(
                        'restaurant_minimum_quantity',
                        $updateData
                    )
                ) {
                    $updateData['restaurant_minimum_quantity'] =
                        $this->normalizeNonNegativeDecimal(
                            value:
                                $updateData[
                                    'restaurant_minimum_quantity'
                                ],

                            field:
                                'restaurant_minimum_quantity',

                            label:
                                'Restaurant minimum quantity'
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Normalize Active Status
                |--------------------------------------------------------------------------
                */

                if (
                    array_key_exists(
                        'is_active',
                        $updateData
                    )
                ) {
                    $newIsActive =
                        $this->normalizeBoolean(
                            value:
                                $updateData['is_active'],

                            field:
                                'is_active'
                        );

                    if (
                        (bool) $lockedMaterial->is_active
                        &&
                        ! $newIsActive
                    ) {
                        $this->ensureRawMaterialCanBeDeactivated(
                            $lockedMaterial
                        );
                    }

                    $updateData['is_active'] =
                        $newIsActive;
                }

                /*
                |--------------------------------------------------------------------------
                | Audit
                |--------------------------------------------------------------------------
                */

                $updateData['updated_by'] =
                    $user->id;

                $lockedMaterial->update(
                    $updateData
                );

                return $lockedMaterial->fresh([
                    'warehouseStock.rawMaterial',
                    'creator',
                    'updater',
                ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Raw Material Status
    |--------------------------------------------------------------------------
    */

    public function toggleRawMaterialStatus(
        RawMaterial $rawMaterial,
        User $user
    ): RawMaterial {
        return DB::transaction(
            function () use (
                $rawMaterial,
                $user
            ): RawMaterial {
                /*
                |--------------------------------------------------------------------------
                | Lock Raw Material
                |--------------------------------------------------------------------------
                */

                $lockedMaterial = RawMaterial::query()
                    ->whereKey(
                        $rawMaterial->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                $newIsActive =
                    ! (bool) $lockedMaterial
                        ->is_active;

                /*
                |--------------------------------------------------------------------------
                | Deactivation Protection
                |--------------------------------------------------------------------------
                */

                if (! $newIsActive) {
                    $this->ensureRawMaterialCanBeDeactivated(
                        $lockedMaterial
                    );
                }

                $lockedMaterial->update([
                    'is_active' =>
                        $newIsActive,

                    'updated_by' =>
                        $user->id,
                ]);

                return $lockedMaterial->fresh([
                    'warehouseStock.rawMaterial',
                    'creator',
                    'updater',
                ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Adjust Warehouse Stock
    |--------------------------------------------------------------------------
    */

    public function adjustWarehouseStock(
        RawMaterial $rawMaterial,
        array $data,
        User $user
    ): WarehouseStock {
        /*
        |--------------------------------------------------------------------------
        | Validate Adjustment Type
        |--------------------------------------------------------------------------
        */

        $adjustmentType =
            $data['adjustment_type'] ?? null;

        $allowedAdjustmentTypes = [
            AdjustWarehouseStockRequest::TYPE_INCREASE,
            AdjustWarehouseStockRequest::TYPE_DECREASE,
        ];

        if (
            ! in_array(
                $adjustmentType,
                $allowedAdjustmentTypes,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'adjustment_type' =>
                    'Invalid warehouse stock adjustment type.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Quantity
        |--------------------------------------------------------------------------
        */

        $adjustmentQuantity =
            $this->normalizePositiveDecimal(
                value:
                    $data['quantity']
                    ?? null,

                field:
                    'quantity',

                label:
                    'Adjustment quantity'
            );

        /*
        |--------------------------------------------------------------------------
        | Validate Unit Cost
        |--------------------------------------------------------------------------
        */

        $providedUnitCost = null;

        if (
            array_key_exists(
                'unit_cost',
                $data
            )
            &&
            $data['unit_cost'] !== null
            &&
            $data['unit_cost'] !== ''
        ) {
            $providedUnitCost =
                $this->normalizeNonNegativeDecimal(
                    value:
                        $data['unit_cost'],

                    field:
                        'unit_cost',

                    label:
                        'Unit cost'
                );
        }

        return DB::transaction(
            function () use (
                $rawMaterial,
                $data,
                $user,
                $adjustmentType,
                $adjustmentQuantity,
                $providedUnitCost
            ): WarehouseStock {
                /*
                |--------------------------------------------------------------------------
                | Lock Raw Material
                |--------------------------------------------------------------------------
                */

                $lockedMaterial = RawMaterial::query()
                    ->whereKey(
                        $rawMaterial->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | Active Material Protection
                |--------------------------------------------------------------------------
                */

                if (! $lockedMaterial->is_active) {
                    throw ValidationException::withMessages([
                        'raw_material_id' =>
                            'Inactive raw materials cannot receive stock adjustments.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Lock Warehouse Stock
                |--------------------------------------------------------------------------
                */

                $warehouseStock = WarehouseStock::withTrashed()
                    ->where(
                        'raw_material_id',
                        $lockedMaterial->id
                    )
                    ->lockForUpdate()
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Create Missing Warehouse Stock
                |--------------------------------------------------------------------------
                */

                if (! $warehouseStock) {
                    $warehouseStock = WarehouseStock::create([
                        'raw_material_id' =>
                            $lockedMaterial->id,

                        'quantity' =>
                            0,

                        'average_unit_cost' =>
                            0,

                        'last_received_at' =>
                            null,

                        'created_by' =>
                            $user->id,

                        'updated_by' =>
                            $user->id,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Restore Soft Deleted Stock
                |--------------------------------------------------------------------------
                */

                if ($warehouseStock->trashed()) {
                    $warehouseStock->restore();
                }

                /*
                |--------------------------------------------------------------------------
                | Existing Stock Values
                |--------------------------------------------------------------------------
                */

                $quantityBefore = round(
                    (float) $warehouseStock->quantity,
                    4
                );

                $oldAverageCost = round(
                    (float) $warehouseStock->average_unit_cost,
                    4
                );

                /*
                |--------------------------------------------------------------------------
                | Increase Stock
                |--------------------------------------------------------------------------
                */

                if (
                    $adjustmentType
                    ===
                    AdjustWarehouseStockRequest::TYPE_INCREASE
                ) {
                    $quantityAfter = round(
                        $quantityBefore
                        +
                        $adjustmentQuantity,
                        4
                    );

                    $newAverageCost =
                        $this->calculateWeightedAverageCost(
                            $quantityBefore,
                            $oldAverageCost,
                            $adjustmentQuantity,
                            $providedUnitCost
                        );

                    $movementType =
                        StockMovement::TYPE_WAREHOUSE_ADJUSTMENT_IN;

                    $movementUnitCost =
                        $providedUnitCost
                        ??
                        $oldAverageCost;

                    $lastReceivedAt = now();
                }

                /*
                |--------------------------------------------------------------------------
                | Decrease Stock
                |--------------------------------------------------------------------------
                */

                else {
                    if (
                        $adjustmentQuantity
                        >
                        $quantityBefore
                    ) {
                        throw ValidationException::withMessages([
                            'quantity' =>
                                "Insufficient warehouse stock. Available quantity is {$quantityBefore} {$lockedMaterial->base_unit}.",
                        ]);
                    }

                    $quantityAfter = round(
                        $quantityBefore
                        -
                        $adjustmentQuantity,
                        4
                    );

                    if ($quantityAfter < 0) {
                        throw ValidationException::withMessages([
                            'quantity' =>
                                'Warehouse stock cannot become negative.',
                        ]);
                    }

                    $newAverageCost =
                        $oldAverageCost;

                    $movementType =
                        StockMovement::TYPE_WAREHOUSE_ADJUSTMENT_OUT;

                    $movementUnitCost =
                        $oldAverageCost;

                    $lastReceivedAt =
                        $warehouseStock->last_received_at;
                }

                /*
                |--------------------------------------------------------------------------
                | Update Warehouse Stock
                |--------------------------------------------------------------------------
                */

                $warehouseStock->update([
                    'quantity' =>
                        $quantityAfter,

                    'average_unit_cost' =>
                        $newAverageCost,

                    'last_received_at' =>
                        $lastReceivedAt,

                    'updated_by' =>
                        $user->id,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Save Stock Movement
                |--------------------------------------------------------------------------
                */

                $this->createStockMovement([
                    'raw_material_id' =>
                        $lockedMaterial->id,

                    'location' =>
                        StockMovement::LOCATION_WAREHOUSE,

                    'movement_type' =>
                        $movementType,

                    'quantity' =>
                        $adjustmentQuantity,

                    'quantity_before' =>
                        $quantityBefore,

                    'quantity_after' =>
                        $quantityAfter,

                    'unit_cost' =>
                        $movementUnitCost,

                    'reference_type' =>
                        null,

                    'reference_id' =>
                        null,

                    'unit' =>
                        $lockedMaterial->base_unit,

                    'notes' =>
                        $data['notes'] ?? null,

                    'created_by' =>
                        $user->id,
                ]);

                return $warehouseStock->fresh([
                    'rawMaterial',
                    'creator',
                    'updater',
                ]);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Soft Delete Raw Material
    |--------------------------------------------------------------------------
    */

    public function deleteRawMaterial(
        RawMaterial $rawMaterial,
        User $user
    ): void {
        DB::transaction(
            function () use (
                $rawMaterial,
                $user
            ): void {
                /*
                |--------------------------------------------------------------------------
                | Lock Material
                |--------------------------------------------------------------------------
                */

                $lockedMaterial = RawMaterial::query()
                    ->whereKey(
                        $rawMaterial->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | Lock Warehouse Stock
                |--------------------------------------------------------------------------
                */

                $warehouseStock = WarehouseStock::withTrashed()
                    ->where(
                        'raw_material_id',
                        $lockedMaterial->id
                    )
                    ->lockForUpdate()
                    ->first();

                /*
                |--------------------------------------------------------------------------
                | Lock Restaurant Stock
                |--------------------------------------------------------------------------
                */

                $restaurantStock = RestaurantStock::withTrashed()
                    ->where(
                        'raw_material_id',
                        $lockedMaterial->id
                    )
                    ->lockForUpdate()
                    ->first();

                $warehouseQuantity = round(
                    (float) (
                        $warehouseStock?->quantity
                        ?? 0
                    ),
                    4
                );

                $restaurantQuantity = round(
                    (float) (
                        $restaurantStock?->quantity
                        ?? 0
                    ),
                    4
                );

                /*
                |--------------------------------------------------------------------------
                | Warehouse Stock Delete Protection
                |--------------------------------------------------------------------------
                */

                if ($warehouseQuantity > 0) {
                    throw ValidationException::withMessages([
                        'raw_material' => [
                            "This material cannot be deleted because {$warehouseQuantity} {$lockedMaterial->base_unit} is still available in the warehouse.",
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Restaurant Stock Delete Protection
                |--------------------------------------------------------------------------
                */

                if ($restaurantQuantity > 0) {
                    throw ValidationException::withMessages([
                        'raw_material' => [
                            "This material cannot be deleted because {$restaurantQuantity} {$lockedMaterial->base_unit} is still available in the restaurant.",
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Open Purchase Order Protection
                |--------------------------------------------------------------------------
                */

                if (
                    $this->hasIncompletePurchaseOrder(
                        $lockedMaterial
                    )
                ) {
                    throw ValidationException::withMessages([
                        'raw_material' => [
                            'This material cannot be deleted because it is included in an incomplete purchase order.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Deactivate Material
                |--------------------------------------------------------------------------
                */

                $lockedMaterial->update([
                    'is_active' =>
                        false,

                    'updated_by' =>
                        $user->id,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Soft Delete Warehouse Stock
                |--------------------------------------------------------------------------
                */

                if (
                    $warehouseStock
                    &&
                    ! $warehouseStock->trashed()
                ) {
                    $warehouseStock->update([
                        'updated_by' =>
                            $user->id,
                    ]);

                    $warehouseStock->delete();
                }

                /*
                |--------------------------------------------------------------------------
                | Soft Delete Restaurant Stock
                |--------------------------------------------------------------------------
                */

                if (
                    $restaurantStock
                    &&
                    ! $restaurantStock->trashed()
                ) {
                    $restaurantStock->update([
                        'updated_by' =>
                            $user->id,
                    ]);

                    $restaurantStock->delete();
                }

                /*
                |--------------------------------------------------------------------------
                | Soft Delete Raw Material
                |--------------------------------------------------------------------------
                */

                $lockedMaterial->delete();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ensure Raw Material Can Be Deactivated
    |--------------------------------------------------------------------------
    |
    | Inactive materials cannot be received, adjusted or transferred.
    | Deactivation is therefore blocked while stock or an incomplete PO exists.
    |
    */

    private function ensureRawMaterialCanBeDeactivated(
        RawMaterial $rawMaterial
    ): void {
        $warehouseStock = WarehouseStock::withTrashed()
            ->where(
                'raw_material_id',
                $rawMaterial->id
            )
            ->lockForUpdate()
            ->first();

        $restaurantStock = RestaurantStock::withTrashed()
            ->where(
                'raw_material_id',
                $rawMaterial->id
            )
            ->lockForUpdate()
            ->first();

        $warehouseQuantity = round(
            (float) (
                $warehouseStock?->quantity
                ?? 0
            ),
            4
        );

        $restaurantQuantity = round(
            (float) (
                $restaurantStock?->quantity
                ?? 0
            ),
            4
        );

        if ($warehouseQuantity > 0) {
            throw ValidationException::withMessages([
                'is_active' => [
                    "This material cannot be deactivated because {$warehouseQuantity} {$rawMaterial->base_unit} is still available in the warehouse.",
                ],
            ]);
        }

        if ($restaurantQuantity > 0) {
            throw ValidationException::withMessages([
                'is_active' => [
                    "This material cannot be deactivated because {$restaurantQuantity} {$rawMaterial->base_unit} is still available in the restaurant.",
                ],
            ]);
        }

        if (
            $this->hasIncompletePurchaseOrder(
                $rawMaterial
            )
        ) {
            throw ValidationException::withMessages([
                'is_active' => [
                    'This material cannot be deactivated because it is included in an incomplete purchase order.',
                ],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Incomplete Purchase Order Check
    |--------------------------------------------------------------------------
    */

    private function hasIncompletePurchaseOrder(
        RawMaterial $rawMaterial
    ): bool {
        return $rawMaterial
            ->purchaseOrderItems()
            ->whereRaw(
                'COALESCE(received_quantity, 0) < quantity'
            )
            ->whereHas(
                'purchaseOrder',
                function (
                    Builder $orderQuery
                ): void {
                    $orderQuery->whereIn(
                        'status',
                        [
                            PurchaseOrder::STATUS_ORDERED,
                            PurchaseOrder::STATUS_PARTIAL,
                        ]
                    );
                }
            )
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Non-Negative Decimal
    |--------------------------------------------------------------------------
    */

    private function normalizeNonNegativeDecimal(
        mixed $value,
        string $field,
        string $label,
        int $scale = 4
    ): float {
        if (
            $value === null
            ||
            (
                is_string($value)
                &&
                trim($value) === ''
            )
        ) {
            $value = 0;
        }

        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                $field => [
                    "{$label} must be a valid number.",
                ],
            ]);
        }

        $number = round(
            (float) $value,
            $scale
        );

        if ($number < 0) {
            throw ValidationException::withMessages([
                $field => [
                    "{$label} cannot be negative.",
                ],
            ]);
        }

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Positive Decimal
    |--------------------------------------------------------------------------
    */

    private function normalizePositiveDecimal(
        mixed $value,
        string $field,
        string $label,
        int $scale = 4
    ): float {
        if (
            $value === null
            ||
            (
                is_string($value)
                &&
                trim($value) === ''
            )
            ||
            ! is_numeric($value)
        ) {
            throw ValidationException::withMessages([
                $field => [
                    "{$label} must be a valid number.",
                ],
            ]);
        }

        $number = round(
            (float) $value,
            $scale
        );

        if ($number <= 0) {
            throw ValidationException::withMessages([
                $field => [
                    "{$label} must be greater than zero.",
                ],
            ]);
        }

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Boolean
    |--------------------------------------------------------------------------
    */

    private function normalizeBoolean(
        mixed $value,
        string $field
    ): bool {
        if (is_bool($value)) {
            return $value;
        }

        if (
            is_int($value)
            &&
            in_array(
                $value,
                [0, 1],
                true
            )
        ) {
            return (bool) $value;
        }

        $normalized = filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($normalized === null) {
            throw ValidationException::withMessages([
                $field => [
                    'Active status must be a valid boolean value.',
                ],
            ]);
        }

        return $normalized;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Stock Movement
    |--------------------------------------------------------------------------
    */

    private function createStockMovement(
        array $data
    ): StockMovement {
        return StockMovement::create([
            'raw_material_id' =>
                $data['raw_material_id'],

            'location' =>
                $data['location'],

            'movement_type' =>
                $data['movement_type'],

            'quantity' =>
                round(
                    (float) $data['quantity'],
                    4
                ),

            'quantity_before' =>
                round(
                    (float) $data['quantity_before'],
                    4
                ),

            'quantity_after' =>
                round(
                    (float) $data['quantity_after'],
                    4
                ),

            'unit_cost' =>
                isset($data['unit_cost'])
                &&
                $data['unit_cost'] !== null
                    ? round(
                        (float) $data['unit_cost'],
                        4
                    )
                    : null,

            'reference_type' =>
                $data['reference_type']
                ?? null,

            'reference_id' =>
                $data['reference_id']
                ?? null,

            'unit' =>
                $data['unit'],

            'notes' =>
                $data['notes']
                ?? null,

            'created_by' =>
                $data['created_by'],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Weighted Average Cost
    |--------------------------------------------------------------------------
    */

    private function calculateWeightedAverageCost(
        float $currentQuantity,
        float $currentAverageCost,
        float $addedQuantity,
        ?float $addedUnitCost
    ): float {
        /*
        |--------------------------------------------------------------------------
        | No Unit Cost Provided
        |--------------------------------------------------------------------------
        */

        if ($addedUnitCost === null) {
            return round(
                $currentAverageCost,
                4
            );
        }

        /*
        |--------------------------------------------------------------------------
        | New Quantity
        |--------------------------------------------------------------------------
        */

        $newQuantity =
            $currentQuantity
            +
            $addedQuantity;

        if ($newQuantity <= 0) {
            return 0;
        }

        /*
        |--------------------------------------------------------------------------
        | Current Stock Value
        |--------------------------------------------------------------------------
        */

        $currentStockValue =
            $currentQuantity
            *
            $currentAverageCost;

        /*
        |--------------------------------------------------------------------------
        | Added Stock Value
        |--------------------------------------------------------------------------
        */

        $addedStockValue =
            $addedQuantity
            *
            $addedUnitCost;

        /*
        |--------------------------------------------------------------------------
        | Weighted Average
        |--------------------------------------------------------------------------
        */

        return round(
            (
                $currentStockValue
                +
                $addedStockValue
            )
            /
            $newQuantity,
            4
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Raw Material Stock Status Filter
    |--------------------------------------------------------------------------
    */

    private function applyRawMaterialStockStatusFilter(
        Builder $query,
        string $status
    ): Builder {
        return match ($status) {
            /*
            |--------------------------------------------------------------------------
            | Out of Stock
            |--------------------------------------------------------------------------
            |
            | Stock row না থাকলেও Out of Stock হিসেবে গণনা হবে।
            |
            */

            WarehouseStock::STATUS_OUT_OF_STOCK =>
                $query->where(
                    function (
                        Builder $materialQuery
                    ): void {
                        $materialQuery
                            ->whereDoesntHave(
                                'warehouseStock'
                            )
                            ->orWhereHas(
                                'warehouseStock',
                                function (
                                    Builder $stockQuery
                                ): void {
                                    $stockQuery->where(
                                        'quantity',
                                        '<=',
                                        0
                                    );
                                }
                            );
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | Limited
            |--------------------------------------------------------------------------
            */

            WarehouseStock::STATUS_LIMITED =>
                $query
                    ->where(
                        'warehouse_minimum_quantity',
                        '>',
                        0
                    )
                    ->whereHas(
                        'warehouseStock',
                        function (
                            Builder $stockQuery
                        ): void {
                            $stockQuery
                                ->where(
                                    'quantity',
                                    '>',
                                    0
                                )
                                ->whereColumn(
                                    'warehouse_stocks.quantity',
                                    '<=',
                                    'raw_materials.warehouse_minimum_quantity'
                                );
                        }
                    ),

            /*
            |--------------------------------------------------------------------------
            | Available
            |--------------------------------------------------------------------------
            */

            WarehouseStock::STATUS_AVAILABLE =>
                $query->whereHas(
                    'warehouseStock',
                    function (
                        Builder $stockQuery
                    ): void {
                        $stockQuery
                            ->where(
                                'quantity',
                                '>',
                                0
                            )
                            ->whereColumn(
                                'warehouse_stocks.quantity',
                                '>',
                                'raw_materials.warehouse_minimum_quantity'
                            );
                    }
                ),

            /*
            |--------------------------------------------------------------------------
            | Unknown Status
            |--------------------------------------------------------------------------
            */

            default =>
                $query,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Warehouse Stock Status Filter
    |--------------------------------------------------------------------------
    */

    private function applyWarehouseStockStatusFilter(
        Builder $query,
        string $status
    ): Builder {
        return match ($status) {
            /*
            |--------------------------------------------------------------------------
            | Out of Stock
            |--------------------------------------------------------------------------
            */

            WarehouseStock::STATUS_OUT_OF_STOCK =>
                $query->where(
                    'quantity',
                    '<=',
                    0
                ),

            /*
            |--------------------------------------------------------------------------
            | Limited
            |--------------------------------------------------------------------------
            */

            WarehouseStock::STATUS_LIMITED =>
                $query
                    ->where(
                        'quantity',
                        '>',
                        0
                    )
                    ->whereHas(
                        'rawMaterial',
                        function (
                            Builder $materialQuery
                        ): void {
                            $materialQuery
                                ->where(
                                    'warehouse_minimum_quantity',
                                    '>',
                                    0
                                )
                                ->whereRaw(
                                    '
                                    warehouse_stocks.quantity
                                    <=
                                    raw_materials.warehouse_minimum_quantity
                                    '
                                );
                        }
                    ),

            /*
            |--------------------------------------------------------------------------
            | Available
            |--------------------------------------------------------------------------
            */

            WarehouseStock::STATUS_AVAILABLE =>
                $query
                    ->where(
                        'quantity',
                        '>',
                        0
                    )
                    ->whereHas(
                        'rawMaterial',
                        function (
                            Builder $materialQuery
                        ): void {
                            $materialQuery->whereRaw(
                                '
                                warehouse_stocks.quantity
                                >
                                raw_materials.warehouse_minimum_quantity
                                '
                            );
                        }
                    ),

            /*
            |--------------------------------------------------------------------------
            | Unknown Status
            |--------------------------------------------------------------------------
            */

            default =>
                $query,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination Limit
    |--------------------------------------------------------------------------
    */

    private function resolvePerPage(
        mixed $perPage
    ): int {
        $perPage = (int) $perPage;

        return max(
            1,
            min(
                $perPage,
                100
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Money Formatter
    |--------------------------------------------------------------------------
    */

    private function money(
        mixed $amount
    ): string {
        return '৳ '
            .
            number_format(
                (float) $amount,
                2
            );
    }
}