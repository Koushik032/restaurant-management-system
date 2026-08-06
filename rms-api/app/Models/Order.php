<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Order statuses
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING = 'pending';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_SERVED = 'served';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELED = 'canceled';

    /*
    |--------------------------------------------------------------------------
    | Payment statuses
    |--------------------------------------------------------------------------
    */

    public const PAYMENT_DUE = 'due';

    public const PAYMENT_PARTIALLY_PAID = 'partially_paid';

    public const PAYMENT_PAID = 'paid';

    /*
    |--------------------------------------------------------------------------
    | Payment methods
    |--------------------------------------------------------------------------
    */

    public const METHOD_CASH = 'cash';

    public const METHOD_CARD = 'card';

    public const METHOD_BKASH = 'bkash';

    public const METHOD_NAGAD = 'nagad';

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_MIXED = 'mixed';

    protected $fillable = [
        'order_number',

        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',

        'restaurant_table_id',

        'status',

        'subtotal',
        'discount_amount',
        'tax_amount',
        'service_charge',
        'total_amount',
        'paid_amount',
        'due_amount',

        'payment_status',
        'payment_method',
        'payment_breakdown',
        'payment_reference',

        'order_note',
        'kitchen_note',

        'sent_to_kitchen_at',
        'preparing_at',
        'ready_at',
        'served_at',
        'completed_at',
        'canceled_at',
        'cancellation_reason',

        'is_customer_spend_recorded',
        'customer_spend_recorded_at',

        'created_by',

        'chef_id',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'service_charge' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_amount' => 'decimal:2',

            'payment_breakdown' => 'array',

            'sent_to_kitchen_at' => 'datetime',
            'preparing_at' => 'datetime',
            'ready_at' => 'datetime',
            'served_at' => 'datetime',
            'completed_at' => 'datetime',
            'canceled_at' => 'datetime',
            'chef_id' => 'integer',

            'is_customer_spend_recorded' => 'boolean',
            'customer_spend_recorded_at' => 'datetime',
        ];
    }

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_SERVED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELED,
        ];
    }

    public static function allowedPaymentStatuses(): array
    {
        return [
            self::PAYMENT_DUE,
            self::PAYMENT_PARTIALLY_PAID,
            self::PAYMENT_PAID,
        ];
    }

    public static function allowedPaymentMethods(): array
    {
        return [
            self::METHOD_CASH,
            self::METHOD_CARD,
            self::METHOD_BKASH,
            self::METHOD_NAGAD,
            self::METHOD_BANK_TRANSFER,
            self::METHOD_MIXED,
        ];
    }

    public static function activeStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_SERVED,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function primaryTable(): BelongsTo
    {
        return $this->belongsTo(
            RestaurantTable::class,
            'restaurant_table_id'
        );
    }

    public function tables(): BelongsToMany
    {
        return $this->belongsToMany(
            RestaurantTable::class,
            'order_tables',
            'order_id',
            'restaurant_table_id'
        )
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
    /*
|--------------------------------------------------------------------------
| Assigned Chef
|--------------------------------------------------------------------------
|
| যে chef kitchen order accept করবে।
|
*/

public function chef(): BelongsTo
{
    return $this->belongsTo(
        User::class,
        'chef_id'
    );
}
    public function payments(): HasMany
    {
        return $this->hasMany(
            OrderPayment::class
        )->latest();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn(
            'status',
            self::activeStatuses()
        );
    }
    /*
|--------------------------------------------------------------------------
| Kitchen Active Orders
|--------------------------------------------------------------------------
*/

public function scopeKitchen(
    Builder $query
): Builder {

    return $query->whereIn(
        'status',
        [
            self::STATUS_PENDING,
            self::STATUS_PREPARING,
            self::STATUS_READY,
        ]
    );
}

    public function scopeSearch(
        Builder $query,
        ?string $search
    ): Builder {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($search): void {
            $builder
                ->where('order_number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('customer_phone', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('payment_status', 'like', "%{$search}%")
                ->orWhere('payment_method', 'like', "%{$search}%")
                ->orWhereHas(
                    'tables',
                    function (Builder $tableQuery) use ($search): void {
                        $tableQuery
                            ->where('table_name', 'like', "%{$search}%")
                            ->orWhere('id', $search);
                    }
                );
        });
    }

    public function mergedTables(): Collection
    {
        return $this->tables
            ->filter(
                fn (RestaurantTable $table): bool =>
                    ! (bool) $table->pivot->is_primary
            )
            ->values();
    }

    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_SERVED
            && $this->payment_status === self::PAYMENT_PAID
            && (float) $this->due_amount <= 0;
    }

    public function isFinalized(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_COMPLETED,
                self::STATUS_CANCELED,
            ],
            true
        );
    }
    /*
|--------------------------------------------------------------------------
| Payment Helpers
|--------------------------------------------------------------------------
*/

public function calculatePaidAmount(): float
{
    return (float) $this->payments()
        ->sum('amount');
}

public function calculateDueAmount(): float
{
    return max(
        0,
        (float) $this->total_amount
        - $this->calculatePaidAmount()
    );
}

public function calculatePaymentStatus(): string
{
    $paid = $this->calculatePaidAmount();

    if ($paid <= 0) {
        return self::PAYMENT_DUE;
    }

    if ($paid >= (float) $this->total_amount) {
        return self::PAYMENT_PAID;
    }

    return self::PAYMENT_PARTIALLY_PAID;
}

public function refreshPaymentSummary(): void
{
    $paid = $this->calculatePaidAmount();

    $due = max(
        0,
        (float) $this->total_amount - $paid
    );

    $this->forceFill([
        'paid_amount' => $paid,
        'due_amount' => $due,
        'payment_status' => $this->calculatePaymentStatus(),
    ])->saveQuietly();
}
/*
|--------------------------------------------------------------------------
| Kitchen Helpers
|--------------------------------------------------------------------------
*/

/**
 * Has this order been accepted
 * by any chef?
 */
public function isAcceptedByChef(): bool
{
    return
        $this->chef_id !== null;
}

/**
 * Can a chef accept
 * this order?
 */
public function canBeAccepted(): bool
{
    return
        $this->status === self::STATUS_PENDING
        &&
        $this->chef_id === null;
}

/**
 * Can chef start preparing?
 */
public function canStartPreparing(): bool
{
    return
        $this->status === self::STATUS_PENDING
        &&
        $this->chef_id !== null;
}

/**
 * Can chef mark
 * this order ready?
 */
public function canBeMarkedReady(): bool
{
    return
        $this->status === self::STATUS_PREPARING;
}

/**
 * Kitchen order finished?
 */
public function isKitchenCompleted(): bool
{
    return in_array(
        $this->status,
        [
            self::STATUS_READY,
            self::STATUS_SERVED,
            self::STATUS_COMPLETED,
            self::STATUS_CANCELED,
        ],
        true
    );
}
}