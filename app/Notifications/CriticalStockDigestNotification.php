<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class CriticalStockDigestNotification extends Notification
{
    use Queueable;

    /**
     * @param  Collection<int, Product>  $products
     */
    public function __construct(public Collection $products) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Estoque crítico: {$this->products->count()} produto(s)")
            ->line('Os seguintes produtos estão abaixo do estoque mínimo:');

        foreach ($this->products as $product) {
            $message->line("- {$product->name} (SKU {$product->sku}): {$product->stock_quantity} {$product->unit} em estoque, mínimo {$product->min_stock}.");
        }

        return $message->action('Ver estoque', route('inventory.products.index', ['critico' => true]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Estoque crítico',
            'message' => "{$this->products->count()} produto(s) abaixo do estoque mínimo.",
            'url' => route('inventory.products.index', ['critico' => true]),
        ];
    }
}
