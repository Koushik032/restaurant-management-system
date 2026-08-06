<?php

namespace App\Http\Controllers\OrderManagement;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {
    }

    /**
     * Payment History
     */
    public function index(Order $order)
    {
        $payments = $order->payments()
            ->with('receiver')
            ->get();

        return PaymentResource::collection(
            $payments
        );
    }

    /**
     * Add Payment
     */
    public function store(
        StoreOrderPaymentRequest $request,
        Order $order
    ): JsonResponse {

        $payment = $this->paymentService
            ->addPayment(
                $order,
                $request->validated()
            );

        return response()->json([
            'message' => 'Payment added successfully.',
            'payment' => new PaymentResource(
                $payment
            ),
        ], 201);
    }
}