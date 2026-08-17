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
            'stock_movements',
            function (Blueprint $table): void {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Raw Material
                |--------------------------------------------------------------------------
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
                | Stock Location
                |--------------------------------------------------------------------------
                |
                | Phase 9A:
                | warehouse
                |
                | Future:
                | restaurant
                |
                */

                $table
                    ->string(
                        'location',
                        30
                    )
                    ->index();

                /*
                |--------------------------------------------------------------------------
                | Movement Type
                |--------------------------------------------------------------------------
                |
                | Initial supported types:
                |
                | opening_stock
                | purchase_receive
                | warehouse_adjustment_in
                | warehouse_adjustment_out
                |
                | Future types:
                |
                | transfer_out
                | transfer_in
                | restaurant_adjustment_in
                | restaurant_adjustment_out
                |
                */

                $table
                    ->string(
                        'movement_type',
                        50
                    )
                    ->index();

                /*
                |--------------------------------------------------------------------------
                | Quantity Snapshot
                |--------------------------------------------------------------------------
                |
                | quantity সবসময় positive absolute amount হবে।
                | movement_type দ্বারা বোঝা যাবে stock বেড়েছে না কমেছে।
                |
                */

                $table
                    ->decimal(
                        'quantity',
                        18,
                        4
                    )
                    ->unsigned();

                $table
                    ->decimal(
                        'quantity_before',
                        18,
                        4
                    )
                    ->unsigned()
                    ->default(0);

                $table
                    ->decimal(
                        'quantity_after',
                        18,
                        4
                    )
                    ->unsigned()
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Cost Snapshot
                |--------------------------------------------------------------------------
                */

                $table
                    ->decimal(
                        'unit_cost',
                        18,
                        4
                    )
                    ->unsigned()
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Reference
                |--------------------------------------------------------------------------
                |
                | Purchase receive বা transfer-এর source record identify করবে।
                |
                */

                $table
                    ->string(
                        'reference_type',
                        100
                    )
                    ->nullable()
                    ->index();

                $table
                    ->unsignedBigInteger(
                        'reference_id'
                    )
                    ->nullable()
                    ->index();

                /*
                |--------------------------------------------------------------------------
                | Additional Information
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'unit',
                        30
                    );

                $table
                    ->text(
                        'notes'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Audit User
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

                /*
                |--------------------------------------------------------------------------
                | Timestamps
                |--------------------------------------------------------------------------
                |
                | Stock movement history soft delete হবে না।
                |
                */

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Reporting Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'raw_material_id',
                        'location',
                        'movement_type',
                    ],
                    'stock_movements_material_location_type_index'
                );

                $table->index(
                    [
                        'reference_type',
                        'reference_id',
                    ],
                    'stock_movements_reference_index'
                );

                $table->index(
                    [
                        'raw_material_id',
                        'created_at',
                    ],
                    'stock_movements_material_date_index'
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
        Schema::dropIfExists(
            'stock_movements'
        );
    }
};