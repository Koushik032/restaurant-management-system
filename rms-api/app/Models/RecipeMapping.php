<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;


class RecipeMapping extends Model
{
    use HasFactory;


    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'menu_item_id',

        'variant_id',

        'add_on_id',

        'raw_material_id',

        'quantity',

        'unit',

        'notes',

        'created_by',

        'updated_by',

    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'menu_item_id' =>
            'integer',

        'variant_id' =>
            'integer',

        'add_on_id' =>
            'integer',

        'raw_material_id' =>
            'integer',

        'quantity' =>
            'decimal:4',

        'created_by' =>
            'integer',

        'updated_by' =>
            'integer',

    ];


    /*
    |--------------------------------------------------------------------------
    | Model Validation
    |--------------------------------------------------------------------------
    |
    | A RecipeMapping belongs to exactly one recipe target:
    |
    | Menu Item:
    |
    |     menu_item_id = ID
    |     add_on_id    = NULL
    |
    | Add-on:
    |
    |     menu_item_id = NULL
    |     add_on_id    = ID
    |
    | Variant:
    |
    |     Menu Item + variant_id
    |     OR
    |     Menu Item + variant_id = NULL
    |
    | Add-ons:
    |
    |     variant_id MUST always be NULL
    |
    */

    protected static function booted(): void
    {
        static::saving(
            function (
                RecipeMapping $mapping
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Target IDs
                |--------------------------------------------------------------------------
                */

                $menuItemId =
                    $mapping->menu_item_id !== null
                        ? (int) $mapping->menu_item_id
                        : null;


                $addOnId =
                    $mapping->add_on_id !== null
                        ? (int) $mapping->add_on_id
                        : null;


                $hasMenuItem =
                    $menuItemId !== null
                    &&
                    $menuItemId > 0;


                $hasAddOn =
                    $addOnId !== null
                    &&
                    $addOnId > 0;


                /*
                |--------------------------------------------------------------------------
                | Exactly One Target
                |--------------------------------------------------------------------------
                */

                if (
                    $hasMenuItem ===
                    $hasAddOn
                ) {

                    throw ValidationException::withMessages([

                        'target' => [

                            'A recipe mapping must belong to exactly one target: either a menu item or an add-on.',

                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Normalize Target IDs
                |--------------------------------------------------------------------------
                */

                $mapping->menu_item_id =
                    $hasMenuItem
                        ? $menuItemId
                        : null;


                $mapping->add_on_id =
                    $hasAddOn
                        ? $addOnId
                        : null;


                /*
                |--------------------------------------------------------------------------
                | Variant
                |--------------------------------------------------------------------------
                */

                $variantId =
                    $mapping->variant_id !== null
                        ? (int) $mapping->variant_id
                        : null;


                /*
                |--------------------------------------------------------------------------
                | Add-ons Cannot Have Variants
                |--------------------------------------------------------------------------
                */

                if (
                    $hasAddOn
                    &&
                    $variantId !== null
                    &&
                    $variantId > 0
                ) {

                    throw ValidationException::withMessages([

                        'variant_id' => [

                            'Add-ons do not support variants.',

                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Normalize Variant
                |--------------------------------------------------------------------------
                */

                if (
                    !$hasMenuItem
                ) {

                    $variantId =
                        null;
                }


                /*
                |--------------------------------------------------------------------------
                | Variant Validation
                |--------------------------------------------------------------------------
                |
                | If variant_id exists, it must belong to the selected
                | Menu Item and must not be soft deleted.
                |
                */

                if (
                    $hasMenuItem
                    &&
                    $variantId !== null
                ) {

                    /** @var MenuItemVariant|null $variant */
                    $variant =
                        MenuItemVariant::query()
                            ->whereKey(
                                $variantId
                            )
                            ->where(
                                'menu_item_id',
                                $menuItemId
                            )
                            ->whereNull(
                                'deleted_at'
                            )
                            ->first();


                    if (
                        !$variant
                    ) {

                        throw ValidationException::withMessages([

                            'variant_id' => [

                                'The selected variant does not belong to the selected menu item or has been deleted.',

                            ],

                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Optional Availability Rule
                    |--------------------------------------------------------------------------
                    |
                    | We intentionally do NOT block inactive variants here.
                    | Existing recipes should remain editable/visible.
                    |
                    */
                }


                $mapping->variant_id =
                    $variantId;


                /*
                |--------------------------------------------------------------------------
                | Raw Material
                |--------------------------------------------------------------------------
                */

                $rawMaterialId =
                    (int) (
                        $mapping->raw_material_id
                        ??
                        0
                    );


                if (
                    $rawMaterialId <= 0
                ) {

                    throw ValidationException::withMessages([

                        'raw_material_id' => [

                            'A valid raw material is required for the recipe mapping.',

                        ],

                    ]);
                }


                /** @var RawMaterial|null $rawMaterial */
                $rawMaterial =
                    RawMaterial::query()
                        ->whereKey(
                            $rawMaterialId
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                        ->first();


                if (
                    !$rawMaterial
                ) {

                    throw ValidationException::withMessages([

                        'raw_material_id' => [

                            'The selected raw material is unavailable or deleted.',

                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Active Raw Material
                |--------------------------------------------------------------------------
                */

                if (
                    !$rawMaterial->is_active
                ) {

                    throw ValidationException::withMessages([

                        'raw_material_id' => [

                            "The raw material \"{$rawMaterial->material_name}\" is inactive.",

                        ],

                    ]);
                }


                $mapping->raw_material_id =
                    $rawMaterial->id;


                /*
                |--------------------------------------------------------------------------
                | Quantity
                |--------------------------------------------------------------------------
                */

                $quantity =
                    round(
                        (float) (
                            $mapping->quantity
                            ??
                            0
                        ),
                        4
                    );


                if (
                    $quantity <= 0
                ) {

                    throw ValidationException::withMessages([

                        'quantity' => [

                            'Recipe ingredient quantity must be greater than zero.',

                        ],

                    ]);
                }


                if (
                    $quantity >
                    9999999999.9999
                ) {

                    throw ValidationException::withMessages([

                        'quantity' => [

                            'Recipe ingredient quantity exceeds the supported maximum.',

                        ],

                    ]);
                }


                $mapping->quantity =
                    $quantity;


                /*
                |--------------------------------------------------------------------------
                | Authoritative Unit
                |--------------------------------------------------------------------------
                */

                $baseUnit =
                    strtolower(
                        trim(
                            (string) $rawMaterial
                                ->base_unit
                        )
                    );


                if (
                    $baseUnit === ''
                ) {

                    throw ValidationException::withMessages([

                        'unit' => [

                            'The selected raw material does not have a valid base unit.',

                        ],

                    ]);
                }


                $submittedUnit =
                    strtolower(
                        trim(
                            (string) (
                                $mapping->unit
                                ??
                                ''
                            )
                        )
                    );


                if (
                    $submittedUnit !== ''
                    &&
                    $submittedUnit !==
                    $baseUnit
                ) {

                    throw ValidationException::withMessages([

                        'unit' => [

                            "Recipe unit must match the raw material base unit ({$rawMaterial->base_unit}).",

                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Always Store Raw Material Base Unit
                |--------------------------------------------------------------------------
                */

                $mapping->unit =
                    $baseUnit;


                /*
                |--------------------------------------------------------------------------
                | Notes
                |--------------------------------------------------------------------------
                */

                $notes =
                    trim(
                        (string) (
                            $mapping->notes
                            ??
                            ''
                        )
                    );


                $mapping->notes =
                    $notes !== ''
                        ? $notes
                        : null;


                if (
                    $mapping->notes !== null
                    &&
                    mb_strlen(
                        $mapping->notes
                    ) > 2000
                ) {

                    throw ValidationException::withMessages([

                        'notes' => [

                            'Recipe ingredient notes cannot exceed 2000 characters.',

                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Duplicate Ingredient Protection
                |--------------------------------------------------------------------------
                |
                | IMPORTANT:
                |
                | The duplicate identity is:
                |
                | Menu Item + Variant + Raw Material
                |
                | OR
                |
                | Add-on + Raw Material
                |
                |--------------------------------------------------------------------------
                */

                $duplicateQuery =
                    RecipeMapping::query()
                        ->where(
                            'raw_material_id',
                            $rawMaterial->id
                        );


                /*
                |--------------------------------------------------------------------------
                | Menu Item Recipe
                |--------------------------------------------------------------------------
                */

                if (
                    $hasMenuItem
                ) {

                    $duplicateQuery
                        ->where(
                            'menu_item_id',
                            $menuItemId
                        )
                        ->whereNull(
                            'add_on_id'
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Variant-specific duplicate
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $variantId !== null
                    ) {

                        $duplicateQuery
                            ->where(
                                'variant_id',
                                $variantId
                            );

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Direct Menu Item Recipe
                        |--------------------------------------------------------------------------
                        */

                        $duplicateQuery
                            ->whereNull(
                                'variant_id'
                            );
                    }

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Add-on Recipe
                    |--------------------------------------------------------------------------
                    */

                    $duplicateQuery
                        ->whereNull(
                            'menu_item_id'
                        )
                        ->whereNull(
                            'variant_id'
                        )
                        ->where(
                            'add_on_id',
                            $addOnId
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Ignore Current Row During Update
                |--------------------------------------------------------------------------
                */

                if (
                    $mapping->exists
                ) {

                    $duplicateQuery->where(
                        $mapping->getKeyName(),
                        '!=',
                        $mapping->getKey()
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Duplicate Found
                |--------------------------------------------------------------------------
                */

                if (
                    $duplicateQuery->exists()
                ) {

                    throw ValidationException::withMessages([

                        'raw_material_id' => [

                            "The raw material \"{$rawMaterial->material_name}\" is already included in this recipe.",

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

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(
            MenuItem::class,
            'menu_item_id'
        );
    }


    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            MenuItemVariant::class,
            'variant_id'
        );
    }


    public function addOn(): BelongsTo
    {
        return $this->belongsTo(
            AddOn::class,
            'add_on_id'
        );
    }


    public function rawMaterial(): BelongsTo
    {
        return $this->belongsTo(
            RawMaterial::class,
            'raw_material_id'
        )->withTrashed();
    }


    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Target Helpers
    |--------------------------------------------------------------------------
    */

    public function isMenuItemRecipe(): bool
    {
        return (

            $this->menu_item_id !== null

            &&

            (int) $this->menu_item_id > 0

            &&

            $this->add_on_id === null

        );
    }


    public function isAddOnRecipe(): bool
    {
        return (

            $this->add_on_id !== null

            &&

            (int) $this->add_on_id > 0

            &&

            $this->menu_item_id === null

        );
    }


    public function isVariantRecipe(): bool
    {
        return (

            $this->isMenuItemRecipe()

            &&

            $this->variant_id !== null

            &&

            (int) $this->variant_id > 0

        );
    }


    public function isDirectMenuItemRecipe(): bool
    {
        return (

            $this->isMenuItemRecipe()

            &&

            $this->variant_id === null

        );
    }


    public function getTargetTypeAttribute(): string
    {
        return $this->isAddOnRecipe()

            ? 'add_on'

            : 'menu_item';
    }


    public function getTargetIdAttribute(): int
    {
        return $this->isAddOnRecipe()

            ? (int) $this->add_on_id

            : (int) $this->menu_item_id;
    }


    /*
    |--------------------------------------------------------------------------
    | Recipe Identity
    |--------------------------------------------------------------------------
    |
    | Useful for frontend / service / debugging.
    |
    | Examples:
    |
    | menu_item:5:0
    | menu_item:5:2
    | add_on:3:0
    |
    */

    public function getRecipeIdentityAttribute(): string
    {
        return (

            $this->target_type

            . ':'

            . $this->target_id

            . ':'

            . (
                $this->isVariantRecipe()
                    ? (int) $this->variant_id
                    : 0
            )

        );
    }
}