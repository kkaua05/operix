<?php

namespace App\Events;

use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever a technician is assigned (or reassigned) to a work order
 * via DispatchService (§23). Drives the "novo atendimento atribuído a
 * você" notification (§37) sent to the technician's user account.
 */
class WorkOrderAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public WorkOrder $workOrder,
        public Technician $technician,
        public User $dispatchedBy,
    ) {}
}
