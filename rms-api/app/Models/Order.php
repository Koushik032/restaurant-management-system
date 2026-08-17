<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use LogicException;


class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Order Statuses
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
    | Payment Statuses
    |--------------------------------------------------------------------------
    */

    public const PAYMENT_DUE = 'due';
    public const PAYMENT_PARTIALLY_PAID = 'partially_paid';
    public const PAYMENT_PAID = 'paid';

    /*
    |--------------------------------------------------------------------------
    | Payment Summary Methods
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
            'customer_id' => 'integer',
            'restaurant_table_id' => 'integer',
            'created_by' => 'integer',
            'chef_id' => 'integer',

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


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            Customer::class
        );
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
        return $this->hasMany(
            OrderItem::class
        );
    }


    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }


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
        );
    }

        /*
    |--------------------------------------------------------------------------
    | Kitchen Batches
    |--------------------------------------------------------------------------
    |
    | One restaurant order may contain multiple kitchen cycles.
    |
    | Batch #1 = original order items
    | Batch #2+ = later order extensions
    |
    */

    public function kitchenBatches(): HasMany
    {
        return $this->hasMany(
            OrderKitchenBatch::class,
            'order_id'
        );
    }


    public function latestKitchenBatch(): HasOne
    {
        return $this->hasOne(
            OrderKitchenBatch::class,
            'order_id'
        )->ofMany(
            'batch_no',
            'max'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Recipe Consumptions
    |--------------------------------------------------------------------------
    |
    | Recipe consumption is now batch-aware. An order may therefore have one
    | immutable consumption ledger header per kitchen batch.
    |
    */

    public function recipeConsumptions(): HasMany
    {
        return $this->hasMany(
            OrderRecipeConsumption::class,
            'order_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Backward-Compatible Latest Recipe Consumption
    |--------------------------------------------------------------------------
    |
    | Existing services/resources currently eager-load `recipeConsumption`.
    | Keep this relation temporarily as the latest ledger row so the application
    | remains compatible while services are migrated to batch-aware logic.
    |
    */

    public function recipeConsumption(): HasOne
    {
        return $this->hasOne(
            OrderRecipeConsumption::class,
            'order_id'
        )->latestOfMany();
    }


    public function hasRecipeConsumption(): bool
    {
        if (
            $this->relationLoaded(
                'recipeConsumptions'
            )
        ) {
            return $this
                ->recipeConsumptions
                ->isNotEmpty();
        }


        if (
            $this->relationLoaded(
                'recipeConsumption'
            )
        ) {
            return $this->recipeConsumption !== null;
        }


        return $this->recipeConsumptions()
            ->exists();
    }


    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->whereIn(
            'status',
            self::activeStatuses()
        );
    }


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
        $search = trim(
            (string) $search
        );

        if ($search === '') {
            return $query;
        }

        return $query->where(
            function (Builder $builder) use ($search): void {
                $builder
                    ->where(
                        'order_number',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'customer_name',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'customer_phone',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'status',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'payment_status',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'payment_method',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'tables',
                        function (Builder $tableQuery) use ($search): void {
                            $tableQuery
                                ->where(
                                    'table_name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'id',
                                    $search
                                );
                        }
                    );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Table Helpers
    |--------------------------------------------------------------------------
    */

    public function mergedTables(): Collection
    {
        return $this->tables
            ->filter(
                fn (RestaurantTable $table): bool =>
                    ! (bool) $table->pivot->is_primary
            )
            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | Completion Helpers
    |--------------------------------------------------------------------------
    */

    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_SERVED
            && $this->payment_status === self::PAYMENT_PAID
            && $this->moneyToCents(
                $this->due_amount
            ) <= 0;
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
        $paidCents = (int) round(
            (float) $this->payments()
                ->sum('amount')
            * 100
        );

        return $this->centsToMoney(
            $paidCents
        );
    }


    public function calculateDueAmount(): float
    {
        $totalCents = $this->moneyToCents(
            $this->total_amount
        );

        $paidCents = $this->moneyToCents(
            $this->calculatePaidAmount()
        );

        return $this->centsToMoney(
            max(
                0,
                $totalCents - $paidCents
            )
        );
    }


    public function calculatePaymentStatus(
        ?float $paidAmount = null
    ): string {
        $totalCents = $this->moneyToCents(
            $this->total_amount
        );

        $paidCents = $this->moneyToCents(
            $paidAmount
            ??
            $this->calculatePaidAmount()
        );

        if ($totalCents <= 0) {
            return self::PAYMENT_PAID;
        }

        if ($paidCents <= 0) {
            return self::PAYMENT_DUE;
        }

        if ($paidCents >= $totalCents) {
            return self::PAYMENT_PAID;
        }

        return self::PAYMENT_PARTIALLY_PAID;
    }


    public function calculatePaymentBreakdown(): array
    {
        return $this->payments()
            ->selectRaw(
                'payment_method, SUM(amount) AS total_amount'
            )
            ->groupBy(
                'payment_method'
            )
            ->get()
            ->mapWithKeys(
                function (OrderPayment $payment): array {
                    return [
                        (string) $payment->payment_method =>
                            round(
                                (float) $payment->getAttribute(
                                    'total_amount'
                                ),
                                2
                            ),
                    ];
                }
            )
            ->all();
    }


    public function calculateSummaryPaymentMethod(
        ?array $paymentBreakdown = null
    ): ?string {
        $paymentBreakdown = $paymentBreakdown
            ??
            $this->calculatePaymentBreakdown();

        $methods = collect(
            $paymentBreakdown
        )
            ->filter(
                static fn (mixed $amount): bool =>
                    round(
                        (float) $amount,
                        2
                    ) > 0
            )
            ->keys()
            ->values();

        if ($methods->isEmpty()) {
            return null;
        }

        if ($methods->count() === 1) {
            return (string) $methods->first();
        }

        return self::METHOD_MIXED;
    }


    public function refreshPaymentSummary(): void
    {
        $totalCents = $this->moneyToCents(
            $this->total_amount
        );

        $paidCents = (int) round(
            (float) $this->payments()
                ->sum('amount')
            * 100
        );

        if ($totalCents < 0) {
            throw new LogicException(
                'Order total amount cannot be negative.'
            );
        }

        if ($paidCents < 0) {
            throw new LogicException(
                'Order paid amount cannot be negative.'
            );
        }

        if ($paidCents > $totalCents) {
            throw new LogicException(
                'Order payment history exceeds the order total amount.'
            );
        }

        $paid = $this->centsToMoney(
            $paidCents
        );

        $due = $this->centsToMoney(
            $totalCents - $paidCents
        );

        $paymentBreakdown =
            $this->calculatePaymentBreakdown();

        $paymentMethod =
            $this->calculateSummaryPaymentMethod(
                $paymentBreakdown
            );

        $this->forceFill([
            'paid_amount' => $paid,
            'due_amount' => $due,
            'payment_status' =>
                $this->calculatePaymentStatus(
                    $paid
                ),
            'payment_method' =>
                $paymentMethod,
            'payment_breakdown' =>
                $paymentBreakdown,
        ])->saveQuietly();

        $this->refresh();
    }


    /*
    |--------------------------------------------------------------------------
    | Kitchen Helpers
    |--------------------------------------------------------------------------
    */

    public function isAcceptedByChef(): bool
    {
        return $this->chef_id !== null;
    }


    public function canBeAccepted(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->chef_id === null;
    }


    public function canStartPreparing(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->chef_id !== null;
    }


    public function canBeMarkedReady(): bool
    {
        return $this->status === self::STATUS_PREPARING;
    }


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


    /*
    |--------------------------------------------------------------------------
    | Money Helpers
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


    private function centsToMoney(
        int $cents
    ): float {
        return round(
            $cents / 100,
            2
        );
    }
}