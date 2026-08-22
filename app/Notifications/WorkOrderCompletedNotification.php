<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use App\Notifications\Channels\WebhookChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkOrderCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public WorkOrder $workOrder) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', WebhookChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'OS concluída',
            'message' => "A ordem de serviço {$this->workOrder->number} foi concluída.",
            'work_order_id' => $this->workOrder->id,
            'url' => route('work-orders.show', $this->workOrder),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toWebhook(mixed $notifiable): array
    {
        return [
            'event' => 'work_order.completed',
            'work_order' => [
                'id' => $this->workOrder->id,
                'number' => $this->workOrder->number,
                'customer_id' => $this->workOrder->customer_id,
                'completed_at' => $this->workOrder->completed_at?->toIso8601String(),
            ],
        ];
    }
}
