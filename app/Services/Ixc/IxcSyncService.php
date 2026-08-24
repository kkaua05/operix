<?php

namespace App\Services\Ixc;

use App\Models\IxcServiceOrder;
use Illuminate\Support\Carbon;

/**
 * Upserts the scraper's payload into ixc_service_orders. The exact field
 * names IXC's internal responses use weren't verified against the live
 * system (see scripts/ixc-sync/README.md) — mapExternalRecord() tries a
 * handful of likely Portuguese/English key names defensively and keeps
 * the untouched record in raw_payload either way, so nothing is lost even
 * when a field isn't recognized yet.
 */
class IxcSyncService
{
    public function __construct(protected ScraperRunner $runner, protected IxcCircuitBreaker $circuitBreaker) {}

    /**
     * @return array{created: int, updated: int, total: int, synced_at: string}
     */
    public function sync(): array
    {
        if ($this->circuitBreaker->isOpen()) {
            $until = $this->circuitBreaker->openUntil()?->toDayDateTimeString();

            throw new IxcCircuitOpenException(
                "IXC sync is paused after repeated failures (circuit breaker open until {$until}). Fix whatever broke, then run `php artisan ixc:sync --reset-circuit`."
            );
        }

        try {
            $payload = $this->runner->run();
        } catch (IxcScraperException $e) {
            $this->circuitBreaker->recordFailure();

            throw $e;
        }

        $this->circuitBreaker->recordSuccess();

        $companyId = (int) config('ixc.company_id');

        if ($companyId <= 0) {
            throw new IxcScraperException('config(ixc.company_id) is not set — pick the Operix company these records belong to.');
        }

        $syncedAt = Carbon::parse($payload['synced_at'] ?? now());
        $branch = $payload['branch'] ?? config('ixc.branch_name');

        $records = [
            ...array_map(fn (array $r) => $this->mapExternalRecord($r, $branch, $syncedAt), $payload['unscheduled'] ?? []),
            ...array_map(fn (array $r) => $this->mapExternalRecord($r, $branch, $syncedAt), $payload['scheduled'] ?? []),
        ];

        $created = 0;
        $updated = 0;

        foreach ($records as $record) {
            if (empty($record['external_id'])) {
                continue;
            }

            $order = IxcServiceOrder::updateOrCreate(
                ['company_id' => $companyId, 'external_id' => $record['external_id']],
                [...$record, 'company_id' => $companyId],
            );

            $order->wasRecentlyCreated ? $created++ : $updated++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count($records),
            'synced_at' => $syncedAt->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    protected function mapExternalRecord(array $record, ?string $branch, Carbon $syncedAt): array
    {
        return [
            'external_id' => (string) $this->firstPresent($record, ['external_id', 'id', 'ID', 'id_os', 'os_id']),
            'branch' => $branch,
            'customer_name' => $this->firstPresent($record, ['customer_name', 'nome', 'cliente', 'razao']),
            'subject' => $this->firstPresent($record, ['subject', 'assunto']),
            'address' => $this->firstPresent($record, ['address', 'endereco', 'endereço']),
            'technician_name' => $this->firstPresent($record, ['technician_name', 'colaborador', 'tecnico', 'técnico']),
            'status' => $this->firstPresent($record, ['status', 'situacao', 'situação']),
            'scheduled_start' => $this->parseDate($this->firstPresent($record, ['scheduled_start', 'data_inicio', 'inicio', 'data_reserva'])),
            'scheduled_end' => $this->parseDate($this->firstPresent($record, ['scheduled_end', 'data_fim', 'fim'])),
            'raw_payload' => $record,
            'synced_at' => $syncedAt,
        ];
    }

    /**
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $keys
     */
    protected function firstPresent(array $record, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($record[$key]) && $record[$key] !== '') {
                return (string) $record[$key];
            }
        }

        return null;
    }

    protected function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
