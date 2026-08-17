<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;


class PurchaseOrderPayment extends Model
{
    use HasFactory;


    /*
    |--------------------------------------------------------------------------
    | Payment Method Constants
    |--------------------------------------------------------------------------
    */

    public const METHOD_CASH =
        'cash';

    public const METHOD_CARD =
        'card';

    public const METHOD_BKASH =
        'bkash';

    public const METHOD_NAGAD =
        'nagad';

    public const METHOD_BANK_TRANSFER =
        'bank_transfer';

    public const METHOD_OTHER =
        'other';


    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'purchase_order_id',

        'payment_date',

        'amount',

        'payment_method',

        'transaction_reference',

        'notes',

        'created_by',

        'updated_by',

    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'purchase_order_id' =>
            'integer',

        'payment_date' =>
            'date',

        'amount' =>
            'decimal:2',

        'created_by' =>
            'integer',

        'updated_by' =>
            'integer',

    ];


    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    |
    | Purchase payment history is an immutable financial ledger.
    |
    | New payment:
    |     Allowed
    |
    | Existing payment update:
    |     Blocked
    |
    | Existing payment delete:
    |     Blocked
    |
    */

    protected static function booted(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Validate New Payment
        |--------------------------------------------------------------------------
        */

        static::creating(

            function (
                PurchaseOrderPayment $payment
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Purchase Order
                |--------------------------------------------------------------------------
                */

                $purchaseOrderId =
                    (int) (
                        $payment->purchase_order_id
                        ?? 0
                    );


                if ($purchaseOrderId <= 0) {

                    throw ValidationException::withMessages([

                        'purchase_order_id' => [
                            'A valid purchase order is required for payment.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Amount
                |--------------------------------------------------------------------------
                */

                $amount =
                    round(
                        (float) (
                            $payment->amount
                            ?? 0
                        ),
                        2
                    );


                if ($amount <= 0) {

                    throw ValidationException::withMessages([

                        'amount' => [
                            'Payment amount must be greater than zero.',
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
                                $payment->payment_method
                                ?? ''
                            )
                        )
                    );


                if (
                    ! in_array(
                        $paymentMethod,
                        self::paymentMethods(),
                        true
                    )
                ) {

                    throw ValidationException::withMessages([

                        'payment_method' => [
                            'A valid payment method is required.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Normalize Payment Method
                |--------------------------------------------------------------------------
                */

                $payment->payment_method =
                    $paymentMethod;


                /*
                |--------------------------------------------------------------------------
                | Normalize Reference
                |--------------------------------------------------------------------------
                */

                if (
                    $payment->transaction_reference
                    !==
                    null
                ) {

                    $reference =
                        trim(
                            (string) $payment
                                ->transaction_reference
                        );


                    $payment->transaction_reference =
                        $reference !== ''
                            ? $reference
                            : null;
                }


                /*
                |--------------------------------------------------------------------------
                | Normalize Notes
                |--------------------------------------------------------------------------
                */

                if ($payment->notes !== null) {

                    $notes =
                        trim(
                            (string) $payment->notes
                        );


                    $payment->notes =
                        $notes !== ''
                            ? $notes
                            : null;
                }
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Payment Modification
        |--------------------------------------------------------------------------
        |
        | Financial payment history must not be silently overwritten.
        |
        */

        static::updating(

            function (): void {

                throw ValidationException::withMessages([

                    'payment' => [
                        'Purchase order payment history cannot be modified.',
                    ],

                ]);
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Payment Deletion
        |--------------------------------------------------------------------------
        */

        static::deleting(

            function (): void {

                throw ValidationException::withMessages([

                    'payment' => [
                        'Purchase order payment history cannot be deleted.',
                    ],

                ]);
            }

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Purchase Order
    |--------------------------------------------------------------------------
    */

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrder::class,
            'purchase_order_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Creator
    |--------------------------------------------------------------------------
    */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Updater
    |--------------------------------------------------------------------------
    */

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Method Helpers
    |--------------------------------------------------------------------------
    */

    public static function paymentMethods(): array
    {
        return [

            self::METHOD_CASH,

            self::METHOD_CARD,

            self::METHOD_BKASH,

            self::METHOD_NAGAD,

            self::METHOD_BANK_TRANSFER,

            self::METHOD_OTHER,

        ];
    }


    public static function paymentMethodLabels(): array
    {
        return [

            self::METHOD_CASH =>
                'Cash',

            self::METHOD_CARD =>
                'Card',

            self::METHOD_BKASH =>
                'bKash',

            self::METHOD_NAGAD =>
                'Nagad',

            self::METHOD_BANK_TRANSFER =>
                'Bank Transfer',

            self::METHOD_OTHER =>
                'Other',

        ];
    }


    public function getPaymentMethodLabelAttribute(): string
    {
        return self::paymentMethodLabels()[
            $this->payment_method
        ]
        ??
        ucwords(
            str_replace(
                '_',
                ' ',
                (string) $this->payment_method
            )
        );
    }
}