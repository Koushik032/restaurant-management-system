<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => [
                'required',
                'string',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
            ],
            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Email or username is required.',
            'login.string' => 'Email or username must be valid text.',
            'password.required' => 'Password is required.',
            'device_name.max' => 'Device name may not be greater than 100 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'login' => trim((string) $this->input('login')),
            'device_name' => trim(
                (string) $this->input('device_name', 'RMS Client')
            ),
        ]);
    }
}