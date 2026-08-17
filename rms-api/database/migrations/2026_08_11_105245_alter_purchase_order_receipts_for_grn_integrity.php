<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Backfill Missing Receipt Numbers
        |--------------------------------------------------------------------------
        |
        | Existing rows may have receipt_no = NULL because the original
        | migration allowed nullable receipt numbers.
        |
        */

        DB::table('purchase_order_receipts')
            ->whereNull('receipt_no')
            ->orderBy('id')
            ->get(['id'])
            ->each(
                function ($receipt): void {

                    DB::table(
                        'purchase_order_receipts'
                    )
                        ->where(
                            'id',
                            $receipt->id
                        )
                        ->update([

                            'receipt_no' =>
                                'GRN-MIGRATED-'
                                .
                                str_pad(
                                    (string) $receipt->id,
                                    6,
                                    '0',
                                    STR_PAD_LEFT
                                ),

                        ]);
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Drop Existing Purchase Order Foreign Key
        |--------------------------------------------------------------------------
        |
        | Original:
        |
        | cascadeOnDelete()
        |
        */

        Schema::table(
            'purchase_order_receipts',

            function (
                Blueprint $table
            ): void {

                $table->dropForeign([
                    'purchase_order_id',
                ]);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Make Receipt Number Required
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'purchase_order_receipts',

            function (
                Blueprint $table
            ): void {

                $table->string(
                    'receipt_no',
                    80
                )
                    ->nullable(false)
                    ->change();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Recreate Purchase Order Foreign Key
        |--------------------------------------------------------------------------
        |
        | Do not delete GRN history when a PO is hard deleted.
        |
        */

        Schema::table(
            'purchase_order_receipts',

            function (
                Blueprint $table
            ): void {

                $table->foreign(
                    'purchase_order_id'
                )
                    ->references(
                        'id'
                    )
                    ->on(
                        'purchase_orders'
                    )
                    ->restrictOnDelete();
            }
        );
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove Restricted Foreign Key
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'purchase_order_receipts',

            function (
                Blueprint $table
            ): void {

                $table->dropForeign([
                    'purchase_order_id',
                ]);
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Restore Nullable Receipt Number
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'purchase_order_receipts',

            function (
                Blueprint $table
            ): void {

                $table->string(
                    'receipt_no',
                    80
                )
                    ->nullable()
                    ->change();
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Restore Original Cascade Foreign Key
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'purchase_order_receipts',

            function (
                Blueprint $table
            ): void {

                $table->foreign(
                    'purchase_order_id'
                )
                    ->references(
                        'id'
                    )
                    ->on(
                        'purchase_orders'
                    )
                    ->cascadeOnDelete();
            }
        );
    }
};