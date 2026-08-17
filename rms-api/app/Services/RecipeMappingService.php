<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\MenuItem;
use App\Models\RawMaterial;
use App\Models\RecipeMapping;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class RecipeMappingService
{
    public const TARGET_MENU_ITEM =
        'menu_item';

    public const TARGET_ADD_ON =
        'add_on';


    /*
    |--------------------------------------------------------------------------
    | Backward-Compatible Menu Item Recipe
    |--------------------------------------------------------------------------
    |
    | Existing Menu Item API/controller যেন এখনই break না করে,
    | সেই জন্য পুরনো methods রাখা হয়েছে।
    |
    */

    public function getRecipe(
        MenuItem $menuItem
    ): MenuItem {

        /** @var MenuItem $target */
        $target =
            $this->loadTargetRecipe(
                $menuItem
            );

        return $target;
    }


    public function replaceRecipe(
        MenuItem $menuItem,
        array $ingredients,
        User $user
    ): MenuItem {

        /** @var MenuItem $target */
        $target =
            $this->replaceTargetRecipe(
                self::TARGET_MENU_ITEM,
                (int) $menuItem->id,
                $ingredients,
                $user
            );

        return $target;
    }


    /*
    |--------------------------------------------------------------------------
    | Add-on Convenience Methods
    |--------------------------------------------------------------------------
    */

    public function getAddOnRecipe(
        AddOn $addOn
    ): AddOn {

        /** @var AddOn $target */
        $target =
            $this->loadTargetRecipe(
                $addOn
            );

        return $target;
    }


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
                $user
            );

        return $target;
    }


    /*
    |--------------------------------------------------------------------------
    | Recipe Mapping List
    |--------------------------------------------------------------------------
    |
    | One list row = one Recipe Target.
    |
    | Example:
    |
    | Menu Item - Chicken Burger
    |     Chicken 0.1500 kg
    |     Bun     1 pcs
    |
    | Add-on - Extra Cheese
    |     Cheese 0.0300 kg
    |
    */

    public function getRecipeMappingList(): Collection
    {
        $mappings =
            RecipeMapping::query()
                ->with([
                    'menuItem',
                    'addOn',
                    'rawMaterial',
                    'creator',
                    'updater',
                ])
                ->orderBy(
                    'id'
                )
                ->get();


        return $mappings

            ->groupBy(
                function (
                    RecipeMapping $mapping
                ): string {

                    return
                        $mapping->target_type
                        . ':'
                        . $mapping->target_id;
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
                        $first
                            ->isAddOnRecipe();


                    $target =
                        $isAddOn
                            ? $first->addOn
                            : $first->menuItem;


                    return [

                        'target_type' =>
                            $isAddOn
                                ? self::TARGET_ADD_ON
                                : self::TARGET_MENU_ITEM,


                        'target_id' =>
                            $first->target_id,


                        'target_name' =>
                            $isAddOn
                                ? (string) (
                                    $target
                                        ?->add_on_name
                                    ?? ''
                                )
                                : (string) (
                                    $target
                                        ?->menu_name
                                    ?? ''
                                ),


                        'is_available' =>
                            $target !== null
                                ? (bool)
                                    $target
                                        ->is_available
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
                static fn (
                    array $recipe
                ): bool =>

                    $recipe[
                        'target_id'
                    ] > 0

                    &&

                    $recipe[
                        'target_name'
                    ] !== ''
            )

            ->sortBy(
                static function (
                    array $recipe
                ): string {

                    return
                        $recipe[
                            'target_type'
                        ]
                        . ':'
                        . mb_strtolower(
                            $recipe[
                                'target_name'
                            ]
                        );
                }
            )

            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | Get Unified Target Recipe
    |--------------------------------------------------------------------------
    |
    | Supports:
    |
    | menu_item
    | add_on
    |
    */

    public function getTargetRecipe(
        string $targetType,
        int $targetId
    ): MenuItem|AddOn {

        $targetType =
            $this->normalizeTargetType(
                $targetType
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
                $target
            );


        return $loadedTarget;
    }


    /*
    |--------------------------------------------------------------------------
    | Replace Unified Target Recipe
    |--------------------------------------------------------------------------
    |
    | Entire recipe is saved atomically.
    |
    | Existing ingredient:
    |     Updated
    |
    | New ingredient:
    |     Created
    |
    | Removed ingredient:
    |     Deleted
    |
    | Empty ingredients array:
    |     Clears the recipe
    |
    | IMPORTANT:
    |     This method NEVER deducts RestaurantStock.
    |
    */

    public function replaceTargetRecipe(
        string $targetType,
        int $targetId,
        array $ingredients,
        User $user
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


        $normalizedIngredients =
            $this->normalizeIngredients(
                $ingredients
            );


        return DB::transaction(

            function () use (
                $targetType,
                $targetId,
                $normalizedIngredients,
                $user
            ): MenuItem|AddOn {


                /*
                |--------------------------------------------------------------------------
                | Lock Recipe Target
                |--------------------------------------------------------------------------
                |
                | একই Menu Item/Add-on recipe একসাথে দুইজন edit করলে
                | race-condition আটকাবে।
                |
                */

                $target =
                    $this->findTargetOrFail(
                        $targetType,
                        $targetId,
                        true
                    );


                /*
                |--------------------------------------------------------------------------
                | Empty Recipe = Explicit Clear
                |--------------------------------------------------------------------------
                */

                if (
                    empty(
                        $normalizedIngredients
                    )
                ) {

                    $this->lockTargetMappings(
                        $targetType,
                        $targetId
                    );


                    $this->targetMappingsQuery(
                        $targetType,
                        $targetId
                    )
                        ->delete();


                    /** @var MenuItem|AddOn $loadedTarget */
                    $loadedTarget =
                        $this->loadTargetRecipe(
                            $target
                        );


                    return $loadedTarget;
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
                | Active Raw Material Validation
                |--------------------------------------------------------------------------
                */

                foreach (
                    $normalizedIngredients
                    as
                    $index => $ingredient
                ) {

                    $rawMaterialId =
                        (int)
                            $ingredient[
                                'raw_material_id'
                            ];


                    if (
                        ! $rawMaterials
                            ->has(
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
                | Lock Existing Recipe Rows
                |--------------------------------------------------------------------------
                */

                $existingMappings =
                    $this->lockTargetMappings(
                        $targetType,
                        $targetId
                    );


                $existingByMaterialId =
                    $existingMappings
                        ->keyBy(
                            'raw_material_id'
                        );


                $desiredMaterialIds =
                    $rawMaterialIds
                        ->all();


                /*
                |--------------------------------------------------------------------------
                | Remove Ingredients No Longer Used
                |--------------------------------------------------------------------------
                */

                $this->targetMappingsQuery(
                    $targetType,
                    $targetId
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
                        (int)
                            $ingredient[
                                'raw_material_id'
                            ];


                    /** @var RawMaterial $rawMaterial */
                    $rawMaterial =
                        $rawMaterials
                            ->get(
                                $rawMaterialId
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | Authoritative Unit
                    |--------------------------------------------------------------------------
                    |
                    | Frontend থেকে unit trust করা হবে না।
                    |
                    | RawMaterial base_unit সবসময় authoritative.
                    |
                    */

                    $unit =
                        strtolower(
                            trim(
                                (string)
                                    $rawMaterial
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


                    /** @var RecipeMapping|null $existing */
                    $existing =
                        $existingByMaterialId
                            ->get(
                                $rawMaterialId
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | Update Existing Mapping
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
                | Fresh Recipe
                |--------------------------------------------------------------------------
                */

                /** @var MenuItem|AddOn $loadedTarget */
                $loadedTarget =
                    $this->loadTargetRecipe(
                        $target
                    );


                return $loadedTarget;
            },


            /*
            |--------------------------------------------------------------------------
            | Deadlock Retry
            |--------------------------------------------------------------------------
            */

            3
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Recipe Mapping
    |--------------------------------------------------------------------------
    |
    | Delete button শুধু recipe definition delete করবে।
    |
    | এটা delete করবে না:
    |
    | Menu Item
    | Add-on
    | Raw Material
    | Stock Movement
    | Historical Recipe Consumption
    |
    */

    public function deleteTargetRecipe(
        string $targetType,
        int $targetId
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


        DB::transaction(

            function () use (
                $targetType,
                $targetId
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
                | Lock Existing Recipe
                |--------------------------------------------------------------------------
                */

                $this->lockTargetMappings(
                    $targetType,
                    $targetId
                );


                /*
                |--------------------------------------------------------------------------
                | Delete Recipe Rows Only
                |--------------------------------------------------------------------------
                */

                $this->targetMappingsQuery(
                    $targetType,
                    $targetId
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
    */

    private function loadTargetRecipe(
        MenuItem|AddOn $target
    ): MenuItem|AddOn {

        return $target->load([

            'recipeMappings' =>
                function (
                    $query
                ): void {

                    $query
                        ->orderBy(
                            'id'
                        );
                },


            'recipeMappings.rawMaterial',

            'recipeMappings.creator',

            'recipeMappings.updater',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Find Recipe Target
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
            $targetType
            ===
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

                $query
                    ->lockForUpdate();
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

            $query
                ->lockForUpdate();
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
    | Target Mapping Query
    |--------------------------------------------------------------------------
    */

    private function targetMappingsQuery(
        string $targetType,
        int $targetId
    ) {

        $query =
            RecipeMapping::query();


        /*
        |--------------------------------------------------------------------------
        | Menu Item Recipe
        |--------------------------------------------------------------------------
        */

        if (
            $targetType
            ===
            self::TARGET_MENU_ITEM
        ) {

            return $query

                ->where(
                    'menu_item_id',
                    $targetId
                )

                ->whereNull(
                    'add_on_id'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Add-on Recipe
        |--------------------------------------------------------------------------
        */

        return $query

            ->whereNull(
                'menu_item_id'
            )

            ->where(
                'add_on_id',
                $targetId
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Lock Target Mappings
    |--------------------------------------------------------------------------
    */

    private function lockTargetMappings(
        string $targetType,
        int $targetId
    ): Collection {

        return $this
            ->targetMappingsQuery(
                $targetType,
                $targetId
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


        $normalized =
            match (
                $targetType
            ) {

                'menu_item',
                'menuitem' =>
                    self::TARGET_MENU_ITEM,


                'add_on',
                'addon' =>
                    self::TARGET_ADD_ON,


                default =>
                    null,
            };


        if (
            $normalized
            ===
            null
        ) {

            throw ValidationException::withMessages([

                'target_type' => [
                    'Recipe target type must be menu_item or add_on.',
                ],

            ]);
        }


        return $normalized;
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
            )
            >
            200
        ) {

            throw ValidationException::withMessages([

                'ingredients' => [
                    'A maximum of 200 ingredients can be mapped to one recipe target.',
                ],

            ]);
        }


        $normalized = [];

        $seenMaterialIds = [];


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
                $rawMaterialId
                <=
                0
            ) {

                throw ValidationException::withMessages([

                    "ingredients.{$index}.raw_material_id" => [
                        'A valid raw material is required.',
                    ],

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Duplicate Raw Material Protection
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
            ] = true;


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
                    (float)
                        $quantityValue,
                    4
                );


            if (
                $quantity
                <=
                0
            ) {

                throw ValidationException::withMessages([

                    "ingredients.{$index}.quantity" => [
                        'Recipe ingredient quantity must be greater than zero.',
                    ],

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Decimal(14,4) Maximum
            |--------------------------------------------------------------------------
            */

            if (
                $quantity
                >
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
                )
                >
                2000
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