<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePurchaseOrderPaymentRequest;
use App\Http\Resources\PurchaseOrderPaymentResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class PurchaseOrderPaymentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */

    public function __construct(
        private readonly PurchaseOrderPaymentService
            $purchaseOrderPaymentService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Payment History
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {

        $this->ensureViewAccess(
            $request
        );


        $payments =
            $this->purchaseOrderPaymentService
                ->getPayments(
                    $purchaseOrder
                );


        /*
        |--------------------------------------------------------------------------
        | Refresh Purchase Order Summary
        |--------------------------------------------------------------------------
        |
        | Payment history is immutable, while paid/due/payment_method summary
        | lives on the purchase order.
        |
        */

        $purchaseOrder->refresh();


        return response()->json([

            'success' =>
                true,


            'message' =>
                'Purchase order payment history loaded successfully.',


            'data' =>
                PurchaseOrderPaymentResource::collection(
                    $payments
                ),


            'summary' =>
                $this->buildSummary(
                    purchaseOrder: $purchaseOrder,
                    paymentCount: $payments->count()
                ),

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Record New Partial / Full Payment
    |--------------------------------------------------------------------------
    */

    public function store(
        StorePurchaseOrderPaymentRequest $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {

        /*
        |--------------------------------------------------------------------------
        | Record Payment
        |--------------------------------------------------------------------------
        |
        | PurchaseOrderPaymentService is authoritative for:
        |
        | - lockForUpdate()
        | - cancelled PO protection
        | - fully-paid protection
        | - overpayment protection
        | - immutable payment ledger
        | - paid/due/payment_method summary
        |
        */

        $payment =
            $this->purchaseOrderPaymentService
                ->recordPayment(

                    purchaseOrder:
                        $purchaseOrder,

                    data:
                        $request->validated(),

                    user:
                        $request->user()
                );


        /*
        |--------------------------------------------------------------------------
        | Payment Audit Relations
        |--------------------------------------------------------------------------
        |
        | PurchaseOrderPaymentResource only exposes these relationships when
        | they are loaded. Do not depend on lazy loading inside the Resource.
        |
        */

        $payment->load([
            'creator',
            'updater',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Updated Purchase Order
        |--------------------------------------------------------------------------
        |
        | Avoid accessing $payment->purchaseOrder just to obtain the summary.
        | The route-bound PO can simply be refreshed after the payment service
        | has completed its transaction.
        |
        */

        $updatedPurchaseOrder =
            $purchaseOrder->fresh();


        return response()->json([

            'success' =>
                true,


            'message' =>
                'Purchase order payment recorded successfully.',


            'data' =>
                new PurchaseOrderPaymentResource(
                    $payment
                ),


            'summary' =>
                $this->buildSummary(

                    purchaseOrder:
                        $updatedPurchaseOrder,

                    paymentCount:
                        $updatedPurchaseOrder
                            ->payments()
                            ->count()
                ),

        ], 201);
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Summary
    |--------------------------------------------------------------------------
    */

    private function buildSummary(
        PurchaseOrder $purchaseOrder,
        int $paymentCount
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Money
        |--------------------------------------------------------------------------
        */

        $total =
            round(
                (float) $purchaseOrder
                    ->total_amount,
                2
            );


        $paid =
            round(
                (float) $purchaseOrder
                    ->paid_amount,
                2
            );


        $due =
            round(
                (float) $purchaseOrder
                    ->due_amount,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Payment Method
        |--------------------------------------------------------------------------
        */

        $paymentMethod =
            $purchaseOrder
                ->payment_method !== null
                ? strtolower(
                    trim(
                        (string) $purchaseOrder
                            ->payment_method
                    )
                )
                : null;


        $paymentMethod =
            $paymentMethod !== ''
                ? $paymentMethod
                : null;


        return [

            /*
            |--------------------------------------------------------------------------
            | Purchase Order
            |--------------------------------------------------------------------------
            */

            'purchase_order_id' =>
                (int) $purchaseOrder->id,


            'supplier_id' =>
                $purchaseOrder->supplier_id !== null
                    ? (int) $purchaseOrder
                        ->supplier_id
                    : null,


            /*
            |--------------------------------------------------------------------------
            | Financial Summary
            |--------------------------------------------------------------------------
            */

            'total_amount' =>
                $total,


            'total_amount_formatted' =>
                $this->money(
                    $total
                ),


            'paid_amount' =>
                $paid,


            'paid_amount_formatted' =>
                $this->money(
                    $paid
                ),


            'due_amount' =>
                $due,


            'due_amount_formatted' =>
                $this->money(
                    $due
                ),


            /*
            |--------------------------------------------------------------------------
            | Payment Method Summary
            |--------------------------------------------------------------------------
            */

            'payment_method' =>
                $paymentMethod,


            'payment_method_label' =>
                $this->paymentMethodLabel(
                    $paymentMethod
                ),


            /*
            |--------------------------------------------------------------------------
            | Payment History
            |--------------------------------------------------------------------------
            */

            'payment_count' =>
                $paymentCount,


            'has_payment' =>
                $purchaseOrder
                    ->hasPayment(),


            'has_due' =>
                $purchaseOrder
                    ->hasDue(),


            'is_fully_paid' =>
                $purchaseOrder
                    ->isFullyPaid(),

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | View Permission
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


        $canView =
            $request
                ->user()
                ->hasAnyPermission([

                    'inventory.view',

                    'inventory.manage',

                    'suppliers.manage',

                ]);


        abort_unless(
            $canView,
            403,
            'You do not have permission to view purchase order payments.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Method Label
    |--------------------------------------------------------------------------
    */

    private function paymentMethodLabel(
        ?string $method
    ): string {

        return match ($method) {

            'cash' =>
                'Cash',

            'card' =>
                'Card',

            'bkash' =>
                'bKash',

            'nagad' =>
                'Nagad',

            'bank_transfer' =>
                'Bank Transfer',

            'other' =>
                'Other',

            'mixed' =>
                'Mixed',

            default =>
                'Not Selected',
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Money Formatter
    |--------------------------------------------------------------------------
    */

    private function money(
        mixed $amount
    ): string {

        return '৳ '
            .
            number_format(
                (float) $amount,
                2
            );
    }
}