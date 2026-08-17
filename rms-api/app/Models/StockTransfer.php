<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;


class StockTransfer extends Model
{
    use HasFactory;


    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'transfer_no',

        'transferred_at',

        'notes',

        'transferred_by',

        'created_by',

        'updated_by',

    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'transferred_at' =>
            'datetime',

        'transferred_by' =>
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
    | Stock transfer is a historical inventory transaction.
    |
    | Create:
    |     Allowed
    |
    | Update:
    |     Blocked
    |
    | Delete:
    |     Blocked
    |
    */

    protected static function booted(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Validate Transfer Creation
        |--------------------------------------------------------------------------
        */

        static::creating(

            function (
                StockTransfer $transfer
            ): void {

                /*
                |--------------------------------------------------------------------------
                | Transfer Number
                |--------------------------------------------------------------------------
                */

                $transferNo =
                    trim(
                        (string) (
                            $transfer->transfer_no
                            ?? ''
                        )
                    );


                if ($transferNo === '') {

                    throw ValidationException::withMessages([

                        'transfer_no' => [
                            'Stock transfer number is required.',
                        ],

                    ]);
                }


                $transfer->transfer_no =
                    $transferNo;


                /*
                |--------------------------------------------------------------------------
                | Transfer Date
                |--------------------------------------------------------------------------
                */

                if ($transfer->transferred_at === null) {

                    throw ValidationException::withMessages([

                        'transferred_at' => [
                            'Stock transfer date and time are required.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Transferred By
                |--------------------------------------------------------------------------
                */

                $transferredBy =
                    (int) (
                        $transfer->transferred_by
                        ?? 0
                    );


                if ($transferredBy <= 0) {

                    throw ValidationException::withMessages([

                        'transferred_by' => [
                            'The user performing the stock transfer is required.',
                        ],

                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Normalize Notes
                |--------------------------------------------------------------------------
                */

                if ($transfer->notes !== null) {

                    $notes =
                        trim(
                            (string) $transfer->notes
                        );


                    $transfer->notes =
                        $notes !== ''
                            ? $notes
                            : null;
                }
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Transfer Modification
        |--------------------------------------------------------------------------
        */

        static::updating(

            function (): void {

                throw ValidationException::withMessages([

                    'stock_transfer' => [
                        'Stock transfer history cannot be modified.',
                    ],

                ]);
            }

        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Transfer Deletion
        |--------------------------------------------------------------------------
        */

        static::deleting(

            function (): void {

                throw ValidationException::withMessages([

                    'stock_transfer' => [
                        'Stock transfer history cannot be deleted.',
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
    | Transfer Items
    |--------------------------------------------------------------------------
    */

    public function items(): HasMany
    {
        return $this->hasMany(
            StockTransferItem::class,
            'stock_transfer_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Transferred By
    |--------------------------------------------------------------------------
    */

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'transferred_by'
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
    | Total Quantity
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


    /*
    |--------------------------------------------------------------------------
    | Item Count
    |--------------------------------------------------------------------------
    */

    public function itemCount(): int
    {
        if (
            $this->relationLoaded(
                'items'
            )
        ) {

            return $this
                ->items
                ->count();
        }


        return $this
            ->items()
            ->count();
    }
}