<?php

namespace App\Livewire\Ixc;

use App\Models\IxcServiceOrder;
use App\Services\Ixc\IxcScraperException;
use App\Services\Ixc\IxcSyncService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Read-only view of the OS synced from the IXC Provedor admin panel
 * (§ integração IXC — sem API disponível nesta conta, ver
 * scripts/ixc-sync/README.md). Refreshed automatically every 2 minutes by
 * the ixc:sync scheduled command; "Sincronizar agora" runs it inline for
 * an immediate pull.
 */
#[Layout('components.layouts.app', ['title' => 'IXC — Operix'])]
class Index extends Component
{
    public ?string $syncMessage = null;

    public bool $syncFailed = false;

    public function mount(): void
    {
        $this->authorize('ixc.view');
    }

    public function syncNow(IxcSyncService $service): void
    {
        $this->authorize('ixc.view');

        try {
            $result = $service->sync();
            $this->syncFailed = false;
            $this->syncMessage = "Sincronizado: {$result['created']} nova(s), {$result['updated']} atualizada(s).";
        } catch (IxcScraperException $e) {
            $this->syncFailed = true;
            $this->syncMessage = $e->getMessage();
        }
    }

    public function render(): View
    {
        $orders = IxcServiceOrder::query()
            ->orderByRaw('scheduled_start IS NULL, scheduled_start')
            ->orderByDesc('synced_at')
            ->get()
            ->groupBy(fn (IxcServiceOrder $order) => $order->technician_name ?: 'Sem técnico / não agendada');

        return view('livewire.ixc.index', [
            'ordersByTechnician' => $orders,
            'lastSyncedAt' => IxcServiceOrder::query()->max('synced_at'),
            'ixcEnabled' => (bool) config('ixc.enabled'),
        ]);
    }
}
