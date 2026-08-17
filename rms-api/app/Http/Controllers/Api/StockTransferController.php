<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStockTransferRequest;
use App\Http\Resources\RestaurantStockResource;
use App\Http\Resources\StockTransferResource;
use App\Models\RawMaterial;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferService
            $stockTransferService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Transfer History
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ): JsonResponse {

        $this->ensureViewAccess(
            $request
        );


        $validated =
            $request->validate([

                'search' => [
                    'nullable',
                    'string',
                    'max:180',
                ],

                'date_from' => [
                    'nullable',
                    'date',
                ],

                'date_to' => [
                    'nullable',
                    'date',
                    'after_or_equal:date_from',
                ],

                'raw_material_id' => [
                    'nullable',
                    'integer',
                    'exists:raw_materials,id',
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

            ]);


        $transfers =
            $this->stockTransferService
                ->getTransfers(
                    $validated
                );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Stock transfer history loaded successfully.',

            'data' =>
                StockTransferResource::collection(
                    $transfers
                ),

            'meta' =>
                $this->paginationMeta(
                    $transfers
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create Transfer
    |--------------------------------------------------------------------------
    */

    public function store(
        StoreStockTransferRequest $request
    ): JsonResponse {

        $transfer =
            $this->stockTransferService
                ->transferToRestaurant(

                    data:
                        $request->validated(),

                    user:
                        $request->user()

                );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Warehouse stock transferred to restaurant successfully.',

            'data' =>
                new StockTransferResource(
                    $transfer
                ),

        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Transfer
    |--------------------------------------------------------------------------
    */

    public function show(
        Request $request,
        StockTransfer $stockTransfer
    ): JsonResponse {

        $this->ensureViewAccess(
            $request
        );


        $stockTransfer =
            $this->stockTransferService
                ->getTransfer(
                    $stockTransfer
                );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Stock transfer loaded successfully.',

            'data' =>
                new StockTransferResource(
                    $stockTransfer
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurant Stock List
    |--------------------------------------------------------------------------
    */

    public function restaurantStocks(
        Request $request
    ): JsonResponse {

        $this->ensureViewAccess(
            $request
        );


        $validated =
            $request->validate([

                'search' => [
                    'nullable',
                    'string',
                    'max:180',
                ],

                'category' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'raw_material_id' => [
                    'nullable',
                    'integer',
                    'exists:raw_materials,id',
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

            ]);


        $stocks =
            $this->stockTransferService
                ->getRestaurantStocks(
                    $validated
                );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Restaurant stocks loaded successfully.',

            'data' =>
                RestaurantStockResource::collection(
                    $stocks
                ),

            'meta' =>
                $this->paginationMeta(
                    $stocks
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Show Restaurant Stock
    |--------------------------------------------------------------------------
    */

    public function showRestaurantStock(
        Request $request,
        RawMaterial $rawMaterial
    ): JsonResponse {

        $this->ensureViewAccess(
            $request
        );


        $stock =
            $this->stockTransferService
                ->getRestaurantStock(
                    $rawMaterial
                );


        return response()->json([

            'success' =>
                true,

            'message' =>
                'Restaurant stock loaded successfully.',

            'data' =>
                new RestaurantStockResource(
                    $stock
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Pagination Meta
    |--------------------------------------------------------------------------
    */

    private function paginationMeta(
        LengthAwarePaginator $paginator
    ): array {

        return [

            'current_page' =>
                $paginator
                    ->currentPage(),

            'last_page' =>
                $paginator
                    ->lastPage(),

            'per_page' =>
                $paginator
                    ->perPage(),

            'total' =>
                $paginator
                    ->total(),

            'from' =>
                $paginator
                    ->firstItem(),

            'to' =>
                $paginator
                    ->lastItem(),

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | View Access
    |--------------------------------------------------------------------------
    */

    private function ensureViewAccess(
        Request $request
    ): void {

        abort_unless(
            $request->user(),
            401,
            'Authentication is required.'
        );


        abort_unless(

            $request
                ->user()
                ->hasAnyPermission([
                    'inventory.view',
                    'inventory.manage',
                ]),

            403,

            'You do not have permission to view inventory stock transfers.'

        );
    }
}