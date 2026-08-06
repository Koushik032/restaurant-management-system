<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identification
            |--------------------------------------------------------------------------
            */

            $table->string('order_number', 50)
                ->unique();

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();

            /*
             * Order-এর সময়কার customer information snapshot।
             * পরে customer profile পরিবর্তন হলেও পুরোনো invoice ঠিক থাকবে।
             */
            $table->string('customer_name', 150)
                ->nullable();

            $table->string('customer_phone', 30)
                ->nullable();

            $table->string('customer_email', 150)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Primary table
            |--------------------------------------------------------------------------
            */

            $table->foreignId('restaurant_table_id')
                ->nullable()
                ->constrained('restaurant_tables')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Order status
            |--------------------------------------------------------------------------
            */

            $table->string('status', 30)
                ->default('pending');

            /*
            |--------------------------------------------------------------------------
            | Amounts
            |--------------------------------------------------------------------------
            */

            $table->decimal('subtotal', 15, 2)
                ->default(0);

            $table->decimal('discount_amount', 15, 2)
                ->default(0);

            $table->decimal('tax_amount', 15, 2)
                ->default(0);

            $table->decimal('service_charge', 15, 2)
                ->default(0);

            $table->decimal('total_amount', 15, 2)
                ->default(0);

            $table->decimal('paid_amount', 15, 2)
                ->default(0);

            $table->decimal('due_amount', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $table->string('payment_status', 30)
                ->default('due');

            $table->string('payment_method', 30)
                ->nullable();

            /*
             * Mixed payment হলে breakdown রাখা যাবে:
             *
             * {
             *   "cash": 500,
             *   "bkash": 750
             * }
             */
            $table->json('payment_breakdown')
                ->nullable();

            $table->string('payment_reference', 150)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->text('order_note')
                ->nullable();

            $table->text('kitchen_note')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Important timestamps
            |--------------------------------------------------------------------------
            */

            $table->dateTime('sent_to_kitchen_at')
                ->nullable();

            $table->dateTime('preparing_at')
                ->nullable();

            $table->dateTime('ready_at')
                ->nullable();

            $table->dateTime('served_at')
                ->nullable();

            $table->dateTime('completed_at')
                ->nullable();

            $table->dateTime('canceled_at')
                ->nullable();

            $table->text('cancellation_reason')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Customer spending protection
            |--------------------------------------------------------------------------
            |
            | একই order বারবার complete API-তে পাঠানো হলেও customer total_spent
            | যেন দ্বিতীয়বার না বাড়ে।
            |
            */

            $table->boolean('is_customer_spend_recorded')
                ->default(false);

            $table->dateTime('customer_spend_recorded_at')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Created by
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('status');
            $table->index('payment_status');
            $table->index('payment_method');
            $table->index('created_at');

            $table->index([
                'restaurant_table_id',
                'status',
            ]);

            $table->index([
                'customer_id',
                'completed_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};