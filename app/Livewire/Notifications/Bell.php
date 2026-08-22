<?php

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * The notification bell in the app header: the last 10 database
 * notifications (§37) for the logged-in user, with mark-as-read actions.
 * Polls on its own via wire:poll in the view rather than a broadcast
 * channel — good enough at this scale without adding a queue/broadcast
 * dependency.
 */
class Bell extends Component
{
    public function markAsRead(string $notificationId): void
    {
        auth()->user()->notifications()->whereKey($notificationId)->first()?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render(): View
    {
        return view('livewire.notifications.bell', [
            'notifications' => auth()->user()->notifications()->latest()->limit(10)->get(),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ]);
    }
}
