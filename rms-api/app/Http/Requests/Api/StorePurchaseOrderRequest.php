<?php

namespace App\Http\Requests\Api;


use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;

use App\Models\PurchaseOrder;





class StorePurchaseOrderRequest extends FormRequest
{


    public function authorize(): bool
    {

        return true;

    }









    public function rules(): array
    {


        return [



            /*
            |--------------------------------------------------------------------------
            | Supplier
            |--------------------------------------------------------------------------
            */


            'supplier_id'=>[

                'required',

                'integer',

                Rule::exists(

                    'suppliers',

                    'id'

                ),

            ],







            /*
            |--------------------------------------------------------------------------
            | Order Information
            |--------------------------------------------------------------------------
            */


            'order_date'=>[

                'required',

                'date',

            ],





            'delivery_date'=>[

                'nullable',

                'date',

                'after_or_equal:order_date',

            ],








            'status'=>[

                'nullable',

                Rule::in(

                    PurchaseOrder::statuses()

                ),

            ],







            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */


            'tax'=>[

                'nullable',

                'numeric',

                'min:0',

            ],





            'service_charge'=>[

                'nullable',

                'numeric',

                'min:0',

            ],





            'paid_amount'=>[

                'nullable',

                'numeric',

                'min:0',

            ],







            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */


            'payment_method'=>[

                'nullable',

                'string',

                'max:50',

            ],







            'notes'=>[

                'nullable',

                'string',

                'max:2000',

            ],







            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */


            'items'=>[

                'required',

                'array',

                'min:1',

            ],







            'items.*.item_name'=>[

                'required',

                'string',

                'max:255',

            ],








            'items.*.unit'=>[

                'required',

                'string',

                'max:30',

            ],







            'items.*.quantity'=>[

                'required',

                'numeric',

                'min:0.01',

            ],








            'items.*.received_quantity'=>[

                'nullable',

                'numeric',

                'min:0',

                'lte:items.*.quantity',

            ],







            'items.*.unit_price'=>[

                'required',

                'numeric',

                'min:0.01',

            ],



        ];

    }





}