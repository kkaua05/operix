<?php

use App\Livewire\Notifications\Bell;
use App\Models\User;
use App\Notifications\WorkOrderCancelledNotification;
use Livewire\Livewire;

test('it shows the unread count and lists recent notifications', function () {
    $admin = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($admin->company_id);

    $admin->notify(new WorkOrderCancelledNotification($workOrder));
    $admin->notify(new WorkOrderCancelledNotification($workOrder));

    Livewire::test(Bell::class)
        ->assertViewHas('unreadCount', 2)
        ->assertSee('OS cancelada');
});

test('marking a single notification as read decrements the unread count', function () {
    $admin = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($admin->company_id);

    $admin->notify(new WorkOrderCancelledNotification($workOrder));
    $admin->notify(new WorkOrderCancelledNotification($workOrder));

    $notificationId = $admin->notifications()->first()->id;

    Livewire::test(Bell::class)
        ->call('markAsRead', $notificationId)
        ->assertViewHas('unreadCount', 1);
});

test('marking all as read clears the unread count', function () {
    $admin = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($admin->company_id);

    $admin->notify(new WorkOrderCancelledNotification($workOrder));
    $admin->notify(new WorkOrderCancelledNotification($workOrder));

    Livewire::test(Bell::class)
        ->call('markAllAsRead')
        ->assertViewHas('unreadCount', 0);
});

test('a user cannot mark another user notification as read', function () {
    $admin = actingAsCompanyUser(['admin']);
    $otherUser = User::factory()->create(['company_id' => $admin->company_id]);
    $workOrder = createWorkOrderForCompany($admin->company_id);

    $otherUser->notify(new WorkOrderCancelledNotification($workOrder));
    $notificationId = $otherUser->notifications()->first()->id;

    Livewire::test(Bell::class)->call('markAsRead', $notificationId);

    expect($otherUser->notifications()->first()->read_at)->toBeNull();
});
