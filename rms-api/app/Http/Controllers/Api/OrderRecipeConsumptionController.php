<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderRecipeConsumptionResource;
use App\Models\Order;
use App\Models\OrderRecipeConsumption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class OrderRecipeConsumptionController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Show Consumption For Order
    |--------------------------------------------------------------------------
    |
    | Read-only audit endpoint.
    |
    | This controller never:
    |
    | - deducts RestaurantStock
    | - creates StockMovement rows
    | - creates recipe consumption rows
    | - updates or deletes immutable history
    |
    */

    public function showForOrder(
        Request $request,
        Order $order
    ): JsonResponse {

        $this->ensureInventoryViewAccess(
            $request
        );


        /*
        |--------------------------------------------------------------------------
        | Immutable Consumption Record
        |--------------------------------------------------------------------------
        */

        $consumption =
            OrderRecipeConsumption::query()
                ->with([
                    'order',
                    'creator',
                    'items.rawMaterial',
                ])
                ->where(
                    'order_id',
                    $order->id
                )
                ->first();


        if (
            !$consumption
        ) {

            return response()->json(
                [
                    'success' =>
                        false,

                    'message' =>
                        'No recipe consumption record exists for this order.',

                    'data' =>
                        null,
                ],
                404
            );
        }


        return response()->json([
            'success' =>
                true,

            'message' =>
                'Order recipe consumption loaded successfully.',

            'data' =>
                (
                    new OrderRecipeConsumptionResource(
                        $consumption
                    )
                )->resolve(
                    $request
                ),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Inventory View Access
    |--------------------------------------------------------------------------
    */

    private function ensureInventoryViewAccess(
        Request $request
    ): void {

        $this->ensureAuthenticated(
            $request
        );


        $canView =
            $request
                ->user()
                ->hasAnyPermission([
                    'inventory.view',
                    'inventory.manage',
                ]);


        abort_unless(
            $canView,
            403,
            'You do not have permission to view inventory.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    private function ensureAuthenticated(
        Request $request
    ): void {

        abort_unless(
            $request->user(),
            401,
            'Authentication is required.'
        );
    }
}