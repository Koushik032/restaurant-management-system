<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Models\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;


class PurchaseOrderPaymentService
{
    /*
    |--------------------------------------------------------------------------
    | Payment History
    |--------------------------------------------------------------------------
    */

    public function getPayments(
        PurchaseOrder $purchaseOrder
    ): Collection {

        return $purchaseOrder
            ->payments()
            ->with([
                'creator',
                'updater',
            ])
            ->orderByDesc(
                'payment_date'
            )
            ->orderByDesc(
                'id'
            )
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Normal Payment Endpoint
    |--------------------------------------------------------------------------
    |
    | Standalone payment requests use this method.
    |
    | The Purchase Order row is locked so two simultaneous payments cannot
    | both calculate the same due amount.
    |
    */

    public function recordPayment(
        PurchaseOrder $purchaseOrder,
        array $data,
        User $user
    ): PurchaseOrderPayment {

        return DB::transaction(

            function () use (
                $purchaseOrder,
                $data,
                $user
            ): PurchaseOrderPayment {

                /*
                |--------------------------------------------------------------------------
                | Lock Purchase Order
                |--------------------------------------------------------------------------
                */

                $lockedPurchaseOrder =
                    PurchaseOrder::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $purchaseOrder->id
                        );


                /*
                |--------------------------------------------------------------------------
                | Record Payment
                |--------------------------------------------------------------------------
                */

                return $this
                    ->recordPaymentForLockedPurchaseOrder(
                        purchaseOrder:
                            $lockedPurchaseOrder,

                        data:
                            $data,

                        user:
                            $user
                    );
            }

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Inside Existing Transaction
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | This method assumes the caller already owns a DB transaction and the
    | PurchaseOrder row is already locked.
    |
    | Examples:
    |
    | - PurchaseReceiveService
    | - PurchaseOrderService while creating initial advance
    |
    */

    public function recordPaymentForLockedPurchaseOrder(
        PurchaseOrder $purchaseOrder,
        array $data,
        User $user,
        string $errorPrefix = ''
    ): PurchaseOrderPayment {

        /*
        |--------------------------------------------------------------------------
        | Valid User
        |--------------------------------------------------------------------------
        */

        if ((int) $user->id <= 0) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'user'
                ) => [
                    'A valid user is required to record the payment.',
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Cancelled Purchase Order Protection
        |--------------------------------------------------------------------------
        */

        if (
            $purchaseOrder->status
            ===
            PurchaseOrder::STATUS_CANCELLED
        ) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'purchase_order'
                ) => [
                    'Payment cannot be added to a cancelled purchase order.',
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Payment Amount
        |--------------------------------------------------------------------------
        */

        if (
            ! array_key_exists(
                'amount',
                $data
            )
            ||
            ! is_numeric(
                $data['amount']
            )
        ) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'amount'
                ) => [
                    'Payment amount must be a valid number.',
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Convert Money To Integer Cents
        |--------------------------------------------------------------------------
        |
        | Financial calculations use integer cents internally.
        |
        | Example:
        |
        | ৳ 100.25
        | becomes
        | 10025 cents
        |
        | This avoids floating-point comparison problems.
        |
        */

        $amountCents =
            $this->moneyToCents(
                $data['amount']
            );


        if ($amountCents <= 0) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'amount'
                ) => [
                    'Payment amount must be greater than zero.',
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Purchase Order Financial Summary
        |--------------------------------------------------------------------------
        */

        $totalAmountCents =
            $this->moneyToCents(
                $purchaseOrder->total_amount
            );


        $currentPaidAmountCents =
            $this->moneyToCents(
                $purchaseOrder->paid_amount
            );


        /*
        |--------------------------------------------------------------------------
        | Financial Integrity Protection
        |--------------------------------------------------------------------------
        */

        if ($totalAmountCents < 0) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'purchase_order'
                ) => [
                    'Purchase order total amount is invalid.',
                ],

            ]);
        }


        if ($currentPaidAmountCents < 0) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'purchase_order'
                ) => [
                    'Purchase order paid amount is invalid.',
                ],

            ]);
        }


        if (
            $currentPaidAmountCents
            >
            $totalAmountCents
        ) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'purchase_order'
                ) => [
                    'Purchase order payment summary is inconsistent because paid amount exceeds total amount.',
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Current Due
        |--------------------------------------------------------------------------
        */

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

                $this->errorField(
                    $errorPrefix,
                    'amount'
                ) => [
                    'This purchase order is already fully paid.',
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

                $this->errorField(
                    $errorPrefix,
                    'amount'
                ) => [

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
        */

        $paymentMethod =
            strtolower(
                trim(
                    (string) (
                        $data[
                            'payment_method'
                        ]
                        ??
                        ''
                    )
                )
            );


        if (
            ! in_array(
                $paymentMethod,
                PurchaseOrderPayment::paymentMethods(),
                true
            )
        ) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'payment_method'
                ) => [
                    'Please select a valid payment method.',
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Payment Date
        |--------------------------------------------------------------------------
        */

        $paymentDate =
            $this->resolvePaymentDate(
                value:
                    $data[
                        'payment_date'
                    ]
                    ??
                    null,

                errorField:
                    $this->errorField(
                        $errorPrefix,
                        'payment_date'
                    )
            );


        /*
        |--------------------------------------------------------------------------
        | Transaction Reference
        |--------------------------------------------------------------------------
        */

        $transactionReference =
            $this->nullableText(
                $data[
                    'transaction_reference'
                ]
                ??
                null
            );


        if (
            $transactionReference !== null
            &&
            mb_strlen(
                $transactionReference
            ) > 255
        ) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'transaction_reference'
                ) => [
                    'Transaction reference may not be greater than 255 characters.',
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Payment Notes
        |--------------------------------------------------------------------------
        */

        $notes =
            $this->nullableText(
                $data[
                    'notes'
                ]
                ??
                null
            );


        if (
            $notes !== null
            &&
            mb_strlen(
                $notes
            ) > 2000
        ) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'notes'
                ) => [
                    'Payment notes may not be greater than 2000 characters.',
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | New Financial Totals
        |--------------------------------------------------------------------------
        */

        $newPaidAmountCents =
            $currentPaidAmountCents
            +
            $amountCents;


        $newDueAmountCents =
            $totalAmountCents
            -
            $newPaidAmountCents;


        /*
        |--------------------------------------------------------------------------
        | Final Overpayment Protection
        |--------------------------------------------------------------------------
        */

        if (
            $newPaidAmountCents
            >
            $totalAmountCents
        ) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'amount'
                ) => [
                    'Paid amount cannot exceed the purchase order total.',
                ],

            ]);
        }


        if ($newDueAmountCents < 0) {

            throw ValidationException::withMessages([

                $this->errorField(
                    $errorPrefix,
                    'amount'
                ) => [
                    'Payment would result in an invalid negative due amount.',
                ],

            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Determine PO Payment Method Summary
        |--------------------------------------------------------------------------
        */

        $summaryPaymentMethod =
            $this->resolveSummaryPaymentMethod(

                currentPaidAmountCents:
                    $currentPaidAmountCents,

                currentPaymentMethod:
                    $purchaseOrder
                        ->payment_method,

                newPaymentMethod:
                    $paymentMethod
            );


        /*
        |--------------------------------------------------------------------------
        | Create Immutable Payment Ledger Row
        |--------------------------------------------------------------------------
        |
        | Every payment becomes a new row.
        |
        | Existing rows are never updated or deleted.
        |
        */

        $payment =
            PurchaseOrderPayment::create([

                'purchase_order_id' =>
                    $purchaseOrder->id,

                'payment_date' =>
                    $paymentDate,

                'amount' =>
                    $this->centsToMoney(
                        $amountCents
                    ),

                'payment_method' =>
                    $paymentMethod,

                'transaction_reference' =>
                    $transactionReference,

                'notes' =>
                    $notes,

                'created_by' =>
                    $user->id,

                'updated_by' =>
                    $user->id,

            ]);


        /*
        |--------------------------------------------------------------------------
        | Update Purchase Order Summary
        |--------------------------------------------------------------------------
        |
        | PurchaseOrder is the current financial summary.
        |
        | PurchaseOrderPayment remains the immutable ledger.
        |
        */

        $purchaseOrder->update([

            'paid_amount' =>
                $this->centsToMoney(
                    $newPaidAmountCents
                ),

            'due_amount' =>
                $this->centsToMoney(
                    $newDueAmountCents
                ),

            'payment_method' =>
                $summaryPaymentMethod,

            'updated_by' =>
                $user->id,

        ]);


        /*
        |--------------------------------------------------------------------------
        | Refresh In-Memory PO
        |--------------------------------------------------------------------------
        */

        $purchaseOrder->refresh();


        /*
        |--------------------------------------------------------------------------
        | Return Payment
        |--------------------------------------------------------------------------
        */

        return $payment->fresh([

            'purchaseOrder.supplier',

            'creator',

            'updater',

        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Summary Payment Method
    |--------------------------------------------------------------------------
    |
    | Examples:
    |
    | Cash + Cash
    |     => cash
    |
    | Cash + bKash
    |     => mixed
    |
    | mixed + Cash
    |     => mixed
    |
    */

    private function resolveSummaryPaymentMethod(
        int $currentPaidAmountCents,
        ?string $currentPaymentMethod,
        string $newPaymentMethod
    ): string {

        $currentPaymentMethod =
            strtolower(
                trim(
                    (string) $currentPaymentMethod
                )
            );


        if (
            $currentPaidAmountCents <= 0
            ||
            $currentPaymentMethod === ''
        ) {

            return $newPaymentMethod;
        }


        if (
            $currentPaymentMethod
            ===
            'mixed'
        ) {

            return 'mixed';
        }


        if (
            $currentPaymentMethod
            ===
            $newPaymentMethod
        ) {

            return $newPaymentMethod;
        }


        return 'mixed';
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Payment Date
    |--------------------------------------------------------------------------
    */

    private function resolvePaymentDate(
        mixed $value,
        string $errorField
    ): string {

        if (
            $value === null
            ||
            (
                is_string($value)
                &&
                trim($value) === ''
            )
        ) {

            return now()
                ->toDateString();
        }


        /*
        |--------------------------------------------------------------------------
        | DateTime Object
        |--------------------------------------------------------------------------
        */

        if (
            $value instanceof DateTimeInterface
        ) {

            return Carbon::instance(
                $value
            )
                ->toDateString();
        }


        /*
        |--------------------------------------------------------------------------
        | String Date
        |--------------------------------------------------------------------------
        */

        if (! is_string($value)) {

            throw ValidationException::withMessages([

                $errorField => [
                    'Payment date must be a valid date.',
                ],

            ]);
        }


        try {

            $date =
                Carbon::parse(
                    trim($value)
                );

        } catch (Throwable) {

            throw ValidationException::withMessages([

                $errorField => [
                    'Payment date must be a valid date.',
                ],

            ]);
        }


        return $date
            ->toDateString();
    }


    /*
    |--------------------------------------------------------------------------
    | Error Field
    |--------------------------------------------------------------------------
    */

    private function errorField(
        string $prefix,
        string $field
    ): string {

        return $prefix . $field;
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