<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkOrderAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public WorkOrder $workOrder) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nova OS atribuída: {$this->workOrder->number}")
            ->line("Você foi designado para atender a ordem de serviço {$this->workOrder->number}.")
            ->line("Cliente: {$this->workOrder->customer->name}")
            ->action('Ver ordem de serviço', route('portal.work-orders.show', $this->workOrder));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Nova OS atribuída a você',
            'message' => "Você foi designado para a OS {$this->workOrder->number}.",
            'work_order_id' => $this->workOrder->id,
            'url' => route('portal.work-orders.show', $this->workOrder),
        ];
    }
}
