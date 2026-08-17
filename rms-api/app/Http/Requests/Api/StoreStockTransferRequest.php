<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StoreStockTransferRequest extends FormRequest
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
    */

    protected function prepareForValidation(): void
    {
        $data = [];


        /*
        |--------------------------------------------------------------------------
        | Transfer Date
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'transferred_at'
            )
        ) {
            $transferredAt =
                trim(
                    (string) $this->input(
                        'transferred_at'
                    )
                );


            $data['transferred_at'] =
                $transferredAt !== ''
                    ? $transferredAt
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Transfer Notes
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


        /*
        |--------------------------------------------------------------------------
        | Transfer Items
        |--------------------------------------------------------------------------
        */

        $items =
            $this->input(
                'items'
            );


        if (is_array($items)) {

            $data['items'] =
                collect($items)

                    ->map(

                        static function (
                            mixed $item
                        ): mixed {

                            if (!is_array($item)) {
                                return $item;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Item Notes
                            |--------------------------------------------------------------------------
                            */

                            if (
                                array_key_exists(
                                    'notes',
                                    $item
                                )
                            ) {
                                $notes =
                                    trim(
                                        (string) (
                                            $item['notes']
                                            ?? ''
                                        )
                                    );


                                $item['notes'] =
                                    $notes !== ''
                                        ? $notes
                                        : null;
                            }


                            return $item;
                        }

                    )

                    ->values()

                    ->all();
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
            | Transfer Date
            |--------------------------------------------------------------------------
            |
            | Null means StockTransferService may use current timestamp.
            |
            */

            'transferred_at' => [
                'bail',
                'nullable',
                'date',
            ],


            /*
            |--------------------------------------------------------------------------
            | Transfer Notes
            |--------------------------------------------------------------------------
            */

            'notes' => [
                'bail',
                'nullable',
                'string',
                'max:2000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Transfer Items
            |--------------------------------------------------------------------------
            */

            'items' => [
                'bail',
                'required',
                'array',
                'min:1',
                'max:200',
            ],


            /*
            |--------------------------------------------------------------------------
            | Raw Material
            |--------------------------------------------------------------------------
            |
            | Only active, non-deleted raw materials may be transferred.
            |
            */

            'items.*.raw_material_id' => [
                'bail',
                'required',
                'integer',
                'distinct',

                Rule::exists(
                    'raw_materials',
                    'id'
                )
                    ->where(
                        static function (
                            $query
                        ): void {

                            $query
                                ->whereNull(
                                    'deleted_at'
                                )
                                ->where(
                                    'is_active',
                                    true
                                );
                        }
                    ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Transfer Quantity
            |--------------------------------------------------------------------------
            |
            | Inventory quantities use 4-decimal precision.
            |
            */

            'items.*.quantity' => [
                'bail',
                'required',
                'numeric',
                'decimal:0,4',
                'gt:0',
                'max:9999999999.9999',
            ],


            /*
            |--------------------------------------------------------------------------
            | Item Notes
            |--------------------------------------------------------------------------
            */

            'items.*.notes' => [
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

            /*
            |--------------------------------------------------------------------------
            | Transfer Date
            |--------------------------------------------------------------------------
            */

            'transferred_at.date' =>
                'Transfer date must be a valid date.',


            /*
            |--------------------------------------------------------------------------
            | Transfer Notes
            |--------------------------------------------------------------------------
            */

            'notes.string' =>
                'Transfer notes must be valid text.',


            'notes.max' =>
                'Transfer notes cannot exceed 2000 characters.',


            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items.required' =>
                'At least one raw material is required for transfer.',


            'items.array' =>
                'Transfer items must be provided as a valid list.',


            'items.min' =>
                'At least one raw material is required for transfer.',


            'items.max' =>
                'A maximum of 200 raw materials can be transferred at once.',


            /*
            |--------------------------------------------------------------------------
            | Raw Material
            |--------------------------------------------------------------------------
            */

            'items.*.raw_material_id.required' =>
                'Please select a raw material for each transfer item.',


            'items.*.raw_material_id.integer' =>
                'The selected raw material is invalid.',


            'items.*.raw_material_id.distinct' =>
                'The same raw material cannot be transferred more than once in the same request.',


            'items.*.raw_material_id.exists' =>
                'The selected raw material was not found, is inactive, or has been deleted.',


            /*
            |--------------------------------------------------------------------------
            | Quantity
            |--------------------------------------------------------------------------
            */

            'items.*.quantity.required' =>
                'Transfer quantity is required.',


            'items.*.quantity.numeric' =>
                'Transfer quantity must be numeric.',


            'items.*.quantity.decimal' =>
                'Transfer quantity may contain up to 4 decimal places.',


            'items.*.quantity.gt' =>
                'Transfer quantity must be greater than zero.',


            'items.*.quantity.max' =>
                'Transfer quantity exceeds the maximum allowed value.',


            /*
            |--------------------------------------------------------------------------
            | Item Notes
            |--------------------------------------------------------------------------
            */

            'items.*.notes.string' =>
                'Transfer item notes must be valid text.',


            'items.*.notes.max' =>
                'Transfer item notes cannot exceed 2000 characters.',
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

            'transferred_at' =>
                'transfer date',


            'notes' =>
                'transfer notes',


            'items' =>
                'transfer items',


            'items.*.raw_material_id' =>
                'raw material',


            'items.*.quantity' =>
                'transfer quantity',


            'items.*.notes' =>
                'item notes',
        ];
    }
}