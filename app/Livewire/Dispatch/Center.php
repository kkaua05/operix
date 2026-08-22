<?php

namespace App\Livewire\Dispatch;

use App\Enums\WorkOrderPriority;
use App\Enums\WorkOrderStatus;
use App\Models\Appointment;
use App\Models\Technician;
use App\Models\WorkOrder;
use App\Services\DispatchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Dispatch — Operix'])]
class Center extends Component
{
    public function mount(): void
    {
        $this->authorize('dispatch.view');
    }

    public function assign(int $workOrderId, int $technicianId, DispatchService $dispatchService): void
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);
        $technician = Technician::findOrFail($technicianId);

        $this->authorize('assign', $workOrder);

        if ($technician->company_id !== $workOrder->company_id) {
            abort(403);
        }

        $dispatchService->assign($workOrder, $technician, auth()->user());

        session()->flash('status', "OS {$workOrder->number} atribuída a {$technician->name}.");
    }

    public function changePriority(int $workOrderId, string $priority): void
    {
        $workOrder = WorkOrder::findOrFail($workOrderId);

        $this->authorize('update', $workOrder);

        $workOrder->update(['priority' => $priority]);
    }

    public function render(): View
    {
        $priorityOrder = ['critical' => 0, 'urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4];
        $openStatuses = [WorkOrderStatus::Completed->value, WorkOrderStatus::Cancelled->value];

        $pendingWorkOrders = WorkOrder::query()
            ->whereNull('technician_id')
            ->whereNotIn('status', $openStatuses)
            ->with('customer')
            ->get()
            ->sortBy(fn (WorkOrder $workOrder) => $priorityOrder[$workOrder->priority->value])
            ->values();

        $technicians = Technician::query()
            ->withCount(['workOrders' => fn ($query) => $query->whereNotIn('status', $openStatuses)])
            ->orderBy('name')
            ->get();

        $todayAppointments = Appointment::query()
            ->whereDate('scheduled_start', now()->toDateString())
            ->with(['technician', 'workOrder.customer'])
            ->orderBy('scheduled_start')
            ->get();

        return view('livewire.dispatch.center', [
            'pendingWorkOrders' => $pendingWorkOrders,
            'technicians' => $technicians,
            'todayAppointments' => $todayAppointments,
            'priorities' => WorkOrderPriority::cases(),
        ]);
    }
}
