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
            'warehouse_stocks',
            function (Blueprint $table): void {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Raw Material
                |--------------------------------------------------------------------------
                |
                | একটি raw material-এর একটি warehouse stock row থাকবে।
                |
                */

                $table
                    ->foreignId(
                        'raw_material_id'
                    )
                    ->constrained(
                        'raw_materials'
                    )
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Stock Quantity
                |--------------------------------------------------------------------------
                |
                | unsigned থাকার কারণে database negative quantity গ্রহণ করবে না।
                |
                */

                $table
                    ->decimal(
                        'quantity',
                        18,
                        4
                    )
                    ->unsigned()
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Cost Information
                |--------------------------------------------------------------------------
                */

                $table
                    ->decimal(
                        'average_unit_cost',
                        18,
                        4
                    )
                    ->unsigned()
                    ->default(0);

                $table
                    ->timestamp(
                        'last_received_at'
                    )
                    ->nullable();

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
                | Constraints
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    'raw_material_id',
                    'warehouse_stocks_raw_material_unique'
                );

                $table->index([
                    'quantity',
                    'last_received_at',
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
            'warehouse_stocks'
        );
    }
};