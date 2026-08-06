<?php

namespace App\Http\Requests\Api;


use Illuminate\Foundation\Http\FormRequest;



class UpdateSupplierRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }




    public function rules(): array
    {

        return [

            'supplier_name'=>[
                'sometimes',
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
                'sometimes',
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