<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('add_ons', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('add_on_category_id')
                ->constrained('add_on_categories')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('add_on_name', 150);

            $table->decimal('price', 12, 2)
                ->default(0);

            $table->text('description')
                ->nullable();

            $table->boolean('is_available')
                ->default(true)
                ->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'add_on_category_id',
                'is_available',
            ]);

            $table->unique(
                [
                    'add_on_category_id',
                    'add_on_name',
                ],
                'add_on_category_name_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('add_ons');
    }
};