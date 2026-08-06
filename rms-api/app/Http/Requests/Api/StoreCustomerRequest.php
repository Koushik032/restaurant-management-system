<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorize
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Data
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(
                (string) $this->input('name')
            ),

            'phone' => trim(
                (string) $this->input('phone')
            ),

            'email' => strtolower(
                trim(
                    (string) $this->input('email')
                )
            ),

            'notes' => trim(
                (string) $this->input('notes')
            ),
        ]);
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
            | Customer Name
            |--------------------------------------------------------------------------
            */

            'name' => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],

            /*
            |--------------------------------------------------------------------------
            | Phone
            |--------------------------------------------------------------------------
            */

            'phone' => [
                'required',
                'string',
                'max:30',

                Rule::unique('customers', 'phone'),
            ],

            /*
            |--------------------------------------------------------------------------
            | Email
            |--------------------------------------------------------------------------
            */

            'email' => [
                'nullable',
                'email:rfc,dns',
                'max:150',

                Rule::unique('customers', 'email'),
            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_active' => [
                'nullable',
                'boolean',
            ],

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => [
                'nullable',
                'string',
                'max:1000',
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

            'name.required' =>
                'Customer name is required.',

            'name.min' =>
                'Customer name must be at least 2 characters.',

            'name.max' =>
                'Customer name may not exceed 150 characters.',

            'phone.required' =>
                'Phone number is required.',

            'phone.unique' =>
                'This phone number already exists.',

            'email.email' =>
                'Please enter a valid email address.',

            'email.unique' =>
                'This email address already exists.',

            'notes.max' =>
                'Notes may not exceed 1000 characters.',

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validated Customer Data
    |--------------------------------------------------------------------------
    */

    public function customerData(): array
    {
        $validated =
            $this->validated();

        return [

            'name' =>
                $validated['name'],

            'phone' =>
                $validated['phone'],

            'email' =>
                $validated['email']
                    ?? null,

            'is_active' =>
                $validated['is_active']
                    ?? true,

            'notes' =>
                $validated['notes']
                    ?? null,

        ];
    }
}