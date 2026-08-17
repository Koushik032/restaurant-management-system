<?php

namespace App\Services;

use App\Models\RawMaterial;
use App\Models\RestaurantStock;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


class StockTransferService
{
    /*
    |--------------------------------------------------------------------------
    | Transfer History
    |--------------------------------------------------------------------------
    */

    public function getTransfers(
        array $filters = []
    ): LengthAwarePaginator {

        $query =
            StockTransfer::query()

                ->with([
                    'items.rawMaterial',
                    'transferredBy',
                    'creator',
                    'updater',
                ]);


        /*
        |--------------------------------------------------------------------------
        | Date From
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $filters[
                    'date_from'
                ]
            )
        ) {

            $query->whereDate(
                'transferred_at',
                '>=',
                $filters[
                    'date_from'
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date To
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $filters[
                    'date_to'
                ]
            )
        ) {

            $query->whereDate(
                'transferred_at',
                '<=',
                $filters[
                    'date_to'
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Raw Material
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $filters[
                    'raw_material_id'
                ]
            )
        ) {

            $rawMaterialId =
                (int) $filters[
                    'raw_material_id'
                ];


            $query->whereHas(
                'items',
                static function (
                    $itemQuery
                ) use (
                    $rawMaterialId
                ): void {

                    $itemQuery->where(
                        'raw_material_id',
                        $rawMaterialId
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        $search =
            trim(
                (string) (
                    $filters[
                        'search'
                    ]
                    ??
                    ''
                )
            );


        if (
            $search !== ''
        ) {

            $query->where(
                static function (
                    $searchQuery
                ) use (
                    $search
                ): void {

                    $searchQuery

                        ->where(
                            'transfer_no',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhere(
                            'notes',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhereHas(
                            'items',
                            static function (
                                $itemQuery
                            ) use (
                                $search
                            ): void {

                                $itemQuery
                                    ->where(
                                        'item_name',
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
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage =
            max(
                1,
                min(
                    100,
                    (int) (
                        $filters[
                            'per_page'
                        ]
                        ??
                        15
                    )
                )
            );


        return $query

            ->orderByDesc(
                'transferred_at'
            )

            ->orderByDesc(
                'id'
            )

            ->paginate(
                $perPage
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Single Transfer
    |--------------------------------------------------------------------------
    */

    public function getTransfer(
        StockTransfer $stockTransfer
    ): StockTransfer {

        return $stockTransfer
            ->load([
                'items.rawMaterial',
                'transferredBy',
                'creator',
                'updater',
            ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurant Stocks
    |--------------------------------------------------------------------------
    */

    public function getRestaurantStocks(
        array $filters = []
    ): LengthAwarePaginator {

        $query =
            RestaurantStock::query()

                ->with([
                    'rawMaterial',
                    'creator',
                    'updater',
                ]);


        /*
        |--------------------------------------------------------------------------
        | Raw Material
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $filters[
                    'raw_material_id'
                ]
            )
        ) {

            $query->where(
                'raw_material_id',
                (int) $filters[
                    'raw_material_id'
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        $search =
            trim(
                (string) (
                    $filters[
                        'search'
                    ]
                    ??
                    ''
                )
            );


        if (
            $search !== ''
        ) {

            $query->whereHas(
                'rawMaterial',
                static function (
                    $materialQuery
                ) use (
                    $search
                ): void {

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


        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $filters[
                    'category'
                ]
            )
        ) {

            $category =
                trim(
                    (string) $filters[
                        'category'
                    ]
                );


            $query->whereHas(
                'rawMaterial',
                static function (
                    $materialQuery
                ) use (
                    $category
                ): void {

                    $materialQuery->where(
                        'category',
                        $category
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage =
            max(
                1,
                min(
                    100,
                    (int) (
                        $filters[
                            'per_page'
                        ]
                        ??
                        15
                    )
                )
            );


        return $query

            ->orderByDesc(
                'updated_at'
            )

            ->orderByDesc(
                'id'
            )

            ->paginate(
                $perPage
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Single Restaurant Stock
    |--------------------------------------------------------------------------
    */

    public function getRestaurantStock(
        RawMaterial $rawMaterial
    ): RestaurantStock {

        return RestaurantStock::query()

            ->with([
                'rawMaterial',
                'creator',
                'updater',
            ])

            ->where(
                'raw_material_id',
                $rawMaterial->id
            )

            ->firstOrFail();
    }


    /*
    |--------------------------------------------------------------------------
    | Transfer Warehouse → Restaurant
    |--------------------------------------------------------------------------
    */

    public function transferToRestaurant(
        array $data,
        User $user
    ): StockTransfer {

        return DB::transaction(

            function () use (
                $data,
                $user
            ): StockTransfer {


                /*
                |--------------------------------------------------------------------------
                | Validate Items
                |--------------------------------------------------------------------------
                */

                $items =
                    collect(
                        $data[
                            'items'
                        ]
                        ??
                        []
                    )
                        ->values();


                if (
                    $items->isEmpty()
                ) {

                    throw ValidationException::withMessages([
                        'items' => [
                            'At least one raw material is required for transfer.',
                        ],
                    ]);
                }
                if (
                    $items->count()
                    >
                    200
                ) {

                    throw ValidationException::withMessages([
                        'items' => [
                            'A maximum of 200 raw materials can be transferred at once.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Raw Material IDs
                |--------------------------------------------------------------------------
                */

                $rawMaterialIds =
                    $items

                        ->pluck(
                            'raw_material_id'
                        )

                        ->map(
                            static fn (
                                mixed $id
                            ): int =>
                                (int) $id
                        )

                        ->values();

                foreach (
    $rawMaterialIds
    as
    $index => $rawMaterialId
) {

    if (
        $rawMaterialId
        <=
        0
    ) {

        throw ValidationException::withMessages([

            "items.{$index}.raw_material_id" => [
                'The selected raw material is invalid.',
            ],

        ]);
    }
}


                /*
                |--------------------------------------------------------------------------
                | Duplicate Protection
                |--------------------------------------------------------------------------
                */

                if (
                    $rawMaterialIds
                        ->count()
                    !==
                    $rawMaterialIds
                        ->unique()
                        ->count()
                ) {

                    throw ValidationException::withMessages([
                        'items' => [
                            'Duplicate raw materials are not allowed in the same stock transfer.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Lock Raw Materials in Deterministic Order
                |--------------------------------------------------------------------------
                |
                | Sorting lock order helps reduce deadlock risk when two transfers
                | include overlapping raw materials.
                |
                */

                $rawMaterials =
                    RawMaterial::query()

                        ->whereIn(
                            'id',
                            $rawMaterialIds
                        )

                        ->whereNull(
                            'deleted_at'
                        )

                        ->orderBy(
                            'id'
                        )

                        ->lockForUpdate()

                        ->get()

                        ->keyBy(
                            'id'
                        );


                if (
                    $rawMaterials
                        ->count()
                    !==
                    $rawMaterialIds
                        ->unique()
                        ->count()
                ) {

                    throw ValidationException::withMessages([
                        'items' => [
                            'One or more raw materials are unavailable or deleted.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Validate Active Materials Before Header Creation
                |--------------------------------------------------------------------------
                */

                foreach (
                    $items
                    as
                    $index => $item
                ) {

                    $rawMaterial =
                        $rawMaterials->get(
                            (int) (
                                $item[
                                    'raw_material_id'
                                ]
                                ??
                                0
                            )
                        );


                    if (
                        !$rawMaterial
                    ) {
                        continue;
                    }


                    if (
                        !$rawMaterial
                            ->is_active
                    ) {

                        throw ValidationException::withMessages([

                            "items.{$index}.raw_material_id" => [
                                "The raw material \"{$rawMaterial->material_name}\" is inactive.",
                            ],

                        ]);
                    }


                    $quantity =
                        round(
                            (float) (
                                $item[
                                    'quantity'
                                ]
                                ??
                                0
                            ),
                            4
                        );


                    if (
                        $quantity <= 0
                    ) {

                        throw ValidationException::withMessages([

                            "items.{$index}.quantity" => [
                                'Transfer quantity must be greater than zero.',
                            ],

                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Transfer Time
                |--------------------------------------------------------------------------
                */

                $transferredAt =
                    !empty(
                        $data[
                            'transferred_at'
                        ]
                    )

                        ? Carbon::parse(
                            $data[
                                'transferred_at'
                            ]
                        )

                        : now();


                /*
                |--------------------------------------------------------------------------
                | Create Transfer Header
                |--------------------------------------------------------------------------
                |
                | StockTransfer is immutable after creation. Therefore the row must be
                | created with a non-empty unique transfer number. We use a temporary
                | transaction-only number first so the auto-increment ID becomes available.
                | The final human-readable number is written directly to the same row
                | before the transaction is committed.
                |
                */

                $temporaryTransferNo =
                    'TMP-'
                    .
                    Str::upper(
                        Str::random(
                            16
                        )
                    );


                $transfer =
                    StockTransfer::create([

                        'transfer_no' =>
                            $temporaryTransferNo,

                        'transferred_at' =>
                            $transferredAt,

                        'notes' =>
                            $this->cleanNullableText(
                                $data[
                                    'notes'
                                ]
                                ??
                                null
                            ),

                        'transferred_by' =>
                            $user->id,

                        'created_by' =>
                            $user->id,

                        'updated_by' =>
                            $user->id,

                    ]);


                /*
                |--------------------------------------------------------------------------
                | Generate Final Transfer Number
                |--------------------------------------------------------------------------
                |
                | Do not call $transfer->update() here because StockTransfer history is
                | intentionally immutable at the Eloquent model layer. This direct query
                | is part of the same uncommitted transaction and only finalizes the
                | generated transfer number before any transfer item/movement is created.
                |
                */

                $transferNo =
                    sprintf(
                        'TRF-%s-%06d',
                        $transferredAt
                            ->format(
                                'Ymd'
                            ),
                        $transfer->id
                    );


                DB::table(
                    'stock_transfers'
                )
                    ->where(
                        'id',
                        $transfer->id
                    )
                    ->update([
                        'transfer_no' =>
                            $transferNo,
                    ]);


                $transfer->refresh();


                /*
                |--------------------------------------------------------------------------
                | Transfer Each Material
                |--------------------------------------------------------------------------
                */

                foreach (
                    $items
                    as
                    $index => $item
                ) {

                    /** @var RawMaterial $rawMaterial */
                    $rawMaterial =
                        $rawMaterials->get(
                            (int) $item[
                                'raw_material_id'
                            ]
                        );


                    $this->transferItem(

                        transfer:
                            $transfer,

                        rawMaterial:
                            $rawMaterial,

                        submittedItem:
                            $item,

                        requestIndex:
                            $index,

                        transferredAt:
                            $transferredAt,

                        generalNotes:
                            $data[
                                'notes'
                            ]
                            ??
                            null,

                        user:
                            $user

                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Return Fresh Transfer
                |--------------------------------------------------------------------------
                */

                return $transfer
                    ->fresh([
                        'items.rawMaterial',
                        'transferredBy',
                        'creator',
                        'updater',
                    ]);
                },

                3

                );
    }


    /*
    |--------------------------------------------------------------------------
    | Transfer Single Item
    |--------------------------------------------------------------------------
    */

    private function transferItem(
        StockTransfer $transfer,
        RawMaterial $rawMaterial,
        array $submittedItem,
        int $requestIndex,
        Carbon $transferredAt,
        mixed $generalNotes,
        User $user
    ): void {

        $quantity =
            round(
                (float) (
                    $submittedItem[
                        'quantity'
                    ]
                    ??
                    0
                ),
                4
            );


        /*
        |--------------------------------------------------------------------------
        | Lock Warehouse Stock
        |--------------------------------------------------------------------------
        */

        $warehouseStock =
            WarehouseStock::query()

                ->where(
                    'raw_material_id',
                    $rawMaterial->id
                )

                ->lockForUpdate()

                ->first();


        if (
            !$warehouseStock
        ) {

            throw ValidationException::withMessages([

                "items.{$requestIndex}.quantity" => [
                    "No active warehouse stock exists for \"{$rawMaterial->material_name}\".",
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Warehouse Quantity
        |--------------------------------------------------------------------------
        */

        $warehouseQuantityBefore =
            round(
                (float) $warehouseStock
                    ->quantity,
                4
            );


        if (
            $quantity
            >
            $warehouseQuantityBefore
        ) {

            throw ValidationException::withMessages([

                "items.{$requestIndex}.quantity" => [

                    "Insufficient warehouse stock for \"{$rawMaterial->material_name}\". Available quantity is {$warehouseQuantityBefore} {$rawMaterial->base_unit}.",

                ],

            ]);
        }


        $warehouseQuantityAfter =
            round(
                $warehouseQuantityBefore
                -
                $quantity,
                4
            );


        if (
            $warehouseQuantityAfter
            <
            0
        ) {

            throw ValidationException::withMessages([

                "items.{$requestIndex}.quantity" => [
                    'Warehouse stock cannot become negative.',
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Transfer Unit Cost
        |--------------------------------------------------------------------------
        |
        | Stock moves to restaurant using current warehouse average cost.
        |
        */

        $transferUnitCost =
            round(
                (float) $warehouseStock
                    ->average_unit_cost,
                4
            );


        /*
        |--------------------------------------------------------------------------
        | Lock / Create Restaurant Stock
        |--------------------------------------------------------------------------
        */

        $restaurantStock =
            RestaurantStock::withTrashed()

                ->where(
                    'raw_material_id',
                    $rawMaterial->id
                )

                ->lockForUpdate()

                ->first();


        if (
            !$restaurantStock
        ) {

            $restaurantStock =
                RestaurantStock::create([

                    'raw_material_id' =>
                        $rawMaterial->id,

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


            $restaurantStock =
                RestaurantStock::query()

                    ->whereKey(
                        $restaurantStock->id
                    )

                    ->lockForUpdate()

                    ->firstOrFail();
        }


        /*
        |--------------------------------------------------------------------------
        | Restore Restaurant Stock If Soft Deleted
        |--------------------------------------------------------------------------
        */

        if (
            $restaurantStock
                ->trashed()
        ) {

            $restaurantStock
                ->restore();


            $restaurantStock =
                RestaurantStock::query()

                    ->whereKey(
                        $restaurantStock->id
                    )

                    ->lockForUpdate()

                    ->firstOrFail();
        }


        /*
        |--------------------------------------------------------------------------
        | Restaurant Quantity
        |--------------------------------------------------------------------------
        */

        $restaurantQuantityBefore =
            round(
                (float) $restaurantStock
                    ->quantity,
                4
            );


        $restaurantQuantityAfter =
            round(
                $restaurantQuantityBefore
                +
                $quantity,
                4
            );


        /*
        |--------------------------------------------------------------------------
        | Restaurant Weighted Average Cost
        |--------------------------------------------------------------------------
        */

        $restaurantAverageBefore =
            round(
                (float) $restaurantStock
                    ->average_unit_cost,
                4
            );


        $restaurantAverageAfter =
            $this->calculateWeightedAverageCost(

                oldQuantity:
                    $restaurantQuantityBefore,

                oldAverageCost:
                    $restaurantAverageBefore,

                receivedQuantity:
                    $quantity,

                receivedUnitCost:
                    $transferUnitCost

            );


        /*
        |--------------------------------------------------------------------------
        | Update Warehouse
        |--------------------------------------------------------------------------
        */

        $warehouseStock
            ->update([

                'quantity' =>
                    $warehouseQuantityAfter,

                /*
                | Removing quantity does not change warehouse average unit cost.
                */

                'updated_by' =>
                    $user->id,

            ]);

        /*
        |--------------------------------------------------------------------------
        | Restaurant Last Received At
        |--------------------------------------------------------------------------
        |
        | A backdated transfer must not move last_received_at backwards.
        |
        */

        $currentRestaurantLastReceivedAt =
            $restaurantStock
                ->last_received_at
                    ? Carbon::parse(
                        $restaurantStock
                            ->last_received_at
                    )
                    : null;


        $restaurantLastReceivedAt =
            $currentRestaurantLastReceivedAt
            &&
            $currentRestaurantLastReceivedAt
                ->greaterThan(
                    $transferredAt
                )

                ? $currentRestaurantLastReceivedAt
                : $transferredAt;
        /*
        |--------------------------------------------------------------------------
        | Update Restaurant
        |--------------------------------------------------------------------------
        */

        $restaurantStock
            ->update([

                'quantity' =>
                    $restaurantQuantityAfter,

                'average_unit_cost' =>
                    $restaurantAverageAfter,

                'last_received_at' =>
                    $transferredAt,

                'updated_by' =>
                    $user->id,

            ]);


        /*
        |--------------------------------------------------------------------------
        | Item Notes
        |--------------------------------------------------------------------------
        */

        $itemNotes =
            $this->cleanNullableText(
                $submittedItem[
                    'notes'
                ]
                ??
                null
            );


        /*
        |--------------------------------------------------------------------------
        | Transfer Item Snapshot
        |--------------------------------------------------------------------------
        */

        StockTransferItem::create([

            'stock_transfer_id' =>
                $transfer->id,

            'raw_material_id' =>
                $rawMaterial->id,

            'item_name' =>
                $rawMaterial
                    ->material_name,

            'unit' =>
                $rawMaterial
                    ->base_unit,

            'quantity' =>
                $quantity,

            'unit_cost' =>
                $transferUnitCost,

            'warehouse_quantity_before' =>
                $warehouseQuantityBefore,

            'warehouse_quantity_after' =>
                $warehouseQuantityAfter,

            'restaurant_quantity_before' =>
                $restaurantQuantityBefore,

            'restaurant_quantity_after' =>
                $restaurantQuantityAfter,

            'notes' =>
                $itemNotes,

        ]);


        /*
        |--------------------------------------------------------------------------
        | Warehouse Movement
        |--------------------------------------------------------------------------
        */

        StockMovement::create([

            'raw_material_id' =>
                $rawMaterial->id,

            'location' =>
                StockMovement::LOCATION_WAREHOUSE,

            'movement_type' =>
                StockMovement::TYPE_TRANSFER_OUT,

            'quantity' =>
                $quantity,

            'quantity_before' =>
                $warehouseQuantityBefore,

            'quantity_after' =>
                $warehouseQuantityAfter,

            'unit_cost' =>
                $transferUnitCost,

            'reference_type' =>
                StockTransfer::class,

            'reference_id' =>
                $transfer->id,

            'unit' =>
                $rawMaterial
                    ->base_unit,

            'notes' =>
                $this->buildMovementNotes(
                    transfer:
                        $transfer,
                    rawMaterial:
                        $rawMaterial,
                    direction:
                        'Warehouse to Restaurant',
                    itemNotes:
                        $itemNotes,
                    generalNotes:
                        $generalNotes
                ),

            'created_by' =>
                $user->id,

        ]);


        /*
        |--------------------------------------------------------------------------
        | Restaurant Movement
        |--------------------------------------------------------------------------
        */

        StockMovement::create([

            'raw_material_id' =>
                $rawMaterial->id,

            'location' =>
                StockMovement::LOCATION_RESTAURANT,

            'movement_type' =>
                StockMovement::TYPE_TRANSFER_IN,

            'quantity' =>
                $quantity,

            'quantity_before' =>
                $restaurantQuantityBefore,

            'quantity_after' =>
                $restaurantQuantityAfter,

            'unit_cost' =>
                $transferUnitCost,

            'reference_type' =>
                StockTransfer::class,

            'reference_id' =>
                $transfer->id,

            'unit' =>
                $rawMaterial
                    ->base_unit,

            'notes' =>
                $this->buildMovementNotes(
                    transfer:
                        $transfer,
                    rawMaterial:
                        $rawMaterial,
                    direction:
                        'Restaurant received from Warehouse',
                    itemNotes:
                        $itemNotes,
                    generalNotes:
                        $generalNotes
                ),

            'created_by' =>
                $user->id,

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Weighted Average Cost
    |--------------------------------------------------------------------------
    */

    private function calculateWeightedAverageCost(
        float $oldQuantity,
        float $oldAverageCost,
        float $receivedQuantity,
        float $receivedUnitCost
    ): float {

        $newQuantity =
            $oldQuantity
            +
            $receivedQuantity;


        if (
            $newQuantity <= 0
        ) {
            return 0;
        }


        return round(

            (
                (
                    $oldQuantity
                    *
                    $oldAverageCost
                )
                +
                (
                    $receivedQuantity
                    *
                    $receivedUnitCost
                )
            )
            /
            $newQuantity,

            4

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Clean Nullable Text
    |--------------------------------------------------------------------------
    */

    private function cleanNullableText(
        mixed $value
    ): ?string {

        if (
            $value === null
        ) {
            return null;
        }


        $value =
            trim(
                (string) $value
            );


        return $value !== ''
            ? $value
            : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Movement Notes
    |--------------------------------------------------------------------------
    */

    private function buildMovementNotes(
        StockTransfer $transfer,
        RawMaterial $rawMaterial,
        string $direction,
        mixed $itemNotes,
        mixed $generalNotes
    ): string {

        $notes = [

            "Transfer {$transfer->transfer_no}",

            $direction,

            "Item: {$rawMaterial->material_name}",

        ];


        $cleanItemNotes =
            $this->cleanNullableText(
                $itemNotes
            );


        if (
            $cleanItemNotes
        ) {

            $notes[] =
                "Item note: {$cleanItemNotes}";
        }


        $cleanGeneralNotes =
            $this->cleanNullableText(
                $generalNotes
            );


        if (
            $cleanGeneralNotes
        ) {

            $notes[] =
                "Transfer note: {$cleanGeneralNotes}";
        }


        return mb_substr(
            implode(
                ' | ',
                $notes
            ),
            0,
            2000
        );
    }
}