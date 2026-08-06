<?php

namespace App\Http\Requests\Api;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
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

            /*
            |--------------------------------------------------------------------------
            | Category
            |--------------------------------------------------------------------------
            */

            'expense_category_id' => [

                'required',

                'integer',

                Rule::exists(
                    'expense_categories',
                    'id'
                )->where(
                    'is_active',
                    true
                ),

            ],



            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'expense_date' => [

                'required',

                'date',

            ],



            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */

            'amount' => [

                'required',

                'numeric',

                'min:0.01',

                'max:9999999999.99',

            ],



            /*
            |--------------------------------------------------------------------------
            | Payment Method
            |--------------------------------------------------------------------------
            */

            'payment_method' => [

                'required',

                'string',

                Rule::in(
                    Expense::allowedPaymentMethods()
                ),

            ],



            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

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

            'expense_category_id.required' =>
                'Expense category is required.',


            'expense_category_id.exists' =>
                'Selected category is invalid.',


            'amount.required' =>
                'Expense amount is required.',


            'amount.min' =>
                'Amount must be greater than zero.',


            'payment_method.required' =>
                'Payment method is required.',


            'payment_method.in' =>
                'Invalid payment method selected.',

        ];
    }
}