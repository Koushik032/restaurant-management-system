<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;



class Supplier extends Model
{

    use HasFactory;
    use SoftDeletes;



    /*
    |--------------------------------------------------------------------------
    | Mass Assignment
    |--------------------------------------------------------------------------
    */


    protected $fillable = [

        'supplier_name',

        'contact_person',

        'email',

        'phone',

        'address',

        'gstin',

        'created_by',

        'updated_by',

    ];





    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */


    protected $casts = [

        'deleted_at' => 'datetime',

    ];







    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */



    /**
     * Supplier has many purchase orders
     */

    public function purchaseOrders(): HasMany
    {

        return $this->hasMany(
            PurchaseOrder::class
        );

    }






    /**
     * User who created supplier
     */

    public function creator(): BelongsTo
    {

        return $this->belongsTo(
            User::class,
            'created_by'
        );

    }







    /**
     * User who updated supplier
     */

    public function updater(): BelongsTo
    {

        return $this->belongsTo(
            User::class,
            'updated_by'
        );

    }



}