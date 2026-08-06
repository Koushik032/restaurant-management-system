<?php

namespace App\Http\Requests\Api;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    /**
     * Authorization
     */
    public function authorize(): bool
    {
        return true;
    }


    /**
     * Validation Rules
     */
    public function rules(): array
    {
        return [

            'expense_category_id' => [

                'sometimes',

                'integer',

                Rule::exists(
                    'expense_categories',
                    'id'
                ),

            ],



            'expense_date' => [

                'sometimes',

                'date',

            ],



            'amount' => [

                'sometimes',

                'numeric',

                'min:0.01',

                'max:9999999999.99',

            ],



            'payment_method' => [

                'sometimes',

                'string',

                Rule::in(
                    Expense::allowedPaymentMethods()
                ),

            ],



            'notes' => [

                'nullable',

                'string',

                'max:2000',

            ],

        ];
    }



    public function messages(): array
    {
        return [

            'expense_category_id.exists' =>
                'Selected category is invalid.',


            'amount.min' =>
                'Amount must be greater than zero.',


            'payment_method.in' =>
                'Invalid payment method selected.',

        ];
    }
}