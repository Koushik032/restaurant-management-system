<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Orders Report
    |--------------------------------------------------------------------------
    */

    public function orders(
        Request $request
    ): JsonResponse {

        $orders =
            $this->reportService->getOrders(
                $request->all()
            );


        return $this->successResponse(
            $orders,
            'Orders report loaded successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Expenses Report
    |--------------------------------------------------------------------------
    */

    public function expenses(
        Request $request
    ): JsonResponse {

        $expenses =
            $this->reportService->getExpenses(
                $request->all()
            );


        return $this->successResponse(
            $expenses,
            'Expenses report loaded successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Expense Summary
    |--------------------------------------------------------------------------
    */

    public function expenseSummary(
        Request $request
    ): JsonResponse {

        return response()->json([

            'success' => true,

            'message' =>
                'Expense summary loaded successfully.',

            'data' =>
                $this->reportService->expenseSummary(
                    $request->all()
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Orders
    |--------------------------------------------------------------------------
    */

    public function purchaseOrders(
        Request $request
    ): JsonResponse {

        $purchaseOrders =
            $this->reportService->getPurchaseOrders(
                $request->all()
            );


        return $this->successResponse(
            $purchaseOrders,
            'Purchase report loaded successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Restaurant Stock
    |--------------------------------------------------------------------------
    */

    public function restaurantStock(
        Request $request
    ): JsonResponse {

        $stocks =
            $this->reportService->getRestaurantStock(
                $request->all()
            );


        return $this->successResponse(
            $stocks,
            'Restaurant stock loaded successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Warehouse Stock
    |--------------------------------------------------------------------------
    */

    public function warehouseStock(
        Request $request
    ): JsonResponse {

        $stocks =
            $this->reportService->getWarehouseStock(
                $request->all()
            );


        return $this->successResponse(
            $stocks,
            'Warehouse stock loaded successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Stock Transfers
    |--------------------------------------------------------------------------
    */

    public function stockTransfers(
        Request $request
    ): JsonResponse {

        $transfers =
            $this->reportService->getStockTransfers(
                $request->all()
            );


        return $this->successResponse(
            $transfers,
            'Stock transfer report loaded successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Attendance
    |--------------------------------------------------------------------------
    */

    public function attendance(
        Request $request
    ): JsonResponse {

        $attendance =
            $this->reportService->getAttendance(
                $request->all()
            );


        return $this->successResponse(
            $attendance,
            'Attendance report loaded successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Pagination Response
    |--------------------------------------------------------------------------
    */

    private function successResponse(
        $data,
        string $message
    ): JsonResponse {

        if (
            $data instanceof LengthAwarePaginator
        ) {

            return response()->json([

                'success' => true,

                'message' => $message,

                'data' =>
                    $data->items(),

                'meta' => [

                    'current_page' =>
                        $data->currentPage(),

                    'last_page' =>
                        $data->lastPage(),

                    'per_page' =>
                        $data->perPage(),

                    'total' =>
                        $data->total(),

                    'from' =>
                        $data->firstItem(),

                    'to' =>
                        $data->lastItem(),

                ],

            ]);
        }


        return response()->json([

            'success' => true,

            'message' => $message,

            'data' => $data,

        ]);
    }
}