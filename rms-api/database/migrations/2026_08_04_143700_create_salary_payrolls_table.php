<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'salary_payrolls',
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
                | Employee Snapshot
                |--------------------------------------------------------------------------
                |
                | Employee profile পরে পরিবর্তন হলেও পুরোনো payroll-এর
                | employee information অপরিবর্তিত থাকবে।
                |
                */

                $table
                    ->string('employee_name');

                $table
                    ->string('employee_phone')
                    ->nullable();

                $table
                    ->string('employee_email')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Salary Period
                |--------------------------------------------------------------------------
                */

                $table->date('period_start');

                $table->date('period_end');

                /*
                |--------------------------------------------------------------------------
                | Salary Snapshot and Amount
                |--------------------------------------------------------------------------
                */

                $table
                    ->decimal(
                        'hourly_rate',
                        12,
                        2
                    )
                    ->default(0);

                $table
                    ->decimal(
                        'regular_salary',
                        14,
                        2
                    )
                    ->default(0);

                $table
                    ->decimal(
                        'overtime_salary',
                        14,
                        2
                    )
                    ->default(0);

                $table
                    ->decimal(
                        'adjustment_amount',
                        14,
                        2
                    )
                    ->default(0);

                $table
                    ->decimal(
                        'total_amount',
                        14,
                        2
                    )
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Payment
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'payment_status',
                        20
                    )
                    ->default('unpaid');

                $table
                    ->dateTime('paid_at')
                    ->nullable();

                $table
                    ->foreignId('paid_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Other
                |--------------------------------------------------------------------------
                */

                $table
                    ->text('notes')
                    ->nullable();

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

                $table->softDeletes();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'employee_id',
                        'period_start',
                        'period_end',
                    ],
                    'salary_payroll_employee_period_index'
                );

                $table->index(
                    [
                        'payment_status',
                        'period_start',
                        'period_end',
                    ],
                    'salary_payroll_status_period_index'
                );

            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'salary_payrolls'
        );
    }
};