<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('menu_category_id')
                ->constrained('menu_categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('menu_name', 180);

            $table->enum('item_type', [
                'regular',
                'combo',
                'set_meal',
            ])->default('regular');

            $table->decimal('price', 12, 2)
                ->default(0);

            $table->text('ingredients')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->string('image_path')
                ->nullable();

            $table->unsignedInteger('preparation_time')
                ->nullable()
                ->comment('Preparation time in minutes');

            $table->boolean('is_available')
                ->default(true)
                ->index();

            $table->boolean('is_featured')
                ->default(false)
                ->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'menu_category_id',
                'is_available',
            ]);

            $table->index([
                'item_type',
                'is_available',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};