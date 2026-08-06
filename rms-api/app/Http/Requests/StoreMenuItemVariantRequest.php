<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_item_id' => [
                'required',
                'integer',

                Rule::exists(
                    'menu_items',
                    'id'
                )->whereNull('deleted_at'),
            ],

            'variant_name' => [
                'required',
                'string',
                'max:120',

                Rule::unique(
                    'menu_item_variants',
                    'variant_name'
                )->where(
                    fn ($query) =>
                    $query
                        ->where(
                            'menu_item_id',
                            $this->input(
                                'menu_item_id'
                            )
                        )
                        ->whereNull(
                            'deleted_at'
                        )
                ),
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'is_available' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'menu_item_id.required' =>
                'Menu item is required.',

            'menu_item_id.exists' =>
                'The selected menu item does not exist.',

            'variant_name.required' =>
                'Variant name is required.',

            'variant_name.max' =>
                'Variant name cannot be longer than 120 characters.',

            'variant_name.unique' =>
                'This variant already exists for the selected menu item.',

            'price.required' =>
                'Variant price is required.',

            'price.numeric' =>
                'Variant price must be numeric.',

            'price.min' =>
                'Variant price cannot be negative.',

            'price.max' =>
                'Variant price is too large.',

            'is_available.boolean' =>
                'Availability status must be true or false.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'menu_item_id' =>
                $this->input(
                    'menu_item_id'
                ) !== ''
                    ? (int) $this->input(
                        'menu_item_id'
                    )
                    : null,

            'variant_name' => trim(
                (string) $this->input(
                    'variant_name'
                )
            ),

            'price' =>
                $this->input('price') !== ''
                    ? $this->input('price')
                    : null,

            'is_available' =>
                $this->boolean(
                    'is_available'
                ),
        ]);
    }
}