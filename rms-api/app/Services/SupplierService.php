<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;


class SupplierService
{


    /*
    |--------------------------------------------------------------------------
    | Supplier List
    |--------------------------------------------------------------------------
    */


    public function getSuppliers(
        array $filters = []
    ): LengthAwarePaginator {


        $query =
            Supplier::query()
                ->with([
                    'creator',
                ]);



        if(
            !empty($filters['search'])
        ){

            $search =
                $filters['search'];


            $query->where(
                function($q) use ($search){

                    $q->where(
                        'supplier_name',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'contact_person',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'phone',
                        'like',
                        "%{$search}%"
                    )

                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    );

                }
            );

        }



        return $query

            ->orderBy(
                'id',
                'desc'
            )

            ->paginate(
                $filters['per_page']
                ??
                10
            );

    }







    /*
    |--------------------------------------------------------------------------
    | Create Supplier
    |--------------------------------------------------------------------------
    */


    public function createSupplier(
        array $data,
        User $user
    ): Supplier {


        return DB::transaction(

            function()
            use(
                $data,
                $user
            ){


                return Supplier::create([


                    'supplier_name' =>
                        $data['supplier_name'],


                    'contact_person' =>
                        $data['contact_person']
                        ??
                        null,


                    'email' =>
                        $data['email']
                        ??
                        null,


                    'phone' =>
                        $data['phone'],


                    'address' =>
                        $data['address']
                        ??
                        null,


                    'gstin' =>
                        $data['gstin']
                        ??
                        null,


                    'created_by' =>
                        $user->id,


                    'updated_by' =>
                        $user->id,


                ]);


            }

        );


    }








    /*
    |--------------------------------------------------------------------------
    | Update Supplier
    |--------------------------------------------------------------------------
    */


    public function updateSupplier(
        Supplier $supplier,
        array $data,
        User $user
    ): Supplier {


        $supplier->update([


            'supplier_name' =>
                $data['supplier_name']
                ??
                $supplier->supplier_name,


            'contact_person' =>
                $data['contact_person']
                ??
                $supplier->contact_person,


            'email' =>
                $data['email']
                ??
                $supplier->email,


            'phone' =>
                $data['phone']
                ??
                $supplier->phone,


            'address' =>
                $data['address']
                ??
                $supplier->address,


            'gstin' =>
                $data['gstin']
                ??
                $supplier->gstin,


            'updated_by' =>
                $user->id,


        ]);



        return $supplier->fresh();

    }








    /*
    |--------------------------------------------------------------------------
    | Delete Supplier
    |--------------------------------------------------------------------------
    */


    public function deleteSupplier(
        Supplier $supplier
    ): void {


        $supplier->delete();


    }


}