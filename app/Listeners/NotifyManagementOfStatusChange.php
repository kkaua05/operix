<?php

namespace App\Listeners;

use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderStatusChanged;
use App\Notifications\WorkOrderCancelledNotification;
use App\Notifications\WorkOrderCompletedNotification;
use App\Support\NotificationRecipients;
use Illuminate\Support\Facades\Notification;

/**
 * Notifies company management (admin + manager) of the two work order
 * status transitions worth their attention (§37) — everything in between
 * (assigned, en route, in progress...) is operational noise they don't
 * need pushed to them.
 */
class NotifyManagementOfStatusChange
{
    public function handle(WorkOrderStatusChanged $event): void
    {
        $notification = match ($event->to) {
            WorkOrderStatus::Completed => new WorkOrderCompletedNotification($event->workOrder),
            WorkOrderStatus::Cancelled => new WorkOrderCancelledNotification($event->workOrder),
            default => null,
        };

        if ($notification === null) {
            return;
        }

        $recipients = NotificationRecipients::management($event->workOrder->company_id);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, $notification);
    }
}
