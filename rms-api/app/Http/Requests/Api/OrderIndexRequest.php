<?php

namespace App\Http\Requests\Api;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:150',
            ],

            'status' => [
                'nullable',
                Rule::in(Order::allowedStatuses()),
            ],

            'payment_status' => [
                'nullable',
                Rule::in(Order::allowedPaymentStatuses()),
            ],

            'payment_method' => [
                'nullable',
                Rule::in(Order::allowedPaymentMethods()),
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                Rule::in([
                    10,
                    25,
                    50,
                    100,
                ]),
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'sort_direction' => [
                'nullable',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->filled('search')
                ? trim((string) $this->input('search'))
                : null,

            'per_page' => $this->input(
                'per_page',
                10
            ),

            'sort_direction' => $this->input(
                'sort_direction',
                'desc'
            ),
        ]);
    }
}