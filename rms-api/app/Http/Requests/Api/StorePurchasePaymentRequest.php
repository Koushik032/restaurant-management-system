<?php

namespace App\Http\Requests\Api;

use App\Models\PurchaseOrderPayment;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;


class StorePurchasePaymentRequest extends FormRequest
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
    | Prepare For Validation
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
        | Legacy paid_at Compatibility
        |--------------------------------------------------------------------------
        |
        | Older frontend/API payloads may still send:
        |
        | paid_at
        |
        | Current payment architecture uses:
        |
        | payment_date
        |
        | paid_at is converted only when payment_date was not supplied.
        |
        */

        elseif (
            $this->exists(
                'paid_at'
            )
        ) {

            $paidAt =
                trim(
                    (string) $this->input(
                        'paid_at'
                    )
                );


            if ($paidAt === '') {

                $data['payment_date'] =
                    null;

            } else {

                try {

                    $data['payment_date'] =
                        Carbon::parse(
                            $paidAt
                        )
                            ->toDateString();

                } catch (Throwable) {

                    /*
                    |--------------------------------------------------------------------------
                    | Leave Invalid Value For Validator
                    |--------------------------------------------------------------------------
                    |
                    | Do not throw from prepareForValidation().
                    | date_format rule below will return normal 422 validation.
                    |
                    */

                    $data['payment_date'] =
                        $paidAt;
                }
            }
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


            'amount.decimal' =>
                'Payment amount may contain up to 2 decimal places.',


            'amount.gt' =>
                'Payment amount must be greater than zero.',


            'amount.max' =>
                'Payment amount exceeds the maximum allowed value.',


            'payment_method.required' =>
                'Payment method is required.',


            'payment_method.in' =>
                'The selected payment method is invalid.',


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