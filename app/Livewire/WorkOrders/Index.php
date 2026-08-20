<?php

namespace App\Livewire\WorkOrders;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\WorkOrder;
use App\Services\SlaService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Ordens de Serviço — Operix'])]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'busca', history: true)]
    public string $search = '';

    #[Url(as: 'status')]
    public string $status = '';

    #[Url(as: 'prioridade')]
    public string $priority = '';

    public ?int $confirmingDeleteId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPriority(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $workOrderId): void
    {
        $this->confirmingDeleteId = $workOrderId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function delete(): void
    {
        $workOrder = WorkOrder::findOrFail($this->confirmingDeleteId);

        $this->authorize('delete', $workOrder);

        $workOrder->delete();

        $this->confirmingDeleteId = null;
        $this->resetPage();

        session()->flash('status', 'Ordem de serviço excluída com sucesso.');
    }

    public function render(SlaService $slaService): View
    {
        $this->authorize('viewAny', WorkOrder::class);

        $workOrders = WorkOrder::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('number', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->priority !== '', fn ($query) => $query->where('priority', $this->priority))
            ->with(['customer', 'technician'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('livewire.work-orders.index', [
            'workOrders' => $workOrders,
            'statuses' => WorkOrderStatus::cases(),
            'priorities' => WorkOrderPriority::cases(),
            'slaService' => $slaService,
        ]);
    }
}
