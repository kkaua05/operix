<?php

use App\Enums\WorkOrderStatus;
use App\Notifications\WorkOrderCompletedNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('a webhook is posted when the company has a webhook url configured', function () {
    Http::fake();

    $admin = actingAsCompanyUser(['admin']);
    $admin->company->update(['settings' => ['webhook_url' => 'https://example.com/hooks/operix']]);

    $workOrder = createWorkOrderForCompany($admin->company_id, ['status' => WorkOrderStatus::Resolved->value]);

    $admin->notify(new WorkOrderCompletedNotification($workOrder));

    Http::assertSent(function ($request) use ($workOrder) {
        return $request->url() === 'https://example.com/hooks/operix'
            && $request['event'] === 'work_order.completed'
            && $request['work_order']['id'] === $workOrder->id;
    });
});

test('no webhook is posted when the company has no webhook url configured', function () {
    Http::fake();

    $admin = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($admin->company_id, ['status' => WorkOrderStatus::Resolved->value]);

    $admin->notify(new WorkOrderCompletedNotification($workOrder));

    Http::assertNothingSent();
});

test('a failing webhook request does not throw or block the notification', function () {
    Http::fake(fn () => throw new ConnectionException('boom'));

    $admin = actingAsCompanyUser(['admin']);
    $admin->company->update(['settings' => ['webhook_url' => 'https://example.com/hooks/operix']]);

    $workOrder = createWorkOrderForCompany($admin->company_id, ['status' => WorkOrderStatus::Resolved->value]);

    $admin->notify(new WorkOrderCompletedNotification($workOrder));

    expect($admin->notifications()->count())->toBe(1);
});
