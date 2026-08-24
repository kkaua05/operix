<?php

namespace App\Livewire\Settings;

use App\Services\AuditService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Lets a company admin configure the outbound webhook URL (§37) that
 * receives integration events (work_order.completed, work_order.sla_breached).
 */
#[Layout('components.layouts.app', ['title' => 'Notificações — Operix'])]
class Notifications extends Component
{
    public string $webhook_url = '';

    public function mount(): void
    {
        $this->authorize('settings.manage');

        $this->webhook_url = auth()->user()->company->webhookUrl() ?? '';
    }

    protected function rules(): array
    {
        return [
            'webhook_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function save(AuditService $auditService): void
    {
        $this->authorize('settings.manage');

        $validated = $this->validate();

        $company = auth()->user()->company;
        $oldUrl = $company->webhookUrl();
        $settings = $company->settings ?? [];
        $settings['webhook_url'] = $validated['webhook_url'] !== '' ? $validated['webhook_url'] : null;

        $company->update(['settings' => $settings]);

        $auditService->log(
            'settings.webhook_updated',
            $company,
            ['webhook_url' => $oldUrl],
            ['webhook_url' => $settings['webhook_url']],
            auth()->user(),
        );

        session()->flash('status', 'Configurações de notificação atualizadas com sucesso.');
    }

    public function render(): View
    {
        return view('livewire.settings.notifications');
    }
}
