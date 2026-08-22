<?php

use App\Enums\WorkOrderStatus;
use App\Models\Technician;
use App\Models\User;
use App\Notifications\SlaBreachedNotification;
use App\Notifications\WorkOrderAssignedNotification;
use App\Notifications\WorkOrderCancelledNotification;
use App\Notifications\WorkOrderCompletedNotification;
use App\Services\DispatchService;
use App\Services\WorkOrderStatusService;
use Illuminate\Support\Facades\Notification;

test('assigning a technician notifies their linked user account', function () {
    Notification::fake();

    $dispatcher = actingAsCompanyUser(['dispatcher']);
    $technicianUser = User::factory()->create(['company_id' => $dispatcher->company_id]);
    $technician = Technician::factory()->create(['company_id' => $dispatcher->company_id, 'user_id' => $technicianUser->id]);
    $workOrder = createWorkOrderForCompany($dispatcher->company_id);

    app(DispatchService::class)->assign($workOrder, $technician, $dispatcher);

    Notification::assertSentTo($technicianUser, WorkOrderAssignedNotification::class);
});

test('assigning a technician without a linked user account does not error', function () {
    Notification::fake();

    $dispatcher = actingAsCompanyUser(['dispatcher']);
    $technician = Technician::factory()->create(['company_id' => $dispatcher->company_id, 'user_id' => null]);
    $workOrder = createWorkOrderForCompany($dispatcher->company_id);

    app(DispatchService::class)->assign($workOrder, $technician, $dispatcher);

    Notification::assertNothingSent();
});

test('completing a work order notifies admins and managers but not other roles', function () {
    Notification::fake();

    $admin = actingAsCompanyUser(['admin']);
    $manager = User::factory()->create(['company_id' => $admin->company_id]);
    $manager->assignRole('manager');
    $technician = User::factory()->create(['company_id' => $admin->company_id]);
    $technician->assignRole('technician');

    $workOrder = createWorkOrderForCompany($admin->company_id, ['status' => WorkOrderStatus::Resolved->value]);

    app(WorkOrderStatusService::class)->transition($workOrder, WorkOrderStatus::Completed, $admin);

    Notification::assertSentTo($admin, WorkOrderCompletedNotification::class);
    Notification::assertSentTo($manager, WorkOrderCompletedNotification::class);
    Notification::assertNotSentTo($technician, WorkOrderCompletedNotification::class);
});

test('cancelling a work order notifies management', function () {
    Notification::fake();

    $admin = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($admin->company_id, ['status' => WorkOrderStatus::New->value]);

    app(WorkOrderStatusService::class)->transition($workOrder, WorkOrderStatus::Cancelled, $admin);

    Notification::assertSentTo($admin, WorkOrderCancelledNotification::class);
});

test('an sla breach notifies management exactly once, not on every subsequent transition', function () {
    Notification::fake();

    $admin = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($admin->company_id, [
        'status' => WorkOrderStatus::InProgress->value,
        'sla_due_at' => now()->subHour(),
    ]);

    $statusService = app(WorkOrderStatusService::class);

    // Resolved is neither a paused nor a terminal-for-SLA-purposes status,
    // so this transition evaluates the overdue due date and breaches.
    $statusService->transition($workOrder, WorkOrderStatus::Resolved, $admin);

    Notification::assertSentToTimes($admin, SlaBreachedNotification::class, 1);

    // Still overdue after moving back to in_progress, but sla_status was
    // already "breached" — no new breach event, no duplicate notification.
    $statusService->transition($workOrder->fresh(), WorkOrderStatus::InProgress, $admin);

    Notification::assertSentToTimes($admin, SlaBreachedNotification::class, 1);
});

test('an ordinary transition with no sla breach does not notify management', function () {
    Notification::fake();

    $admin = actingAsCompanyUser(['admin']);
    $workOrder = createWorkOrderForCompany($admin->company_id, ['status' => WorkOrderStatus::New->value]);

    app(WorkOrderStatusService::class)->transition($workOrder, WorkOrderStatus::Triage, $admin);

    Notification::assertNothingSentTo($admin);
});
