<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'add_on_name' => [
                'required',
                'string',
                'max:150',

                Rule::unique(
                    'add_ons',
                    'add_on_name'
                )->whereNull('deleted_at'),
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
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
            'add_on_name.required' =>
                'Add-on name is required.',

            'add_on_name.string' =>
                'Add-on name must be valid text.',

            'add_on_name.max' =>
                'Add-on name cannot be longer than 150 characters.',

            'add_on_name.unique' =>
                'An add-on with this name already exists.',

            'price.required' =>
                'Add-on price is required.',

            'price.numeric' =>
                'Add-on price must be numeric.',

            'price.min' =>
                'Add-on price cannot be negative.',

            'price.max' =>
                'Add-on price is too large.',

            'description.string' =>
                'Description must be valid text.',

            'description.max' =>
                'Description cannot be longer than 2000 characters.',

            'is_available.required' =>
                'Availability status is required.',

            'is_available.boolean' =>
                'Availability status must be true or false.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $description = $this->input('description');

        $this->merge([
            'add_on_name' => trim(
                (string) $this->input('add_on_name')
            ),

            'description' => $description !== null
                ? trim((string) $description)
                : null,

            'price' => $this->input('price') !== ''
                ? $this->input('price')
                : null,

            'is_available' => $this->boolean(
                'is_available'
            ),
        ]);
    }
}