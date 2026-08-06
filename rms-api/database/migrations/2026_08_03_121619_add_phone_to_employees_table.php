<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'employees',
            function (Blueprint $table): void {

                $table->string(
                    'phone',
                    30
                )
                    ->nullable()
                    ->after('user_id')
                    ->index();

            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'employees',
            function (Blueprint $table): void {

                $table->dropIndex([
                    'phone',
                ]);

                $table->dropColumn(
                    'phone'
                );

            }
        );
    }
};