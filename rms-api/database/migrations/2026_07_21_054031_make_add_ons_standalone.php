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
        | Step 1: Drop foreign key if it still exists
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'add_ons',
                'add_on_category_id'
            ) &&
            $this->foreignKeyExists(
                'add_ons',
                'add_ons_add_on_category_id_foreign'
            )
        ) {
            Schema::table(
                'add_ons',
                function (Blueprint $table): void {
                    $table->dropForeign(
                        'add_ons_add_on_category_id_foreign'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Step 2: Drop old indexes only if they exist
        |--------------------------------------------------------------------------
        */

        if (
            $this->indexExists(
                'add_ons',
                'add_ons_add_on_category_id_is_available_index'
            )
        ) {
            Schema::table(
                'add_ons',
                function (Blueprint $table): void {
                    $table->dropIndex(
                        'add_ons_add_on_category_id_is_available_index'
                    );
                }
            );
        }

        if (
            $this->indexExists(
                'add_ons',
                'add_on_category_name_unique'
            )
        ) {
            Schema::table(
                'add_ons',
                function (Blueprint $table): void {
                    $table->dropUnique(
                        'add_on_category_name_unique'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Step 3: Remove category column
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'add_ons',
                'add_on_category_id'
            )
        ) {
            Schema::table(
                'add_ons',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'add_on_category_id'
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Step 4: Make add-on name globally unique
        |--------------------------------------------------------------------------
        */

        if (
            !$this->indexExists(
                'add_ons',
                'add_on_name_unique'
            )
        ) {
            Schema::table(
                'add_ons',
                function (Blueprint $table): void {
                    $table->unique(
                        'add_on_name',
                        'add_on_name_unique'
                    );
                }
            );
        }
    }

    public function down(): void
    {
        if (
            $this->indexExists(
                'add_ons',
                'add_on_name_unique'
            )
        ) {
            Schema::table(
                'add_ons',
                function (Blueprint $table): void {
                    $table->dropUnique(
                        'add_on_name_unique'
                    );
                }
            );
        }

        if (
            !Schema::hasColumn(
                'add_ons',
                'add_on_category_id'
            )
        ) {
            Schema::table(
                'add_ons',
                function (Blueprint $table): void {
                    $table
                        ->unsignedBigInteger(
                            'add_on_category_id'
                        )
                        ->nullable()
                        ->after('id');
                }
            );
        }

        if (
            !$this->foreignKeyExists(
                'add_ons',
                'add_ons_add_on_category_id_foreign'
            )
        ) {
            Schema::table(
                'add_ons',
                function (Blueprint $table): void {
                    $table
                        ->foreign(
                            'add_on_category_id',
                            'add_ons_add_on_category_id_foreign'
                        )
                        ->references('id')
                        ->on('add_on_categories')
                        ->restrictOnDelete()
                        ->cascadeOnUpdate();
                }
            );
        }

        if (
            !$this->indexExists(
                'add_ons',
                'add_ons_add_on_category_id_is_available_index'
            )
        ) {
            Schema::table(
                'add_ons',
                function (Blueprint $table): void {
                    $table->index(
                        [
                            'add_on_category_id',
                            'is_available',
                        ],
                        'add_ons_add_on_category_id_is_available_index'
                    );
                }
            );
        }

        if (
            !$this->indexExists(
                'add_ons',
                'add_on_category_name_unique'
            )
        ) {
            Schema::table(
                'add_ons',
                function (Blueprint $table): void {
                    $table->unique(
                        [
                            'add_on_category_id',
                            'add_on_name',
                        ],
                        'add_on_category_name_unique'
                    );
                }
            );
        }
    }

    private function indexExists(
        string $table,
        string $indexName
    ): bool {
        $databaseName = DB::getDatabaseName();

        $result = DB::selectOne(
            '
                SELECT COUNT(*) AS aggregate
                FROM information_schema.statistics
                WHERE table_schema = ?
                  AND table_name = ?
                  AND index_name = ?
            ',
            [
                $databaseName,
                $table,
                $indexName,
            ]
        );

        return (int) $result->aggregate > 0;
    }

    private function foreignKeyExists(
        string $table,
        string $foreignKeyName
    ): bool {
        $databaseName = DB::getDatabaseName();

        $result = DB::selectOne(
            '
                SELECT COUNT(*) AS aggregate
                FROM information_schema.table_constraints
                WHERE constraint_schema = ?
                  AND table_name = ?
                  AND constraint_name = ?
                  AND constraint_type = ?
            ',
            [
                $databaseName,
                $table,
                $foreignKeyName,
                'FOREIGN KEY',
            ]
        );

        return (int) $result->aggregate > 0;
    }
};