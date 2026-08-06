<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shift_schedules',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Employee
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('employee_id')
                    ->constrained('employees')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Shift Information
                |--------------------------------------------------------------------------
                */

                $table->date('shift_date');

                $table->time('start_time');

                $table->time('end_time');

                /*
                |--------------------------------------------------------------------------
                | Grace Period
                |--------------------------------------------------------------------------
                |
                | Example:
                |
                | Shift start: 09:00
                | Grace: 10 minutes
                |
                | Employee 09:10 পর্যন্ত late হিসেবে ধরা হবে না।
                |
                */

                $table
                    ->unsignedSmallInteger('grace_minutes')
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Schedule Status
                |--------------------------------------------------------------------------
                */

                $table
                    ->boolean('is_active')
                    ->default(true);

                $table
                    ->text('notes')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Audit Information
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table
                    ->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Soft Delete
                |--------------------------------------------------------------------------
                |
                | Schedule delete হলেও history database-এ থাকবে।
                |
                */

                $table->softDeletes();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([
                    'employee_id',
                    'shift_date',
                ]);

                $table->index([
                    'shift_date',
                    'is_active',
                ]);

            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'shift_schedules'
        );
    }
};