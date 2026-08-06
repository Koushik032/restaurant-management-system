<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'payment_method' => [
                'required',
                'string',
                Rule::in([
                    'cash',
                    'bkash',
                    'nagad',
                    'rocket',
                    'card',
                    'bank_transfer',
                    'other',
                ]),
            ],

            'reference' => [
                'nullable',
                'string',
                'max:150',
            ],

            'note' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' =>
                'Payment amount is required.',

            'amount.numeric' =>
                'Payment amount must be a valid number.',

            'amount.gt' =>
                'Payment amount must be greater than zero.',

            'amount.decimal' =>
                'Payment amount may contain a maximum of two decimal places.',

            'payment_method.required' =>
                'Payment method is required.',

            'payment_method.in' =>
                'The selected payment method is invalid.',

            'reference.max' =>
                'Payment reference may not exceed 150 characters.',

            'note.max' =>
                'Payment note may not exceed 1000 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'payment_method' => is_string($this->payment_method)
                ? strtolower(trim($this->payment_method))
                : $this->payment_method,

            'reference' => is_string($this->reference)
                ? trim($this->reference)
                : $this->reference,

            'note' => is_string($this->note)
                ? trim($this->note)
                : $this->note,
        ]);
    }
}