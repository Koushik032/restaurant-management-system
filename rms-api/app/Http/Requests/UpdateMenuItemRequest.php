<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized
     * to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'menu_category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists(
                    'menu_categories',
                    'id'
                )->whereNull('deleted_at'),
            ],

            'menu_name' => [
                'sometimes',
                'required',
                'string',
                'max:180',
            ],

            'item_type' => [
                'sometimes',
                'required',
                Rule::in(
                    MenuItem::allowedTypes()
                ),
            ],

            'price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'ingredients' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            'image' => [
                'sometimes',
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],

            'remove_image' => [
                'sometimes',
                'boolean',
            ],

            'preparation_time' => [
                'sometimes',
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

    /**
     * Validation error messages.
     */
    public function messages(): array
    {
        return [
            'menu_category_id.required' =>
                'Menu category is required.',

            'menu_category_id.integer' =>
                'Menu category ID must be a valid number.',

            'menu_category_id.exists' =>
                'The selected menu category does not exist.',

            'menu_name.required' =>
                'Menu item name is required.',

            'menu_name.string' =>
                'Menu item name must be valid text.',

            'menu_name.max' =>
                'Menu item name cannot be longer than 180 characters.',

            'item_type.required' =>
                'Menu item type is required.',

            'item_type.in' =>
                'Menu item type must be regular, combo, or set meal.',

            'price.required' =>
                'Menu item price is required.',

            'price.numeric' =>
                'Menu item price must be numeric.',

            'price.min' =>
                'Menu item price cannot be negative.',

            'price.max' =>
                'Menu item price is too large.',

            'ingredients.string' =>
                'Ingredients must be valid text.',

            'ingredients.max' =>
                'Ingredients cannot be longer than 5000 characters.',

            'description.string' =>
                'Description must be valid text.',

            'description.max' =>
                'Description cannot be longer than 5000 characters.',

            'image.image' =>
                'The uploaded file must be an image.',

            'image.mimes' =>
                'The image must be JPG, JPEG, PNG, or WEBP.',

            'image.max' =>
                'The image size cannot be larger than 3 MB.',

            'remove_image.boolean' =>
                'Remove image value must be true or false.',

            'preparation_time.integer' =>
                'Preparation time must be a whole number.',

            'preparation_time.min' =>
                'Preparation time cannot be negative.',

            'preparation_time.max' =>
                'Preparation time cannot exceed 1440 minutes.',

            'is_available.boolean' =>
                'Availability status must be true or false.',

            'is_featured.boolean' =>
                'Featured status must be true or false.',
        ];
    }

    /**
     * Prepare the data before validation.
     */
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('menu_category_id')) {
            $data['menu_category_id'] =
                $this->input(
                    'menu_category_id'
                ) !== ''
                    ? (int) $this->input(
                        'menu_category_id'
                    )
                    : null;
        }

        if ($this->has('menu_name')) {
            $data['menu_name'] = trim(
                (string) $this->input(
                    'menu_name'
                )
            );
        }

        if ($this->has('item_type')) {
            $data['item_type'] = strtolower(
                trim(
                    (string) $this->input(
                        'item_type'
                    )
                )
            );
        }

        if ($this->has('price')) {
            $data['price'] =
                $this->input('price') !== ''
                    ? $this->input('price')
                    : null;
        }

        if ($this->has('ingredients')) {
            $ingredients =
                $this->input('ingredients');

            $data['ingredients'] =
                $ingredients !== null &&
                $ingredients !== ''
                    ? trim(
                        (string) $ingredients
                    )
                    : null;
        }

        if ($this->has('description')) {
            $description =
                $this->input('description');

            $data['description'] =
                $description !== null &&
                $description !== ''
                    ? trim(
                        (string) $description
                    )
                    : null;
        }

        if (
            $this->has('preparation_time')
        ) {
            $preparationTime =
                $this->input(
                    'preparation_time'
                );

            $data['preparation_time'] =
                $preparationTime !== null &&
                $preparationTime !== ''
                    ? (int) $preparationTime
                    : null;
        }

        if ($this->has('is_available')) {
            $data['is_available'] =
                $this->convertToBoolean(
                    $this->input(
                        'is_available'
                    )
                );
        }

        if ($this->has('is_featured')) {
            $data['is_featured'] =
                $this->convertToBoolean(
                    $this->input(
                        'is_featured'
                    )
                );
        }

        if ($this->has('remove_image')) {
            $data['remove_image'] =
                $this->convertToBoolean(
                    $this->input(
                        'remove_image'
                    )
                );
        }

        $this->merge($data);
    }

    /**
     * Convert FormData boolean values safely.
     */
    private function convertToBoolean(
        mixed $value
    ): bool {
        return in_array(
            $value,
            [
                true,
                1,
                '1',
                'true',
                'on',
                'yes',
            ],
            true
        );
    }
}