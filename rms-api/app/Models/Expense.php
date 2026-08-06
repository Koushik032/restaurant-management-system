<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
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

    public const METHOD_MIXED =
        'mixed';

    public const METHOD_OTHER =
        'other';

    /*
    |--------------------------------------------------------------------------
    | Mass Assignable Fields
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'expense_category_id',
        'expense_date',
        'amount',
        'payment_method',
        'notes',
        'created_by',
        'updated_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Attribute Casting
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'expense_category_id' =>
                'integer',

            'expense_date' =>
                'datetime',

            'amount' =>
                'decimal:2',

            'created_by' =>
                'integer',

            'updated_by' =>
                'integer',

            'deleted_at' =>
                'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            ExpenseCategory::class,
            'expense_category_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeDateRange(
        Builder $query,
        ?string $dateFrom,
        ?string $dateTo
    ): Builder {
        $dateFrom =
            trim(
                (string) $dateFrom
            );

        $dateTo =
            trim(
                (string) $dateTo
            );

        /*
        |--------------------------------------------------------------------------
        | From and To Date
        |--------------------------------------------------------------------------
        */

        if (
            $dateFrom !== '' &&
            $dateTo !== ''
        ) {
            return $query->whereBetween(
                'expense_date',
                [
                    $dateFrom
                        . ' 00:00:00',

                    $dateTo
                        . ' 23:59:59',
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Only From Date
        |--------------------------------------------------------------------------
        |
        | একটি date select করলে ওই একদিনের expense দেখাবে।
        |
        */

        if ($dateFrom !== '') {
            return $query->whereDate(
                'expense_date',
                $dateFrom
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Only To Date
        |--------------------------------------------------------------------------
        |
        | একটি date select করলে ওই একদিনের expense দেখাবে।
        |
        */

        if ($dateTo !== '') {
            return $query->whereDate(
                'expense_date',
                $dateTo
            );
        }

        return $query;
    }

    public function scopeCategory(
        Builder $query,
        int|string|null $categoryId
    ): Builder {
        if (
            $categoryId === null ||
            $categoryId === ''
        ) {
            return $query;
        }

        return $query->where(
            'expense_category_id',
            (int) $categoryId
        );
    }

    public function scopePaymentMethod(
        Builder $query,
        ?string $paymentMethod
    ): Builder {
        $paymentMethod =
            trim(
                (string) $paymentMethod
            );

        if ($paymentMethod === '') {
            return $query;
        }

        return $query->where(
            'payment_method',
            $paymentMethod
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
            function (
                Builder $builder
            ) use (
                $search
            ): void {
                $builder
                    ->where(
                        'notes',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'category',
                        function (
                            Builder $categoryQuery
                        ) use (
                            $search
                        ): void {
                            $categoryQuery->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    )
                    ->orWhereHas(
                        'creator',
                        function (
                            Builder $userQuery
                        ) use (
                            $search
                        ): void {
                            $userQuery
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Payment Method Helpers
    |--------------------------------------------------------------------------
    */

    public static function allowedPaymentMethods(): array
    {
        return [
            self::METHOD_CASH,
            self::METHOD_CARD,
            self::METHOD_BKASH,
            self::METHOD_NAGAD,
            self::METHOD_BANK_TRANSFER,
            self::METHOD_MIXED,
            self::METHOD_OTHER,
        ];
    }

    public static function paymentMethodOptions(): array
    {
        return [
            [
                'value' =>
                    self::METHOD_CASH,

                'label' =>
                    'Cash',
            ],

            [
                'value' =>
                    self::METHOD_CARD,

                'label' =>
                    'Card',
            ],

            [
                'value' =>
                    self::METHOD_BKASH,

                'label' =>
                    'bKash',
            ],

            [
                'value' =>
                    self::METHOD_NAGAD,

                'label' =>
                    'Nagad',
            ],

            [
                'value' =>
                    self::METHOD_BANK_TRANSFER,

                'label' =>
                    'Bank Transfer',
            ],

            [
                'value' =>
                    self::METHOD_MIXED,

                'label' =>
                    'Mixed Payment',
            ],

            [
                'value' =>
                    self::METHOD_OTHER,

                'label' =>
                    'Other',
            ],
        ];
    }

    public function paymentMethodLabel(): string
    {
        return match (
            $this->payment_method
        ) {
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

            self::METHOD_MIXED =>
                'Mixed Payment',

            self::METHOD_OTHER =>
                'Other',

            default =>
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        (string)
                            $this
                                ->payment_method
                    )
                ),
        };
    }
}