<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_tables', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('restaurant_table_id')
                ->constrained('restaurant_tables')
                ->cascadeOnDelete();

            /*
             * Main selected table হলে true।
             * Merged tables হলে false।
             */
            $table->boolean('is_primary')
                ->default(false);

            $table->timestamps();

            $table->unique([
                'order_id',
                'restaurant_table_id',
            ]);

            $table->index([
                'restaurant_table_id',
                'order_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_tables');
    }
};