<?php

namespace App\Notifications\Channels;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Posts a notification's payload to the recipient's company webhook URL
 * (§37), when one is configured. A webhook is a best-effort integration
 * point — a failure here must never break the request that triggered the
 * notification, so every error is caught and logged instead of thrown.
 */
class WebhookChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebhook')) {
            return;
        }

        if (! $notifiable instanceof User || ! $notifiable->company) {
            return;
        }

        $url = $notifiable->company->webhookUrl();

        if (! $url) {
            return;
        }

        $payload = $notification->toWebhook($notifiable);

        try {
            Http::timeout(5)->post($url, $payload);
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar webhook de notificação', [
                'company_id' => $notifiable->company_id,
                'notification' => $notification::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
