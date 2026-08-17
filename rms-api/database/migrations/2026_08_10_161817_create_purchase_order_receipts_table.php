<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'purchase_order_receipts',
            function (Blueprint $table): void {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | Purchase Order
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'purchase_order_id'
                )
                    ->constrained(
                        'purchase_orders'
                    )
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Receipt / GRN Number
                |--------------------------------------------------------------------------
                |
                | Receipt create হওয়ার পরে ID ব্যবহার করে:
                |
                | GRN-20260810-000001
                |
                | format generate করব।
                |
                */

                $table->string(
                    'receipt_no',
                    80
                )
                    ->nullable()
                    ->unique();


                /*
                |--------------------------------------------------------------------------
                | Receive Information
                |--------------------------------------------------------------------------
                */

                $table->timestamp(
                    'received_at'
                );


                $table->text(
                    'notes'
                )
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Received By
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'received_by'
                )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();


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


                /*
                |--------------------------------------------------------------------------
                | Index
                |--------------------------------------------------------------------------
                */

                $table->index([
                    'purchase_order_id',
                    'received_at',
                ]);
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'purchase_order_receipts'
        );
    }
};