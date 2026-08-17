<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KitchenOrderResource;
use App\Models\Order;
use App\Services\KitchenOrderService;
use App\Services\RecipeConsumptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class KitchenOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        private readonly KitchenOrderService $kitchenService,
        private readonly RecipeConsumptionService $recipeConsumptionService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Kitchen Order List
    |--------------------------------------------------------------------------
    */

    /**
     * Display active kitchen orders.
     */
    public function index(
        Request $request
    ): JsonResponse {
        $orders =
            $this->kitchenService
                ->getKitchenOrders(
                    $request->only([
                        'search',
                        'status',
                        'assignment',
                        'chef_id',
                        'page',
                        'per_page',
                    ])
                );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Kitchen orders loaded successfully.',

            /*
            |--------------------------------------------------------------------------
            | Kitchen Orders
            |--------------------------------------------------------------------------
            */

            'data' =>
                KitchenOrderResource::collection(
                    $orders->getCollection()
                )->resolve(
                    $request
                ),

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            */

            'meta' => [
                'current_page' =>
                    $orders->currentPage(),

                'last_page' =>
                    $orders->lastPage(),

                'per_page' =>
                    $orders->perPage(),

                'total' =>
                    $orders->total(),

                'from' =>
                    $orders->firstItem(),

                'to' =>
                    $orders->lastItem(),
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Kitchen Order Details
    |--------------------------------------------------------------------------
    */

    /**
     * Display one kitchen order.
     */
    public function show(
        Request $request,
        Order $order
    ): JsonResponse {
        $kitchenOrder =
            $this->kitchenService
                ->getKitchenOrder(
                    $order
                );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Kitchen order loaded successfully.',

            'data' =>
                (
                    new KitchenOrderResource(
                        $kitchenOrder
                    )
                )->resolve(
                    $request
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Accept Kitchen Order
    |--------------------------------------------------------------------------
    */

    /**
     * Assign the authenticated chef to a pending order.
     */
    public function accept(
        Request $request,
        Order $order
    ): JsonResponse {
        $user =
            $request->user();


        abort_unless(
            $user,
            401,
            'Authentication is required.'
        );


        $updatedOrder =
            $this->kitchenService
                ->acceptOrder(
                    order:
                        $order,

                    user:
                        $user
                );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Order accepted successfully.',

            'data' =>
                (
                    new KitchenOrderResource(
                        $updatedOrder
                    )
                )->resolve(
                    $request
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Start Preparing
    |--------------------------------------------------------------------------
    */

    /**
     * Move an accepted order into preparing state and consume
     * the mapped recipe ingredients from Restaurant Stock.
     */
    public function startPreparing(
        Request $request,
        Order $order
    ): JsonResponse {
        $user =
            $request->user();


        abort_unless(
            $user,
            401,
            'Authentication is required.'
        );


        $updatedOrder =
            DB::transaction(

                function () use (
                    $order,
                    $user
                ): Order {

                    /*
                    |--------------------------------------------------------------------------
                    | Kitchen Status Transition
                    |--------------------------------------------------------------------------
                    |
                    | KitchenOrderService remains authoritative for:
                    |
                    | - chef / assignment validation
                    | - current status validation
                    | - status = preparing
                    | - preparing_at timestamp
                    |
                    */

                    $preparingOrder =
                        $this->kitchenService
                            ->startPreparing(
                                order:
                                    $order,

                                user:
                                    $user
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | Recipe Consumption
                    |--------------------------------------------------------------------------
                    |
                    | RecipeConsumptionService handles:
                    |
                    | - idempotency
                    | - recipe validation
                    | - ingredient aggregation
                    | - RestaurantStock locking
                    | - insufficient-stock prevention
                    | - RestaurantStock deduction
                    | - immutable consumption ledger
                    | - recipe_consumption StockMovement
                    |
                    | If this step fails, this outer transaction also rolls
                    | back the kitchen status transition.
                    |
                    */

                    $this->recipeConsumptionService
                        ->consumeForOrder(
                            order:
                                $preparingOrder,

                            user:
                                $user
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Fresh Kitchen Order
                    |--------------------------------------------------------------------------
                    |
                    | Reload using KitchenOrderService so API response keeps the
                    | same relationship structure as the existing kitchen API.
                    |
                    */

                    return $this->kitchenService
                        ->getKitchenOrder(
                            $preparingOrder
                                ->fresh()
                        );
                },

                3
            );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Order preparation started and recipe ingredients consumed successfully.',

            'data' =>
                (
                    new KitchenOrderResource(
                        $updatedOrder
                    )
                )->resolve(
                    $request
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Mark Ready
    |--------------------------------------------------------------------------
    */

    /**
     * Move a preparing order into ready state.
     */
    public function markReady(
        Request $request,
        Order $order
    ): JsonResponse {
        $user =
            $request->user();


        abort_unless(
            $user,
            401,
            'Authentication is required.'
        );


        $updatedOrder =
            $this->kitchenService
                ->markReady(
                    order:
                        $order,

                    user:
                        $user
                );


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Order marked as ready successfully.',

            'data' =>
                (
                    new KitchenOrderResource(
                        $updatedOrder
                    )
                )->resolve(
                    $request
                ),
        ]);
    }
}