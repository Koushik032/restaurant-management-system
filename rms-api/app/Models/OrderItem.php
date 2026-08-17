<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_kitchen_batch_id',
        'menu_item_id',
        'menu_item_variant_id',
        'item_name',
        'variant_name',
        'unit_price',
        'quantity',
        'addon_total',
        'line_total',
        'status',
        'kitchen_note',
    ];

    protected function casts(): array
    {
        return [
            'order_id' =>
                'integer',

            'order_kitchen_batch_id' =>
                'integer',

            'menu_item_id' =>
                'integer',

            'menu_item_variant_id' =>
                'integer',

            'unit_price' =>
                'decimal:2',

            'quantity' =>
                'integer',

            'addon_total' =>
                'decimal:2',

            'line_total' =>
                'decimal:2',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Parent Order
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Kitchen Batch
    |--------------------------------------------------------------------------
    |
    | Every order item belongs to exactly one kitchen batch.
    |
    | Example:
    |
    | Order #1001
    |
    | Batch #1
    | - Burger
    | - Fries
    |
    | Batch #2
    | - Pizza
    |
    */

    public function kitchenBatch(): BelongsTo
    {
        return $this->belongsTo(
            OrderKitchenBatch::class,
            'order_kitchen_batch_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Menu Item
    |--------------------------------------------------------------------------
    */

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(
            MenuItem::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Menu Item Variant
    |--------------------------------------------------------------------------
    */

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            MenuItemVariant::class,
            'menu_item_variant_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Ordered Add-ons
    |--------------------------------------------------------------------------
    */

    public function addons(): HasMany
    {
        return $this->hasMany(
            OrderItemAddon::class
        );
    }
}