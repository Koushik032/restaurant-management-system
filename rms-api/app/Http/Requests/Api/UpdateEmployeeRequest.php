<?php

namespace App\Http\Requests\Api;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employee =
            $this->route('employee');

        $userId =
            $employee?->user_id;

        return [

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
            ],

            'username' => [
                'sometimes',
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'users',
                    'username'
                )->ignore($userId),
            ],

            'email' => [
                'sometimes',
                'required',
                'email',
                'max:180',

                Rule::unique(
                    'users',
                    'email'
                )->ignore($userId),
            ],

            /*
            |--------------------------------------------------------------------------
            | Empty password পাঠালে password পরিবর্তন হবে না
            |--------------------------------------------------------------------------
            */

            'password' => [
                'nullable',
                'string',
                'min:8',
                'max:255',
                'confirmed',
            ],

            'role_id' => [
                'sometimes',
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
                'sometimes',
                'required',
                'date',
            ],

            'hourly_rate' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
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

            'password.min' =>
                'Password must be at least 8 characters.',

            'password.confirmed' =>
                'Password confirmation does not match.',

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
        $prepared = [];

        if ($this->has('name')) {
            $prepared['name'] =
                is_string($this->name)
                    ? trim($this->name)
                    : $this->name;
        }

        if ($this->has('username')) {
            $prepared['username'] =
                is_string($this->username)
                    ? trim($this->username)
                    : $this->username;
        }

        if ($this->has('email')) {
            $prepared['email'] =
                is_string($this->email)
                    ? strtolower(
                        trim($this->email)
                    )
                    : $this->email;
        }

        if ($this->has('phone')) {
            $prepared['phone'] =
                is_string($this->phone)
                    ? trim($this->phone)
                    : $this->phone;
        }

        /*
        |--------------------------------------------------------------------------
        | Edit form থেকে empty password এলে field বাদ দেওয়া হবে
        |--------------------------------------------------------------------------
        */

        if (
            $this->has('password')
            &&
            (
                $this->password === ''
                ||
                $this->password === null
            )
        ) {
            $this->request->remove('password');
            $this->request->remove(
                'password_confirmation'
            );
        }

        if (count($prepared) > 0) {
            $this->merge($prepared);
        }
    }
}