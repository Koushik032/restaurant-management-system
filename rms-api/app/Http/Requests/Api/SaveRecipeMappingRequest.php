<?php

namespace App\Http\Requests\Api;

use App\Models\AddOn;
use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class SaveRecipeMappingRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return $this->user()?->hasPermission(
            'inventory.manage'
        ) ?? false;
    }


    /*
    |--------------------------------------------------------------------------
    | Prepare Input
    |--------------------------------------------------------------------------
    |
    | Supports both:
    |
    | New unified request:
    |     target_type
    |     target_id
    |
    | Legacy route:
    |     /recipe-mappings/{menuItem}
    |
    */

    protected function prepareForValidation(): void
    {
        $targetType =
            $this->input(
                'target_type'
            );

        $targetId =
            $this->input(
                'target_id'
            );


        /*
        |--------------------------------------------------------------------------
        | Normalize Target Type
        |--------------------------------------------------------------------------
        */

        if (
            is_string(
                $targetType
            )
        ) {
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

            $targetType =
                match (
                    $targetType
                ) {
                    'menu_item',
                    'menuitem' =>
                        'menu_item',

                    'add_on',
                    'addon' =>
                        'add_on',

                    default =>
                        $targetType,
                };
        }


        /*
        |--------------------------------------------------------------------------
        | Legacy Menu Item Route Fallback
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $targetType
            )
            ||
            empty(
                $targetId
            )
        ) {
            $menuItem =
                $this->route(
                    'menuItem'
                );

            if (
                $menuItem instanceof MenuItem
            ) {
                $targetType =
                    'menu_item';

                $targetId =
                    $menuItem->getKey();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Optional Add-on Route Fallback
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $targetType
            )
            ||
            empty(
                $targetId
            )
        ) {
            $addOn =
                $this->route(
                    'addOn'
                );

            if (
                $addOn instanceof AddOn
            ) {
                $targetType =
                    'add_on';

                $targetId =
                    $addOn->getKey();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Normalize Ingredients
        |--------------------------------------------------------------------------
        */

        $ingredients =
            $this->input(
                'ingredients'
            );


        if (
            is_array(
                $ingredients
            )
        ) {
            $ingredients =
                collect(
                    $ingredients
                )

                    ->map(
                        static function (
                            mixed $ingredient
                        ): mixed {

                            if (
                                ! is_array(
                                    $ingredient
                                )
                            ) {
                                return $ingredient;
                            }


                            if (
                                array_key_exists(
                                    'notes',
                                    $ingredient
                                )
                            ) {
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

                                $ingredient[
                                    'notes'
                                ] =
                                    $notes !== ''
                                        ? $notes
                                        : null;
                            }


                            return $ingredient;
                        }
                    )

                    ->values()

                    ->all();
        }


        $this->merge([
            'target_type' =>
                $targetType,

            'target_id' =>
                $targetId,

            'ingredients' =>
                $ingredients,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        $targetType =
            $this->input(
                'target_type'
            );


        $targetTable =
            $targetType === 'add_on'
                ? 'add_ons'
                : 'menu_items';


        return [

            /*
            |--------------------------------------------------------------------------
            | Recipe Target
            |--------------------------------------------------------------------------
            */

            'target_type' => [
                'bail',
                'required',
                'string',

                Rule::in([
                    'menu_item',
                    'add_on',
                ]),
            ],


            'target_id' => [
                'bail',
                'required',
                'integer',
                'min:1',

                Rule::exists(
                    $targetTable,
                    'id'
                )
                    ->whereNull(
                        'deleted_at'
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Ingredients
            |--------------------------------------------------------------------------
            |
            | [] is intentionally allowed.
            | Empty array means clear/delete the target recipe.
            |
            */

            'ingredients' => [
                'bail',
                'required',
                'array',
                'max:200',
            ],


            /*
            |--------------------------------------------------------------------------
            | Raw Material
            |--------------------------------------------------------------------------
            */

            'ingredients.*.raw_material_id' => [
                'bail',
                'required',
                'integer',
                'distinct',

                Rule::exists(
                    'raw_materials',
                    'id'
                )
                    ->whereNull(
                        'deleted_at'
                    )
                    ->where(
                        'is_active',
                        true
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'ingredients.*.quantity' => [
                'bail',
                'required',
                'numeric',
                'decimal:0,4',
                'gt:0',
                'max:9999999999.9999',
            ],


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'ingredients.*.notes' => [
                'bail',
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [

            'target_type.required' =>
                'Please select whether this recipe belongs to a menu item or an add-on.',

            'target_type.in' =>
                'Recipe target type must be menu_item or add_on.',


            'target_id.required' =>
                'Please select a menu item or add-on for this recipe.',

            'target_id.integer' =>
                'The selected recipe target is invalid.',

            'target_id.min' =>
                'The selected recipe target is invalid.',

            'target_id.exists' =>
                'The selected menu item or add-on was not found or has been archived.',


            'ingredients.required' =>
                'Recipe ingredients are required.',

            'ingredients.array' =>
                'Recipe ingredients must be provided as a valid list.',

            'ingredients.max' =>
                'A maximum of 200 ingredients can be mapped to one recipe target.',


            'ingredients.*.raw_material_id.required' =>
                'Please select a raw material for each recipe ingredient.',

            'ingredients.*.raw_material_id.integer' =>
                'The selected raw material is invalid.',

            'ingredients.*.raw_material_id.distinct' =>
                'The same raw material cannot be added more than once to the same recipe.',

            'ingredients.*.raw_material_id.exists' =>
                'The selected raw material was not found, is inactive, or is archived.',


            'ingredients.*.quantity.required' =>
                'Recipe ingredient quantity is required.',

            'ingredients.*.quantity.numeric' =>
                'Recipe ingredient quantity must be numeric.',

            'ingredients.*.quantity.decimal' =>
                'Recipe ingredient quantity may contain up to 4 decimal places.',

            'ingredients.*.quantity.gt' =>
                'Recipe ingredient quantity must be greater than zero.',

            'ingredients.*.quantity.max' =>
                'Recipe ingredient quantity is too large.',


            'ingredients.*.notes.string' =>
                'Recipe ingredient notes must be valid text.',

            'ingredients.*.notes.max' =>
                'Recipe ingredient notes cannot exceed 2000 characters.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Attributes
    |--------------------------------------------------------------------------
    */

    public function attributes(): array
    {
        return [
            'target_type' =>
                'recipe target type',

            'target_id' =>
                'recipe target',

            'ingredients' =>
                'recipe ingredients',

            'ingredients.*.raw_material_id' =>
                'raw material',

            'ingredients.*.quantity' =>
                'ingredient quantity',

            'ingredients.*.notes' =>
                'ingredient notes',
        ];
    }
}