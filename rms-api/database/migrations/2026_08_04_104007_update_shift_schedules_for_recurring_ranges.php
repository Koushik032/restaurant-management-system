<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Temporary Foreign Key Supporting Index
        |--------------------------------------------------------------------------
        |
        | পুরোনো composite index employee_id foreign key support করছে।
        | তাই সেটি drop করার আগে employee_id-এর জন্য আলাদা index তৈরি করছি।
        |
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->index(
                    'employee_id',
                    'shift_schedules_employee_id_temp_index'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Drop Old Indexes
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->dropIndex(
                    'shift_schedules_employee_id_shift_date_index'
                );

                $table->dropIndex(
                    'shift_schedules_shift_date_is_active_index'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Rename Old Date Column
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->renameColumn(
                    'shift_date',
                    'start_date'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Add Recurring Schedule Fields
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table
                    ->date('end_date')
                    ->nullable()
                    ->after('start_date');

                $table
                    ->json('working_days')
                    ->nullable()
                    ->after('end_date');

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Convert Existing Single-Day Schedules
        |--------------------------------------------------------------------------
        */

        DB::table('shift_schedules')
            ->select([
                'id',
                'start_date',
            ])
            ->orderBy('id')
            ->chunkById(
                100,
                function ($schedules) {

                    foreach ($schedules as $schedule) {

                        $dayName = strtolower(
                            Carbon::parse(
                                $schedule->start_date
                            )->format('l')
                        );

                        DB::table('shift_schedules')
                            ->where(
                                'id',
                                $schedule->id
                            )
                            ->update([

                                'end_date' =>
                                    $schedule->start_date,

                                'working_days' =>
                                    json_encode([
                                        $dayName,
                                    ]),

                            ]);

                    }

                }
            );


        /*
        |--------------------------------------------------------------------------
        | Add New Indexes
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->index(
                    [
                        'employee_id',
                        'start_date',
                        'end_date',
                    ],
                    'shift_schedules_employee_date_range_index'
                );

                $table->index(
                    [
                        'start_date',
                        'end_date',
                        'is_active',
                    ],
                    'shift_schedules_date_range_status_index'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Remove Temporary Index
        |--------------------------------------------------------------------------
        |
        | নতুন employee_id + start_date + end_date composite index এখন
        | foreign key support করবে।
        |
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->dropIndex(
                    'shift_schedules_employee_id_temp_index'
                );

            }
        );
    }


    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Temporary Foreign Key Supporting Index
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->index(
                    'employee_id',
                    'shift_schedules_employee_id_temp_index'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Drop New Indexes
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->dropIndex(
                    'shift_schedules_employee_date_range_index'
                );

                $table->dropIndex(
                    'shift_schedules_date_range_status_index'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Drop Recurring Fields
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->dropColumn([
                    'end_date',
                    'working_days',
                ]);

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Restore Old Date Column
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->renameColumn(
                    'start_date',
                    'shift_date'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Restore Old Indexes
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->index(
                    [
                        'employee_id',
                        'shift_date',
                    ],
                    'shift_schedules_employee_id_shift_date_index'
                );

                $table->index(
                    [
                        'shift_date',
                        'is_active',
                    ],
                    'shift_schedules_shift_date_is_active_index'
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Remove Temporary Index
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'shift_schedules',
            function (Blueprint $table) {

                $table->dropIndex(
                    'shift_schedules_employee_id_temp_index'
                );

            }
        );
    }
};