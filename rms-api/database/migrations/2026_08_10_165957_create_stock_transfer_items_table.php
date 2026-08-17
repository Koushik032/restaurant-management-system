<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'stock_transfer_items',
            function (Blueprint $table): void {

                $table->id();

                $table->foreignId(
                    'stock_transfer_id'
                )
                    ->constrained(
                        'stock_transfers'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'raw_material_id'
                )
                    ->constrained(
                        'raw_materials'
                    )
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Snapshot
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'item_name',
                    255
                );

                $table->string(
                    'unit',
                    30
                );

                $table->decimal(
                    'quantity',
                    14,
                    4
                );

                $table->decimal(
                    'unit_cost',
                    14,
                    4
                );

                /*
                |--------------------------------------------------------------------------
                | Warehouse Snapshot
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'warehouse_quantity_before',
                    14,
                    4
                );

                $table->decimal(
                    'warehouse_quantity_after',
                    14,
                    4
                );

                /*
                |--------------------------------------------------------------------------
                | Restaurant Snapshot
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

                $table->text(
                    'notes'
                )->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'stock_transfer_id',
                        'raw_material_id',
                    ],
                    'stock_transfer_material_unique'
                );

                $table->index(
                    'raw_material_id'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'stock_transfer_items'
        );
    }
};