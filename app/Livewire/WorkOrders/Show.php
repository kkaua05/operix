<?php

namespace App\Livewire\WorkOrders;

use App\Enums\WorkOrderStatus;
use App\Exceptions\InvalidWorkOrderStatusTransitionException;
use App\Models\WorkOrder;
use App\Services\SlaService;
use App\Services\WorkOrderStatusService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Show extends Component
{
    public WorkOrder $workOrder;

    #[Url(as: 'aba')]
    public string $activeTab = 'detalhes';

    public function mount(WorkOrder $workOrder): void
    {
        $this->authorize('view', $workOrder);

        $this->workOrder = $workOrder;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function transitionTo(string $status, WorkOrderStatusService $statusService): void
    {
        $target = WorkOrderStatus::from($status);

        $this->authorize($this->abilityForTransition($target), $this->workOrder);

        try {
            $this->workOrder = $statusService->transition($this->workOrder, $target, auth()->user());
            session()->flash('status', 'Status atualizado para "'.$target->label().'".');
        } catch (InvalidWorkOrderStatusTransitionException $e) {
            $this->addError('status', $e->getMessage());
        }
    }

    protected function abilityForTransition(WorkOrderStatus $to): string
    {
        return match ($to) {
            WorkOrderStatus::Completed, WorkOrderStatus::Resolved => 'complete',
            WorkOrderStatus::InProgress, WorkOrderStatus::EnRoute => 'start',
            WorkOrderStatus::Assigned => 'assign',
            default => 'update',
        };
    }

    public function render(SlaService $slaService): View
    {
        $this->workOrder->load([
            'customer', 'address', 'equipment', 'technician', 'team', 'slaPolicy', 'createdBy',
        ]);

        return view('livewire.work-orders.show', [
            'allowedTransitions' => $this->workOrder->status->allowedTransitions(),
            'statusHistory' => $this->activeTab === 'timeline'
                ? $this->workOrder->statusHistory()->with('changedBy')->orderByDesc('created_at')->get()
                : collect(),
            'liveSlaStatus' => $slaService->refreshStatus($this->workOrder),
            'slaPercentage' => $slaService->percentageElapsed($this->workOrder),
            'slaEvents' => $this->activeTab === 'sla'
                ? $this->workOrder->slaEvents()->orderByDesc('occurred_at')->get()
                : collect(),
        ]);
    }
}
