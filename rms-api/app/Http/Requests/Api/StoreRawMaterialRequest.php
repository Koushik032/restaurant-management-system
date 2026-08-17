<?php

namespace App\Http\Requests\Api;

use App\Models\RawMaterial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;


class StoreRawMaterialRequest extends FormRequest
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
    | Prepare For Validation
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $data = [];


        /*
        |--------------------------------------------------------------------------
        | Material Name
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'material_name'
            )
        ) {
            $data['material_name'] =
                trim(
                    (string) $this->input(
                        'material_name'
                    )
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'category'
            )
        ) {
            $category =
                trim(
                    (string) $this->input(
                        'category'
                    )
                );


            $data['category'] =
                $category !== ''
                    ? $category
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Base Unit
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'base_unit'
            )
        ) {
            $baseUnit =
                strtolower(
                    trim(
                        (string) $this->input(
                            'base_unit'
                        )
                    )
                );


            $data['base_unit'] =
                $baseUnit !== ''
                    ? $baseUnit
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Opening Quantity
        |--------------------------------------------------------------------------
        |
        | Missing/blank opening stock means zero opening stock.
        |
        */

        if (
            !$this->exists(
                'opening_quantity'
            )
        ) {
            $data['opening_quantity'] = 0;
        } else {

            $openingQuantity =
                $this->input(
                    'opening_quantity'
                );


            if (
                $openingQuantity === null
                ||
                (
                    is_string(
                        $openingQuantity
                    )
                    &&
                    trim(
                        $openingQuantity
                    ) === ''
                )
            ) {
                $data['opening_quantity'] = 0;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Opening Unit Cost
        |--------------------------------------------------------------------------
        */

        if (
            !$this->exists(
                'opening_unit_cost'
            )
        ) {
            $data['opening_unit_cost'] = 0;
        } else {

            $openingUnitCost =
                $this->input(
                    'opening_unit_cost'
                );


            if (
                $openingUnitCost === null
                ||
                (
                    is_string(
                        $openingUnitCost
                    )
                    &&
                    trim(
                        $openingUnitCost
                    ) === ''
                )
            ) {
                $data['opening_unit_cost'] = 0;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Opening Stock Notes
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'opening_stock_notes'
            )
        ) {
            $notes =
                trim(
                    (string) $this->input(
                        'opening_stock_notes'
                    )
                );


            $data['opening_stock_notes'] =
                $notes !== ''
                    ? $notes
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Active Status
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'is_active'
            )
            &&
            $this->input(
                'is_active'
            ) !== null
        ) {
            $data['is_active'] =
                $this->normalizeBoolean(
                    $this->input(
                        'is_active'
                    )
                );
        }


        if ($data !== []) {
            $this->merge(
                $data
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Material
            |--------------------------------------------------------------------------
            */

            'material_name' => [
                'bail',
                'required',
                'string',
                'max:180',

                Rule::unique(
                    'raw_materials',
                    'material_name'
                )
                    ->withoutTrashed(),
            ],


            'category' => [
                'bail',
                'nullable',
                'string',
                'max:100',
            ],


            /*
            |--------------------------------------------------------------------------
            | Base Unit
            |--------------------------------------------------------------------------
            */

            'base_unit' => [
                'bail',
                'required',
                'string',
                'max:30',

                Rule::in(
                    RawMaterial::allowedUnits()
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Minimum Quantities
            |--------------------------------------------------------------------------
            |
            | Inventory quantities use 4-decimal precision.
            |
            */

            'warehouse_minimum_quantity' => [
                'bail',
                'required',
                'numeric',
                'decimal:0,4',
                'min:0',
                'max:99999999999999.9999',
            ],


            'restaurant_minimum_quantity' => [
                'bail',
                'required',
                'numeric',
                'decimal:0,4',
                'min:0',
                'max:99999999999999.9999',
            ],


            /*
            |--------------------------------------------------------------------------
            | Opening Stock
            |--------------------------------------------------------------------------
            */

            'opening_quantity' => [
                'bail',
                'nullable',
                'numeric',
                'decimal:0,4',
                'min:0',
                'max:99999999999999.9999',
            ],


            'opening_unit_cost' => [
                'bail',
                'nullable',
                'numeric',
                'decimal:0,4',
                'min:0',
                'max:99999999999999.9999',
            ],


            'opening_stock_notes' => [
                'bail',
                'nullable',
                'string',
                'max:2000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_active' => [
                'bail',
                'sometimes',
                'boolean',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Additional Validation
    |--------------------------------------------------------------------------
    */

    public function withValidator(
        Validator $validator
    ): void {

        $validator->after(
            function (
                Validator $validator
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Opening Stock Cost Consistency
                |--------------------------------------------------------------------------
                |
                | A positive unit cost with zero opening quantity has no opening
                | stock to value and is most likely accidental input.
                |
                */

                $openingQuantity =
                    $this->input(
                        'opening_quantity',
                        0
                    );


                $openingUnitCost =
                    $this->input(
                        'opening_unit_cost',
                        0
                    );


                if (
                    !is_numeric(
                        $openingQuantity
                    )
                    ||
                    !is_numeric(
                        $openingUnitCost
                    )
                ) {
                    return;
                }


                $quantity =
                    round(
                        (float) $openingQuantity,
                        4
                    );


                $unitCost =
                    round(
                        (float) $openingUnitCost,
                        4
                    );


                if (
                    $quantity <= 0
                    &&
                    $unitCost > 0
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'opening_unit_cost',
                            'Opening unit cost must be zero when opening quantity is zero.'
                        );
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [

            'material_name.required' =>
                'Raw material name is required.',

            'material_name.string' =>
                'Raw material name must be valid text.',

            'material_name.max' =>
                'Raw material name cannot exceed 180 characters.',

            'material_name.unique' =>
                'This raw material already exists.',


            'category.string' =>
                'Category must be valid text.',

            'category.max' =>
                'Category cannot exceed 100 characters.',


            'base_unit.required' =>
                'Base unit is required.',

            'base_unit.in' =>
                'The selected base unit is invalid.',


            'warehouse_minimum_quantity.required' =>
                'Warehouse minimum quantity is required.',

            'warehouse_minimum_quantity.numeric' =>
                'Warehouse minimum quantity must be numeric.',

            'warehouse_minimum_quantity.decimal' =>
                'Warehouse minimum quantity may contain up to 4 decimal places.',

            'warehouse_minimum_quantity.min' =>
                'Warehouse minimum quantity cannot be negative.',


            'restaurant_minimum_quantity.required' =>
                'Restaurant minimum quantity is required.',

            'restaurant_minimum_quantity.numeric' =>
                'Restaurant minimum quantity must be numeric.',

            'restaurant_minimum_quantity.decimal' =>
                'Restaurant minimum quantity may contain up to 4 decimal places.',

            'restaurant_minimum_quantity.min' =>
                'Restaurant minimum quantity cannot be negative.',


            'opening_quantity.numeric' =>
                'Opening quantity must be numeric.',

            'opening_quantity.decimal' =>
                'Opening quantity may contain up to 4 decimal places.',

            'opening_quantity.min' =>
                'Opening quantity cannot be negative.',


            'opening_unit_cost.numeric' =>
                'Opening unit cost must be numeric.',

            'opening_unit_cost.decimal' =>
                'Opening unit cost may contain up to 4 decimal places.',

            'opening_unit_cost.min' =>
                'Opening unit cost cannot be negative.',


            'opening_stock_notes.max' =>
                'Opening stock notes cannot exceed 2000 characters.',


            'is_active.boolean' =>
                'Active status must be true or false.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Attribute Names
    |--------------------------------------------------------------------------
    */

    public function attributes(): array
    {
        return [

            'material_name' =>
                'raw material name',

            'category' =>
                'category',

            'base_unit' =>
                'base unit',

            'warehouse_minimum_quantity' =>
                'warehouse minimum quantity',

            'restaurant_minimum_quantity' =>
                'restaurant minimum quantity',

            'opening_quantity' =>
                'opening quantity',

            'opening_unit_cost' =>
                'opening unit cost',

            'opening_stock_notes' =>
                'opening stock notes',

            'is_active' =>
                'active status',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Boolean
    |--------------------------------------------------------------------------
    */

    private function normalizeBoolean(
        mixed $value
    ): mixed {

        if (is_bool($value)) {
            return $value;
        }


        if (
            is_int($value)
            ||
            is_float($value)
        ) {

            if (
                (float) $value
                ===
                1.0
            ) {
                return true;
            }


            if (
                (float) $value
                ===
                0.0
            ) {
                return false;
            }


            return $value;
        }


        if (is_string($value)) {

            return match (
                strtolower(
                    trim(
                        $value
                    )
                )
            ) {

                '1',
                'true',
                'on',
                'yes' =>
                    true,


                '0',
                'false',
                'off',
                'no' =>
                    false,


                default =>
                    $value,
            };
        }


        return $value;
    }
}