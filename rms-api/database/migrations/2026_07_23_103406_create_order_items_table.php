<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('menu_item_id')
                ->nullable()
                ->constrained('menu_items')
                ->nullOnDelete();

            $table->foreignId('menu_item_variant_id')
                ->nullable()
                ->constrained('menu_item_variants')
                ->nullOnDelete();

            /*
             * Menu item পরে edit/delete হলেও order history ঠিক থাকবে।
             */
            $table->string('item_name', 180);

            $table->string('variant_name', 150)
                ->nullable();

            $table->decimal('unit_price', 15, 2)
                ->default(0);

            $table->unsignedInteger('quantity')
                ->default(1);

            $table->decimal('addon_total', 15, 2)
                ->default(0);

            $table->decimal('line_total', 15, 2)
                ->default(0);

            $table->string('status', 30)
                ->default('pending');

            $table->text('kitchen_note')
                ->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index([
                'order_id',
                'menu_item_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};