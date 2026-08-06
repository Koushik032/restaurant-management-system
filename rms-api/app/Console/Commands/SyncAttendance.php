<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Illuminate\Console\Command;

class SyncAttendance extends Command
{
    protected $signature =
        'attendance:sync
        {--date= : Attendance date in YYYY-MM-DD format}';


    protected $description =
        'Generate, reconcile and synchronize attendance from staff schedules.';


    public function handle(
        AttendanceService $attendanceService
    ): int {

        $date =
            $this->option('date')
            ??
            now()->format('Y-m-d');


        $result =
            $attendanceService
                ->syncForDate(
                    $date
                );


        if (
            $result['future_date_skipped']
            ??
            false
        ) {

            $this->warn(
                'Future attendance was not generated.'
            );

            return self::SUCCESS;
        }


        $this->info(
            'Attendance synchronization completed.'
        );


        $this->table(
            [
                'Date',
                'Created',
                'Updated',
                'Removed',
                'Auto Checkout',
                'Status Updates',
                'Conflicts',
            ],
            [
                [
                    $result['date'],
                    $result['created'],
                    $result['updated'],
                    $result['removed'],
                    $result['auto_checked_out'],
                    $result['employee_status_updates'],
                    $result['conflicts'],
                ],
            ]
        );


        return self::SUCCESS;
    }
}