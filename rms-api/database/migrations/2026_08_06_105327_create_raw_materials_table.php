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
        Schema::create(
            'raw_materials',
            function (Blueprint $table): void {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Material Information
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'material_name',
                        180
                    )
                    ->index();

                $table
                    ->string(
                        'category',
                        100
                    )
                    ->nullable()
                    ->index();

                /*
                |--------------------------------------------------------------------------
                | Base Unit
                |--------------------------------------------------------------------------
                |
                | Examples:
                | kg, gram, litre, ml, pcs, packet, bottle
                |
                */

                $table
                    ->string(
                        'base_unit',
                        30
                    );

                /*
                |--------------------------------------------------------------------------
                | Low Stock Limits
                |--------------------------------------------------------------------------
                */

                $table
                    ->decimal(
                        'warehouse_minimum_quantity',
                        18,
                        4
                    )
                    ->unsigned()
                    ->default(0);

                $table
                    ->decimal(
                        'restaurant_minimum_quantity',
                        18,
                        4
                    )
                    ->unsigned()
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                $table
                    ->boolean(
                        'is_active'
                    )
                    ->default(true)
                    ->index();

                /*
                |--------------------------------------------------------------------------
                | Audit Users
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId(
                        'created_by'
                    )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();

                $table
                    ->foreignId(
                        'updated_by'
                    )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Timestamps and Soft Delete
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                $table->softDeletes();

                /*
                |--------------------------------------------------------------------------
                | Search Index
                |--------------------------------------------------------------------------
                */

                $table->index([
                    'material_name',
                    'base_unit',
                    'is_active',
                ]);
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
        Schema::dropIfExists(
            'raw_materials'
        );
    }
};