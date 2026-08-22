<?php

namespace App\Services;

use App\Enums\SlaStatus;
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
 *
 * Also re-evaluates the SLA status (§21) on every transition — a move into
 * a WAITING_* status pauses the SLA clock, a move out of it resumes it —
 * and logs the pause/resume/breach as an sla_events entry.
 */
class WorkOrderStatusService
{
    public function __construct(protected SlaService $slaService) {}

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

            $previousSlaStatus = $workOrder->sla_status;
            $newSlaStatus = $this->slaService->refreshStatus($workOrder);
            $workOrder->sla_status = $newSlaStatus;

            $workOrder->save();

            $workOrder->statusHistory()->create([
                'from_status' => $from->value,
                'to_status' => $to->value,
                'changed_by' => $user?->id,
                'notes' => $notes,
            ]);

            $this->logSlaEventIfChanged($workOrder, $previousSlaStatus, $newSlaStatus);
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

    /**
     * Records a timeline entry without a status change — e.g. "técnico
     * chegou ao local" (§26), which is a real milestone worth showing on
     * the OS timeline (§22) but isn't one of the WorkOrderStatus values.
     */
    public function logMilestone(WorkOrder $workOrder, string $note, ?User $user = null): void
    {
        $workOrder->statusHistory()->create([
            'from_status' => $workOrder->status->value,
            'to_status' => $workOrder->status->value,
            'changed_by' => $user?->id,
            'notes' => $note,
        ]);
    }

    protected function logSlaEventIfChanged(WorkOrder $workOrder, ?SlaStatus $previous, SlaStatus $new): void
    {
        if ($previous === $new) {
            return;
        }

        $eventType = match ($new) {
            SlaStatus::Paused => 'paused',
            SlaStatus::Breached => 'breached',
            default => $previous === SlaStatus::Paused ? 'resumed' : null,
        };

        if ($eventType === null) {
            return;
        }

        $workOrder->slaEvents()->create([
            'event_type' => $eventType,
            'occurred_at' => now(),
        ]);
    }
}
