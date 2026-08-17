<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class PurchaseOrder extends Model
{
    use HasFactory;
    use SoftDeletes;


    /*
    |--------------------------------------------------------------------------
    | Status Constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_ORDERED =
        'ordered';

    public const STATUS_PARTIAL =
        'partially_received';

    public const STATUS_RECEIVED =
        'received';

    public const STATUS_CANCELLED =
        'cancelled';


    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'supplier_id',

        'order_date',

        'delivery_date',

        'status',

        'subtotal',

        'tax',

        'service_charge',

        'total_amount',

        'paid_amount',

        'due_amount',

        'payment_method',

        'ordered_by',

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

        'supplier_id' =>
            'integer',

        'order_date' =>
            'datetime',

        'delivery_date' =>
            'date',

        'subtotal' =>
            'decimal:2',

        'tax' =>
            'decimal:2',

        'service_charge' =>
            'decimal:2',

        'total_amount' =>
            'decimal:2',

        'paid_amount' =>
            'decimal:2',

        'due_amount' =>
            'decimal:2',

        'ordered_by' =>
            'integer',

        'created_by' =>
            'integer',

        'updated_by' =>
            'integer',

        'deleted_at' =>
            'datetime',

    ];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | Supplier
    |--------------------------------------------------------------------------
    */

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            Supplier::class,
            'supplier_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Order Items
    |--------------------------------------------------------------------------
    */

    public function items(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderItem::class,
            'purchase_order_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Order Payments
    |--------------------------------------------------------------------------
    |
    | Every advance / partial / final payment should be stored as
    | an individual PurchaseOrderPayment record.
    |
    */

    public function payments(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderPayment::class,
            'purchase_order_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Purchase Receipts / GRN
    |--------------------------------------------------------------------------
    */

    public function receipts(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderReceipt::class,
            'purchase_order_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Ordered By
    |--------------------------------------------------------------------------
    */

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'ordered_by'
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
    | Status Helpers
    |--------------------------------------------------------------------------
    */

    public static function statuses(): array
    {
        return [

            self::STATUS_ORDERED,

            self::STATUS_PARTIAL,

            self::STATUS_RECEIVED,

            self::STATUS_CANCELLED,

        ];
    }


    public function isOrdered(): bool
    {
        return $this->status
            ===
            self::STATUS_ORDERED;
    }


    public function isPartiallyReceivedStatus(): bool
    {
        return $this->status
            ===
            self::STATUS_PARTIAL;
    }


    public function isReceived(): bool
    {
        return $this->status
            ===
            self::STATUS_RECEIVED;
    }


    public function isCancelled(): bool
    {
        return $this->status
            ===
            self::STATUS_CANCELLED;
    }


    /*
    |--------------------------------------------------------------------------
    | Receive Helpers
    |--------------------------------------------------------------------------
    */

    public function hasReceivedItems(): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Use Loaded Collection When Available
        |--------------------------------------------------------------------------
        */

        if (
            $this->relationLoaded(
                'items'
            )
        ) {

            return $this
                ->items
                ->contains(

                    static fn (
                        PurchaseOrderItem $item
                    ): bool =>

                        (float) $item
                            ->received_quantity
                        >
                        0

                );
        }


        /*
        |--------------------------------------------------------------------------
        | Database Check
        |--------------------------------------------------------------------------
        */

        return $this
            ->items()
            ->where(
                'received_quantity',
                '>',
                0
            )
            ->exists();
    }


    public function isFullyReceived(): bool
    {
        $items =
            $this->getReceiveItems();


        if ($items->isEmpty()) {
            return false;
        }


        return $items->every(

            static fn (
                PurchaseOrderItem $item
            ): bool =>

                (bool) $item
                    ->is_fully_received

        );
    }


    public function isPartiallyReceived(): bool
    {
        $items =
            $this->getReceiveItems();


        if ($items->isEmpty()) {
            return false;
        }


        $hasReceivedItem =
            $items->contains(

                static fn (
                    PurchaseOrderItem $item
                ): bool =>

                    (float) $item
                        ->received_quantity
                    >
                    0

            );


        if (! $hasReceivedItem) {
            return false;
        }


        return ! $items->every(

            static fn (
                PurchaseOrderItem $item
            ): bool =>

                (bool) $item
                    ->is_fully_received

        );
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Helpers
    |--------------------------------------------------------------------------
    */

    public function hasPayment(): bool
    {
        return round(
            (float) $this->paid_amount,
            2
        ) > 0;
    }


    public function isFullyPaid(): bool
    {
        $totalAmount =
            round(
                (float) $this->total_amount,
                2
            );


        $paidAmount =
            round(
                (float) $this->paid_amount,
                2
            );


        $dueAmount =
            round(
                (float) $this->due_amount,
                2
            );


        if ($totalAmount <= 0) {
            return false;
        }


        return
            $paidAmount >= $totalAmount
            &&
            $dueAmount <= 0;
    }


    public function hasDue(): bool
    {
        return round(
            (float) $this->due_amount,
            2
        ) > 0;
    }


    public function remainingDueAmount(): float
    {
        return round(
            max(
                0,
                (float) $this->total_amount
                -
                (float) $this->paid_amount
            ),
            2
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Internal Helpers
    |--------------------------------------------------------------------------
    */

    private function getReceiveItems()
    {
        /*
        |--------------------------------------------------------------------------
        | Prevent Unnecessary Duplicate Queries
        |--------------------------------------------------------------------------
        */

        if (
            $this->relationLoaded(
                'items'
            )
        ) {
            return $this->items;
        }


        return $this
            ->items()
            ->get();
    }
}