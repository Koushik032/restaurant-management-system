<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'salary_details',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Payroll
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('salary_payroll_id')
                    ->constrained('salary_payrolls')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Attendance
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('attendance_id')
                    ->nullable()
                    ->constrained('attendances')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();

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

                $table->date('salary_date');

                /*
                |--------------------------------------------------------------------------
                | Attendance Snapshot
                |--------------------------------------------------------------------------
                */

                $table
                    ->unsignedInteger('scheduled_minutes')
                    ->default(0);

                $table
                    ->unsignedInteger('worked_minutes')
                    ->default(0);

                $table
                    ->unsignedInteger('late_minutes')
                    ->default(0);

                $table
                    ->unsignedInteger('break_minutes')
                    ->default(0);

                $table
                    ->unsignedInteger('overtime_minutes')
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Salary Information
                |--------------------------------------------------------------------------
                */

                $table
                    ->decimal(
                        'hourly_rate',
                        12,
                        2
                    )
                    ->default(0);

                /*
                | full_salary
                | half_salary
                | full_salary_overtime
                | half_salary_overtime
                | overtime_only
                | no_salary
                */

                $table
                    ->string(
                        'salary_type',
                        50
                    )
                    ->default('no_salary');

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
                        'total_amount',
                        14,
                        2
                    )
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Calculation Source
                |--------------------------------------------------------------------------
                |
                | automatic
                | manual
                |
                */

                $table
                    ->string(
                        'calculation_source',
                        20
                    )
                    ->default('automatic');

                $table
                    ->text('notes')
                    ->nullable();

                $table
                    ->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'salary_payroll_id',
                        'salary_date',
                    ],
                    'salary_detail_payroll_date_index'
                );

                $table->index(
                    [
                        'employee_id',
                        'salary_date',
                    ],
                    'salary_detail_employee_date_index'
                );

                $table->index(
                    [
                        'salary_type',
                        'salary_date',
                    ],
                    'salary_detail_type_date_index'
                );

            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'salary_details'
        );
    }
};