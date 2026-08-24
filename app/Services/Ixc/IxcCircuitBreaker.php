<?php

namespace App\Services\Ixc;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Stops ixc:sync from retrying forever against a broken selector or a
 * login that's started failing — both look like abuse to whatever
 * anti-bot protection IXC runs, and a login that fails repeatedly risks
 * locking the account outright. After config('ixc.circuit_breaker.max_failures')
 * consecutive failures, sync pauses itself for the configured cooldown
 * instead of hammering IXC every cycle; a human needs to look at what broke.
 */
class IxcCircuitBreaker
{
    protected const FAILURES_KEY = 'ixc:circuit:failures';

    protected const OPEN_UNTIL_KEY = 'ixc:circuit:open_until';

    public function isOpen(): bool
    {
        $openUntil = $this->openUntil();

        return $openUntil !== null && now()->lt($openUntil);
    }

    public function openUntil(): ?Carbon
    {
        $value = Cache::get(self::OPEN_UNTIL_KEY);

        return $value ? Carbon::parse($value) : null;
    }

    public function recordSuccess(): void
    {
        Cache::forget(self::FAILURES_KEY);
        Cache::forget(self::OPEN_UNTIL_KEY);
    }

    /**
     * @return int the consecutive failure count after recording this one
     */
    public function recordFailure(): int
    {
        $failures = (int) Cache::get(self::FAILURES_KEY, 0) + 1;
        Cache::put(self::FAILURES_KEY, $failures, now()->addDay());

        if ($failures >= (int) config('ixc.circuit_breaker.max_failures')) {
            $cooldown = (int) config('ixc.circuit_breaker.cooldown_minutes');
            Cache::put(self::OPEN_UNTIL_KEY, now()->addMinutes($cooldown), now()->addDay());
        }

        return $failures;
    }

    /**
     * Manual override for a human who just fixed the selectors and wants
     * the next scheduled run to try again immediately.
     */
    public function reset(): void
    {
        $this->recordSuccess();
    }
}
