<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized
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
            | Date Filters
            |--------------------------------------------------------------------------
            */

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],


            /*
            |--------------------------------------------------------------------------
            | Category Filter
            |--------------------------------------------------------------------------
            */

            'category_id' => [
                'nullable',
                'integer',
                'exists:expense_categories,id',
            ],


            /*
            |--------------------------------------------------------------------------
            | Payment Filter
            |--------------------------------------------------------------------------
            */

            'payment_method' => [
                'nullable',
                'string',
                'max:50',
            ],


            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],


            /*
            |--------------------------------------------------------------------------
            | Search (Future Use)
            |--------------------------------------------------------------------------
            */

            'search' => [
                'nullable',
                'string',
                'max:255',
            ],


            /*
            |--------------------------------------------------------------------------
            | Sorting
            |--------------------------------------------------------------------------
            */

            'sort_by' => [
                'nullable',
                'string',
                'in:
                    expense_date,
                    amount,
                    created_at',
            ],


            'sort_direction' => [
                'nullable',
                'string',
                'in:asc,desc',
            ],

        ];
    }



    /**
     * Custom Messages
     */
    public function messages(): array
    {
        return [

            'date_to.after_or_equal' =>
                'To date must be after or equal to From date.',


            'category_id.exists' =>
                'Selected expense category does not exist.',


            'per_page.max' =>
                'Maximum 100 records can be loaded per page.',

        ];
    }
}