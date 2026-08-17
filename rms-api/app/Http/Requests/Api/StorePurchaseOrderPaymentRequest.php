<?php

namespace App\Http\Requests\Api;

use App\Models\PurchaseOrderPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class StorePurchaseOrderPaymentRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return $this->user()?->hasAnyPermission([
            'inventory.manage',
            'suppliers.manage',
        ]) ?? false;
    }


    /*
    |--------------------------------------------------------------------------
    | Prepare Input
    |--------------------------------------------------------------------------
    */

    protected function prepareForValidation(): void
    {
        $data = [];


        /*
        |--------------------------------------------------------------------------
        | Payment Method
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'payment_method'
            )
        ) {

            $paymentMethod =
                strtolower(
                    trim(
                        (string) $this->input(
                            'payment_method'
                        )
                    )
                );


            $data['payment_method'] =
                $paymentMethod !== ''
                    ? $paymentMethod
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Payment Date
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'payment_date'
            )
        ) {

            $paymentDate =
                trim(
                    (string) $this->input(
                        'payment_date'
                    )
                );


            $data['payment_date'] =
                $paymentDate !== ''
                    ? $paymentDate
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Transaction Reference
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'transaction_reference'
            )
        ) {

            $reference =
                trim(
                    (string) $this->input(
                        'transaction_reference'
                    )
                );


            $data['transaction_reference'] =
                $reference !== ''
                    ? $reference
                    : null;
        }


        /*
        |--------------------------------------------------------------------------
        | Notes
        |--------------------------------------------------------------------------
        */

        if (
            $this->exists(
                'notes'
            )
        ) {

            $notes =
                trim(
                    (string) $this->input(
                        'notes'
                    )
                );


            $data['notes'] =
                $notes !== ''
                    ? $notes
                    : null;
        }


        if ($data !== []) {
            $this->merge(
                $data
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            |
            | Supplier payment money uses 2-decimal precision.
            |
            */

            'amount' => [
                'bail',
                'required',
                'numeric',
                'decimal:0,2',
                'gt:0',
                'max:999999999999.99',
            ],


            /*
            |--------------------------------------------------------------------------
            | Payment Method
            |--------------------------------------------------------------------------
            */

            'payment_method' => [
                'bail',
                'required',
                'string',
                'max:50',

                Rule::in(
                    PurchaseOrderPayment::paymentMethods()
                ),
            ],


            /*
            |--------------------------------------------------------------------------
            | Payment Date
            |--------------------------------------------------------------------------
            |
            | Null means the PaymentService may use its normal/default date
            | handling. When supplied, API accepts an explicit Y-m-d value.
            |
            */

            'payment_date' => [
                'bail',
                'nullable',
                'date_format:Y-m-d',
            ],


            /*
            |--------------------------------------------------------------------------
            | Transaction Reference
            |--------------------------------------------------------------------------
            */

            'transaction_reference' => [
                'bail',
                'nullable',
                'string',
                'max:255',
            ],


            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            'notes' => [
                'bail',
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): array
    {
        return [

            'amount.required' =>
                'Payment amount is required.',


            'amount.numeric' =>
                'Payment amount must be numeric.',


            'amount.gt' =>
                'Payment amount must be greater than zero.',


            'amount.decimal' =>
                'Payment amount may contain up to 2 decimal places.',


            'amount.max' =>
                'Payment amount exceeds the maximum allowed value.',


            'payment_method.required' =>
                'Payment method is required.',


            'payment_method.in' =>
                'Please select a valid payment method.',


            'payment_date.date_format' =>
                'Payment date must be in YYYY-MM-DD format.',


            'transaction_reference.max' =>
                'Transaction reference cannot exceed 255 characters.',


            'notes.max' =>
                'Payment notes cannot exceed 2000 characters.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Attribute Names
    |--------------------------------------------------------------------------
    */

    public function attributes(): array
    {
        return [

            'amount' =>
                'payment amount',


            'payment_method' =>
                'payment method',


            'payment_date' =>
                'payment date',


            'transaction_reference' =>
                'transaction reference',


            'notes' =>
                'payment notes',
        ];
    }
}