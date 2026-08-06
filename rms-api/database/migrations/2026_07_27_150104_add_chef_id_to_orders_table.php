<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the assigned chef reference to orders.
     */
    public function up(): void
    {
        Schema::table(
            'orders',
            function (Blueprint $table): void {
                /*
                |--------------------------------------------------------------------------
                | Assigned Chef
                |--------------------------------------------------------------------------
                |
                | যে chef order accept করবে, তার user ID এখানে থাকবে।
                | User delete হলেও historical order থাকবে এবং chef_id null হবে।
                |
                */

                $table
                    ->foreignId('chef_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Kitchen Query Index
                |--------------------------------------------------------------------------
                |
                | Assigned chef এবং kitchen status অনুযায়ী order দ্রুত filter
                | করার জন্য composite index।
                |
                */

                $table->index(
                    [
                        'chef_id',
                        'status',
                    ],
                    'orders_chef_status_index'
                );
            }
        );
    }

    /**
     * Remove the assigned chef reference.
     */
    public function down(): void
    {
        Schema::table(
            'orders',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'orders_chef_status_index'
                );

                $table->dropConstrainedForeignId(
                    'chef_id'
                );
            }
        );
    }
};