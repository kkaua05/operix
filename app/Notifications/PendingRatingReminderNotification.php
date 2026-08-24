<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PendingRatingReminderNotification extends Notification
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
            ->subject("Avaliação pendente: {$this->workOrder->number}")
            ->line("A OS {$this->workOrder->number} foi finalizada e ainda não tem a avaliação do cliente registrada.")
            ->action('Registrar avaliação', route('portal.work-orders.show', $this->workOrder));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Avaliação pendente',
            'message' => "A OS {$this->workOrder->number} ainda não tem avaliação do cliente registrada.",
            'work_order_id' => $this->workOrder->id,
            'url' => route('portal.work-orders.show', $this->workOrder),
        ];
    }
}
