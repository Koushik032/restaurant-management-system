<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'restaurant_tables',
            function (Blueprint $table): void {
                $table->id();

                $table->string(
                    'table_name',
                    100
                );

                $table->unsignedInteger(
                    'capacity'
                )->default(1);

                $table->enum(
                    'section',
                    [
                        'ac',
                        'non_ac',
                        'outdoor',
                    ]
                )->index();

                $table->enum(
                    'status',
                    [
                        'available',
                        'occupied',
                        'reserved',
                        'cleaning',
                    ]
                )
                    ->default('available')
                    ->index();

                $table->foreignId(
                    'merged_with_id'
                )
                    ->nullable()
                    ->constrained(
                        'restaurant_tables'
                    )
                    ->nullOnDelete()
                    ->cascadeOnUpdate();

                $table->text(
                    'notes'
                )->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    [
                        'table_name',
                        'deleted_at',
                    ],
                    'restaurant_tables_name_deleted_unique'
                );

                $table->index(
                    [
                        'section',
                        'status',
                    ],
                    'restaurant_tables_section_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'restaurant_tables'
        );
    }
};