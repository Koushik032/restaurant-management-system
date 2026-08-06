<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'restaurant_tables',
            function (Blueprint $table): void {
                $table
                    ->dateTime('reservation_start_at')
                    ->nullable()
                    ->after('status');

                $table
                    ->dateTime('reservation_end_at')
                    ->nullable()
                    ->after('reservation_start_at');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'restaurant_tables',
            function (Blueprint $table): void {
                $table->dropColumn([
                    'reservation_start_at',
                    'reservation_end_at',
                ]);
            }
        );
    }
};