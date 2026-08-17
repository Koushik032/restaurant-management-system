<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class PaymentService
{
    /*
    |--------------------------------------------------------------------------
    | Add Payment
    |--------------------------------------------------------------------------
    */

    public function addPayment(
        Order $order,
        array $data
    ): OrderPayment {

        return DB::transaction(

            function () use (
                $order,
                $data
            ): OrderPayment {

                /*
                |--------------------------------------------------------------------------
                | Lock Order
                |--------------------------------------------------------------------------
                |
                | Serializes concurrent payment requests for the same order.
                |
                */

                $lockedOrder =
                    Order::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $order->id
                        );


                /*
                |--------------------------------------------------------------------------
                | Finalized Order Protection
                |--------------------------------------------------------------------------
                */

                if ($lockedOrder->isFinalized()) {

                    throw ValidationException::withMessages([
                        'order' => [
                            $lockedOrder->status === Order::STATUS_CANCELED
                                ? 'Payment cannot be added to a canceled order.'
                                : 'Payment cannot be added to a completed order.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Authenticated Receiver
                |--------------------------------------------------------------------------
                */

                $receivedBy = Auth::id();

                if (!$receivedBy) {

                    throw ValidationException::withMessages([
                        'user' => [
                            'An authenticated user is required to receive payment.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Validate Payment Amount
                |--------------------------------------------------------------------------
                */

                if (
                    !array_key_exists(
                        'amount',
                        $data
                    )
                    ||
                    !is_numeric(
                        $data['amount']
                    )
                ) {

                    throw ValidationException::withMessages([
                        'amount' => [
                            'Payment amount must be a valid number.',
                        ],
                    ]);
                }


                $amountCents =
                    $this->moneyToCents(
                        $data['amount']
                    );

                if ($amountCents <= 0) {

                    throw ValidationException::withMessages([
                        'amount' => [
                            'Payment amount must be greater than zero.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Ledger-Based Current Payment Summary
                |--------------------------------------------------------------------------
                |
                | OrderPayment rows are the source of truth.
                | We do not trust a potentially stale due_amount snapshot here.
                |
                */

                $totalAmountCents =
                    $this->moneyToCents(
                        $lockedOrder->total_amount
                    );

                $currentPaidAmountCents =
                    $this->moneyToCents(
                        $lockedOrder->calculatePaidAmount()
                    );


                if ($totalAmountCents < 0) {

                    throw ValidationException::withMessages([
                        'order' => [
                            'Order total amount is invalid.',
                        ],
                    ]);
                }


                if ($currentPaidAmountCents < 0) {

                    throw ValidationException::withMessages([
                        'order' => [
                            'Order payment history is invalid.',
                        ],
                    ]);
                }


                if (
                    $currentPaidAmountCents
                    >
                    $totalAmountCents
                ) {

                    throw ValidationException::withMessages([
                        'order' => [
                            'Order payment history is inconsistent because paid amount exceeds total amount.',
                        ],
                    ]);
                }


                $currentDueAmountCents =
                    $totalAmountCents
                    -
                    $currentPaidAmountCents;


                /*
                |--------------------------------------------------------------------------
                | Fully Paid Protection
                |--------------------------------------------------------------------------
                */

                if ($currentDueAmountCents <= 0) {

                    throw ValidationException::withMessages([
                        'amount' => [
                            'This order is already fully paid.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Overpayment Protection
                |--------------------------------------------------------------------------
                */

                if (
                    $amountCents
                    >
                    $currentDueAmountCents
                ) {

                    throw ValidationException::withMessages([
                        'amount' => [
                            'Payment amount cannot exceed the current due amount of ৳ '
                            .
                            number_format(
                                $this->centsToMoney(
                                    $currentDueAmountCents
                                ),
                                2
                            )
                            .
                            '.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Payment Method
                |--------------------------------------------------------------------------
                |
                | Individual ledger rows never store "mixed".
                | "mixed" is only an Order summary value.
                |
                */

                $paymentMethod =
                    strtolower(
                        trim(
                            (string) (
                                $data['payment_method']
                                ??
                                ''
                            )
                        )
                    );


                if (
                    !in_array(
                        $paymentMethod,
                        OrderPayment::paymentMethods(),
                        true
                    )
                ) {

                    throw ValidationException::withMessages([
                        'payment_method' => [
                            'Please select a valid payment method.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Reference
                |--------------------------------------------------------------------------
                */

                $reference =
                    $this->nullableText(
                        $data['reference']
                        ??
                        null
                    );

                if (
                    $reference !== null
                    &&
                    mb_strlen($reference) > 255
                ) {

                    throw ValidationException::withMessages([
                        'reference' => [
                            'Payment reference may not be greater than 255 characters.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Note
                |--------------------------------------------------------------------------
                */

                $note =
                    $this->nullableText(
                        $data['note']
                        ??
                        null
                    );

                if (
                    $note !== null
                    &&
                    mb_strlen($note) > 2000
                ) {

                    throw ValidationException::withMessages([
                        'note' => [
                            'Payment note may not be greater than 2000 characters.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Final Amount Safety
                |--------------------------------------------------------------------------
                */

                $newPaidAmountCents =
                    $currentPaidAmountCents
                    +
                    $amountCents;

                if (
                    $newPaidAmountCents
                    >
                    $totalAmountCents
                ) {

                    throw ValidationException::withMessages([
                        'amount' => [
                            'Paid amount cannot exceed the order total.',
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Create Immutable Payment Ledger Row
                |--------------------------------------------------------------------------
                */

                $payment =
                    OrderPayment::create([
                        'order_id' =>
                            $lockedOrder->id,

                        'amount' =>
                            $this->centsToMoney(
                                $amountCents
                            ),

                        'payment_method' =>
                            $paymentMethod,

                        'reference' =>
                            $reference,

                        'note' =>
                            $note,

                        'received_by' =>
                            $receivedBy,
                    ]);


                /*
                |--------------------------------------------------------------------------
                | Rebuild Order Payment Summary From Ledger
                |--------------------------------------------------------------------------
                |
                | Updates:
                |
                | - paid_amount
                | - due_amount
                | - payment_status
                | - payment_method
                | - payment_breakdown
                |
                */

                $lockedOrder
                    ->refreshPaymentSummary();


                /*
                |--------------------------------------------------------------------------
                | Return Payment
                |--------------------------------------------------------------------------
                */

                return $payment->fresh([
                    'order',
                    'receiver',
                ]);
            }

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Nullable Text
    |--------------------------------------------------------------------------
    */

    private function nullableText(
        mixed $value
    ): ?string {

        if ($value === null) {
            return null;
        }


        $value =
            trim(
                (string) $value
            );


        return $value !== ''
            ? $value
            : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Money To Cents
    |--------------------------------------------------------------------------
    */

    private function moneyToCents(
        mixed $value
    ): int {

        return (int) round(
            (float) $value
            *
            100
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Cents To Money
    |--------------------------------------------------------------------------
    */

    private function centsToMoney(
        int $cents
    ): float {

        return round(
            $cents / 100,
            2
        );
    }
}