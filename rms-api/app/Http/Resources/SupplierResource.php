<?php

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;



class SupplierResource extends JsonResource
{


    public function toArray(
        Request $request
    ): array {


        return [


            'id'=>
                (int)$this->id,


            'supplier_name'=>
                $this->supplier_name,


            'contact_person'=>
                $this->contact_person,


            'email'=>
                $this->email,


            'phone'=>
                $this->phone,


            'address'=>
                $this->address,


            'gstin'=>
                $this->gstin,


            'created_by'=>
                $this->creator?->name,


            'created_at'=>
                $this->created_at
                ?->toISOString(),


        ];


    }


}