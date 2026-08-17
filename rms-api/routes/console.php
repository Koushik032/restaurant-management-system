<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command(
    'salary:auto-calculate'
)
    ->dailyAt('22:00')
    ->timezone('Asia/Dhaka')
    ->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Automatic Attendance Sync
|--------------------------------------------------------------------------
*/

Schedule::command(
    'attendance:sync'
)
    ->everyMinute()
    ->withoutOverlapping();