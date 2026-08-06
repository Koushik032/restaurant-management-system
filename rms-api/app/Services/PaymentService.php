<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Add a payment to an order.
     */
    public function addPayment(
        Order $order,
        array $data
    ): OrderPayment {

        return DB::transaction(function () use ($order, $data) {

            $order->loadMissing('payments');

            $amount = round((float) $data['amount'], 2);

            if ($amount <= 0) {
                throw new InvalidArgumentException(
                    'Payment amount must be greater than zero.'
                );
            }

            $remaining = max(
                0,
                (float) $order->due_amount
            );

            if ($amount > $remaining) {
                throw new InvalidArgumentException(
                    'Payment amount exceeds the due amount.'
                );
            }

            $payment = OrderPayment::create([
                'order_id' => $order->id,
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
                'received_by' => Auth::id(),
            ]);

            $order->refreshPaymentSummary();

            return $payment->fresh('receiver');
        });
    }
}