<?php

use App\Livewire\Settings\Notifications;
use Livewire\Livewire;

test('a user without settings.manage is forbidden', function () {
    actingAsCompanyUser(['technician']);

    Livewire::test(Notifications::class)->assertForbidden();
});

test('an admin can set the webhook url', function () {
    $admin = actingAsCompanyUser(['admin']);

    Livewire::test(Notifications::class)
        ->set('webhook_url', 'https://example.com/hooks/operix')
        ->call('save')
        ->assertHasNoErrors();

    expect($admin->company->fresh()->webhookUrl())->toBe('https://example.com/hooks/operix');
});

test('the webhook url must be a valid url', function () {
    actingAsCompanyUser(['admin']);

    Livewire::test(Notifications::class)
        ->set('webhook_url', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['webhook_url']);
});

test('clearing the webhook url disables it', function () {
    $admin = actingAsCompanyUser(['admin']);
    $admin->company->update(['settings' => ['webhook_url' => 'https://example.com/hooks/operix']]);

    Livewire::test(Notifications::class)
        ->set('webhook_url', '')
        ->call('save')
        ->assertHasNoErrors();

    expect($admin->company->fresh()->webhookUrl())->toBeNull();
});
