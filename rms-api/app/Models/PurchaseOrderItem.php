<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;



class PurchaseOrderItem extends Model
{


    use HasFactory;





    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */


    protected $fillable = [


        'purchase_order_id',

        'item_name',

        'unit',

        'quantity',

        'received_quantity',

        'unit_price',

        'total_price',


    ];








    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */


    protected $casts = [


        'quantity' =>
            'decimal:2',


        'received_quantity' =>
            'decimal:2',


        'unit_price' =>
            'decimal:2',


        'total_price' =>
            'decimal:2',


    ];









    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */


    public function purchaseOrder(): BelongsTo
    {

        return $this->belongsTo(
            PurchaseOrder::class
        );

    }



}