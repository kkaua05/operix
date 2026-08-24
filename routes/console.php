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

// No-op while ixc.enabled is false (config/ixc.php) — the command checks
// that itself and exits early, so it's safe to leave scheduled.
//
// Every 10 minutes, not every 1-2: this logs into a third-party admin
// panel with no API, and IXC has no idea this traffic is automated.
// A tighter interval reads as bot-like to whatever anti-abuse protection
// they run, and risks kicking out a human dispatcher's real session if
// the scraper shares their login — use a dedicated IXC user for this if
// at all possible (see scripts/ixc-sync/README.md). The circuit breaker
// (config('ixc.circuit_breaker')) additionally pauses sync entirely
// after repeated failures instead of retrying every cycle.
Schedule::command('ixc:sync')->everyTenMinutes()->withoutOverlapping();
