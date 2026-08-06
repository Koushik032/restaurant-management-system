<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;


use App\Http\Requests\Api\StorePurchaseOrderRequest;
use App\Http\Requests\Api\UpdatePurchaseOrderRequest;


use App\Http\Resources\PurchaseOrderResource;


use App\Models\PurchaseOrder;


use App\Services\PurchaseOrderService;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class PurchaseOrderController extends Controller
{


    public function __construct(
        private readonly PurchaseOrderService $purchaseOrderService
    )
    {

    }








    /*
    |--------------------------------------------------------------------------
    | Purchase Order List
    |--------------------------------------------------------------------------
    */


    public function index(

        Request $request

    ): JsonResponse {



        $orders =

            $this->purchaseOrderService

                ->getPurchaseOrders(

                    $request->all()

                );







        return response()->json([


            'success'=>true,



            'message'=>

                'Purchase orders loaded successfully.',





            'data'=>

                PurchaseOrderResource::collection(

                    $orders

                ),





            'meta'=>[


                'current_page'=>

                    $orders->currentPage(),




                'last_page'=>

                    $orders->lastPage(),




                'per_page'=>

                    $orders->perPage(),




                'total'=>

                    $orders->total(),



                'from'=>

                    $orders->firstItem(),




                'to'=>

                    $orders->lastItem(),



            ],



        ]);

    }













    /*
    |--------------------------------------------------------------------------
    | Store Purchase Order
    |--------------------------------------------------------------------------
    */


    public function store(

        StorePurchaseOrderRequest $request

    ): JsonResponse {


        $purchaseOrder =


            $this->purchaseOrderService

                ->createPurchaseOrder(

                    $request->validated(),

                    Auth::user()

                );







        $purchaseOrder->load([


            'supplier',

            'items',

            'orderedBy',

            'creator'


        ]);









        return response()->json([



            'success'=>true,



            'message'=>

                'Purchase order created successfully.',




            'data'=>

                new PurchaseOrderResource(

                    $purchaseOrder

                ),



        ],201);



    }












    /*
    |--------------------------------------------------------------------------
    | Show Purchase Order
    |--------------------------------------------------------------------------
    */


    public function show(

        PurchaseOrder $purchaseOrder

    ): JsonResponse {



        $purchaseOrder->load([


            'supplier',


            'items',


            'orderedBy',


            'creator',


            'updater'


        ]);








        return response()->json([



            'success'=>true,



            'message'=>

                'Purchase order loaded successfully.',




            'data'=>

                new PurchaseOrderResource(

                    $purchaseOrder

                ),



        ]);



    }





    /*
|--------------------------------------------------------------------------
| Update Purchase Order Status
|--------------------------------------------------------------------------
*/

public function updateStatus(
    Request $request,
    PurchaseOrder $purchaseOrder
): JsonResponse {

    $validated = $request->validate([

        'status' => [

            'required',

            'string',

            \Illuminate\Validation\Rule::in(
                PurchaseOrder::statuses()
            ),

        ],

    ]);


    $purchaseOrder->update([

        'status' =>
            $validated['status'],

        'updated_by' =>
            Auth::id(),

    ]);


    $purchaseOrder->load([

        'supplier',

        'items',

        'orderedBy',

        'creator',

        'updater',

    ]);


    return response()->json([

        'success' =>
            true,

        'message' =>
            'Purchase order status updated successfully.',

        'data' =>
            new PurchaseOrderResource(
                $purchaseOrder
            ),

    ]);
}






    /*
    |--------------------------------------------------------------------------
    | Update Purchase Order
    |--------------------------------------------------------------------------
    */


    public function update(

        UpdatePurchaseOrderRequest $request,


        PurchaseOrder $purchaseOrder


    ): JsonResponse {



        $purchaseOrder =


            $this->purchaseOrderService

                ->updatePurchaseOrder(

                    $purchaseOrder,


                    $request->validated(),


                    Auth::user()

                );








        $purchaseOrder->load([


            'supplier',


            'items',


            'orderedBy',


            'creator',


            'updater'


        ]);










        return response()->json([



            'success'=>true,



            'message'=>

                'Purchase order updated successfully.',




            'data'=>

                new PurchaseOrderResource(

                    $purchaseOrder

                ),



        ]);



    }












    /*
    |--------------------------------------------------------------------------
    | Delete Purchase Order
    |--------------------------------------------------------------------------
    */


    public function destroy(

        PurchaseOrder $purchaseOrder

    ): JsonResponse {



        $this->purchaseOrderService

            ->deletePurchaseOrder(

                $purchaseOrder

            );








        return response()->json([



            'success'=>true,



            'message'=>

                'Purchase order deleted successfully.',




            'data'=>null,



        ]);



    }




}