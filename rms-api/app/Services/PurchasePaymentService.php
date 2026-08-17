<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderPayment;
use App\Models\User;


/**
 * Legacy compatibility service.
 *
 * New purchase-order payment business logic lives only inside
 * PurchaseOrderPaymentService.
 *
 * This wrapper prevents old application code from maintaining a
 * second, conflicting payment implementation.
 */
class PurchasePaymentService
{
    public function __construct(
        private readonly PurchaseOrderPaymentService $purchaseOrderPaymentService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Record Purchase Order Payment
    |--------------------------------------------------------------------------
    |
    | Legacy callers may still inject PurchasePaymentService.
    |
    | All actual payment validation, locking, ledger creation and purchase
    | order summary updates are delegated to PurchaseOrderPaymentService.
    |
    */

    public function recordPayment(
        PurchaseOrder $purchaseOrder,
        array $data,
        User $user
    ): PurchaseOrderPayment {

        return $this
            ->purchaseOrderPaymentService
            ->recordPayment(
                purchaseOrder:
                    $purchaseOrder,

                data:
                    $data,

                user:
                    $user
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Record Payment For Already Locked Purchase Order
    |--------------------------------------------------------------------------
    |
    | Used only when the caller is already inside a DB transaction and has
    | locked the PurchaseOrder row.
    |
    */

    public function recordPaymentForLockedPurchaseOrder(
        PurchaseOrder $purchaseOrder,
        array $data,
        User $user,
        string $errorPrefix = ''
    ): PurchaseOrderPayment {

        return $this
            ->purchaseOrderPaymentService
            ->recordPaymentForLockedPurchaseOrder(
                purchaseOrder:
                    $purchaseOrder,

                data:
                    $data,

                user:
                    $user,

                errorPrefix:
                    $errorPrefix
            );
    }
}