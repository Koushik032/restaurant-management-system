<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'purchase_order_payments',
            function (Blueprint $table) {

                /*
                |--------------------------------------------------------------------------
                | Increase Payment Amount Precision
                |--------------------------------------------------------------------------
                |
                | Previous: decimal(12, 2)
                | New:      decimal(14, 2)
                |
                */

                $table->decimal(
                    'amount',
                    14,
                    2
                )->change();


                /*
                |--------------------------------------------------------------------------
                | Payment History Index
                |--------------------------------------------------------------------------
                */

                $table->index([
                    'purchase_order_id',
                    'payment_date',
                ]);
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'purchase_order_payments',
            function (Blueprint $table) {

                $table->dropIndex([
                    'purchase_order_id',
                    'payment_date',
                ]);


                $table->decimal(
                    'amount',
                    12,
                    2
                )->change();
            }
        );
    }
};