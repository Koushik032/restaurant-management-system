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
        | Consumption Header
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'order_recipe_consumptions',

            function (
                Blueprint $table
            ): void {

                $table->id();

                $table->foreignId(
                    'order_id'
                );

                $table->string(
                    'order_number',
                    100
                );

                $table->string(
                    'trigger',
                    50
                )
                    ->default(
                        'start_preparing'
                    );

                $table->string(
                    'order_status_snapshot',
                    30
                );

                $table->timestamp(
                    'consumed_at'
                );

                $table->foreignId(
                    'created_by'
                )
                    ->nullable();

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'order_id',
                    'orc_order_fk'
                )
                    ->references(
                        'id'
                    )
                    ->on(
                        'orders'
                    )
                    ->restrictOnDelete();


                $table->foreign(
                    'created_by',
                    'orc_created_by_fk'
                )
                    ->references(
                        'id'
                    )
                    ->on(
                        'users'
                    )
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Hard Idempotency
                |--------------------------------------------------------------------------
                |
                | One recipe consumption only per Order.
                |
                */

                $table->unique(
                    'order_id',
                    'orc_order_unique'
                );


                $table->index(
                    'consumed_at',
                    'orc_consumed_at_idx'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Consumption Ingredient Items
        |--------------------------------------------------------------------------
        */

        Schema::create(
            'order_recipe_consumption_items',

            function (
                Blueprint $table
            ): void {

                $table->id();

                $table->foreignId(
                    'order_recipe_consumption_id'
                );

                $table->foreignId(
                    'raw_material_id'
                );


                /*
                |--------------------------------------------------------------------------
                | Raw Material Snapshot
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'material_name',
                    255
                );

                $table->string(
                    'unit',
                    30
                );


                /*
                |--------------------------------------------------------------------------
                | Consumption Quantity
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'quantity',
                    14,
                    4
                );


                /*
                |--------------------------------------------------------------------------
                | Cost Snapshot
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'unit_cost',
                    14,
                    4
                )
                    ->default(
                        0
                    );


                /*
                |--------------------------------------------------------------------------
                | Restaurant Stock Snapshot
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'restaurant_quantity_before',
                    14,
                    4
                );

                $table->decimal(
                    'restaurant_quantity_after',
                    14,
                    4
                );


                /*
                |--------------------------------------------------------------------------
                | Recipe Source Breakdown
                |--------------------------------------------------------------------------
                */

                $table->json(
                    'source_breakdown'
                )
                    ->nullable();


                $table->text(
                    'notes'
                )
                    ->nullable();


                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Foreign Keys
                |--------------------------------------------------------------------------
                */

                $table->foreign(
                    'order_recipe_consumption_id',
                    'orci_consumption_fk'
                )
                    ->references(
                        'id'
                    )
                    ->on(
                        'order_recipe_consumptions'
                    )
                    ->cascadeOnDelete();


                $table->foreign(
                    'raw_material_id',
                    'orci_material_fk'
                )
                    ->references(
                        'id'
                    )
                    ->on(
                        'raw_materials'
                    )
                    ->restrictOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Unique Ingredient Per Consumption
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'order_recipe_consumption_id',
                        'raw_material_id',
                    ],
                    'orci_consumption_material_unique'
                );


                $table->index(
                    'raw_material_id',
                    'orci_material_idx'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'order_recipe_consumption_items'
        );

        Schema::dropIfExists(
            'order_recipe_consumptions'
        );
    }
};