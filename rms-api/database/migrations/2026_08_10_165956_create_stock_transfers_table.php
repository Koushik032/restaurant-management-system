<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'stock_transfers',
            function (Blueprint $table): void {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Transfer Number
                |--------------------------------------------------------------------------
                |
                | Service will generate a readable number after create:
                |
                | TRF-20260810-000001
                |
                */

                $table->string(
                    'transfer_no',
                    80
                )
                    ->nullable()
                    ->unique();

                $table->timestamp(
                    'transferred_at'
                );

                $table->text(
                    'notes'
                )->nullable();

                $table->foreignId(
                    'transferred_by'
                )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();

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

                $table->index(
                    'transferred_at'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'stock_transfers'
        );
    }
};