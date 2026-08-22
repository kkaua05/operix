<?php

namespace App\Listeners;

use App\Events\WorkOrderAssigned;
use App\Notifications\WorkOrderAssignedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyTechnicianOfAssignment
{
    public function handle(WorkOrderAssigned $event): void
    {
        $user = $event->technician->user;

        if ($user === null) {
            return;
        }

        Notification::send($user, new WorkOrderAssignedNotification($event->workOrder));
    }
}
