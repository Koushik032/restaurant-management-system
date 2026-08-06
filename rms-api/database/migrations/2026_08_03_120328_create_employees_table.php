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
            'employees',
            function (Blueprint $table): void {

                $table->id();


                /*
                |--------------------------------------------------------------------------
                | User Account Relation
                |--------------------------------------------------------------------------
                |
                | Employee-এর name, email, phone, username, password ও role
                | users table-এ থাকবে।
                |
                | একজন user-এর একটি employee profile থাকবে।
                |
                */

                $table->foreignId(
                    'user_id'
                )
                    ->unique()
                    ->constrained(
                        'users'
                    )
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();


                /*
                |--------------------------------------------------------------------------
                | Employment Information
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'joining_date'
                );


                /*
                |--------------------------------------------------------------------------
                | Hourly Rate
                |--------------------------------------------------------------------------
                |
                | Future Payroll module-এ:
                |
                | Salary =
                | Payable Hours × Hourly Rate
                |
                */

                $table->decimal(
                    'hourly_rate',
                    12,
                    2
                )->default(0);


                /*
                |--------------------------------------------------------------------------
                | Current Working Status
                |--------------------------------------------------------------------------
                |
                | Supported values:
                |
                | none
                | present
                | break
                | absent
                | leave
                |
                | enum ব্যবহার করা হয়নি, যাতে ভবিষ্যতে নতুন status যোগ করা সহজ হয়।
                |
                */

                $table->string(
                    'current_status',
                    30
                )
                    ->default(
                        'none'
                    )
                    ->index();


                /*
                |--------------------------------------------------------------------------
                | Status Updated Time
                |--------------------------------------------------------------------------
                |
                | Employee-এর বর্তমান status সর্বশেষ কখন পরিবর্তন হয়েছে।
                |
                | Attendance module তৈরি হলে check-in, break এবং checkout
                | tracking-এর কাজে এটি ব্যবহার করা যাবে।
                |
                */

                $table->dateTime(
                    'status_updated_at'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Created By
                |--------------------------------------------------------------------------
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
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([
                    'joining_date',
                    'current_status',
                ]);

                $table->index([
                    'current_status',
                    'status_updated_at',
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
            'employees'
        );
    }
};