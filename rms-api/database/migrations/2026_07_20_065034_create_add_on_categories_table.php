<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('add_on_categories', function (Blueprint $table): void {
            $table->id();

            $table->string('category_name', 150)
                ->unique();

            $table->text('description')
                ->nullable();

            $table->boolean('is_available')
                ->default(true)
                ->index();

            $table->unsignedInteger('display_order')
                ->default(0)
                ->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('add_on_categories');
    }
};