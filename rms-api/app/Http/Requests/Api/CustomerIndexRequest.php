<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerIndexRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorize Request
    |--------------------------------------------------------------------------
    |
    | Route-level permission middleware access control করবে।
    |
    */

    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Supported query parameters:
    |
    | - search
    | - status
    | - sort
    | - page
    | - per_page
    |
    */

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
                'string',

                Rule::in([
                    '',
                    'all',
                    'active',
                    'inactive',
                ]),
            ],

            'sort' => [
                'nullable',
                'string',

                Rule::in([
                    'latest',
                    'oldest',
                    'name_asc',
                    'name_desc',
                    'visits_high',
                    'visits_low',
                    'spend_high',
                    'spend_low',
                    'last_visit_latest',
                    'last_visit_oldest',
                ]),
            ],

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
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Prepare Input
    |--------------------------------------------------------------------------
    |
    | Query parameter values clean এবং consistent করা হবে।
    |
    */

    protected function prepareForValidation(): void
    {
        $search = trim(
            (string) $this->input(
                'search',
                ''
            )
        );

        $status = strtolower(
            trim(
                (string) $this->input(
                    'status',
                    ''
                )
            )
        );

        $sort = strtolower(
            trim(
                (string) $this->input(
                    'sort',
                    'latest'
                )
            )
        );

        $this->merge([
            'search' =>
                $search !== ''
                    ? $search
                    : null,

            'status' =>
                $status !== ''
                    ? $status
                    : 'all',

            'sort' =>
                $sort !== ''
                    ? $sort
                    : 'latest',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [
            'search.string' =>
                'The customer search value must be text.',

            'search.max' =>
                'The customer search value may not exceed 150 characters.',

            'status.in' =>
                'The selected customer status is invalid.',

            'sort.in' =>
                'The selected customer sorting option is invalid.',

            'page.integer' =>
                'The page number must be an integer.',

            'page.min' =>
                'The page number must be at least 1.',

            'per_page.integer' =>
                'The records per page value must be an integer.',

            'per_page.min' =>
                'At least 1 customer must be shown per page.',

            'per_page.max' =>
                'A maximum of 100 customers may be shown per page.',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Validated Customer Filters
    |--------------------------------------------------------------------------
    |
    | Controller থেকে clean filter array নেওয়ার জন্য helper method।
    |
    */

    public function filters(): array
    {
        $validated =
            $this->validated();

        return [
            'search' =>
                $validated['search']
                ?? null,

            'status' =>
                $validated['status']
                ?? 'all',

            'sort' =>
                $validated['sort']
                ?? 'latest',

            'page' =>
                (int) (
                    $validated['page']
                    ?? 1
                ),

            'per_page' =>
                (int) (
                    $validated['per_page']
                    ?? 10
                ),
        ];
    }
}