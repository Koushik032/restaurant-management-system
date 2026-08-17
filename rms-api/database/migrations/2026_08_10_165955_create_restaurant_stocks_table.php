<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'restaurant_stocks',
            function (Blueprint $table): void {

                $table->id();

                $table->foreignId(
                    'raw_material_id'
                )
                    ->constrained(
                        'raw_materials'
                    )
                    ->restrictOnDelete();

                $table->decimal(
                    'quantity',
                    14,
                    4
                )->default(0);

                $table->decimal(
                    'average_unit_cost',
                    14,
                    4
                )->default(0);

                $table->timestamp(
                    'last_received_at'
                )->nullable();

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
                $table->softDeletes();

                $table->unique(
                    'raw_material_id',
                    'restaurant_stocks_raw_material_unique'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'restaurant_stocks'
        );
    }
};