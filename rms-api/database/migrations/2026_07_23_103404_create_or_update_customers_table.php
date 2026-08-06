<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Create customers table if it does not exist
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasTable('customers')) {
            Schema::create('customers', function (Blueprint $table): void {
                $table->id();

                $table->string('name', 150);
                $table->string('phone', 30)->unique();
                $table->string('email', 150)->nullable()->unique();

                $table->dateTime('last_visit_at')->nullable();

                $table->unsignedInteger('total_orders')
                    ->default(0);

                $table->decimal('total_spent', 15, 2)
                    ->default(0);

                $table->boolean('is_active')
                    ->default(true);

                $table->text('notes')
                    ->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index('name');
                $table->index('phone');
                $table->index('email');
                $table->index('last_visit_at');
            });

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Add missing columns if customers table already exists
        |--------------------------------------------------------------------------
        */

        Schema::table('customers', function (Blueprint $table): void {
            if (! Schema::hasColumn('customers', 'name')) {
                $table->string('name', 150);
            }

            if (! Schema::hasColumn('customers', 'phone')) {
                $table->string('phone', 30)
                    ->nullable()
                    ->index();
            }

            if (! Schema::hasColumn('customers', 'email')) {
                $table->string('email', 150)
                    ->nullable()
                    ->index();
            }

            if (! Schema::hasColumn('customers', 'last_visit_at')) {
                $table->dateTime('last_visit_at')
                    ->nullable()
                    ->index();
            }

            if (! Schema::hasColumn('customers', 'total_orders')) {
                $table->unsignedInteger('total_orders')
                    ->default(0);
            }

            if (! Schema::hasColumn('customers', 'total_spent')) {
                $table->decimal('total_spent', 15, 2)
                    ->default(0);
            }

            if (! Schema::hasColumn('customers', 'is_active')) {
                $table->boolean('is_active')
                    ->default(true);
            }

            if (! Schema::hasColumn('customers', 'notes')) {
                $table->text('notes')
                    ->nullable();
            }

            if (! Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        /*
         * Existing customer data নিরাপদ রাখার জন্য এখানে
         * customers table drop করা হচ্ছে না।
         */
    }
};