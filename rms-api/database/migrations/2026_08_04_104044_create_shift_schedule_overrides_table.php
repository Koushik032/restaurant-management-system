<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'shift_schedule_overrides',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Parent Recurring Schedule
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('shift_schedule_id')
                    ->constrained('shift_schedules')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Override Date
                |--------------------------------------------------------------------------
                */

                $table->date('override_date');

                /*
                |--------------------------------------------------------------------------
                | Override Type
                |--------------------------------------------------------------------------
                |
                | modified:
                | ওই দিনের start/end time পরিবর্তন হবে।
                |
                | day_off:
                | ওই দিনের shift বাতিল থাকবে।
                |
                */

                $table
                    ->string(
                        'override_type',
                        30
                    )
                    ->default('modified');

                /*
                |--------------------------------------------------------------------------
                | Modified Time
                |--------------------------------------------------------------------------
                |
                | day_off হলে এগুলো null থাকবে।
                |
                */

                $table
                    ->time('start_time')
                    ->nullable();

                $table
                    ->time('end_time')
                    ->nullable();

                $table
                    ->unsignedSmallInteger(
                        'grace_minutes'
                    )
                    ->nullable();

                $table
                    ->text('notes')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Audit
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

                $table->softDeletes();

                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index([
                    'shift_schedule_id',
                    'override_date',
                ]);

                $table->index([
                    'override_date',
                    'override_type',
                ]);

            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'shift_schedule_overrides'
        );
    }
};