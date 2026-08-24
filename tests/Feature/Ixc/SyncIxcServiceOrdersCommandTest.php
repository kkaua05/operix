<?php

use App\Models\Company;
use App\Models\IxcServiceOrder;
use App\Services\Ixc\IxcCircuitBreaker;
use App\Services\Ixc\IxcScraperException;
use App\Services\Ixc\ScraperRunner;

beforeEach(function () {
    $this->company = Company::factory()->create();
    config(['ixc.company_id' => $this->company->id]);
    app(IxcCircuitBreaker::class)->reset();
});

test('it does nothing when ixc.enabled is false', function () {
    config(['ixc.enabled' => false]);

    $this->artisan('ixc:sync')->assertSuccessful();

    expect(IxcServiceOrder::count())->toBe(0);
});

test('it syncs when enabled', function () {
    config(['ixc.enabled' => true]);

    $this->app->bind(ScraperRunner::class, fn () => new class implements ScraperRunner
    {
        public function run(): array
        {
            return ['synced_at' => now()->toIso8601String(), 'unscheduled' => [['id' => '1', 'nome' => 'Cliente']], 'scheduled' => []];
        }
    });

    $this->artisan('ixc:sync')->assertSuccessful();

    expect(IxcServiceOrder::count())->toBe(1);
});

test('it reports failure and exits non-zero when the scraper throws', function () {
    config(['ixc.enabled' => true]);

    $this->app->bind(ScraperRunner::class, fn () => new class implements ScraperRunner
    {
        public function run(): array
        {
            throw new IxcScraperException('login failed');
        }
    });

    $this->artisan('ixc:sync')->assertFailed();
});

test('--reset-circuit clears the breaker before attempting to sync', function () {
    config(['ixc.enabled' => true, 'ixc.circuit_breaker.max_failures' => 1]);

    $breaker = app(IxcCircuitBreaker::class);
    $breaker->recordFailure();
    expect($breaker->isOpen())->toBeTrue();

    $this->app->bind(ScraperRunner::class, fn () => new class implements ScraperRunner
    {
        public function run(): array
        {
            return ['synced_at' => now()->toIso8601String(), 'unscheduled' => [], 'scheduled' => []];
        }
    });

    $this->artisan('ixc:sync --reset-circuit')->assertSuccessful();
});
