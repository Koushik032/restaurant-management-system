<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderRecipeConsumption;
use App\Models\OrderRecipeConsumptionItem;
use App\Models\RawMaterial;
use App\Models\RecipeMapping;
use App\Models\RestaurantStock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\OrderKitchenBatch;

class RecipeConsumptionService
{
    /*
    |--------------------------------------------------------------------------
    | Consume Order Recipe
    |--------------------------------------------------------------------------
    |
    | Called from the kitchen "Start Preparing" workflow.
    | This service does not change the Order status.
    |
    | Consumption includes:
    | - Menu Item recipe mappings
    | - Ordered Add-on recipe mappings
    |
    */

        public function consumeForBatch(
        OrderKitchenBatch $batch,
        User $user
    ): OrderRecipeConsumption {
        return DB::transaction(
            function () use (
                $batch,
                $user
            ): OrderRecipeConsumption {

                /*
                |--------------------------------------------------------------------------
                | Lock Parent Order First
                |--------------------------------------------------------------------------
                |
                | Order → Batch lock order deterministic রাখা হচ্ছে।
                |
                */

                $lockedOrder =
                    Order::query()
                        ->whereKey(
                            $batch->order_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | Lock Kitchen Batch
                |--------------------------------------------------------------------------
                */

                $lockedBatch =
                    OrderKitchenBatch::query()
                        ->whereKey(
                            $batch->id
                        )
                        ->where(
                            'order_id',
                            $lockedOrder->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | Batch-Level Idempotency
                |--------------------------------------------------------------------------
                |
                | একই kitchen batch-এর recipe consumption একবারই হবে।
                |
                */

                $existingConsumption =
                    OrderRecipeConsumption::query()
                        ->where(
                            'order_id',
                            $lockedOrder->id
                        )
                        ->where(
                            'order_kitchen_batch_id',
                            $lockedBatch->id
                        )
                        ->first();

                if ($existingConsumption) {
                    return $existingConsumption
                        ->load([
                            'order',
                            'kitchenBatch',
                            'items.rawMaterial',
                            'creator',
                        ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Allowed Kitchen Batch State
                |--------------------------------------------------------------------------
                */

                if (
                    ! in_array(
                        $lockedBatch->status,
                        [
                            OrderKitchenBatch::STATUS_PENDING,
                            OrderKitchenBatch::STATUS_PREPARING,
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'kitchen_batch' => [
                            'Recipe consumption can only be created while the kitchen batch is pending or preparing.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Chef Required
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedBatch->chef_id === null
                ) {
                    throw ValidationException::withMessages([
                        'kitchen_batch' => [
                            'A chef must accept the kitchen batch before preparation can start.',
                        ],
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Current Batch Items Only
                |--------------------------------------------------------------------------
                |
                | Historical Batch #1 served items Batch #2 preparation-এর সময়
                | এখানে আর load হবে না।
                |
                */

                $orderItems =
                    $lockedBatch
                        ->items()
                        ->with(
                            'addons'
                        )
                        ->orderBy(
                            'id'
                        )
                        ->get();

                if (
                    $orderItems->isEmpty()
                ) {
                    throw ValidationException::withMessages([
                        'kitchen_batch' => [
                            'The kitchen batch has no items to consume.',
                        ],
                    ]);
                }

                $normalizedOrderItems =
                    $this->normalizeOrderItems(
                        $orderItems
                    );

                $menuItemIds =
                    collect(
                        $normalizedOrderItems
                    )
                        ->pluck(
                            'menu_item_id'
                        )
                        ->map(
                            static fn (
                                mixed $id
                            ): int =>
                                (int) $id
                        )
                        ->unique()
                        ->sort()
                        ->values();

                $addOnIds =
                    collect(
                        $normalizedOrderItems
                    )
                        ->pluck(
                            'addons'
                        )
                        ->flatten(
                            1
                        )
                        ->pluck(
                            'add_on_id'
                        )
                        ->map(
                            static fn (
                                mixed $id
                            ): int =>
                                (int) $id
                        )
                        ->filter(
                            static fn (
                                int $id
                            ): bool =>
                                $id > 0
                        )
                        ->unique()
                        ->sort()
                        ->values();

                /*
                |--------------------------------------------------------------------------
                | Lock Menu Items
                |--------------------------------------------------------------------------
                */

                $menuItems =
                    MenuItem::query()
                        ->whereIn(
                            'id',
                            $menuItemIds->all()
                        )
                        ->orderBy(
                            'id'
                        )
                        ->lockForUpdate()
                        ->get()
                        ->keyBy(
                            'id'
                        );

                foreach (
                    $menuItemIds
                    as
                    $menuItemId
                ) {
                    if (
                        ! $menuItems->has(
                            (int) $menuItemId
                        )
                    ) {
                        throw ValidationException::withMessages([
                            'order' => [
                                "Menu item ID {$menuItemId} is no longer available for recipe consumption.",
                            ],
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Lock Add-ons
                |--------------------------------------------------------------------------
                */

                $addOns =
                    collect();

                if (
                    $addOnIds->isNotEmpty()
                ) {
                    $addOns =
                        AddOn::query()
                            ->whereIn(
                                'id',
                                $addOnIds->all()
                            )
                            ->orderBy(
                                'id'
                            )
                            ->lockForUpdate()
                            ->get()
                            ->keyBy(
                                'id'
                            );

                    foreach (
                        $addOnIds
                        as
                        $addOnId
                    ) {
                        if (
                            ! $addOns->has(
                                (int) $addOnId
                            )
                        ) {
                            throw ValidationException::withMessages([
                                'order' => [
                                    "Add-on ID {$addOnId} is no longer available for recipe consumption.",
                                ],
                            ]);
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Recipe Mapping Snapshots
                |--------------------------------------------------------------------------
                */

                $menuRecipeMappings =
                    RecipeMapping::query()
                        ->whereIn(
                            'menu_item_id',
                            $menuItemIds->all()
                        )
                        ->whereNull(
                            'add_on_id'
                        )
                        ->orderBy(
                            'menu_item_id'
                        )
                        ->orderBy(
                            'raw_material_id'
                        )
                        ->orderBy(
                            'id'
                        )
                        ->get()
                        ->groupBy(
                            'menu_item_id'
                        );

                $addOnRecipeMappings =
                    collect();

                if (
                    $addOnIds->isNotEmpty()
                ) {
                    $addOnRecipeMappings =
                        RecipeMapping::query()
                            ->whereIn(
                                'add_on_id',
                                $addOnIds->all()
                            )
                            ->whereNull(
                                'menu_item_id'
                            )
                            ->orderBy(
                                'add_on_id'
                            )
                            ->orderBy(
                                'raw_material_id'
                            )
                            ->orderBy(
                                'id'
                            )
                            ->get()
                            ->groupBy(
                                'add_on_id'
                            );
                }

                /*
                |--------------------------------------------------------------------------
                | Aggregate Ingredients
                |--------------------------------------------------------------------------
                */

                $aggregated =
                    $this->aggregateIngredients(
                        orderItems:
                            $normalizedOrderItems,

                        menuItems:
                            $menuItems,

                        addOns:
                            $addOns,

                        menuRecipeMappings:
                            $menuRecipeMappings,

                        addOnRecipeMappings:
                            $addOnRecipeMappings
                    );

                if (
                    empty(
                        $aggregated
                    )
                ) {
                    throw ValidationException::withMessages([
                        'kitchen_batch' => [
                            'No recipe ingredients were found for this kitchen batch.',
                        ],
                    ]);
                }

                ksort(
                    $aggregated,
                    SORT_NUMERIC
                );

                $rawMaterialIds =
                    array_map(
                        'intval',
                        array_keys(
                            $aggregated
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | Lock And Validate Raw Materials
                |--------------------------------------------------------------------------
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
                        ->where(
                            'is_active',
                            true
                        )
                        ->orderBy(
                            'id'
                        )
                        ->lockForUpdate()
                        ->get()
                        ->keyBy(
                            'id'
                        );

                foreach (
                    $rawMaterialIds
                    as
                    $rawMaterialId
                ) {
                    /** @var RawMaterial|null $rawMaterial */

                    $rawMaterial =
                        $rawMaterials->get(
                            $rawMaterialId
                        );

                    if (
                        ! $rawMaterial
                    ) {
                        throw ValidationException::withMessages([
                            'recipe' => [
                                "Raw material ID {$rawMaterialId} used by this recipe was not found, is inactive, or is archived.",
                            ],
                        ]);
                    }

                    $mappingUnit =
                        strtolower(
                            trim(
                                (string) (
                                    $aggregated[
                                        $rawMaterialId
                                    ][
                                        'unit'
                                    ]
                                    ??
                                    ''
                                )
                            )
                        );

                    $baseUnit =
                        strtolower(
                            trim(
                                (string)
                                    $rawMaterial
                                        ->base_unit
                            )
                        );

                    if (
                        $mappingUnit === ''
                        ||
                        $mappingUnit !==
                        $baseUnit
                    ) {
                        throw ValidationException::withMessages([
                            'recipe' => [
                                "Recipe unit for \"{$rawMaterial->material_name}\" no longer matches its base unit ({$baseUnit}). Please update the recipe mapping before preparing this kitchen batch.",
                            ],
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Lock Restaurant Stock
                |--------------------------------------------------------------------------
                */

                $restaurantStocks =
                    RestaurantStock::query()
                        ->whereIn(
                            'raw_material_id',
                            $rawMaterialIds
                        )
                        ->orderBy(
                            'raw_material_id'
                        )
                        ->lockForUpdate()
                        ->get()
                        ->keyBy(
                            'raw_material_id'
                        );

                /*
                |--------------------------------------------------------------------------
                | Validate Stock Before Deduction
                |--------------------------------------------------------------------------
                */

                foreach (
                    $rawMaterialIds
                    as
                    $rawMaterialId
                ) {
                    /** @var RawMaterial $rawMaterial */

                    $rawMaterial =
                        $rawMaterials->get(
                            $rawMaterialId
                        );

                    /** @var RestaurantStock|null $restaurantStock */

                    $restaurantStock =
                        $restaurantStocks->get(
                            $rawMaterialId
                        );

                    $requiredQuantity =
                        round(
                            (float)
                                $aggregated[
                                    $rawMaterialId
                                ][
                                    'quantity'
                                ],
                            4
                        );

                    if (
                        ! $restaurantStock
                    ) {
                        throw ValidationException::withMessages([
                            'restaurant_stock' => [
                                "No active restaurant stock exists for \"{$rawMaterial->material_name}\".",
                            ],
                        ]);
                    }

                    if (
                        ! $restaurantStock
                            ->canDeduct(
                                $requiredQuantity
                            )
                    ) {
                        $available =
                            round(
                                (float)
                                    $restaurantStock
                                        ->quantity,
                                4
                            );

                        throw ValidationException::withMessages([
                            'restaurant_stock' => [
                                "Insufficient restaurant stock for \"{$rawMaterial->material_name}\". Required {$requiredQuantity} {$rawMaterial->base_unit}, available {$available} {$rawMaterial->base_unit}.",
                            ],
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Create Immutable Consumption Header
                |--------------------------------------------------------------------------
                */

                $consumption =
                    OrderRecipeConsumption::create([
                        'order_id' =>
                            $lockedOrder->id,

                        'order_kitchen_batch_id' =>
                            $lockedBatch->id,

                        'order_number' =>
                            trim(
                                (string)
                                    $lockedOrder
                                        ->order_number
                            ),

                        'trigger' =>
                            OrderRecipeConsumption::
                                TRIGGER_START_PREPARING,

                        'order_status_snapshot' =>
                            $lockedOrder->status,

                        'consumed_at' =>
                            now(),

                        'created_by' =>
                            $user->id,
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Deduct Aggregated Ingredients
                |--------------------------------------------------------------------------
                */

                foreach (
                    $rawMaterialIds
                    as
                    $rawMaterialId
                ) {
                    /** @var RawMaterial $rawMaterial */

                    $rawMaterial =
                        $rawMaterials->get(
                            $rawMaterialId
                        );

                    /** @var RestaurantStock $restaurantStock */

                    $restaurantStock =
                        $restaurantStocks->get(
                            $rawMaterialId
                        );

                    $requiredQuantity =
                        round(
                            (float)
                                $aggregated[
                                    $rawMaterialId
                                ][
                                    'quantity'
                                ],
                            4
                        );

                    $quantityBefore =
                        round(
                            (float)
                                $restaurantStock
                                    ->quantity,
                            4
                        );

                    $quantityAfter =
                        $restaurantStock
                            ->quantityAfterDeduction(
                                $requiredQuantity
                            );

                    $unitCost =
                        round(
                            (float)
                                $restaurantStock
                                    ->average_unit_cost,
                            4
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | Update Restaurant Stock
                    |--------------------------------------------------------------------------
                    */

                    $restaurantStock
                        ->update([
                            'quantity' =>
                                $quantityAfter,

                            'updated_by' =>
                                $user->id,
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Immutable Consumption Ledger Item
                    |--------------------------------------------------------------------------
                    */

                    OrderRecipeConsumptionItem::create([
                        'order_recipe_consumption_id' =>
                            $consumption->id,

                        'raw_material_id' =>
                            $rawMaterial->id,

                        'material_name' =>
                            $rawMaterial
                                ->material_name,

                        'unit' =>
                            $rawMaterial
                                ->base_unit,

                        'quantity' =>
                            $requiredQuantity,

                        'unit_cost' =>
                            $unitCost,

                        'restaurant_quantity_before' =>
                            $quantityBefore,

                        'restaurant_quantity_after' =>
                            $quantityAfter,

                        'source_breakdown' =>
                            $aggregated[
                                $rawMaterialId
                            ][
                                'source_breakdown'
                            ],

                        'notes' =>
                            "Recipe consumption for order {$lockedOrder->order_number}, kitchen batch #{$lockedBatch->batch_no}.",
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Immutable Stock Movement
                    |--------------------------------------------------------------------------
                    */

                    StockMovement::create([
                        'raw_material_id' =>
                            $rawMaterial->id,

                        'location' =>
                            StockMovement::
                                LOCATION_RESTAURANT,

                        'movement_type' =>
                            StockMovement::
                                TYPE_RECIPE_CONSUMPTION,

                        'quantity' =>
                            $requiredQuantity,

                        'quantity_before' =>
                            $quantityBefore,

                        'quantity_after' =>
                            $quantityAfter,

                        'unit_cost' =>
                            $unitCost,

                        'reference_type' =>
                            OrderRecipeConsumption::class,

                        'reference_id' =>
                            $consumption->id,

                        'unit' =>
                            $rawMaterial
                                ->base_unit,

                        'notes' =>
                            $this
                                ->buildMovementNotes(
                                    order:
                                        $lockedOrder,

                                    batch:
                                        $lockedBatch,

                                    rawMaterial:
                                        $rawMaterial,

                                    quantity:
                                        $requiredQuantity
                                ),

                        'created_by' =>
                            $user->id,
                    ]);
                }

                return $consumption
                    ->load([
                        'order',
                        'kitchenBatch',
                        'items.rawMaterial',
                        'creator',
                    ]);
            },
            3
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Backward-Compatible Order Entry Point
    |--------------------------------------------------------------------------
    |
    | KitchenOrderService এখনো consumeForOrder() call করছে।
    | পরের step-এ KitchenOrderService batch-aware করার আগ পর্যন্ত এই wrapper
    | application compatibility ধরে রাখবে।
    |
    */

    public function consumeForOrder(
        Order $order,
        User $user
    ): OrderRecipeConsumption {
        $batch =
            OrderKitchenBatch::query()
                ->where(
                    'order_id',
                    $order->id
                )
                ->whereIn(
                    'status',
                    OrderKitchenBatch::
                        activeStatuses()
                )
                ->orderByDesc(
                    'batch_no'
                )
                ->orderByDesc(
                    'id'
                )
                ->first();

        if (
            ! $batch
        ) {
            throw ValidationException::withMessages([
                'kitchen_batch' => [
                    'No active kitchen batch is available for recipe consumption.',
                ],
            ]);
        }

        return $this
            ->consumeForBatch(
                $batch,
                $user
            );
    }
    /*
    |--------------------------------------------------------------------------
    | Normalize Order Items
    |--------------------------------------------------------------------------
    */

    private function normalizeOrderItems(
        Collection $orderItems
    ): array {
        $normalized = [];

        foreach ($orderItems as $orderItem) {
            if (! $orderItem instanceof Model) {
                continue;
            }

            $orderItemId =
                (int) $orderItem->getKey();

            $menuItemId =
                (int) $orderItem->getAttribute(
                    'menu_item_id'
                );

            $orderQuantity =
                round(
                    (float) $orderItem->getAttribute(
                        'quantity'
                    ),
                    4
                );

            if (
                $orderItemId <= 0
                ||
                $menuItemId <= 0
            ) {
                throw ValidationException::withMessages([
                    'order' => [
                        'One or more order items are missing a valid menu item reference.',
                    ],
                ]);
            }

            if ($orderQuantity <= 0) {
                throw ValidationException::withMessages([
                    'order' => [
                        "Order item ID {$orderItemId} has an invalid quantity.",
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Ordered Add-ons
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | The quantity stored in the OrderItem Add-on snapshot is treated as
            | the authoritative ordered Add-on quantity.
            |
            | It is NOT multiplied again by the parent Menu Item quantity.
            |
            */

            $normalizedAddOns = [];

            $orderAddOns =
                $orderItem->relationLoaded('addons')
                    ? $orderItem->getRelation('addons')
                    : collect();

            foreach ($orderAddOns as $orderAddOn) {
                if (! $orderAddOn instanceof Model) {
                    continue;
                }

                $orderItemAddOnId =
                    (int) $orderAddOn->getKey();

                $addOnId =
                    (int) $orderAddOn->getAttribute(
                        'menu_addon_id'
                    );

                $addOnQuantity =
                    round(
                        (float) $orderAddOn->getAttribute(
                            'quantity'
                        ),
                        4
                    );

                $addOnName =
                    trim(
                        (string) (
                            $orderAddOn->getAttribute(
                                'addon_name'
                            )
                            ??
                            ''
                        )
                    );

                if (
                    $orderItemAddOnId <= 0
                    ||
                    $addOnId <= 0
                ) {
                    throw ValidationException::withMessages([
                        'order' => [
                            "Order item ID {$orderItemId} contains an add-on without a valid master add-on reference.",
                        ],
                    ]);
                }

                if ($addOnQuantity <= 0) {
                    throw ValidationException::withMessages([
                        'order' => [
                            "Order add-on ID {$orderItemAddOnId} has an invalid quantity.",
                        ],
                    ]);
                }

                $normalizedAddOns[] = [
                    'order_item_addon' =>
                        $orderAddOn,

                    'order_item_addon_id' =>
                        $orderItemAddOnId,

                    'add_on_id' =>
                        $addOnId,

                    'add_on_name' =>
                        $addOnName,

                    'ordered_quantity' =>
                        $addOnQuantity,
                ];
            }

            $normalized[] = [
                'order_item' =>
                    $orderItem,

                'order_item_id' =>
                    $orderItemId,

                'menu_item_id' =>
                    $menuItemId,

                'order_quantity' =>
                    $orderQuantity,

                'addons' =>
                    $normalizedAddOns,
            ];
        }

        if (empty($normalized)) {
            throw ValidationException::withMessages([
                'order' => [
                    'The order has no valid menu items to consume.',
                ],
            ]);
        }

        return $normalized;
    }


    /*
    |--------------------------------------------------------------------------
    | Aggregate Ingredients
    |--------------------------------------------------------------------------
    */

    private function aggregateIngredients(
        array $orderItems,
        Collection $menuItems,
        Collection $addOns,
        Collection $menuRecipeMappings,
        Collection $addOnRecipeMappings
    ): array {
        $aggregated = [];

        foreach ($orderItems as $normalizedOrderItem) {
            /** @var Model $orderItem */
            $orderItem =
                $normalizedOrderItem[
                    'order_item'
                ];

            $orderItemId =
                (int) $normalizedOrderItem[
                    'order_item_id'
                ];

            $menuItemId =
                (int) $normalizedOrderItem[
                    'menu_item_id'
                ];

            $orderQuantity =
                round(
                    (float) $normalizedOrderItem[
                        'order_quantity'
                    ],
                    4
                );

            /** @var MenuItem $menuItem */
            $menuItem =
                $menuItems->get(
                    $menuItemId
                );

            /*
            |--------------------------------------------------------------------------
            | Menu Item Recipe
            |--------------------------------------------------------------------------
            */

            /** @var Collection $menuMappings */
            $menuMappings =
                $menuRecipeMappings->get(
                    $menuItemId,
                    collect()
                );

            if ($menuMappings->isEmpty()) {
                $menuItemName =
                    $this->resolveMenuItemName(
                        orderItem: $orderItem,
                        menuItem: $menuItem
                    );

                throw ValidationException::withMessages([
                    'recipe' => [
                        "No recipe mapping exists for \"{$menuItemName}\". Add its ingredients before starting preparation.",
                    ],
                ]);
            }

            foreach ($menuMappings as $mapping) {
                /** @var RecipeMapping $mapping */

                $this->addMappingConsumption(
                    aggregated: $aggregated,

                    mapping: $mapping,

                    orderedQuantity: $orderQuantity,

                    sourceBreakdown: [
                        'source_type' =>
                            'menu_item',

                        'order_item_id' =>
                            $orderItemId,

                        'menu_item_id' =>
                            $menuItemId,

                        'menu_item_name' =>
                            $this->resolveMenuItemName(
                                orderItem: $orderItem,
                                menuItem: $menuItem
                            ),
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Ordered Add-on Recipes
            |--------------------------------------------------------------------------
            */

            foreach (
                $normalizedOrderItem[
                    'addons'
                ]
                as
                $normalizedAddOn
            ) {
                $addOnId =
                    (int) $normalizedAddOn[
                        'add_on_id'
                    ];

                $orderedAddOnQuantity =
                    round(
                        (float) $normalizedAddOn[
                            'ordered_quantity'
                        ],
                        4
                    );

                /** @var AddOn $addOn */
                $addOn =
                    $addOns->get(
                        $addOnId
                    );

                /** @var Collection $addOnMappings */
                $addOnMappings =
                    $addOnRecipeMappings->get(
                        $addOnId,
                        collect()
                    );

                $addOnName =
                    $this->resolveAddOnName(
                        orderAddOn:
                            $normalizedAddOn[
                                'order_item_addon'
                            ],

                        addOn:
                            $addOn
                    );

                if ($addOnMappings->isEmpty()) {
                    throw ValidationException::withMessages([
                        'recipe' => [
                            "No recipe mapping exists for add-on \"{$addOnName}\". Add its ingredients before starting preparation.",
                        ],
                    ]);
                }

                foreach ($addOnMappings as $mapping) {
                    /** @var RecipeMapping $mapping */

                    $this->addMappingConsumption(
                        aggregated: $aggregated,

                        mapping: $mapping,

                        orderedQuantity:
                            $orderedAddOnQuantity,

                        sourceBreakdown: [
                            'source_type' =>
                                'add_on',

                            'order_item_id' =>
                                $orderItemId,

                            'order_item_addon_id' =>
                                (int) $normalizedAddOn[
                                    'order_item_addon_id'
                                ],

                            'add_on_id' =>
                                $addOnId,

                            'add_on_name' =>
                                $addOnName,
                        ]
                    );
                }
            }
        }

        return $aggregated;
    }


    /*
    |--------------------------------------------------------------------------
    | Add Mapping Consumption To Aggregate
    |--------------------------------------------------------------------------
    */

    private function addMappingConsumption(
        array &$aggregated,
        RecipeMapping $mapping,
        float $orderedQuantity,
        array $sourceBreakdown
    ): void {
        $rawMaterialId =
            (int) $mapping->raw_material_id;

        $recipeQuantity =
            round(
                (float) $mapping->quantity,
                4
            );

        $unit =
            strtolower(
                trim(
                    (string) $mapping->unit
                )
            );

        if (
            $rawMaterialId <= 0
            ||
            $recipeQuantity <= 0
            ||
            $orderedQuantity <= 0
            ||
            $unit === ''
        ) {
            throw ValidationException::withMessages([
                'recipe' => [
                    "Recipe mapping ID {$mapping->id} contains invalid ingredient data.",
                ],
            ]);
        }

        $consumedQuantity =
            round(
                $recipeQuantity
                *
                $orderedQuantity,
                4
            );

        if ($consumedQuantity <= 0) {
            throw ValidationException::withMessages([
                'recipe' => [
                    "Recipe mapping ID {$mapping->id} produced an invalid consumption quantity.",
                ],
            ]);
        }

        if (
            ! isset(
                $aggregated[
                    $rawMaterialId
                ]
            )
        ) {
            $aggregated[
                $rawMaterialId
            ] = [
                'quantity' =>
                    0.0,

                'unit' =>
                    $unit,

                'source_breakdown' =>
                    [],
            ];
        }

        if (
            $aggregated[
                $rawMaterialId
            ][
                'unit'
            ]
            !==
            $unit
        ) {
            throw ValidationException::withMessages([
                'recipe' => [
                    "Conflicting recipe units were found for raw material ID {$rawMaterialId}.",
                ],
            ]);
        }

        $newAggregateQuantity =
            round(
                (float) $aggregated[
                    $rawMaterialId
                ][
                    'quantity'
                ]
                +
                $consumedQuantity,
                4
            );

        if (
            $newAggregateQuantity
            >
            9999999999.9999
        ) {
            throw ValidationException::withMessages([
                'recipe' => [
                    'Aggregated recipe consumption quantity is too large.',
                ],
            ]);
        }

        $aggregated[
            $rawMaterialId
        ][
            'quantity'
        ] =
            $newAggregateQuantity;

        $aggregated[
            $rawMaterialId
        ][
            'source_breakdown'
        ][] =
            array_merge(
                $sourceBreakdown,
                [
                    'recipe_mapping_id' =>
                        (int) $mapping->id,

                    'order_quantity' =>
                        $orderedQuantity,

                    'recipe_quantity' =>
                        $recipeQuantity,

                    'consumed_quantity' =>
                        $consumedQuantity,
                ]
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Menu Item Name
    |--------------------------------------------------------------------------
    */

    private function resolveMenuItemName(
        Model $orderItem,
        MenuItem $menuItem
    ): string {
        $candidates = [
            $orderItem->getAttribute(
                'item_name'
            ),

            $orderItem->getAttribute(
                'menu_item_name'
            ),

            $menuItem->getAttribute(
                'menu_name'
            ),

            $menuItem->getAttribute(
                'item_name'
            ),

            $menuItem->getAttribute(
                'name'
            ),

            $menuItem->getAttribute(
                'title'
            ),
        ];

        foreach (
            $candidates
            as
            $candidate
        ) {
            $name =
                trim(
                    (string) (
                        $candidate
                        ??
                        ''
                    )
                );

            if ($name !== '') {
                return $name;
            }
        }

        return "Menu Item #{$menuItem->id}";
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Add-on Name
    |--------------------------------------------------------------------------
    */

    private function resolveAddOnName(
        Model $orderAddOn,
        AddOn $addOn
    ): string {
        $candidates = [
            $orderAddOn->getAttribute(
                'addon_name'
            ),

            $orderAddOn->getAttribute(
                'add_on_name'
            ),

            $addOn->getAttribute(
                'add_on_name'
            ),

            $addOn->getAttribute(
                'name'
            ),
        ];

        foreach (
            $candidates
            as
            $candidate
        ) {
            $name =
                trim(
                    (string) (
                        $candidate
                        ??
                        ''
                    )
                );

            if ($name !== '') {
                return $name;
            }
        }

        return "Add-on #{$addOn->id}";
    }


    /*
    |--------------------------------------------------------------------------
    | Movement Notes
    |--------------------------------------------------------------------------
    */


        private function buildMovementNotes(
        Order $order,
        OrderKitchenBatch $batch,
        RawMaterial $rawMaterial,
        float $quantity
    ): string {
        $notes =
            sprintf(
                'Recipe consumption | Order: %s | Kitchen Batch: #%d | Material: %s | Quantity: %.4f %s',
                (string)
                    $order->order_number,

                (int)
                    $batch->batch_no,

                (string)
                    $rawMaterial
                        ->material_name,

                $quantity,

                (string)
                    $rawMaterial
                        ->base_unit
            );

        return mb_substr(
            $notes,
            0,
            2000
        );
    }
}