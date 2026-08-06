<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

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