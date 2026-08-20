<?php

namespace App\Services;

use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderStatusChanged;
use App\Exceptions\InvalidWorkOrderStatusTransitionException;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

/**
 * Centralizes work order status transitions so every code path (UI,
 * future API, jobs) enforces the same rules (§19): only the transitions
 * declared in WorkOrderStatus::allowedTransitions() are permitted, every
 * change is recorded in work_order_status_history (the OS timeline, §22),
 * and started_at/completed_at are stamped automatically.
 */
class WorkOrderStatusService
{
    public function transition(WorkOrder $workOrder, WorkOrderStatus $to, ?User $user = null, ?string $notes = null): WorkOrder
    {
        $from = $workOrder->status;

        if (! in_array($to, $from->allowedTransitions(), true)) {
            throw InvalidWorkOrderStatusTransitionException::make($from, $to);
        }

        DB::transaction(function () use ($workOrder, $from, $to, $user, $notes) {
            $workOrder->status = $to;

            if ($to === WorkOrderStatus::InProgress && ! $workOrder->started_at) {
                $workOrder->started_at = now();
            }

            if ($to === WorkOrderStatus::Completed) {
                $workOrder->completed_at = now();
            }

            $workOrder->save();

            $workOrder->statusHistory()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_by' => $user?->id,
                'notes' => $notes,
            ]);
        });

        WorkOrderStatusChanged::dispatch($workOrder, $from, $to, $user);

        return $workOrder->fresh();
    }

    public function recordCreation(WorkOrder $workOrder, ?User $user = null): void
    {
        $workOrder->statusHistory()->create([
            'from_status' => null,
            'to_status' => $workOrder->status->value,
            'changed_by' => $user?->id,
            'notes' => null,
        ]);
    }
}
