<?php

namespace App\Listeners;

use App\Events\SlaBreached;
use App\Notifications\SlaBreachedNotification;
use App\Support\NotificationRecipients;
use Illuminate\Support\Facades\Notification;

class NotifyManagementOfSlaBreach
{
    public function handle(SlaBreached $event): void
    {
        $recipients = NotificationRecipients::management($event->workOrder->company_id);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new SlaBreachedNotification($event->workOrder));
    }
}
