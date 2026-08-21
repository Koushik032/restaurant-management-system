<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\RawMaterial;
use App\Models\RecipeMapping;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class RecipeMappingService
{
    /*
    |--------------------------------------------------------------------------
    | Target Types
    |--------------------------------------------------------------------------
    */

    public const TARGET_MENU_ITEM =
        'menu_item';


    public const TARGET_ADD_ON =
        'add_on';


    /*
    |--------------------------------------------------------------------------
    | Legacy: Get Menu Item Recipe
    |--------------------------------------------------------------------------
    |
    | variant_id = null
    |     -> Direct Menu Item recipe
    |
    | variant_id = 5
    |     -> Variant-specific recipe
    |
    */

    public function getRecipe(
        MenuItem $menuItem,
        ?int $variantId = null
    ): MenuItem {

        $variantId =
            $this->normalizeVariantId(
                self::TARGET_MENU_ITEM,
                (int) $menuItem->id,
                $variantId
            );


        /** @var MenuItem $target */
        $target =
            $this->loadTargetRecipe(
                $menuItem,
                $variantId
            );


        return $target;
    }


    /*
    |--------------------------------------------------------------------------
    | Legacy: Replace Menu Item Recipe
    |--------------------------------------------------------------------------
    */

    public function replaceRecipe(
        MenuItem $menuItem,
        array $ingredients,
        User $user,
        ?int $variantId = null
    ): MenuItem {

        $target =
            $this->replaceTargetRecipe(
                self::TARGET_MENU_ITEM,
                (int) $menuItem->id,
                $ingredients,
                $user,
                $variantId
            );


        /** @var MenuItem $target */
        return $target;
    }


    /*
    |--------------------------------------------------------------------------
    | Add-on Convenience: Get Recipe
    |--------------------------------------------------------------------------
    */

    public function getAddOnRecipe(
        AddOn $addOn
    ): AddOn {

        /** @var AddOn $target */
        $target =
            $this->loadTargetRecipe(
                $addOn,
                null
            );


        return $target;
    }


    /*
    |--------------------------------------------------------------------------
    | Add-on Convenience: Replace Recipe
    |--------------------------------------------------------------------------
    */

    public function replaceAddOnRecipe(
        AddOn $addOn,
        array $ingredients,
        User $user
    ): AddOn {

        /** @var AddOn $target */
        $target =
            $this->replaceTargetRecipe(
                self::TARGET_ADD_ON,
                (int) $addOn->id,
                $ingredients,
                $user,
                null
            );


        return $target;
    }


    /*
    |--------------------------------------------------------------------------
    | Recipe Mapping List
    |--------------------------------------------------------------------------
    |
    | One row = one recipe identity.
    |
    | Examples:
    |
    | menu_item:5:0
    | menu_item:5:1
    | menu_item:5:2
    | add_on:3:0
    |
    */

    public function getRecipeMappingList(): Collection
{
    $mappings =
        RecipeMapping::query()
            ->with([
                'menuItem',
                'variant',
                'addOn',
                'rawMaterial',
                'creator',
                'updater',
            ])
            ->orderBy('id')
            ->get();


    return $mappings

        ->groupBy(
            function (
                RecipeMapping $mapping
            ): string {

                if (
                    $mapping->isMenuItemRecipe()
                ) {
                    return
                        self::TARGET_MENU_ITEM
                        . ':'
                        . (int) $mapping->menu_item_id
                        . ':'
                        . (
                            $mapping->variant_id !== null
                                ? (int) $mapping->variant_id
                                : 0
                        );
                }


                return
                    self::TARGET_ADD_ON
                    . ':'
                    . (int) $mapping->add_on_id
                    . ':0';
            }
        )

        ->map(
            function (
                Collection $group
            ): array {

                /** @var RecipeMapping $first */
                $first =
                    $group->first();


                $isAddOn =
                    $first->isAddOnRecipe();


                if (
                    $isAddOn
                ) {

                    $target =
                        $first->addOn;


                    $targetName =
                        (string) (
                            $target?->add_on_name
                            ?? ''
                        );


                    $variantId =
                        null;


                    $variantName =
                        null;


                    $imageUrl =
                        null;

                } else {

                    $target =
                        $first->menuItem;


                    $targetName =
                        (string) (
                            $target?->menu_name
                            ?? ''
                        );


                    $variantId =
                        $first->variant_id !== null
                            ? (int) $first->variant_id
                            : null;


                    $variantName =
                        $first
                            ->variant
                            ?->variant_name;


                    $imageUrl =
                        $target?->image_url
                        ?? null;
                }


                return [

                    'target_type' =>
                        $isAddOn
                            ? self::TARGET_ADD_ON
                            : self::TARGET_MENU_ITEM,


                    'target_id' =>
                        (int) $first->target_id,


                    'target_name' =>
                        $targetName,


                    'variant_id' =>
                        $variantId,


                    'variant_name' =>
                        $variantName,


                    'image_url' =>
                        $imageUrl,


                    'is_available' =>
                        $target !== null
                            ? (bool)
                                $target->is_available
                            : false,


                    'ingredients' =>
                        $group
                            ->sortBy(
                                'id'
                            )
                            ->values(),
                ];
            }
        )

        ->filter(
            static function (
                array $recipe
            ): bool {

                return
                    $recipe['target_id'] > 0
                    &&
                    $recipe['target_name'] !== '';
            }
        )

        ->sortBy(
            static function (
                array $recipe
            ): string {

                return
                    (
                        $recipe['target_type']
                        . ':'
                        . mb_strtolower(
                            $recipe['target_name']
                        )
                        . ':'
                        . mb_strtolower(
                            (string) (
                                $recipe['variant_name']
                                ?? ''
                            )
                        )
                    );
            }
        )

        ->values();
}


    /*
    |--------------------------------------------------------------------------
    | Get Unified Target Recipe
    |--------------------------------------------------------------------------
    */

    public function getTargetRecipe(
        string $targetType,
        int $targetId,
        ?int $variantId = null
    ): MenuItem|AddOn {

        $targetType =
            $this->normalizeTargetType(
                $targetType
            );


        $targetId =
            $this->normalizePositiveId(
                $targetId,
                'target_id',
                'A valid recipe target is required.'
            );


        $variantId =
            $this->normalizeVariantId(
                $targetType,
                $targetId,
                $variantId
            );


        $target =
            $this->findTargetOrFail(
                $targetType,
                $targetId,
                false
            );


        /** @var MenuItem|AddOn $loadedTarget */
        $loadedTarget =
            $this->loadTargetRecipe(
                $target,
                $variantId
            );


        return $loadedTarget;
    }


    /*
    |--------------------------------------------------------------------------
    | Replace Unified Target Recipe
    |--------------------------------------------------------------------------
    |
    | Entire recipe for one identity is replaced atomically.
    |
    | Menu Item direct:
    |     targetType = menu_item
    |     variantId  = null
    |
    | Menu Item variant:
    |     targetType = menu_item
    |     variantId  = selected variant
    |
    | Add-on:
    |     targetType = add_on
    |     variantId  = null
    |
    */

    public function replaceTargetRecipe(
        string $targetType,
        int $targetId,
        array $ingredients,
        User $user,
        ?int $variantId = null
    ): MenuItem|AddOn {

        $targetType =
            $this->normalizeTargetType(
                $targetType
            );


        $targetId =
            $this->normalizePositiveId(
                $targetId,
                'target_id',
                'A valid recipe target is required.'
            );


        $variantId =
            $this->normalizeVariantId(
                $targetType,
                $targetId,
                $variantId
            );


        $normalizedIngredients =
            $this->normalizeIngredients(
                $ingredients
            );


        return DB::transaction(

            function () use (
                $targetType,
                $targetId,
                $variantId,
                $normalizedIngredients,
                $user
            ): MenuItem|AddOn {

                /*
                |--------------------------------------------------------------------------
                | Lock Target
                |--------------------------------------------------------------------------
                */

                $target =
                    $this->findTargetOrFail(
                        $targetType,
                        $targetId,
                        true
                    );


                /*
                |--------------------------------------------------------------------------
                | Empty Recipe = Clear This Exact Recipe
                |--------------------------------------------------------------------------
                */

                if (
                    empty(
                        $normalizedIngredients
                    )
                ) {

                    $this->lockTargetMappings(
                        $targetType,
                        $targetId,
                        $variantId
                    );


                    $this->targetMappingsQuery(
                        $targetType,
                        $targetId,
                        $variantId
                    )
                        ->delete();


                    return $this->loadTargetRecipe(
                        $target,
                        $variantId
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Deterministic Raw Material Locks
                |--------------------------------------------------------------------------
                */

                $rawMaterialIds =
                    collect(
                        $normalizedIngredients
                    )
                        ->pluck(
                            'raw_material_id'
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


                $rawMaterials =
                    RawMaterial::query()
                        ->whereIn(
                            'id',
                            $rawMaterialIds
                                ->all()
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


                /*
                |--------------------------------------------------------------------------
                | Validate Raw Materials
                |--------------------------------------------------------------------------
                */

                foreach (
                    $normalizedIngredients
                    as
                    $index => $ingredient
                ) {

                    $rawMaterialId =
                        (int) $ingredient[
                            'raw_material_id'
                        ];


                    if (
                        ! $rawMaterials->has(
                            $rawMaterialId
                        )
                    ) {

                        throw ValidationException::withMessages([

                            "ingredients.{$index}.raw_material_id" => [

                                'The selected raw material was not found or is inactive.',

                            ],

                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Lock Existing Rows For Exact Recipe Identity
                |--------------------------------------------------------------------------
                */

                $existingMappings =
                    $this->lockTargetMappings(
                        $targetType,
                        $targetId,
                        $variantId
                    );


                /*
                |--------------------------------------------------------------------------
                | Existing Mapping By Raw Material
                |--------------------------------------------------------------------------
                */

                $existingByMaterialId =
                    $existingMappings
                        ->keyBy(
                            'raw_material_id'
                        );


                /*
                |--------------------------------------------------------------------------
                | Desired Material IDs
                |--------------------------------------------------------------------------
                */

                $desiredMaterialIds =
                    $rawMaterialIds
                        ->all();


                /*
                |--------------------------------------------------------------------------
                | Delete Removed Ingredients
                |--------------------------------------------------------------------------
                */

                $this->targetMappingsQuery(
                    $targetType,
                    $targetId,
                    $variantId
                )
                    ->whereNotIn(
                        'raw_material_id',
                        $desiredMaterialIds
                    )
                    ->delete();


                /*
                |--------------------------------------------------------------------------
                | Create / Update Ingredients
                |--------------------------------------------------------------------------
                */

                foreach (
                    $normalizedIngredients
                    as
                    $ingredient
                ) {

                    $rawMaterialId =
                        (int) $ingredient[
                            'raw_material_id'
                        ];


                    /** @var RawMaterial $rawMaterial */
                    $rawMaterial =
                        $rawMaterials->get(
                            $rawMaterialId
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Authoritative Unit
                    |--------------------------------------------------------------------------
                    */

                    $unit =
                        strtolower(
                            trim(
                                (string) $rawMaterial
                                    ->base_unit
                            )
                        );


                    if (
                        $unit === ''
                    ) {

                        throw ValidationException::withMessages([

                            'ingredients' => [

                                "Raw material \"{$rawMaterial->material_name}\" does not have a valid base unit.",

                            ],

                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Existing Mapping
                    |--------------------------------------------------------------------------
                    */

                    /** @var RecipeMapping|null $existing */
                    $existing =
                        $existingByMaterialId->get(
                            $rawMaterialId
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Update Existing
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $existing
                    ) {

                        $existing->update([

                            'quantity' =>
                                $ingredient[
                                    'quantity'
                                ],


                            'unit' =>
                                $unit,


                            'notes' =>
                                $ingredient[
                                    'notes'
                                ],


                            'updated_by' =>
                                $user->id,

                        ]);


                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Create New Mapping
                    |--------------------------------------------------------------------------
                    */

                    RecipeMapping::create([

                        'menu_item_id' =>

                            $targetType
                            ===
                            self::TARGET_MENU_ITEM

                                ? $targetId

                                : null,


                        'variant_id' =>

                            $targetType
                            ===
                            self::TARGET_MENU_ITEM

                            &&

                            $variantId !== null

                                ? $variantId

                                : null,


                        'add_on_id' =>

                            $targetType
                            ===
                            self::TARGET_ADD_ON

                                ? $targetId

                                : null,


                        'raw_material_id' =>
                            $rawMaterialId,


                        'quantity' =>
                            $ingredient[
                                'quantity'
                            ],


                        'unit' =>
                            $unit,


                        'notes' =>
                            $ingredient[
                                'notes'
                            ],


                        'created_by' =>
                            $user->id,


                        'updated_by' =>
                            $user->id,

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Return Exact Recipe
                |--------------------------------------------------------------------------
                */

                return $this->loadTargetRecipe(
                    $target,
                    $variantId
                );

            },

            3

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Exact Recipe Identity
    |--------------------------------------------------------------------------
    |
    | Deletes only:
    |
    | Menu Item + Variant recipe
    |
    | OR
    |
    | Menu Item direct recipe
    |
    | OR
    |
    | Add-on recipe
    |
    */

    public function deleteTargetRecipe(
        string $targetType,
        int $targetId,
        ?int $variantId = null
    ): void {

        $targetType =
            $this->normalizeTargetType(
                $targetType
            );


        $targetId =
            $this->normalizePositiveId(
                $targetId,
                'target_id',
                'A valid recipe target is required.'
            );


        $variantId =
            $this->normalizeVariantId(
                $targetType,
                $targetId,
                $variantId
            );


        DB::transaction(

            function () use (
                $targetType,
                $targetId,
                $variantId
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Lock Target
                |--------------------------------------------------------------------------
                */

                $this->findTargetOrFail(
                    $targetType,
                    $targetId,
                    true
                );


                /*
                |--------------------------------------------------------------------------
                | Lock Exact Recipe Rows
                |--------------------------------------------------------------------------
                */

                $this->lockTargetMappings(
                    $targetType,
                    $targetId,
                    $variantId
                );


                /*
                |--------------------------------------------------------------------------
                | Delete Exact Recipe Only
                |--------------------------------------------------------------------------
                */

                $this->targetMappingsQuery(
                    $targetType,
                    $targetId,
                    $variantId
                )
                    ->delete();

            },

            3

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Load Target Recipe
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | This loads ONLY the requested recipe identity.
    |
    */

    private function loadTargetRecipe(
    MenuItem|AddOn $target,
    ?int $variantId = null
): MenuItem|AddOn {

    $targetType =
        $target instanceof AddOn
            ? self::TARGET_ADD_ON
            : self::TARGET_MENU_ITEM;


    /*
    |--------------------------------------------------------------------------
    | Normalize Variant
    |--------------------------------------------------------------------------
    */

    if (
        $targetType ===
        self::TARGET_ADD_ON
    ) {
        $variantId = null;
    }


    /*
    |--------------------------------------------------------------------------
    | Recipe Mapping Query
    |--------------------------------------------------------------------------
    */

    $target->load([
        'recipeMappings' =>
            function (
                $query
            ) use (
                $targetType,
                $variantId
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Menu Item
                |--------------------------------------------------------------------------
                */

                if (
                    $targetType ===
                    self::TARGET_MENU_ITEM
                ) {

                    if (
                        $variantId !== null
                    ) {

                        $query->where(
                            'variant_id',
                            $variantId
                        );

                    } else {

                        $query->whereNull(
                            'variant_id'
                        );
                    }

                }

                /*
                |--------------------------------------------------------------------------
                | Add-on
                |--------------------------------------------------------------------------
                */

                else {

                    $query
                        ->whereNull(
                            'variant_id'
                        );
                }


                $query->orderBy(
                    'id'
                );
            },

        'recipeMappings.menuItem',

        'recipeMappings.variant',

        'recipeMappings.addOn',

        'recipeMappings.rawMaterial',

        'recipeMappings.creator',

        'recipeMappings.updater',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Menu Item Variant Relation
    |--------------------------------------------------------------------------
    |
    | NEVER load variants on AddOn.
    |
    */

    if (
        $target instanceof MenuItem
    ) {

        $target->loadMissing([
            'variants',
        ]);
    }


    return $target;
}


    /*
    |--------------------------------------------------------------------------
    | Find Target
    |--------------------------------------------------------------------------
    */

    private function findTargetOrFail(
        string $targetType,
        int $targetId,
        bool $lockForUpdate
    ): MenuItem|AddOn {

        $targetId =
            $this->normalizePositiveId(
                $targetId,
                'target_id',
                'A valid recipe target is required.'
            );


        /*
        |--------------------------------------------------------------------------
        | Menu Item
        |--------------------------------------------------------------------------
        */

        if (
            $targetType ===
            self::TARGET_MENU_ITEM
        ) {

            $query =
                MenuItem::query()
                    ->whereKey(
                        $targetId
                    );


            if (
                $lockForUpdate
            ) {

                $query->lockForUpdate();
            }


            /** @var MenuItem|null $menuItem */
            $menuItem =
                $query->first();


            if (
                ! $menuItem
            ) {

                throw ValidationException::withMessages([

                    'target_id' => [

                        'The selected menu item was not found or has been deleted.',

                    ],

                ]);
            }


            return $menuItem;
        }


        /*
        |--------------------------------------------------------------------------
        | Add-on
        |--------------------------------------------------------------------------
        */

        $query =
            AddOn::query()
                ->whereKey(
                    $targetId
                );


        if (
            $lockForUpdate
        ) {

            $query->lockForUpdate();
        }


        /** @var AddOn|null $addOn */
        $addOn =
            $query->first();


        if (
            ! $addOn
        ) {

            throw ValidationException::withMessages([

                'target_id' => [

                    'The selected add-on was not found or has been deleted.',

                ],

            ]);
        }


        return $addOn;
    }


    /*
    |--------------------------------------------------------------------------
    | Exact Recipe Mapping Query
    |--------------------------------------------------------------------------
    */

    private function targetMappingsQuery(
        string $targetType,
        int $targetId,
        ?int $variantId = null
    ) {

        $query =
            RecipeMapping::query();


        /*
        |--------------------------------------------------------------------------
        | Menu Item Recipe
        |--------------------------------------------------------------------------
        */

        if (
            $targetType ===
            self::TARGET_MENU_ITEM
        ) {

            $query
                ->where(
                    'menu_item_id',
                    $targetId
                )
                ->whereNull(
                    'add_on_id'
                );


            /*
            |--------------------------------------------------------------------------
            | Variant-specific
            |--------------------------------------------------------------------------
            */

            if (
                $variantId !== null
            ) {

                $query->where(
                    'variant_id',
                    $variantId
                );

            } else {

                /*
                |--------------------------------------------------------------------------
                | Direct Menu Item
                |--------------------------------------------------------------------------
                */

                $query->whereNull(
                    'variant_id'
                );
            }


            return $query;
        }


        /*
        |--------------------------------------------------------------------------
        | Add-on
        |--------------------------------------------------------------------------
        */

        return $query

            ->whereNull(
                'menu_item_id'
            )

            ->whereNull(
                'variant_id'
            )

            ->where(
                'add_on_id',
                $targetId
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Lock Exact Recipe Mappings
    |--------------------------------------------------------------------------
    */

    private function lockTargetMappings(
        string $targetType,
        int $targetId,
        ?int $variantId = null
    ): Collection {

        return $this

            ->targetMappingsQuery(

                $targetType,

                $targetId,

                $variantId

            )

            ->orderBy(
                'id'
            )

            ->lockForUpdate()

            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Target Type
    |--------------------------------------------------------------------------
    */

    private function normalizeTargetType(
        string $targetType
    ): string {

        $targetType =
            strtolower(
                trim(
                    $targetType
                )
            );


        $targetType =
            str_replace(
                '-',
                '_',
                $targetType
            );


        return match (
            $targetType
        ) {

            'menu_item',
            'menuitem' =>
                self::TARGET_MENU_ITEM,


            'add_on',
            'addon' =>
                self::TARGET_ADD_ON,


            default =>
                throw ValidationException::withMessages([

                    'target_type' => [

                        'Recipe target type must be menu_item or add_on.',

                    ],

                ]),
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Positive ID
    |--------------------------------------------------------------------------
    */

    private function normalizePositiveId(
        mixed $value,
        string $field,
        string $message
    ): int {

        $id =
            (int) $value;


        if (
            $id <= 0
        ) {

            throw ValidationException::withMessages([

                $field => [

                    $message,

                ],

            ]);
        }


        return $id;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Variant ID
    |--------------------------------------------------------------------------
    |
    | Add-on:
    |     variant_id MUST be NULL
    |
    | Menu Item:
    |     variant_id may be NULL
    |     OR a valid variant belonging to the menu item
    |
    */

    private function normalizeVariantId(
        string $targetType,
        int $targetId,
        ?int $variantId
    ): ?int {

        /*
        |--------------------------------------------------------------------------
        | Add-on
        |--------------------------------------------------------------------------
        */

        if (
            $targetType ===
            self::TARGET_ADD_ON
        ) {

            if (
                $variantId !== null
                &&
                (int) $variantId > 0
            ) {

                throw ValidationException::withMessages([

                    'variant_id' => [

                        'Add-ons do not support variants.',

                    ],

                ]);
            }


            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Direct Menu Item Recipe
        |--------------------------------------------------------------------------
        */

        if (
            $variantId === null
            ||
            $variantId === ''
            ||
            (int) $variantId <= 0
        ) {

            return null;
        }


        $variantId =
            (int) $variantId;


        /*
        |--------------------------------------------------------------------------
        | Verify Variant
        |--------------------------------------------------------------------------
        */

        $variant =
            MenuItemVariant::query()
                ->whereKey(
                    $variantId
                )
                ->where(
                    'menu_item_id',
                    $targetId
                )
                ->whereNull(
                    'deleted_at'
                )
            ->first();


        if (
            ! $variant
        ) {

            throw ValidationException::withMessages([

                'variant_id' => [

                    'The selected variant does not belong to the selected menu item or has been deleted.',

                ],

            ]);
        }


        return $variantId;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Ingredients
    |--------------------------------------------------------------------------
    */

    private function normalizeIngredients(
        array $ingredients
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Maximum Ingredients
        |--------------------------------------------------------------------------
        */

        if (
            count(
                $ingredients
            ) > 200
        ) {

            throw ValidationException::withMessages([

                'ingredients' => [

                    'A maximum of 200 ingredients can be mapped to one recipe target.',

                ],

            ]);
        }


        $normalized =
            [];


        $seenMaterialIds =
            [];


        /*
        |--------------------------------------------------------------------------
        | Each Ingredient
        |--------------------------------------------------------------------------
        */

        foreach (
            $ingredients
            as
            $index => $ingredient
        ) {

            /*
            |--------------------------------------------------------------------------
            | Ingredient Structure
            |--------------------------------------------------------------------------
            */

            if (
                ! is_array(
                    $ingredient
                )
            ) {

                throw ValidationException::withMessages([

                    "ingredients.{$index}" => [

                        'Each recipe ingredient must be a valid object.',

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

                    $ingredient[
                        'raw_material_id'
                    ]

                    ??

                    0

                );


            if (
                $rawMaterialId <= 0
            ) {

                throw ValidationException::withMessages([

                    "ingredients.{$index}.raw_material_id" => [

                        'A valid raw material is required.',

                    ],

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Duplicate Raw Material Within Submitted Recipe
            |--------------------------------------------------------------------------
            */

            if (
                isset(
                    $seenMaterialIds[
                        $rawMaterialId
                    ]
                )
            ) {

                throw ValidationException::withMessages([

                    "ingredients.{$index}.raw_material_id" => [

                        'The same raw material cannot be added more than once to the same recipe.',

                    ],

                ]);
            }


            $seenMaterialIds[
                $rawMaterialId
            ] =
                true;


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            $quantityValue =
                $ingredient[
                    'quantity'
                ]
                ??
                null;


            if (
                ! is_numeric(
                    $quantityValue
                )
            ) {

                throw ValidationException::withMessages([

                    "ingredients.{$index}.quantity" => [

                        'Recipe ingredient quantity must be numeric.',

                    ],

                ]);
            }


            $quantity =
                round(
                    (float) $quantityValue,
                    4
                );


            if (
                $quantity <= 0
            ) {

                throw ValidationException::withMessages([

                    "ingredients.{$index}.quantity" => [

                        'Recipe ingredient quantity must be greater than zero.',

                    ],

                ]);
            }


            if (
                $quantity >
                9999999999.9999
            ) {

                throw ValidationException::withMessages([

                    "ingredients.{$index}.quantity" => [

                        'Recipe ingredient quantity is too large.',

                    ],

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $notes =
                trim(
                    (string) (

                        $ingredient[
                            'notes'
                        ]

                        ??

                        ''

                    )
                );


            if (
                mb_strlen(
                    $notes
                ) > 2000
            ) {

                throw ValidationException::withMessages([

                    "ingredients.{$index}.notes" => [

                        'Recipe ingredient notes cannot exceed 2000 characters.',

                    ],

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Normalized Ingredient
            |--------------------------------------------------------------------------
            */

            $normalized[] = [

                'raw_material_id' =>
                    $rawMaterialId,


                'quantity' =>
                    $quantity,


                'notes' =>
                    $notes !== ''
                        ? $notes
                        : null,

            ];
        }


        return $normalized;
    }
}