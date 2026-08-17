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
        | Add Kitchen Batch Reference
        |--------------------------------------------------------------------------
        |
        | Initially nullable because existing order items must first be attached
        | to their historical Batch #1 before the column becomes required.
        |
        */

        Schema::table(
            'order_items',
            function (Blueprint $table): void {
                $table->unsignedBigInteger(
                    'order_kitchen_batch_id'
                )
                    ->nullable()
                    ->after('order_id');

                $table->foreign(
                    'order_kitchen_batch_id',
                    'order_items_kitchen_batch_fk'
                )
                    ->references('id')
                    ->on('order_kitchen_batches')
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Kitchen Queue Index
                |--------------------------------------------------------------------------
                |
                | Future kitchen queries will frequently filter:
                |
                | batch + item status
                |
                */

                $table->index(
                    [
                        'order_kitchen_batch_id',
                        'status',
                    ],
                    'order_items_batch_status_index'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Backfill Existing Order Items
        |--------------------------------------------------------------------------
        |
        | Every existing order already received Kitchen Batch #1 in the previous
        | migration.
        |
        | All current historical order_items therefore belong to Batch #1.
        |
        */

        DB::statement(
            <<<'SQL'
            UPDATE order_items AS oi
            INNER JOIN order_kitchen_batches AS okb
                ON okb.order_id = oi.order_id
               AND okb.batch_no = 1
            SET oi.order_kitchen_batch_id = okb.id
            WHERE oi.order_kitchen_batch_id IS NULL
            SQL
        );

        /*
        |--------------------------------------------------------------------------
        | Backfill Safety Check
        |--------------------------------------------------------------------------
        |
        | We must never leave an order item without a kitchen batch.
        |
        */

        $unassignedItemCount =
            DB::table('order_items')
                ->whereNull(
                    'order_kitchen_batch_id'
                )
                ->count();

        if ($unassignedItemCount > 0) {
            throw new \RuntimeException(
                "Unable to assign {$unassignedItemCount} existing order item(s) to Kitchen Batch #1."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Make Kitchen Batch Required
        |--------------------------------------------------------------------------
        |
        | From this point every OrderItem must always belong to one batch.
        |
        */

        DB::statement(
            <<<'SQL'
            ALTER TABLE order_items
            MODIFY order_kitchen_batch_id BIGINT UNSIGNED NOT NULL
            SQL
        );
    }

    public function down(): void
    {
        Schema::table(
            'order_items',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'order_items_batch_status_index'
                );

                $table->dropForeign(
                    'order_items_kitchen_batch_fk'
                );

                $table->dropColumn(
                    'order_kitchen_batch_id'
                );
            }
        );
    }
};