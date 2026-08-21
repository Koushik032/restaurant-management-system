<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KitchenOrderResource;
use App\Models\Order;
use App\Services\KitchenOrderService;
use App\Services\RecipeConsumptionService;
use App\Models\OrderKitchenBatch;
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
                | - chef assignment validation
                | - current batch validation
                | - batch status transition
                | - preparing timestamp
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
                | Resolve Exact Active Kitchen Batch
                |--------------------------------------------------------------------------
                |
                | Recipe consumption must belong to the exact batch that
                | has just started preparing.
                |
                */

                $batch =
                    $preparingOrder
                        ->kitchenBatches()
                        ->where(
                            'status',
                            OrderKitchenBatch::STATUS_PREPARING
                        )
                        ->orderByDesc(
                            'batch_no'
                        )
                        ->orderByDesc(
                            'id'
                        )
                        ->lockForUpdate()
                        ->first();


                if (
                    ! $batch
                ) {

                    throw ValidationException::withMessages([
                        'kitchen_batch' => [
                            'The active preparing kitchen batch could not be found.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Batch-level Recipe Consumption
                |--------------------------------------------------------------------------
                |
                | This handles:
                |
                | - variant-specific recipe selection
                | - direct recipe selection
                | - add-on recipes
                | - raw material locking
                | - restaurant stock locking
                | - stock deduction
                | - immutable consumption ledger
                | - stock movement
                | - idempotency
                |
                */

                $this->recipeConsumptionService
                    ->consumeForBatch(
                        batch:
                            $batch,

                        user:
                            $user
                    );


                /*
                |--------------------------------------------------------------------------
                | Fresh Kitchen Order
                |--------------------------------------------------------------------------
                |
                | Reload using KitchenOrderService so the API response keeps
                | the existing kitchen response structure.
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