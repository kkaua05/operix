<?php

namespace App\Livewire\Audit;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Auditoria — Operix'])]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'acao')]
    public string $action = '';

    #[Url(as: 'usuario')]
    public string $userId = '';

    public function mount(): void
    {
        $this->authorize('audit.view');
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $logs = AuditLog::query()
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->when($this->userId !== '', fn ($query) => $query->where('user_id', $this->userId))
            ->with(['user', 'auditable'])
            ->orderByDesc('created_at')
            ->paginate(25);

        $availableActions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('livewire.audit.index', [
            'logs' => $logs,
            'availableActions' => $availableActions,
        ]);
    }
}
