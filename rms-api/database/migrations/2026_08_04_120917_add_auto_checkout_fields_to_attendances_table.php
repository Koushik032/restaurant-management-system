<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'attendances',
            function (Blueprint $table) {

                $table
                    ->boolean('auto_checked_out')
                    ->default(false)
                    ->after('check_out_at');

                $table
                    ->string(
                        'auto_checkout_reason',
                        255
                    )
                    ->nullable()
                    ->after('auto_checked_out');

            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'attendances',
            function (Blueprint $table) {

                $table->dropColumn([

                    'auto_checked_out',

                    'auto_checkout_reason',

                ]);

            }
        );
    }
};