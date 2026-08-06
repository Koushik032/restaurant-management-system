<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;

use App\Http\Requests\Api\StoreSupplierRequest;
use App\Http\Requests\Api\UpdateSupplierRequest;

use App\Http\Resources\SupplierResource;

use App\Models\Supplier;

use App\Services\SupplierService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class SupplierController extends Controller
{


    public function __construct(
        private readonly SupplierService $supplierService
    ){

    }






    /*
    |--------------------------------------------------------------------------
    | Supplier List
    |--------------------------------------------------------------------------
    */


    public function index(
        Request $request
    ): JsonResponse {


        $suppliers =

            $this->supplierService
                ->getSuppliers(
                    $request->all()
                );




        return response()->json([


            'success'=>true,


            'message'=>
                'Suppliers loaded successfully.',


            'data'=>

                SupplierResource::collection(
                    $suppliers
                ),



            'meta'=>[


                'current_page'=>
                    $suppliers->currentPage(),


                'last_page'=>
                    $suppliers->lastPage(),


                'per_page'=>
                    $suppliers->perPage(),


                'total'=>
                    $suppliers->total(),


            ],


        ]);

    }









    /*
    |--------------------------------------------------------------------------
    | Store Supplier
    |--------------------------------------------------------------------------
    */


    public function store(
        StoreSupplierRequest $request
    ): JsonResponse {



        $supplier =

            $this->supplierService
                ->createSupplier(

                    $request->validated(),

                    Auth::user()

                );




        $supplier->load([
            'creator'
        ]);





        return response()->json([


            'success'=>true,


            'message'=>
                'Supplier created successfully.',



            'data'=>

                new SupplierResource(
                    $supplier
                ),


        ],201);



    }









    /*
    |--------------------------------------------------------------------------
    | Show Supplier
    |--------------------------------------------------------------------------
    */


    public function show(
        Supplier $supplier
    ): JsonResponse {



        $supplier->load([
            'creator',
            'updater',
        ]);




        return response()->json([


            'success'=>true,


            'message'=>
                'Supplier loaded successfully.',



            'data'=>

                new SupplierResource(
                    $supplier
                ),


        ]);

    }









    /*
    |--------------------------------------------------------------------------
    | Update Supplier
    |--------------------------------------------------------------------------
    */


    public function update(
        UpdateSupplierRequest $request,

        Supplier $supplier

    ): JsonResponse {



        $supplier =

            $this->supplierService
                ->updateSupplier(

                    $supplier,

                    $request->validated(),

                    Auth::user()

                );





        $supplier->load([
            'creator',
            'updater',
        ]);





        return response()->json([


            'success'=>true,


            'message'=>
                'Supplier updated successfully.',



            'data'=>

                new SupplierResource(
                    $supplier
                ),


        ]);

    }









    /*
    |--------------------------------------------------------------------------
    | Delete Supplier
    |--------------------------------------------------------------------------
    */


    public function destroy(
        Supplier $supplier
    ): JsonResponse {



        $this->supplierService
            ->deleteSupplier(
                $supplier
            );




        return response()->json([


            'success'=>true,


            'message'=>
                'Supplier deleted successfully.',



            'data'=>null,


        ]);

    }



}