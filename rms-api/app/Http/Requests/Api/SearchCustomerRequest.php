<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SearchCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'required',
                'string',
                'min:1',
                'max:150',
            ],

            'limit' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'search.required' => 'Please enter a customer name, phone or email.',
            'search.min' => 'Please enter at least one character.',
            'search.max' => 'Customer search cannot exceed 150 characters.',
        ];
    }
}