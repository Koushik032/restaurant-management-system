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
            'expenses',
            function (
                Blueprint $table
            ): void {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Expense Category
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'expense_category_id'
                )
                    ->constrained(
                        'expense_categories'
                    )
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();

                /*
                |--------------------------------------------------------------------------
                | Expense Information
                |--------------------------------------------------------------------------
                |
                | dateTime ব্যবহার করা হচ্ছে, যাতে expense date-এর সঙ্গে
                | entry time-ও সংরক্ষণ করা যায়।
                |
                */

                $table->dateTime(
                    'expense_date'
                )->index();

                $table->decimal(
                    'amount',
                    14,
                    2
                )->default(0);

                /*
                |--------------------------------------------------------------------------
                | Payment Method
                |--------------------------------------------------------------------------
                |
                | Supported initial values:
                |
                | cash
                | card
                | bkash
                | nagad
                | bank_transfer
                | mixed
                | other
                |
                | enum না রেখে string ব্যবহার করা হয়েছে, যাতে ভবিষ্যতে নতুন
                | payment method যোগ করতে migration change না লাগে।
                |
                */

                $table->string(
                    'payment_method',
                    50
                )->index();

                /*
                |--------------------------------------------------------------------------
                | Notes
                |--------------------------------------------------------------------------
                */

                $table->text(
                    'notes'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Created By / Paid By
                |--------------------------------------------------------------------------
                |
                | যে authenticated user expense entry তৈরি করবে, তার user ID
                | created_by field-এ থাকবে। UI-তে এই relation-এর user name
                | "Paid By" হিসেবে দেখানো হবে।
                |
                */

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete()
                    ->cascadeOnUpdate();

                /*
                |--------------------------------------------------------------------------
                | Updated By
                |--------------------------------------------------------------------------
                |
                | Expense edit করার সময় সর্বশেষ editor-এর user ID থাকবে।
                |
                */

                $table->foreignId(
                    'updated_by'
                )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete()
                    ->cascadeOnUpdate();

                /*
                |--------------------------------------------------------------------------
                | Timestamps and Soft Delete
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                $table->softDeletes();

                /*
                |--------------------------------------------------------------------------
                | Combined Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([
                    'expense_date',
                    'expense_category_id',
                ]);

                $table->index([
                    'expense_date',
                    'payment_method',
                ]);

                $table->index([
                    'created_by',
                    'expense_date',
                ]);
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
            'expenses'
        );
    }
};