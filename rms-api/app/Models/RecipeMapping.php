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
    | RecipeMapping is a mutable recipe definition.
    |
    | A row belongs to exactly one recipe target:
    |
    |     Menu Item OR Add-on
    |
    | Never both and never neither.
    |
    */

    protected static function booted(): void
    {
        static::saving(
            function (
                RecipeMapping $mapping
            ): void {

                /*
                |------------------------------------------------------------------
                | Exactly One Target
                |------------------------------------------------------------------
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


                if (
                    $hasMenuItem === $hasAddOn
                ) {
                    throw ValidationException::withMessages([
                        'target' => [
                            'A recipe mapping must belong to exactly one target: either a menu item or an add-on.',
                        ],
                    ]);
                }


                $mapping->menu_item_id =
                    $hasMenuItem
                        ? $menuItemId
                        : null;

                $mapping->add_on_id =
                    $hasAddOn
                        ? $addOnId
                        : null;


                /*
                |------------------------------------------------------------------
                | Raw Material
                |------------------------------------------------------------------
                */

                $rawMaterialId =
                    (int) (
                        $mapping->raw_material_id
                        ?? 0
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
                    ! $rawMaterial
                ) {
                    throw ValidationException::withMessages([
                        'raw_material_id' => [
                            'The selected raw material is unavailable or deleted.',
                        ],
                    ]);
                }


                if (
                    ! $rawMaterial->is_active
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
                |------------------------------------------------------------------
                | Quantity
                |------------------------------------------------------------------
                */

                $quantity =
                    round(
                        (float) (
                            $mapping->quantity
                            ?? 0
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
                    $quantity > 9999999999.9999
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
                |------------------------------------------------------------------
                | Authoritative Unit
                |------------------------------------------------------------------
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
                                ?? ''
                            )
                        )
                    );


                if (
                    $submittedUnit !== ''
                    &&
                    $submittedUnit !== $baseUnit
                ) {
                    throw ValidationException::withMessages([
                        'unit' => [
                            "Recipe unit must match the raw material base unit ({$rawMaterial->base_unit}).",
                        ],
                    ]);
                }


                $mapping->unit =
                    $baseUnit;


                /*
                |------------------------------------------------------------------
                | Notes
                |------------------------------------------------------------------
                */

                $notes =
                    trim(
                        (string) (
                            $mapping->notes
                            ?? ''
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
                |------------------------------------------------------------------
                | Duplicate Ingredient Protection
                |------------------------------------------------------------------
                |
                | A raw material may appear only once inside one Menu Item recipe
                | or inside one Add-on recipe.
                |
                */

                $duplicateQuery =
                    RecipeMapping::query()
                        ->where(
                            'raw_material_id',
                            $rawMaterial->id
                        );


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
                }
                else {
                    $duplicateQuery
                        ->whereNull(
                            'menu_item_id'
                        )
                        ->where(
                            'add_on_id',
                            $addOnId
                        );
                }


                if (
                    $mapping->exists
                ) {
                    $duplicateQuery->where(
                        $mapping->getKeyName(),
                        '!=',
                        $mapping->getKey()
                    );
                }


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
        return
            $this->menu_item_id !== null
            &&
            (int) $this->menu_item_id > 0;
    }


    public function isAddOnRecipe(): bool
    {
        return
            $this->add_on_id !== null
            &&
            (int) $this->add_on_id > 0;
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
}