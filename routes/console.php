<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sla:check')->everyFiveMinutes();
Schedule::command('stock:critical-digest')->dailyAt('08:00');
Schedule::command('ratings:remind-pending')->dailyAt('09:00');
