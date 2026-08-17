<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'recipe_mappings',

            function (
                Blueprint $table
            ): void {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Menu Item
                |--------------------------------------------------------------------------
                |
                | একটি Menu Item-এর একাধিক ingredient থাকতে পারবে।
                |
                */

                $table->foreignId(
                    'menu_item_id'
                )
                    ->constrained(
                        'menu_items'
                    )
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Raw Material / Ingredient
                |--------------------------------------------------------------------------
                |
                | Ingredient অবশ্যই inventory Raw Material হবে।
                |
                */

                $table->foreignId(
                    'raw_material_id'
                )
                    ->constrained(
                        'raw_materials'
                    )
                    ->restrictOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Required Quantity
                |--------------------------------------------------------------------------
                |
                | Ingredient quantity Raw Material-এর base unit অনুযায়ী থাকবে।
                |
                | Example:
                |
                | Beef Burger:
                |
                | Bun       = 1.0000 pcs
                | Beef      = 0.1500 kg
                | Cheese    = 1.0000 slice
                | Sauce     = 0.0250 litre
                |
                */

                $table->decimal(
                    'quantity',
                    14,
                    4
                );


                /*
                |--------------------------------------------------------------------------
                | Unit Snapshot
                |--------------------------------------------------------------------------
                |
                | Recipe save করার সময় RawMaterial::base_unit থেকে populate হবে।
                |
                */

                $table->string(
                    'unit',
                    30
                );


                /*
                |--------------------------------------------------------------------------
                | Optional Notes
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'notes'
                )
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Audit Users
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();


                $table->foreignId(
                    'updated_by'
                )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();


                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Duplicate Ingredient Protection
                |--------------------------------------------------------------------------
                |
                | একই Menu Item-এ একই Raw Material দুইবার add করা যাবে না।
                |
                */

                $table->unique(
                    [
                        'menu_item_id',
                        'raw_material_id',
                    ],
                    'recipe_mapping_menu_material_unique'
                );


                /*
                |--------------------------------------------------------------------------
                | Raw Material Lookup
                |--------------------------------------------------------------------------
                */

                $table->index(
                    'raw_material_id',
                    'recipe_mapping_raw_material_index'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'recipe_mappings'
        );
    }
};