<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;

/**
 * Extends the default /up health check (bootstrap/app.php) to also verify
 * the database is reachable — the default check only confirms the PHP
 * process booted, which stays green even during a full DB outage. A
 * monitoring tool polling /up should catch that within a minute, not
 * only once a real user hits an error page.
 */
class CheckDatabaseHealth
{
    public function handle(DiagnosingHealth $event): void
    {
        DB::connection()->getPdo();
    }
}
