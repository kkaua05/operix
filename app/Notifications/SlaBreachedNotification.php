<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use App\Notifications\Channels\WebhookChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaBreachedNotification extends Notification
{
    use Queueable;

    public function __construct(public WorkOrder $workOrder) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'mail', WebhookChannel::class];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("SLA violado: {$this->workOrder->number}")
            ->line("A ordem de serviço {$this->workOrder->number} ultrapassou o prazo de SLA.")
            ->line("Cliente: {$this->workOrder->customer->name}")
            ->action('Ver ordem de serviço', route('work-orders.show', $this->workOrder));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'SLA violado',
            'message' => "A OS {$this->workOrder->number} ultrapassou o prazo de SLA.",
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
            'event' => 'work_order.sla_breached',
            'work_order' => [
                'id' => $this->workOrder->id,
                'number' => $this->workOrder->number,
                'sla_due_at' => $this->workOrder->sla_due_at?->toIso8601String(),
            ],
        ];
    }
}
