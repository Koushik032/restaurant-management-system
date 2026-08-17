<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class AdjustWarehouseStockRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Adjustment Types
    |--------------------------------------------------------------------------
    */

    public const TYPE_INCREASE = 'increase';

    public const TYPE_DECREASE = 'decrease';


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
        | Adjustment Type
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'adjustment_type'
            )
        ) {
            $adjustmentType =
                strtolower(
                    trim(
                        (string) $this->input(
                            'adjustment_type'
                        )
                    )
                );


            $data['adjustment_type'] =
                $adjustmentType !== ''
                    ? $adjustmentType
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Unit Cost
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'unit_cost'
            )
        ) {
            $unitCost =
                $this->input(
                    'unit_cost'
                );


            if (
                $unitCost === null
                ||
                (
                    is_string($unitCost)
                    &&
                    trim($unitCost) === ''
                )
            ) {
                $data['unit_cost'] =
                    null;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'notes'
            )
        ) {
            $notes =
                trim(
                    (string) $this->input(
                        'notes'
                    )
                );


            $data['notes'] =
                $notes !== ''
                    ? $notes
                    : null;
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
            | Adjustment Type
            |--------------------------------------------------------------------------
            */

            'adjustment_type' => [
                'bail',
                'required',
                'string',

                Rule::in(
                    self::allowedTypes()
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            |
            | Inventory quantities use 4-decimal precision.
            |
            */

            'quantity' => [
                'bail',
                'required',
                'numeric',
                'decimal:0,4',
                'gt:0',
                'max:99999999999999.9999',
            ],


            /*
            |--------------------------------------------------------------------------
            | Unit Cost
            |--------------------------------------------------------------------------
            |
            | Increase:
            |   Optional.
            |
            |   When provided, InventoryService uses it in weighted average
            |   warehouse costing.
            |
            |   When omitted, existing warehouse average cost is preserved.
            |
            | Decrease:
            |   Must not be supplied because stock leaves warehouse using the
            |   existing warehouse average cost.
            |
            */

            'unit_cost' => [
                'bail',

                'prohibited_if:adjustment_type,'
                    . self::TYPE_DECREASE,

                'nullable',
                'numeric',
                'decimal:0,4',
                'min:0',
                'max:99999999999999.9999',
            ],


            /*
            |--------------------------------------------------------------------------
            | Adjustment Reason
            |--------------------------------------------------------------------------
            */

            'notes' => [
                'bail',
                'required',
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

            /*
            |--------------------------------------------------------------------------
            | Adjustment Type
            |--------------------------------------------------------------------------
            */

            'adjustment_type.required' =>
                'Adjustment type is required.',


            'adjustment_type.string' =>
                'Adjustment type must be valid text.',


            'adjustment_type.in' =>
                'Adjustment type must be increase or decrease.',


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'quantity.required' =>
                'Adjustment quantity is required.',


            'quantity.numeric' =>
                'Adjustment quantity must be numeric.',


            'quantity.decimal' =>
                'Adjustment quantity may contain up to 4 decimal places.',


            'quantity.gt' =>
                'Adjustment quantity must be greater than zero.',


            'quantity.max' =>
                'Adjustment quantity exceeds the maximum allowed value.',


            /*
            |--------------------------------------------------------------------------
            | Unit Cost
            |--------------------------------------------------------------------------
            */

            'unit_cost.prohibited_if' =>
                'Unit cost cannot be entered for a stock decrease because the current warehouse average cost is used automatically.',


            'unit_cost.numeric' =>
                'Unit cost must be numeric.',


            'unit_cost.decimal' =>
                'Unit cost may contain up to 4 decimal places.',


            'unit_cost.min' =>
                'Unit cost cannot be negative.',


            'unit_cost.max' =>
                'Unit cost exceeds the maximum allowed value.',


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes.required' =>
                'Adjustment reason is required.',


            'notes.string' =>
                'Adjustment reason must be valid text.',


            'notes.max' =>
                'Adjustment reason cannot exceed 2000 characters.',
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

            'adjustment_type' =>
                'adjustment type',


            'quantity' =>
                'adjustment quantity',


            'unit_cost' =>
                'unit cost',


            'notes' =>
                'adjustment reason',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Allowed Types
    |--------------------------------------------------------------------------
    */

    public static function allowedTypes(): array
    {
        return [
            self::TYPE_INCREASE,
            self::TYPE_DECREASE,
        ];
    }
}