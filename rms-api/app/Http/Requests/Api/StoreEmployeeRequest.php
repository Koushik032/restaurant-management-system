<?php

namespace App\Http\Requests\Api;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique(
                    'users',
                    'username'
                ),
            ],

            'email' => [
                'required',
                'email',
                'max:180',
                Rule::unique(
                    'users',
                    'email'
                ),
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
            ],

            /*
            |--------------------------------------------------------------------------
            | Admin এবং inactive role select করা যাবে না
            |--------------------------------------------------------------------------
            */

            'role_id' => [
                'required',
                'integer',

                Rule::exists(
                    'roles',
                    'id'
                )->where(
                    function ($query) {
                        $query
                            ->where(
                                'name',
                                '!=',
                                'admin'
                            )
                            ->where(
                                'is_active',
                                true
                            );
                    }
                ),
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'joining_date' => [
                'required',
                'date',
            ],

            'hourly_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

        ];
    }

    public function messages(): array
    {
        return [

            'name.required' =>
                'Staff name is required.',

            'username.required' =>
                'Username is required.',

            'username.unique' =>
                'This username is already in use.',

            'email.required' =>
                'Email address is required.',

            'email.email' =>
                'Please enter a valid email address.',

            'email.unique' =>
                'This email address is already in use.',

            'password.required' =>
                'Password is required.',

            'password.min' =>
                'Password must be at least 8 characters.',

            'password.confirmed' =>
                'Password confirmation does not match.',

            'role_id.required' =>
                'Please select an employee role.',

            'role_id.exists' =>
                'Selected role is inactive, invalid or cannot be assigned.',

            'joining_date.required' =>
                'Joining date is required.',

            'joining_date.date' =>
                'Please enter a valid joining date.',

            'hourly_rate.required' =>
                'Hourly salary rate is required.',

            'hourly_rate.numeric' =>
                'Hourly salary rate must be a number.',

            'hourly_rate.min' =>
                'Hourly salary rate cannot be negative.',

            'current_status.in' =>
                'Selected employee status is invalid.',

        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([

            'name' =>
                is_string($this->name)
                    ? trim($this->name)
                    : $this->name,

            'username' =>
                is_string($this->username)
                    ? trim($this->username)
                    : $this->username,

            'email' =>
                is_string($this->email)
                    ? strtolower(
                        trim($this->email)
                    )
                    : $this->email,

            'phone' =>
                is_string($this->phone)
                    ? trim($this->phone)
                    : $this->phone,

        ]);
    }
}