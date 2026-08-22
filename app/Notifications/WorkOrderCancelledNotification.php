<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkOrderCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(public WorkOrder $workOrder) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'OS cancelada',
            'message' => "A ordem de serviço {$this->workOrder->number} foi cancelada.",
            'work_order_id' => $this->workOrder->id,
            'url' => route('work-orders.show', $this->workOrder),
        ];
    }
}
