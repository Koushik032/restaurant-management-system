<?php

namespace App\Http\Requests\Api;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorize Request
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Data for Validation
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' =>
                trim(
                    (string) $this->input(
                        'name',
                        ''
                    )
                ),

            'phone' =>
                trim(
                    (string) $this->input(
                        'phone',
                        ''
                    )
                ),

            'email' =>
                strtolower(
                    trim(
                        (string) $this->input(
                            'email',
                            ''
                        )
                    )
                ),

            'notes' =>
                trim(
                    (string) $this->input(
                        'notes',
                        ''
                    )
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
        $customer =
            $this->route(
                'customer'
            );

        $customerId =
            $customer instanceof Customer
                ? $customer->id
                : $customer;

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

                Rule::unique(
                    'customers',
                    'phone'
                )->ignore(
                    $customerId
                ),
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

                Rule::unique(
                    'customers',
                    'email'
                )->ignore(
                    $customerId
                ),
            ],

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_active' => [
                'required',
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
                'This phone number already belongs to another customer.',

            'email.email' =>
                'Please enter a valid email address.',

            'email.unique' =>
                'This email address already belongs to another customer.',

            'is_active.required' =>
                'Customer status is required.',

            'is_active.boolean' =>
                'Customer status must be active or inactive.',

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
                (bool) $validated[
                    'is_active'
                ],

            'notes' =>
                $validated['notes']
                ?? null,
        ];
    }
}