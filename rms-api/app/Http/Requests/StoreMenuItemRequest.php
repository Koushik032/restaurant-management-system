<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'menu_category_id' => [
                'required',
                'integer',
                Rule::exists('menu_categories', 'id')
                    ->whereNull('deleted_at'),
            ],

            'menu_name' => [
                'required',
                'string',
                'max:180',
            ],

            'item_type' => [
                'required',
                Rule::in(MenuItem::allowedTypes()),
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'ingredients' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
            ],

            'preparation_time' => [
                'nullable',
                'integer',
                'min:0',
                'max:1440',
            ],

            'is_available' => [
                'sometimes',
                'boolean',
            ],

            'is_featured' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'menu_category_id.required' => 'Menu category is required.',
            'menu_category_id.integer' => 'Menu category ID must be a valid number.',
            'menu_category_id.exists' => 'The selected menu category does not exist.',

            'menu_name.required' => 'Menu item name is required.',
            'menu_name.string' => 'Menu item name must be valid text.',
            'menu_name.max' => 'Menu item name cannot be longer than 180 characters.',

            'item_type.required' => 'Menu item type is required.',
            'item_type.in' => 'Menu item type must be regular, combo, or set meal.',

            'price.required' => 'Menu item price is required.',
            'price.numeric' => 'Menu item price must be numeric.',
            'price.min' => 'Menu item price cannot be negative.',
            'price.max' => 'Menu item price is too large.',

            'ingredients.string' => 'Ingredients must be valid text.',
            'ingredients.max' => 'Ingredients cannot be longer than 5000 characters.',

            'description.string' => 'Description must be valid text.',
            'description.max' => 'Description cannot be longer than 5000 characters.',

            'image.image' => 'The uploaded file must be an image.',
            'image.mimes' => 'The image must be JPG, JPEG, PNG, or WEBP.',
            'image.max' => 'The image size cannot be larger than 3 MB.',

            'preparation_time.integer' => 'Preparation time must be a whole number.',
            'preparation_time.min' => 'Preparation time cannot be negative.',
            'preparation_time.max' => 'Preparation time cannot exceed 1440 minutes.',

            'is_available.boolean' => 'Availability status must be true or false.',
            'is_featured.boolean' => 'Featured status must be true or false.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('menu_name')) {
            $data['menu_name'] = trim((string) $this->menu_name);
        }

        if ($this->has('item_type')) {
            $data['item_type'] = strtolower(
                trim((string) $this->item_type)
            );
        }

        if ($this->has('ingredients')) {
            $data['ingredients'] = $this->ingredients !== null
                ? trim((string) $this->ingredients)
                : null;
        }

        if ($this->has('description')) {
            $data['description'] = $this->description !== null
                ? trim((string) $this->description)
                : null;
        }

        $this->merge($data);
    }
}