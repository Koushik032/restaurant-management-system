<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /*
         * Route model binding থেকে বর্তমান
         * MenuCategory model নেওয়া হচ্ছে।
         */
        $menuCategory = $this->route(
            'menuCategory'
        );

        /*
         * Model পাওয়া গেলে ID,
         * না পাওয়া গেলে route value ব্যবহার করবে।
         */
        $menuCategoryId =
            is_object($menuCategory)
                ? $menuCategory->getKey()
                : $menuCategory;

        return [
            'category_name' => [
                'required',
                'string',
                'max:150',

                Rule::unique(
                    'menu_categories',
                    'category_name'
                )->ignore($menuCategoryId),
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

            'category_name.string' =>
                'The category name must be valid text.',

            'category_name.max' =>
                'The category name may not be greater than 150 characters.',

            'category_name.unique' =>
                'Another category already uses this name.',

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