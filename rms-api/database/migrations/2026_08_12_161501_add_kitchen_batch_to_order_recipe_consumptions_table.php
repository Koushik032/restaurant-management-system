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
        | Nullable first so existing immutable consumption rows can be safely
        | backfilled to their historical Kitchen Batch #1.
        |
        */

        Schema::table(
            'order_recipe_consumptions',
            function (Blueprint $table): void {
                $table->unsignedBigInteger(
                    'order_kitchen_batch_id'
                )
                    ->nullable()
                    ->after('order_id');

                $table->foreign(
                    'order_kitchen_batch_id',
                    'order_recipe_consumptions_batch_fk'
                )
                    ->references('id')
                    ->on('order_kitchen_batches')
                    ->restrictOnDelete();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Backfill Existing Consumption Rows
        |--------------------------------------------------------------------------
        |
        | Every existing order already has Kitchen Batch #1 from the previous
        | migration, so every historical consumption belongs to Batch #1.
        |
        */

        DB::statement(
            <<<'SQL'
            UPDATE order_recipe_consumptions AS orc
            INNER JOIN order_kitchen_batches AS okb
                ON okb.order_id = orc.order_id
               AND okb.batch_no = 1
            SET orc.order_kitchen_batch_id = okb.id
            WHERE orc.order_kitchen_batch_id IS NULL
            SQL
        );

        /*
        |--------------------------------------------------------------------------
        | Backfill Safety Check
        |--------------------------------------------------------------------------
        */

        $unassignedConsumptionCount =
            DB::table('order_recipe_consumptions')
                ->whereNull(
                    'order_kitchen_batch_id'
                )
                ->count();

        if ($unassignedConsumptionCount > 0) {
            throw new \RuntimeException(
                "Unable to assign {$unassignedConsumptionCount} existing recipe consumption record(s) to Kitchen Batch #1."
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Make Batch Required
        |--------------------------------------------------------------------------
        */

        DB::statement(
            <<<'SQL'
            ALTER TABLE order_recipe_consumptions
            MODIFY order_kitchen_batch_id BIGINT UNSIGNED NOT NULL
            SQL
        );

        /*
        |--------------------------------------------------------------------------
        | New Batch-Level Idempotency
        |--------------------------------------------------------------------------
        |
        | One recipe consumption per kitchen batch.
        |
        | Same order can therefore have:
        |
        | Batch 1 → consumption
        | Batch 2 → consumption
        | Batch 3 → consumption
        |
        */

        Schema::table(
            'order_recipe_consumptions',
            function (Blueprint $table): void {
                $table->unique(
                    [
                        'order_id',
                        'order_kitchen_batch_id',
                    ],
                    'order_recipe_consumptions_order_batch_unique'
                );

                $table->index(
                    'order_kitchen_batch_id',
                    'order_recipe_consumptions_batch_index'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Remove Old UNIQUE(order_id)
        |--------------------------------------------------------------------------
        |
        | Existing migration-এর exact unique index name assume করছি না।
        |
        | MySQL থেকে UNIQUE indexes detect করে যেটার exact column set শুধুমাত্র
        | [order_id], সেটাই remove হবে।
        |
        */

        $this->dropOrderOnlyUniqueIndexes();
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Rollback Safety
        |--------------------------------------------------------------------------
        |
        | যদি একই order-এর multiple batch consumption already তৈরি হয়ে যায়,
        | পুরনো UNIQUE(order_id) contract restore করা data loss ছাড়া সম্ভব না।
        |
        */

        $duplicateOrderExists =
            DB::table('order_recipe_consumptions')
                ->select('order_id')
                ->groupBy('order_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

        if ($duplicateOrderExists) {
            throw new \RuntimeException(
                'Cannot roll back batch-aware recipe consumption because one or more orders already contain multiple recipe consumption records.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Restore Old UNIQUE(order_id)
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'order_recipe_consumptions',
            function (Blueprint $table): void {
                $table->unique(
                    'order_id',
                    'order_recipe_consumptions_order_id_unique'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Remove Batch Foreign Key
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'order_recipe_consumptions',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'order_recipe_consumptions_batch_fk'
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Remove Batch Constraints + Column
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'order_recipe_consumptions',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'order_recipe_consumptions_order_batch_unique'
                );

                $table->dropIndex(
                    'order_recipe_consumptions_batch_index'
                );

                $table->dropColumn(
                    'order_kitchen_batch_id'
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Drop Legacy UNIQUE(order_id)
    |--------------------------------------------------------------------------
    */

    private function dropOrderOnlyUniqueIndexes(): void
    {
        $indexes = DB::select(
            'SHOW INDEX FROM `order_recipe_consumptions` WHERE `Non_unique` = 0'
        );

        $groupedIndexes = [];

        foreach ($indexes as $index) {
            $keyName =
                (string) $index->Key_name;

            if ($keyName === 'PRIMARY') {
                continue;
            }

            $groupedIndexes[
                $keyName
            ][
                (int) $index->Seq_in_index
            ] =
                (string) $index->Column_name;
        }

        foreach (
            $groupedIndexes
            as
            $keyName => $columns
        ) {
            ksort($columns);

            if (
                array_values($columns)
                !==
                ['order_id']
            ) {
                continue;
            }

            $safeIndexName =
                str_replace(
                    '`',
                    '``',
                    $keyName
                );

            DB::statement(
                "ALTER TABLE `order_recipe_consumptions` DROP INDEX `{$safeIndexName}`"
            );
        }
    }
};