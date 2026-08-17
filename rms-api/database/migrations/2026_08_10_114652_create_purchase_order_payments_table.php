<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {
        Schema::create(
            'purchase_order_payments',
            function (Blueprint $table) {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Purchase Order Relation
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'purchase_order_id'
                )
                ->constrained()
                ->cascadeOnDelete();



                /*
                |--------------------------------------------------------------------------
                | Payment Information
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'payment_date'
                );


                $table->decimal(
                    'amount',
                    12,
                    2
                );


                $table->string(
                    'payment_method',
                    50
                )
                ->nullable();


                $table->string(
                    'transaction_reference'
                )
                ->nullable();


                $table->text(
                    'notes'
                )
                ->nullable();



                /*
                |--------------------------------------------------------------------------
                | Audit
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'created_by'
                )
                ->nullable()
                ->constrained(
                    'users'
                )
                ->nullOnDelete();


                $table->foreignId(
                    'updated_by'
                )
                ->nullable()
                ->constrained(
                    'users'
                )
                ->nullOnDelete();



                $table->timestamps();


            }
        );
    }



    public function down(): void
    {
        Schema::dropIfExists(
            'purchase_order_payments'
        );
    }

};