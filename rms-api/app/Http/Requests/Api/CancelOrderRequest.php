<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => [
                'required',
                'string',
                'min:3',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_reason.required' =>
                'Please provide a cancellation reason.',

            'cancellation_reason.min' =>
                'Cancellation reason must contain at least 3 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cancellation_reason' => trim(
                (string) $this->input(
                    'cancellation_reason'
                )
            ),
        ]);
    }
}