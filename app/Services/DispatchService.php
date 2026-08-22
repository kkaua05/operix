<?php

namespace App\Services;

use App\Enums\WorkOrderStatus;
use App\Models\Dispatch;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

/**
 * Assigns (or reassigns) a technician to a work order from the Dispatch
 * Center (§23). Every assignment is logged as a Dispatch record — who
 * dispatched what to whom, and when — separate from the OS timeline.
 * If the work order's current status allows moving to Assigned, it does;
 * otherwise the technician is swapped in place without forcing a status
 * jump (e.g. reassigning a technician on a job already In Progress).
 */
class DispatchService
{
    public function __construct(protected WorkOrderStatusService $statusService) {}

    public function assign(WorkOrder $workOrder, Technician $technician, User $dispatchedBy): WorkOrder
    {
        DB::transaction(function () use ($workOrder, $technician, $dispatchedBy) {
            $workOrder->technician_id = $technician->id;
            $workOrder->save();

            Dispatch::create([
                'company_id' => $workOrder->company_id,
                'work_order_id' => $workOrder->id,
                'technician_id' => $technician->id,
                'dispatched_by' => $dispatchedBy->id,
                'dispatched_at' => now(),
            ]);
        });

        $workOrder->refresh();

        if (in_array(WorkOrderStatus::Assigned, $workOrder->status->allowedTransitions(), true)) {
            $workOrder = $this->statusService->transition(
                $workOrder,
                WorkOrderStatus::Assigned,
                $dispatchedBy,
                'Atribuído via Central de Despacho'
            );
        }

        return $workOrder->fresh();
    }
}
