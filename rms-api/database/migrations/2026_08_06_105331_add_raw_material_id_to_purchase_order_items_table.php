<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Run Migration
    |--------------------------------------------------------------------------
    */

    public function up(): void
    {
        Schema::table(
            'purchase_order_items',
            function (Blueprint $table): void {
                $table
                    ->foreignId(
                        'raw_material_id'
                    )
                    ->nullable()
                    ->after(
                        'purchase_order_id'
                    )
                    ->constrained(
                        'raw_materials'
                    )
                    ->restrictOnDelete();

                $table->index(
                    [
                        'raw_material_id',
                        'purchase_order_id',
                    ],
                    'purchase_items_material_order_index'
                );
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Reverse Migration
    |--------------------------------------------------------------------------
    */

    public function down(): void
    {
        Schema::table(
            'purchase_order_items',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'purchase_items_material_order_index'
                );

                $table->dropConstrainedForeignId(
                    'raw_material_id'
                );
            }
        );
    }
};