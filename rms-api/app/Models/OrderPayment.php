<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;
use LogicException;


class OrderPayment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_BKASH = 'bkash';
    public const METHOD_NAGAD = 'nagad';
    public const METHOD_BANK_TRANSFER = 'bank_transfer';


    /*
    |--------------------------------------------------------------------------
    | Mass Assignable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'order_id',
        'amount',
        'payment_method',
        'reference',
        'note',
        'received_by',
    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'amount' => 'decimal:2',
            'received_by' => 'integer',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Allowed Transaction Payment Methods
    |--------------------------------------------------------------------------
    |
    | "mixed" is an Order summary value only. Each immutable payment row must
    | represent one actual payment method.
    |
    */

    public static function paymentMethods(): array
    {
        return [
            self::METHOD_CASH,
            self::METHOD_CARD,
            self::METHOD_BKASH,
            self::METHOD_NAGAD,
            self::METHOD_BANK_TRANSFER,
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
            function (OrderPayment $payment): void {
                $payment->order_id = (int) $payment->order_id;
                $payment->received_by = (int) $payment->received_by;
                $payment->amount = round(
                    (float) $payment->amount,
                    2
                );

                $payment->payment_method = strtolower(
                    trim(
                        (string) $payment->payment_method
                    )
                );

                $payment->reference = static::normalizeNullableText(
                    $payment->reference
                );

                $payment->note = static::normalizeNullableText(
                    $payment->note
                );

                if ($payment->order_id <= 0) {
                    throw new InvalidArgumentException(
                        'A valid order is required for an order payment.'
                    );
                }

                if ((float) $payment->amount <= 0) {
                    throw new InvalidArgumentException(
                        'Order payment amount must be greater than zero.'
                    );
                }

                if (
                    ! in_array(
                        $payment->payment_method,
                        static::paymentMethods(),
                        true
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Invalid order payment method.'
                    );
                }

                if ($payment->received_by <= 0) {
                    throw new InvalidArgumentException(
                        'A valid payment receiver is required.'
                    );
                }
            }
        );

        static::updating(
            static function (): never {
                throw new LogicException(
                    'Order payment history is immutable and cannot be updated.'
                );
            }
        );

        static::deleting(
            static function (): never {
                throw new LogicException(
                    'Order payment history is immutable and cannot be deleted.'
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
            Order::class
        )->withTrashed();
    }


    public function receiver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'received_by'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeMethod(
        Builder $query,
        ?string $method
    ): Builder {
        $method = strtolower(
            trim(
                (string) $method
            )
        );

        if ($method === '') {
            return $query;
        }

        return $query->where(
            'payment_method',
            $method
        );
    }


    public function scopeLatestFirst(
        Builder $query
    ): Builder {
        return $query->orderByDesc('id');
    }


    private static function normalizeNullableText(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value !== ''
            ? $value
            : null;
    }
}