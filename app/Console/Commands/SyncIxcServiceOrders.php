<?php

namespace App\Console\Commands;

use App\Services\Ixc\IxcCircuitBreaker;
use App\Services\Ixc\IxcCircuitOpenException;
use App\Services\Ixc\IxcScraperException;
use App\Services\Ixc\IxcSyncService;
use Illuminate\Console\Command;

class SyncIxcServiceOrders extends Command
{
    protected $signature = 'ixc:sync {--reset-circuit : Clear the circuit breaker and try again immediately, even if it was open}';

    protected $description = 'Sync service orders from the IXC Provedor admin panel (no API — see scripts/ixc-sync/README.md)';

    public function handle(IxcSyncService $service, IxcCircuitBreaker $circuitBreaker): int
    {
        if ($this->option('reset-circuit')) {
            $circuitBreaker->reset();
            $this->comment('Circuit breaker reset.');
        }

        if (! config('ixc.enabled')) {
            $this->comment('ixc.enabled is false — skipping (set IXC_SYNC_ENABLED=true once the scraper is calibrated).');

            return self::SUCCESS;
        }

        try {
            $result = $service->sync();
        } catch (IxcCircuitOpenException $e) {
            $this->warn($e->getMessage());

            return self::FAILURE;
        } catch (IxcScraperException $e) {
            $this->error('IXC sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("IXC sync complete: {$result['created']} created, {$result['updated']} updated, {$result['total']} total.");

        return self::SUCCESS;
    }
}
