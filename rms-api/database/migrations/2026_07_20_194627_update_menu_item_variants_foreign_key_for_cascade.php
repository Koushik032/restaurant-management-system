<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'menu_item_variants',
            function (
                Blueprint $table
            ): void {
                $table->dropForeign([
                    'menu_item_id',
                ]);

                $table
                    ->foreign('menu_item_id')
                    ->references('id')
                    ->on('menu_items')
                    ->cascadeOnDelete();
            },
        );
    }

    public function down(): void
    {
        Schema::table(
            'menu_item_variants',
            function (
                Blueprint $table
            ): void {
                $table->dropForeign([
                    'menu_item_id',
                ]);

                $table
                    ->foreign('menu_item_id')
                    ->references('id')
                    ->on('menu_items')
                    ->restrictOnDelete();
            },
        );
    }
};