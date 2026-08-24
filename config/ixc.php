<?php

return [

    /*
    |--------------------------------------------------------------------------
    | IXC Provedor sync (no API on this account — see scripts/ixc-sync/)
    |--------------------------------------------------------------------------
    |
    | Credentials for the IXC admin panel, passed to the Node/Playwright
    | scraper as process environment variables — never written to disk by
    | the Laravel side, never logged. Leave `enabled` false until the
    | scraper has been calibrated against the real IXC instance
    | (scripts/ixc-sync/README.md).
    |
    */

    'enabled' => env('IXC_SYNC_ENABLED', false),

    'base_url' => env('IXC_BASE_URL'),
    'username' => env('IXC_USERNAME'),
    'password' => env('IXC_PASSWORD'),

    'branch_name' => env('IXC_BRANCH_NAME', 'FENIX LITORAL-'),

    'technicians' => array_filter(array_map(
        'trim',
        explode(',', (string) env('IXC_TECHNICIANS', 'GUSTAVO BEZZA (LITORAL),CEZAR GUEDES (LITORAL)'))
    )),

    // The Operix company these synced records are attached to.
    'company_id' => env('IXC_COMPANY_ID'),

    // Absolute path to the scraper's scrape.js (defaults to the bundled
    // scripts/ixc-sync/ directory) and to the `node` binary to run it with.
    'script_path' => env('IXC_SCRIPT_PATH', base_path('scripts/ixc-sync/scrape.js')),
    'node_binary' => env('IXC_NODE_BINARY', 'node'),

    // Killed if the scraper hangs (a stuck login page, a slow IXC server).
    'timeout_seconds' => env('IXC_SYNC_TIMEOUT', 90),

    /*
    |--------------------------------------------------------------------------
    | Circuit breaker
    |--------------------------------------------------------------------------
    |
    | Automating a third-party admin panel with no API carries real risk
    | on their side too — repeated failed logins can lock the account,
    | and hammering a broken selector every cycle looks like abuse to
    | whatever anti-bot protection they run. After `max_failures`
    | consecutive failures, sync pauses itself for `cooldown_minutes`
    | instead of retrying forever — a human needs to look at what broke.
    |
    */

    'circuit_breaker' => [
        'max_failures' => env('IXC_CIRCUIT_MAX_FAILURES', 3),
        'cooldown_minutes' => env('IXC_CIRCUIT_COOLDOWN_MINUTES', 30),
    ],

];
