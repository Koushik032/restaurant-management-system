<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KitchenOrderResource;
use App\Models\Order;
use App\Services\KitchenOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KitchenOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        private readonly KitchenOrderService $kitchenService
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
        $orders = $this->kitchenService
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
            'success' => true,

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
                )->resolve($request),

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
            'success' => true,

            'message' =>
                'Kitchen order loaded successfully.',

            'data' =>
                (
                    new KitchenOrderResource(
                        $kitchenOrder
                    )
                )->resolve($request),
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
        $user = $request->user();

        $updatedOrder =
            $this->kitchenService
                ->acceptOrder(
                    order: $order,
                    user: $user
                );

        return response()->json([
            'success' => true,

            'message' =>
                'Order accepted successfully.',

            'data' =>
                (
                    new KitchenOrderResource(
                        $updatedOrder
                    )
                )->resolve($request),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Start Preparing
    |--------------------------------------------------------------------------
    */

    /**
     * Move an accepted order into preparing state.
     */
    public function startPreparing(
        Request $request,
        Order $order
    ): JsonResponse {
        $user = $request->user();

        $updatedOrder =
            $this->kitchenService
                ->startPreparing(
                    order: $order,
                    user: $user
                );

        return response()->json([
            'success' => true,

            'message' =>
                'Order preparation started successfully.',

            'data' =>
                (
                    new KitchenOrderResource(
                        $updatedOrder
                    )
                )->resolve($request),
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
        $user = $request->user();

        $updatedOrder =
            $this->kitchenService
                ->markReady(
                    order: $order,
                    user: $user
                );

        return response()->json([
            'success' => true,

            'message' =>
                'Order marked as ready successfully.',

            'data' =>
                (
                    new KitchenOrderResource(
                        $updatedOrder
                    )
                )->resolve($request),
        ]);
    }
}