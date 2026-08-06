<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Run Migration
    |--------------------------------------------------------------------------
    */

    public function up(): void
    {
        Schema::create(
            'expense_categories',
            function (
                Blueprint $table
            ): void {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Category Information
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'name',
                    150
                )->unique();

                $table->text(
                    'description'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'is_active'
                )
                    ->default(true)
                    ->index();

                /*
                |--------------------------------------------------------------------------
                | Timestamps
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                $table->softDeletes();
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reverse Migration
    |--------------------------------------------------------------------------
    */

    public function down(): void
    {
        Schema::dropIfExists(
            'expense_categories'
        );
    }
};