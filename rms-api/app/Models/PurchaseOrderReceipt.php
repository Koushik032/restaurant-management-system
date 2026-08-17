<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;


class PurchaseOrderReceipt extends Model
{
    use HasFactory;


    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'purchase_order_id',

        'receipt_no',

        'received_at',

        'notes',

        'received_by',

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

        'received_at' =>
            'datetime',

        'received_by' =>
            'integer',

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
    | GRN / Purchase Receipt is transaction history.
    |
    | Once created, it should not be silently edited or deleted.
    |
    */

    protected static function booted(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Receipt Creation
        |--------------------------------------------------------------------------
        */

        static::creating(

            function (
                PurchaseOrderReceipt $receipt
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Purchase Order
                |--------------------------------------------------------------------------
                */

                $purchaseOrderId =
                    (int) (
                        $receipt->purchase_order_id
                        ?? 0
                    );


                if ($purchaseOrderId <= 0) {

                    throw ValidationException::withMessages([

                        'purchase_order_id' => [
                            'A valid purchase order is required for the receipt.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Receipt Number
                |--------------------------------------------------------------------------
                */

                $receiptNo =
                    trim(
                        (string) (
                            $receipt->receipt_no
                            ?? ''
                        )
                    );


                if ($receiptNo === '') {

                    throw ValidationException::withMessages([

                        'receipt_no' => [
                            'Receipt number is required.',
                        ],

                    ]);
                }


                $receipt->receipt_no =
                    $receiptNo;


                /*
                |--------------------------------------------------------------------------
                | Received Date
                |--------------------------------------------------------------------------
                */

                if ($receipt->received_at === null) {

                    throw ValidationException::withMessages([

                        'received_at' => [
                            'Received date is required.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Received By
                |--------------------------------------------------------------------------
                */

                $receivedBy =
                    (int) (
                        $receipt->received_by
                        ?? 0
                    );


                if ($receivedBy <= 0) {

                    throw ValidationException::withMessages([

                        'received_by' => [
                            'The user receiving the purchase order is required.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Normalize Notes
                |--------------------------------------------------------------------------
                */

                if ($receipt->notes !== null) {

                    $notes =
                        trim(
                            (string) $receipt->notes
                        );


                    $receipt->notes =
                        $notes !== ''
                            ? $notes
                            : null;
                }
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Receipt Modification
        |--------------------------------------------------------------------------
        */

        static::updating(

            function (): void {

                throw ValidationException::withMessages([

                    'receipt' => [
                        'Purchase receipt history cannot be modified.',
                    ],

                ]);
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Receipt Deletion
        |--------------------------------------------------------------------------
        */

        static::deleting(

            function (): void {

                throw ValidationException::withMessages([

                    'receipt' => [
                        'Purchase receipt history cannot be deleted.',
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
        )->withTrashed();
    }


    /*
    |--------------------------------------------------------------------------
    | Receipt Items
    |--------------------------------------------------------------------------
    */

    public function items(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderReceiptItem::class,
            'purchase_order_receipt_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Received By
    |--------------------------------------------------------------------------
    */

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'received_by'
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
    | Total Received Quantity
    |--------------------------------------------------------------------------
    */

    public function getTotalQuantityAttribute(): float
    {
        if (
            $this->relationLoaded(
                'items'
            )
        ) {

            return round(
                (float) $this
                    ->items
                    ->sum(
                        'quantity'
                    ),
                4
            );
        }


        return round(
            (float) $this
                ->items()
                ->sum(
                    'quantity'
                ),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Total Cost
    |--------------------------------------------------------------------------
    */

    public function getTotalCostAttribute(): float
    {
        if (
            $this->relationLoaded(
                'items'
            )
        ) {

            return round(
                (float) $this
                    ->items
                    ->sum(
                        'total_cost'
                    ),
                4
            );
        }


        return round(
            (float) $this
                ->items()
                ->sum(
                    'total_cost'
                ),
            4
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Has Items
    |--------------------------------------------------------------------------
    */

    public function hasItems(): bool
    {
        if (
            $this->relationLoaded(
                'items'
            )
        ) {
            return $this
                ->items
                ->isNotEmpty();
        }


        return $this
            ->items()
            ->exists();
    }
}