<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_variants', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('menu_item_id')
                ->constrained('menu_items')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->string('variant_name', 120);

            $table->decimal('price', 12, 2)
                ->default(0);

            $table->boolean('is_available')
                ->default(true)
                ->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'menu_item_id',
                'is_available',
            ]);

            $table->unique(
                [
                    'menu_item_id',
                    'variant_name',
                ],
                'menu_item_variant_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_variants');
    }
};