<?php

namespace App\Http\Requests\Api;


use Illuminate\Foundation\Http\FormRequest;



class StoreSupplierRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }



    public function rules(): array
    {

        return [

            'supplier_name'=>[
                'required',
                'string',
                'max:180',
            ],


            'contact_person'=>[
                'nullable',
                'string',
                'max:180',
            ],


            'email'=>[
                'nullable',
                'email',
                'max:180',
            ],


            'phone'=>[
                'required',
                'string',
                'max:30',
            ],


            'address'=>[
                'nullable',
                'string',
            ],


            'gstin'=>[
                'nullable',
                'string',
                'max:50',
            ],


        ];

    }


}