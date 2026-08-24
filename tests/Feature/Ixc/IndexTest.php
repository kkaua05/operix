<?php

use App\Livewire\Ixc\Index;
use App\Models\Company;
use App\Models\IxcServiceOrder;
use App\Services\Ixc\IxcCircuitBreaker;
use App\Services\Ixc\IxcScraperException;
use App\Services\Ixc\ScraperRunner;
use Livewire\Livewire;

beforeEach(function () {
    app(IxcCircuitBreaker::class)->reset();
});

test('a user without ixc.view is forbidden', function () {
    actingAsCompanyUser(['support']);

    Livewire::test(Index::class)->assertForbidden();
});

test('a user with ixc.view can see synced orders grouped by technician', function () {
    $user = actingAsCompanyUser(['admin']);

    IxcServiceOrder::create([
        'company_id' => $user->company_id, 'external_id' => '1', 'customer_name' => 'Cliente A',
        'technician_name' => 'GUSTAVO BEZZA', 'synced_at' => now(),
    ]);
    IxcServiceOrder::create([
        'company_id' => $user->company_id, 'external_id' => '2', 'customer_name' => 'Cliente B',
        'technician_name' => null, 'synced_at' => now(),
    ]);

    Livewire::test(Index::class)
        ->assertSee('GUSTAVO BEZZA')
        ->assertSee('Cliente A')
        ->assertSee('Sem técnico / não agendada')
        ->assertSee('Cliente B');
});

test('it never shows orders from another company', function () {
    $user = actingAsCompanyUser(['admin']);
    $otherCompany = Company::factory()->create();

    IxcServiceOrder::create([
        'company_id' => $otherCompany->id, 'external_id' => '99', 'customer_name' => 'De outra empresa', 'synced_at' => now(),
    ]);

    Livewire::test(Index::class)->assertDontSee('De outra empresa');
});

test('syncNow runs the sync and reports the result', function () {
    $user = actingAsCompanyUser(['admin']);
    config(['ixc.company_id' => $user->company_id]);

    $this->app->bind(ScraperRunner::class, fn () => new class implements ScraperRunner
    {
        public function run(): array
        {
            return ['synced_at' => now()->toIso8601String(), 'unscheduled' => [['id' => '1', 'nome' => 'Cliente']], 'scheduled' => []];
        }
    });

    Livewire::test(Index::class)
        ->call('syncNow')
        ->assertSet('syncFailed', false)
        ->assertSee('1 nova');

    expect(IxcServiceOrder::count())->toBe(1);
});

test('syncNow surfaces a scraper failure without crashing the page', function () {
    $user = actingAsCompanyUser(['admin']);
    config(['ixc.company_id' => $user->company_id]);

    $this->app->bind(ScraperRunner::class, fn () => new class implements ScraperRunner
    {
        public function run(): array
        {
            throw new IxcScraperException('login failed');
        }
    });

    Livewire::test(Index::class)
        ->call('syncNow')
        ->assertSet('syncFailed', true)
        ->assertSee('login failed');
});
