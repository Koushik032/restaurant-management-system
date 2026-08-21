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
        | Variant Column
        |--------------------------------------------------------------------------
        |
        | The previous migration attempt may already have created this column
        | before failing later in the migration.
        |
        */

        if (
            ! Schema::hasColumn(
                'recipe_mappings',
                'variant_id'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->unsignedBigInteger(
                        'variant_id'
                    )
                        ->nullable()
                        ->after(
                            'menu_item_id'
                        );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Variant Foreign Key
        |--------------------------------------------------------------------------
        |
        | Only create it when it does not already exist.
        |
        */

        if (
            ! $this->foreignKeyExists(
                'recipe_mappings',
                'recipe_mapping_variant_fk'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->foreign(
                        'variant_id',
                        'recipe_mapping_variant_fk'
                    )
                        ->references('id')
                        ->on('menu_item_variants')
                        ->restrictOnDelete()
                        ->cascadeOnUpdate();
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Supporting Menu Item Index
        |--------------------------------------------------------------------------
        |
        | The old unique index:
        |
        | menu_item_id + raw_material_id
        |
        | is currently being used by MySQL as the supporting index for the
        | menu_item_id foreign key.
        |
        | Therefore create a dedicated index first.
        |
        */

        if (
            ! $this->indexExists(
                'recipe_mappings',
                'recipe_mapping_menu_item_index'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->index(
                        'menu_item_id',
                        'recipe_mapping_menu_item_index'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Remove Old Menu Item Unique Index
        |--------------------------------------------------------------------------
        |
        | Old:
        |
        | menu_item_id + raw_material_id
        |
        | New:
        |
        | menu_item_id + variant_id + raw_material_id
        |
        */

        if (
            $this->indexExists(
                'recipe_mappings',
                'recipe_mapping_menu_material_unique'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->dropUnique(
                        'recipe_mapping_menu_material_unique'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | New Menu Item + Variant Unique Index
        |--------------------------------------------------------------------------
        |
        | Protects:
        |
        | Menu Item + Variant + Raw Material
        |
        */

        if (
            ! $this->indexExists(
                'recipe_mappings',
                'recipe_mapping_menu_variant_material_unique'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->unique(
                        [
                            'menu_item_id',
                            'variant_id',
                            'raw_material_id',
                        ],
                        'recipe_mapping_menu_variant_material_unique'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Variant Lookup Index
        |--------------------------------------------------------------------------
        */

        if (
            ! $this->indexExists(
                'recipe_mappings',
                'recipe_mapping_variant_index'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->index(
                        'variant_id',
                        'recipe_mapping_variant_index'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Add-on Unique Index
        |--------------------------------------------------------------------------
        |
        | The existing:
        |
        | add_on_id + raw_material_id
        |
        | constraint is already correct for add-ons.
        |
        | It is intentionally kept.
        |
        */
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove New Menu Item Unique Index
        |--------------------------------------------------------------------------
        */

        if (
            $this->indexExists(
                'recipe_mappings',
                'recipe_mapping_menu_variant_material_unique'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->dropUnique(
                        'recipe_mapping_menu_variant_material_unique'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Restore Old Menu Item Unique Index
        |--------------------------------------------------------------------------
        */

        if (
            ! $this->indexExists(
                'recipe_mappings',
                'recipe_mapping_menu_material_unique'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->unique(
                        [
                            'menu_item_id',
                            'raw_material_id',
                        ],
                        'recipe_mapping_menu_material_unique'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Remove Variant Index
        |--------------------------------------------------------------------------
        */

        if (
            $this->indexExists(
                'recipe_mappings',
                'recipe_mapping_variant_index'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->dropIndex(
                        'recipe_mapping_variant_index'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Remove Variant Foreign Key
        |--------------------------------------------------------------------------
        */

        if (
            $this->foreignKeyExists(
                'recipe_mappings',
                'recipe_mapping_variant_fk'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->dropForeign(
                        'recipe_mapping_variant_fk'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Remove Variant Column
        |--------------------------------------------------------------------------
        */

        if (
            Schema::hasColumn(
                'recipe_mappings',
                'variant_id'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->dropColumn(
                        'variant_id'
                    );
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Remove Supporting Menu Item Index
        |--------------------------------------------------------------------------
        */

        if (
            $this->indexExists(
                'recipe_mappings',
                'recipe_mapping_menu_item_index'
            )
        ) {
            Schema::table(
                'recipe_mappings',
                function (Blueprint $table): void {
                    $table->dropIndex(
                        'recipe_mapping_menu_item_index'
                    );
                }
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Index Exists
    |--------------------------------------------------------------------------
    */

    private function indexExists(
        string $table,
        string $indexName
    ): bool {
        $databaseName =
            Schema::getConnection()
                ->getDatabaseName();

        $result =
            Schema::getConnection()
                ->selectOne(
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

        return (
            (int) $result->aggregate
        ) > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Foreign Key Exists
    |--------------------------------------------------------------------------
    */

    private function foreignKeyExists(
        string $table,
        string $foreignKeyName
    ): bool {
        $databaseName =
            Schema::getConnection()
                ->getDatabaseName();

        $result =
            Schema::getConnection()
                ->selectOne(
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

        return (
            (int) $result->aggregate
        ) > 0;
    }
};