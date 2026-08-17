<?php

namespace App\Http\Requests\Api;

use App\Models\RawMaterial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class UpdateRawMaterialRequest extends FormRequest
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
    | Prepare Data
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
        $rawMaterial =
            $this->getRawMaterial();


        $rawMaterialId =
            $rawMaterial?->getKey();


        return [

            /*
            |--------------------------------------------------------------------------
            | Material Name
            |--------------------------------------------------------------------------
            */

            'material_name' => [
                'bail',
                'sometimes',
                'required',
                'string',
                'max:180',

                Rule::unique(
                    'raw_materials',
                    'material_name'
                )
                    ->ignore(
                        $rawMaterialId
                    )
                    ->withoutTrashed(),
            ],


            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category' => [
                'bail',
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],


            /*
            |--------------------------------------------------------------------------
            | Base Unit
            |--------------------------------------------------------------------------
            |
            | Request validates the unit itself.
            |
            | Whether the existing material is allowed to CHANGE its base unit
            | is a business rule handled by InventoryService because stock and
            | historical inventory records must be checked transactionally.
            |
            */

            'base_unit' => [
                'bail',
                'sometimes',
                'required',
                'string',
                'max:30',

                Rule::in(
                    RawMaterial::allowedUnits()
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Minimum Stock
            |--------------------------------------------------------------------------
            |
            | Inventory quantities use 4-decimal precision.
            |
            */

            'warehouse_minimum_quantity' => [
                'bail',
                'sometimes',
                'required',
                'numeric',
                'decimal:0,4',
                'min:0',
                'max:99999999999999.9999',
            ],


            'restaurant_minimum_quantity' => [
                'bail',
                'sometimes',
                'required',
                'numeric',
                'decimal:0,4',
                'min:0',
                'max:99999999999999.9999',
            ],


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | InventoryService remains authoritative for whether a material can
            | be deactivated while it is still referenced by active operations.
            |
            */

            'is_active' => [
                'bail',
                'sometimes',
                'boolean',
            ],


            /*
            |--------------------------------------------------------------------------
            | Opening Stock Protection
            |--------------------------------------------------------------------------
            |
            | Opening stock belongs only to raw-material creation.
            | Existing stock must be changed through inventory adjustment flows.
            |
            */

            'opening_quantity' => [
                'prohibited',
            ],


            'opening_unit_cost' => [
                'prohibited',
            ],


            'opening_stock_notes' => [
                'prohibited',
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

            /*
            |--------------------------------------------------------------------------
            | Material Name
            |--------------------------------------------------------------------------
            */

            'material_name.required' =>
                'Raw material name is required.',


            'material_name.string' =>
                'Raw material name must be valid text.',


            'material_name.max' =>
                'Raw material name cannot exceed 180 characters.',


            'material_name.unique' =>
                'This raw material already exists.',


            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'category.string' =>
                'Category must be valid text.',


            'category.max' =>
                'Category cannot exceed 100 characters.',


            /*
            |--------------------------------------------------------------------------
            | Base Unit
            |--------------------------------------------------------------------------
            */

            'base_unit.required' =>
                'Base unit is required.',


            'base_unit.string' =>
                'Base unit must be valid text.',


            'base_unit.in' =>
                'The selected base unit is invalid.',


            /*
            |--------------------------------------------------------------------------
            | Warehouse Minimum
            |--------------------------------------------------------------------------
            */

            'warehouse_minimum_quantity.required' =>
                'Warehouse minimum quantity is required.',


            'warehouse_minimum_quantity.numeric' =>
                'Warehouse minimum quantity must be numeric.',


            'warehouse_minimum_quantity.decimal' =>
                'Warehouse minimum quantity may contain up to 4 decimal places.',


            'warehouse_minimum_quantity.min' =>
                'Warehouse minimum quantity cannot be negative.',


            'warehouse_minimum_quantity.max' =>
                'Warehouse minimum quantity exceeds the maximum allowed value.',


            /*
            |--------------------------------------------------------------------------
            | Restaurant Minimum
            |--------------------------------------------------------------------------
            */

            'restaurant_minimum_quantity.required' =>
                'Restaurant minimum quantity is required.',


            'restaurant_minimum_quantity.numeric' =>
                'Restaurant minimum quantity must be numeric.',


            'restaurant_minimum_quantity.decimal' =>
                'Restaurant minimum quantity may contain up to 4 decimal places.',


            'restaurant_minimum_quantity.min' =>
                'Restaurant minimum quantity cannot be negative.',


            'restaurant_minimum_quantity.max' =>
                'Restaurant minimum quantity exceeds the maximum allowed value.',


            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_active.boolean' =>
                'Active status must be true or false.',


            /*
            |--------------------------------------------------------------------------
            | Opening Stock Protection
            |--------------------------------------------------------------------------
            */

            'opening_quantity.prohibited' =>
                'Opening quantity cannot be changed after the raw material has been created. Please use a stock adjustment instead.',


            'opening_unit_cost.prohibited' =>
                'Opening unit cost cannot be changed after the raw material has been created. Please use the inventory adjustment flow instead.',


            'opening_stock_notes.prohibited' =>
                'Opening stock notes cannot be edited from the raw material update endpoint.',
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


            'is_active' =>
                'active status',


            'opening_quantity' =>
                'opening quantity',


            'opening_unit_cost' =>
                'opening unit cost',


            'opening_stock_notes' =>
                'opening stock notes',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Raw Material
    |--------------------------------------------------------------------------
    */

    private function getRawMaterial(): ?RawMaterial
    {
        $rawMaterial =
            $this->route(
                'rawMaterial'
            )
            ??
            $this->route(
                'raw_material'
            );


        if (
            $rawMaterial
            instanceof
            RawMaterial
        ) {
            return $rawMaterial;
        }


        if (
            is_numeric(
                $rawMaterial
            )
            &&
            (int) $rawMaterial > 0
        ) {
            return RawMaterial::query()
                ->find(
                    (int) $rawMaterial
                );
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Boolean Normalizer
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