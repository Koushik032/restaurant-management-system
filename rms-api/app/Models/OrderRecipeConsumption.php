<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;
use LogicException;

class OrderRecipeConsumption extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Trigger Constants
    |--------------------------------------------------------------------------
    */

    public const TRIGGER_START_PREPARING =
        'start_preparing';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'order_id',
        'order_kitchen_batch_id',
        'order_number',
        'trigger',
        'order_status_snapshot',
        'consumed_at',
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

            'order_kitchen_batch_id' =>
                'integer',

            'consumed_at' =>
                'datetime',

            'created_by' =>
                'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Model Integrity / Immutable Ledger
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::creating(
            function (
                OrderRecipeConsumption $consumption
            ): void {
                $consumption->order_id =
                    (int) $consumption->order_id;

                $consumption->order_kitchen_batch_id =
                    (int) $consumption
                        ->order_kitchen_batch_id;

                $consumption->created_by =
                    $consumption->created_by !== null
                        ? (int) $consumption->created_by
                        : null;

                $consumption->order_number =
                    trim(
                        (string) $consumption
                            ->order_number
                    );

                $consumption->trigger =
                    strtolower(
                        trim(
                            (string) (
                                $consumption->trigger
                                ?: self::TRIGGER_START_PREPARING
                            )
                        )
                    );

                $consumption->order_status_snapshot =
                    strtolower(
                        trim(
                            (string) $consumption
                                ->order_status_snapshot
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | Order
                |--------------------------------------------------------------------------
                */

                if (
                    $consumption->order_id <= 0
                ) {
                    throw new InvalidArgumentException(
                        'A valid order is required for recipe consumption.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Kitchen Batch
                |--------------------------------------------------------------------------
                */

                if (
                    $consumption->order_kitchen_batch_id <= 0
                ) {
                    throw new InvalidArgumentException(
                        'A valid kitchen batch is required for recipe consumption.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Order Number Snapshot
                |--------------------------------------------------------------------------
                */

                if (
                    $consumption->order_number === ''
                ) {
                    throw new InvalidArgumentException(
                        'Order number snapshot is required for recipe consumption.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Trigger
                |--------------------------------------------------------------------------
                */

                if (
                    ! in_array(
                        $consumption->trigger,
                        self::allowedTriggers(),
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Invalid recipe consumption trigger.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Order Status Snapshot
                |--------------------------------------------------------------------------
                */

                if (
                    $consumption->order_status_snapshot === ''
                ) {
                    throw new InvalidArgumentException(
                        'Order status snapshot is required for recipe consumption.'
                    );
                }

                if (
                    ! in_array(
                        $consumption->order_status_snapshot,
                        Order::allowedStatuses(),
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Invalid order status snapshot for recipe consumption.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Consumed At
                |--------------------------------------------------------------------------
                */

                if (
                    $consumption->consumed_at === null
                ) {
                    throw new InvalidArgumentException(
                        'Recipe consumption time is required.'
                    );
                }
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Immutable
        |--------------------------------------------------------------------------
        */

        static::updating(
            static function (): never {
                throw new LogicException(
                    'Order recipe consumption history is immutable and cannot be updated.'
                );
            }
        );

        static::deleting(
            static function (): never {
                throw new LogicException(
                    'Order recipe consumption history is immutable and cannot be deleted.'
                );
            }
        );
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

    public function kitchenBatch(): BelongsTo
    {
        return $this->belongsTo(
            OrderKitchenBatch::class,
            'order_kitchen_batch_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            OrderRecipeConsumptionItem::class,
            'order_recipe_consumption_id'
        );
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
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function allowedTriggers(): array
    {
        return [
            self::TRIGGER_START_PREPARING,
        ];
    }
}