<?php

namespace App\Livewire\Portal;

use App\Enums\WorkOrderStatus;
use App\Exceptions\InvalidWorkOrderStatusTransitionException;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Services\SlaService;
use App\Services\WorkOrderStatusService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * "Minhas Ordens" (§26) — the technician's mobile home screen. Only a
 * user with a linked Technician profile may access this, and every
 * action here is scoped to that technician's own assigned work orders
 * regardless of what the generic WorkOrderPolicy would otherwise allow
 * a user with work_orders.start/complete permissions to touch.
 */
#[Layout('components.layouts.portal', ['title' => 'Minhas Ordens — Operix'])]
class MyWorkOrders extends Component
{
    public function mount(): void
    {
        abort_unless($this->technician() !== null, 403);
    }

    public function startTravel(int $workOrderId, WorkOrderStatusService $statusService): void
    {
        $workOrder = $this->myWorkOrderOrFail($workOrderId);

        $this->attemptTransition($statusService, $workOrder, WorkOrderStatus::EnRoute);
    }

    public function markArrived(int $workOrderId, WorkOrderStatusService $statusService): void
    {
        $workOrder = $this->myWorkOrderOrFail($workOrderId);

        $workOrder->arrived_at = now();
        $workOrder->save();

        $statusService->logMilestone($workOrder, 'Técnico chegou ao local', auth()->user());
    }

    public function startService(int $workOrderId, WorkOrderStatusService $statusService): void
    {
        $workOrder = $this->myWorkOrderOrFail($workOrderId);

        $this->attemptTransition($statusService, $workOrder, WorkOrderStatus::InProgress);
    }

    public function pause(int $workOrderId, WorkOrderStatusService $statusService): void
    {
        $workOrder = $this->myWorkOrderOrFail($workOrderId);

        $this->attemptTransition($statusService, $workOrder, WorkOrderStatus::WaitingCustomer);
    }

    public function resume(int $workOrderId, WorkOrderStatusService $statusService): void
    {
        $workOrder = $this->myWorkOrderOrFail($workOrderId);

        $this->attemptTransition($statusService, $workOrder, WorkOrderStatus::InProgress);
    }

    protected function attemptTransition(WorkOrderStatusService $statusService, WorkOrder $workOrder, WorkOrderStatus $to): void
    {
        try {
            $statusService->transition($workOrder, $to, auth()->user());
        } catch (InvalidWorkOrderStatusTransitionException $e) {
            $this->addError('transition', $e->getMessage());
        }
    }

    protected function technician(): ?Technician
    {
        return auth()->user()->technician;
    }

    protected function myWorkOrderOrFail(int $workOrderId): WorkOrder
    {
        $technician = $this->technician();
        abort_unless($technician !== null, 403);

        $workOrder = WorkOrder::findOrFail($workOrderId);

        abort_unless($workOrder->technician_id === $technician->id, 403);

        return $workOrder;
    }

    public function render(SlaService $slaService): View
    {
        $technician = $this->technician();

        $workOrders = WorkOrder::query()
            ->where('technician_id', $technician->id)
            ->whereNotIn('status', [WorkOrderStatus::Completed->value, WorkOrderStatus::Cancelled->value])
            ->with(['customer', 'address'])
            ->orderByRaw('scheduled_at IS NULL, scheduled_at')
            ->get();

        return view('livewire.portal.my-work-orders', [
            'workOrders' => $workOrders,
            'slaService' => $slaService,
        ]);
    }
}
