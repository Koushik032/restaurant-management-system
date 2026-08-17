<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use InvalidArgumentException;

class OrderKitchenBatch extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Kitchen Batch Statuses
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING =
        'pending';

    public const STATUS_PREPARING =
        'preparing';

    public const STATUS_READY =
        'ready';

    public const STATUS_SERVED =
        'served';

    public const STATUS_CANCELED =
        'canceled';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'order_id',
        'batch_no',
        'status',
        'chef_id',
        'sent_to_kitchen_at',
        'preparing_at',
        'ready_at',
        'served_at',
        'created_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'order_id' =>
                'integer',

            'batch_no' =>
                'integer',

            'chef_id' =>
                'integer',

            'created_by' =>
                'integer',

            'sent_to_kitchen_at' =>
                'datetime',

            'preparing_at' =>
                'datetime',

            'ready_at' =>
                'datetime',

            'served_at' =>
                'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Model Integrity
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::saving(
            function (
                OrderKitchenBatch $batch
            ): void {
                $batch->order_id =
                    (int) $batch->order_id;

                $batch->batch_no =
                    (int) $batch->batch_no;

                $batch->chef_id =
                    $batch->chef_id !== null
                        ? (int) $batch->chef_id
                        : null;

                $batch->created_by =
                    $batch->created_by !== null
                        ? (int) $batch->created_by
                        : null;

                /*
                |--------------------------------------------------------------------------
                | Normalize Status
                |--------------------------------------------------------------------------
                */

                $normalizedStatus =
                    strtolower(
                        trim(
                            (string) $batch->status
                        )
                    );

                $batch->status =
                    $normalizedStatus !== ''
                        ? $normalizedStatus
                        : self::STATUS_PENDING;

                /*
                |--------------------------------------------------------------------------
                | Order Validation
                |--------------------------------------------------------------------------
                */

                if (
                    $batch->order_id <= 0
                ) {
                    throw new InvalidArgumentException(
                        'A valid order is required for a kitchen batch.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Batch Number Validation
                |--------------------------------------------------------------------------
                */

                if (
                    $batch->batch_no <= 0
                ) {
                    throw new InvalidArgumentException(
                        'Kitchen batch number must be greater than zero.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Status Validation
                |--------------------------------------------------------------------------
                */

                if (
                    ! in_array(
                        $batch->status,
                        static::allowedStatuses(),
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Invalid kitchen batch status.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Chef Validation
                |--------------------------------------------------------------------------
                */

                if (
                    $batch->chef_id !== null
                    &&
                    $batch->chef_id <= 0
                ) {
                    throw new InvalidArgumentException(
                        'Kitchen batch chef must be a valid user.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Creator Validation
                |--------------------------------------------------------------------------
                */

                if (
                    $batch->created_by !== null
                    &&
                    $batch->created_by <= 0
                ) {
                    throw new InvalidArgumentException(
                        'Kitchen batch creator must be a valid user.'
                    );
                }
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Status Contracts
    |--------------------------------------------------------------------------
    */

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_SERVED,
            self::STATUS_CANCELED,
        ];
    }

    public static function activeStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PREPARING,
            self::STATUS_READY,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
            'order_id'
        )->withTrashed();
    }

    public function chef(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'chef_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            OrderItem::class,
            'order_kitchen_batch_id'
        );
    }

    public function recipeConsumption(): HasOne
    {
        return $this->hasOne(
            OrderRecipeConsumption::class,
            'order_kitchen_batch_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(
        Builder $query
    ): Builder {
        return $query->whereIn(
            'status',
            static::activeStatuses()
        );
    }

    public function scopeLatestFirst(
        Builder $query
    ): Builder {
        return $query
            ->orderByDesc(
                'batch_no'
            )
            ->orderByDesc(
                'id'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Kitchen State Helpers
    |--------------------------------------------------------------------------
    */

    public function isAccepted(): bool
    {
        return (
            $this->status ===
                self::STATUS_PENDING
            &&
            $this->chef_id !== null
        );
    }

    public function canBeAccepted(): bool
    {
        return (
            $this->status ===
                self::STATUS_PENDING
            &&
            $this->chef_id === null
        );
    }

    public function canStartPreparing(): bool
    {
        return (
            $this->status ===
                self::STATUS_PENDING
            &&
            $this->chef_id !== null
        );
    }

    public function canMarkReady(): bool
    {
        return (
            $this->status ===
            self::STATUS_PREPARING
        );
    }

    public function canBeServed(): bool
    {
        return (
            $this->status ===
            self::STATUS_READY
        );
    }

    public function isFinalized(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_SERVED,
                self::STATUS_CANCELED,
            ],
            true
        );
    }
}