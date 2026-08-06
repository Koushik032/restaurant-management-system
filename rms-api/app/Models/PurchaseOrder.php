<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;



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



        'deleted_at' =>
            'datetime',


    ];








    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    /**
     * Purchase order belongs to supplier
     */

    public function supplier(): BelongsTo
    {

        return $this->belongsTo(
            Supplier::class
        );

    }







    /**
     * Purchase order items
     */

    public function items(): HasMany
    {

        return $this->hasMany(
            PurchaseOrderItem::class
        );

    }







    /**
     * User who ordered
     */

    public function orderedBy(): BelongsTo
    {

        return $this->belongsTo(
            User::class,
            'ordered_by'
        );

    }







    /**
     * Created user
     */

    public function creator(): BelongsTo
    {

        return $this->belongsTo(
            User::class,
            'created_by'
        );

    }








    /**
     * Updated user
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
    | Helpers
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


}