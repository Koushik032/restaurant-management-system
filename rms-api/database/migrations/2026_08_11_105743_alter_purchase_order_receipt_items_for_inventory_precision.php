<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Increase Receipt Quantity Precision
        |--------------------------------------------------------------------------
        |
        | Old:
        | decimal(14, 2)
        |
        | New:
        | decimal(14, 4)
        |
        | This keeps GRN quantity consistent with:
        |
        | PurchaseOrderItem
        | WarehouseStock
        | RestaurantStock
        | StockMovement
        | StockTransferItem
        |
        */

        Schema::table(
            'purchase_order_receipt_items',

            function (
                Blueprint $table
            ): void {

                $table->decimal(
                    'quantity',
                    14,
                    4
                )
                    ->change();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Protect Receipt Item History
        |--------------------------------------------------------------------------
        |
        | Original FK used cascadeOnDelete().
        |
        | GRN item history should not disappear automatically if somebody
        | attempts a physical delete of the parent receipt.
        |
        */

        Schema::table(
            'purchase_order_receipt_items',

            function (
                Blueprint $table
            ): void {

                $table->dropForeign([
                    'purchase_order_receipt_id',
                ]);
            }
        );


        Schema::table(
            'purchase_order_receipt_items',

            function (
                Blueprint $table
            ): void {

                $table->foreign(
                    'purchase_order_receipt_id'
                )
                    ->references(
                        'id'
                    )
                    ->on(
                        'purchase_order_receipts'
                    )
                    ->restrictOnDelete();
            }
        );
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Restore Original Receipt FK
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'purchase_order_receipt_items',

            function (
                Blueprint $table
            ): void {

                $table->dropForeign([
                    'purchase_order_receipt_id',
                ]);
            }
        );


        Schema::table(
            'purchase_order_receipt_items',

            function (
                Blueprint $table
            ): void {

                $table->foreign(
                    'purchase_order_receipt_id'
                )
                    ->references(
                        'id'
                    )
                    ->on(
                        'purchase_order_receipts'
                    )
                    ->cascadeOnDelete();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Restore Original Quantity Precision
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'purchase_order_receipt_items',

            function (
                Blueprint $table
            ): void {

                $table->decimal(
                    'quantity',
                    14,
                    2
                )
                    ->change();
            }
        );
    }
};