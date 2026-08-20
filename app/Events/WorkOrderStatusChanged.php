<?php

namespace App\Events;

use App\Enums\WorkOrderStatus;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired on every valid work order status transition. No listeners yet —
 * this is the hook the Fase 17 (Notifications) and Fase 38 (Automation)
 * engines will attach to (e.g. "OS atrasada" → notificar dispatcher).
 */
class WorkOrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WorkOrder $workOrder,
        public WorkOrderStatus $from,
        public WorkOrderStatus $to,
        public ?User $changedBy = null,
    ) {}
}
