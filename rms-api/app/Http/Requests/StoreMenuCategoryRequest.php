<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_name' => [
                'required',
                'string',
                'max:150',

                Rule::unique(
                    'menu_categories',
                    'category_name'
                ),
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
            'category_name.required' =>
                'The category name is required.',

            'category_name.unique' =>
                'A category with this name already exists.',

            'is_available.required' =>
                'The availability status is required.',

            'is_available.boolean' =>
                'The availability status must be true or false.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $categoryName =
            $this->input('category_name');

        $this->merge([
            'category_name' =>
                is_string($categoryName)
                    ? trim($categoryName)
                    : $categoryName,

            'is_available' =>
                $this->boolean(
                    'is_available'
                ),
        ]);
    }
}