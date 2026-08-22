<?php

namespace App\Events;

use App\Models\WorkOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired exactly once per breach: WorkOrderStatusService only dispatches
 * this the moment sla_status transitions INTO "breached" (§21), never on
 * every subsequent transition while it stays breached — otherwise every
 * later status change on an already-late OS would re-notify management.
 */
class SlaBreached
{
    use Dispatchable, SerializesModels;

    public function __construct(public WorkOrder $workOrder) {}
}
