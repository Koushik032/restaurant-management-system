<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Extend Recipe Mappings For Add-ons
    |--------------------------------------------------------------------------
    |
    | Existing:
    |     Menu Item -> Recipe Mapping -> Raw Material
    |
    | New:
    |     Menu Item OR Add-on -> Recipe Mapping -> Raw Material
    |
    | Existing menu-item recipe rows are preserved.
    |
    */

    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Allow Menu Item To Be Nullable
        |--------------------------------------------------------------------------
        |
        | Add-on recipe rows will use:
        |
        | menu_item_id = NULL
        | add_on_id    = valid add-on ID
        |
        */

        DB::statement(
            'ALTER TABLE `recipe_mappings` '
            . 'MODIFY `menu_item_id` BIGINT UNSIGNED NULL'
        );


        /*
        |--------------------------------------------------------------------------
        | Add Add-on Target
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'recipe_mappings',
            function (Blueprint $table): void {

                $table->unsignedBigInteger(
                    'add_on_id'
                )
                    ->nullable()
                    ->after(
                        'menu_item_id'
                    );


                $table->foreign(
                    'add_on_id',
                    'recipe_mapping_addon_fk'
                )
                    ->references('id')
                    ->on('add_ons')
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Add-on Ingredient Duplicate Protection
                |--------------------------------------------------------------------------
                |
                | Same raw material cannot appear twice in the same add-on recipe.
                |
                */

                $table->unique(
                    [
                        'add_on_id',
                        'raw_material_id',
                    ],
                    'recipe_mapping_addon_material_unique'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Exactly One Recipe Target
        |--------------------------------------------------------------------------
        |
        | Valid:
        |
        | menu_item_id = 5
        | add_on_id    = NULL
        |
        | OR
        |
        | menu_item_id = NULL
        | add_on_id    = 7
        |
        | Invalid:
        |
        | both filled
        | both NULL
        |
        */

        DB::statement(
            'ALTER TABLE `recipe_mappings` '
            . 'ADD CONSTRAINT `recipe_mapping_target_check` '
            . 'CHECK ('
            . '(`menu_item_id` IS NOT NULL AND `add_on_id` IS NULL) '
            . 'OR '
            . '(`menu_item_id` IS NULL AND `add_on_id` IS NOT NULL)'
            . ')'
        );
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove Target Check
        |--------------------------------------------------------------------------
        */

        DB::statement(
            'ALTER TABLE `recipe_mappings` '
            . 'DROP CHECK `recipe_mapping_target_check`'
        );


        /*
        |--------------------------------------------------------------------------
        | Remove Add-on Recipe Rows
        |--------------------------------------------------------------------------
        |
        | Old schema cannot represent add-on recipes because menu_item_id was
        | mandatory. Therefore rollback removes only add-on recipe rows.
        |
        */

        DB::table(
            'recipe_mappings'
        )
            ->whereNull(
                'menu_item_id'
            )
            ->delete();


        /*
        |--------------------------------------------------------------------------
        | Remove Add-on Target
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'recipe_mappings',
            function (Blueprint $table): void {

                $table->dropUnique(
                    'recipe_mapping_addon_material_unique'
                );


                $table->dropForeign(
                    'recipe_mapping_addon_fk'
                );


                $table->dropColumn(
                    'add_on_id'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Restore Original Menu Item Requirement
        |--------------------------------------------------------------------------
        */

        DB::statement(
            'ALTER TABLE `recipe_mappings` '
            . 'MODIFY `menu_item_id` BIGINT UNSIGNED NOT NULL'
        );
    }
};