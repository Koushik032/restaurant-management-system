<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('menu_item_add_on', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('menu_item_id')
                ->constrained('menu_items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('add_on_id')
                ->constrained('add_ons')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['menu_item_id', 'add_on_id'],
                'menu_item_add_on_unique'
            );

            $table->index('menu_item_id');
            $table->index('add_on_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_item_add_on');
    }
};