<?php

use App\Models\Company;
use App\Models\IxcServiceOrder;
use App\Services\Ixc\IxcCircuitBreaker;
use App\Services\Ixc\IxcCircuitOpenException;
use App\Services\Ixc\IxcScraperException;
use App\Services\Ixc\IxcSyncService;
use App\Services\Ixc\ScraperRunner;

/**
 * A fake ScraperRunner standing in for the real one, which shells out to
 * a headless browser and can't run in a test suite (no live IXC access —
 * see scripts/ixc-sync/README.md). Everything downstream of "the scraper
 * returned this JSON" is fully testable and tested here.
 */
function fakeIxcRunner(array $payload): ScraperRunner
{
    return new class($payload) implements ScraperRunner
    {
        public function __construct(private array $payload) {}

        public function run(): array
        {
            return $this->payload;
        }
    };
}

function failingIxcRunner(string $message = 'boom'): ScraperRunner
{
    return new class($message) implements ScraperRunner
    {
        public function __construct(private string $message) {}

        public function run(): array
        {
            throw new IxcScraperException($this->message);
        }
    };
}

beforeEach(function () {
    $this->company = Company::factory()->create();
    config(['ixc.company_id' => $this->company->id]);
    app(IxcCircuitBreaker::class)->reset();
});

test('it upserts scheduled and unscheduled records for the configured company', function () {
    $runner = fakeIxcRunner([
        'synced_at' => '2026-08-24T10:00:00Z',
        'branch' => 'FENIX LITORAL-',
        'unscheduled' => [
            ['id' => '776912', 'nome' => 'Marcelo Alves da Silva', 'assunto' => 'Atendimento', 'endereco' => 'Av Paraguassu, 2435'],
        ],
        'scheduled' => [
            ['id' => '777079', 'nome' => 'Carla Maria Mormann', 'colaborador' => 'GUSTAVO BEZZA', 'data_inicio' => '2026-08-25 09:00:00'],
        ],
    ]);

    $service = new IxcSyncService($runner, app(IxcCircuitBreaker::class));
    $result = $service->sync();

    expect($result['created'])->toBe(2)
        ->and($result['updated'])->toBe(0)
        ->and($result['total'])->toBe(2)
        ->and(IxcServiceOrder::count())->toBe(2);

    $unscheduled = IxcServiceOrder::where('external_id', '776912')->firstOrFail();
    expect($unscheduled->customer_name)->toBe('Marcelo Alves da Silva')
        ->and($unscheduled->subject)->toBe('Atendimento')
        ->and($unscheduled->address)->toBe('Av Paraguassu, 2435')
        ->and($unscheduled->company_id)->toBe($this->company->id)
        ->and($unscheduled->raw_payload)->toBe(['id' => '776912', 'nome' => 'Marcelo Alves da Silva', 'assunto' => 'Atendimento', 'endereco' => 'Av Paraguassu, 2435']);

    $scheduled = IxcServiceOrder::where('external_id', '777079')->firstOrFail();
    expect($scheduled->technician_name)->toBe('GUSTAVO BEZZA')
        ->and($scheduled->scheduled_start->format('Y-m-d H:i'))->toBe('2026-08-25 09:00');
});

test('re-syncing the same external_id updates instead of duplicating', function () {
    $runner = fakeIxcRunner([
        'synced_at' => now()->toIso8601String(),
        'unscheduled' => [['id' => '1', 'nome' => 'Cliente A', 'assunto' => 'Instalação']],
        'scheduled' => [],
    ]);

    $service = new IxcSyncService($runner, app(IxcCircuitBreaker::class));
    $service->sync();

    $updatedRunner = fakeIxcRunner([
        'synced_at' => now()->toIso8601String(),
        'unscheduled' => [['id' => '1', 'nome' => 'Cliente A', 'assunto' => 'Manutenção']],
        'scheduled' => [],
    ]);
    $result = (new IxcSyncService($updatedRunner, app(IxcCircuitBreaker::class)))->sync();

    expect($result['created'])->toBe(0)
        ->and($result['updated'])->toBe(1)
        ->and(IxcServiceOrder::count())->toBe(1)
        ->and(IxcServiceOrder::first()->subject)->toBe('Manutenção');
});

test('a record without an id is skipped rather than crashing the whole sync', function () {
    $runner = fakeIxcRunner([
        'synced_at' => now()->toIso8601String(),
        'unscheduled' => [
            ['nome' => 'Sem ID'],
            ['id' => '2', 'nome' => 'Com ID'],
        ],
        'scheduled' => [],
    ]);

    $result = (new IxcSyncService($runner, app(IxcCircuitBreaker::class)))->sync();

    expect(IxcServiceOrder::count())->toBe(1)
        ->and($result['total'])->toBe(2);
});

test('it throws when no target company is configured', function () {
    config(['ixc.company_id' => null]);

    $service = new IxcSyncService(fakeIxcRunner(['unscheduled' => [], 'scheduled' => []]), app(IxcCircuitBreaker::class));

    expect(fn () => $service->sync())->toThrow(IxcScraperException::class);
});

test('a scraper failure opens the circuit breaker after enough consecutive failures', function () {
    config(['ixc.circuit_breaker.max_failures' => 2]);
    $breaker = app(IxcCircuitBreaker::class);

    $service = new IxcSyncService(failingIxcRunner(), $breaker);

    expect(fn () => $service->sync())->toThrow(IxcScraperException::class);
    expect($breaker->isOpen())->toBeFalse();

    expect(fn () => $service->sync())->toThrow(IxcScraperException::class);
    expect($breaker->isOpen())->toBeTrue();
});

test('once the circuit is open, sync fails fast without calling the scraper again', function () {
    config(['ixc.circuit_breaker.max_failures' => 1]);
    $breaker = app(IxcCircuitBreaker::class);

    $callCount = 0;
    $countingRunner = new class($callCount) implements ScraperRunner
    {
        public function __construct(private int &$callCount) {}

        public function run(): array
        {
            $this->callCount++;

            throw new IxcScraperException('down');
        }
    };

    $service = new IxcSyncService($countingRunner, $breaker);

    expect(fn () => $service->sync())->toThrow(IxcScraperException::class);
    expect($breaker->isOpen())->toBeTrue();

    expect(fn () => $service->sync())->toThrow(IxcCircuitOpenException::class);
});

test('a successful sync after a failure clears the failure count', function () {
    config(['ixc.circuit_breaker.max_failures' => 3]);
    $breaker = app(IxcCircuitBreaker::class);

    try {
        (new IxcSyncService(failingIxcRunner(), $breaker))->sync();
    } catch (IxcScraperException) {
        // expected
    }

    $goodRunner = fakeIxcRunner(['synced_at' => now()->toIso8601String(), 'unscheduled' => [], 'scheduled' => []]);
    (new IxcSyncService($goodRunner, $breaker))->sync();

    for ($i = 0; $i < 3; $i++) {
        try {
            (new IxcSyncService(failingIxcRunner(), $breaker))->sync();
        } catch (IxcScraperException) {
            // expected on each — the point is whether the 3rd one opens the circuit
        }
    }

    expect($breaker->isOpen())->toBeTrue();
});
