<?php

use App\Enums\WorkOrderStatus;
use App\Models\Rating;
use App\Models\Technician;
use App\Models\User;
use App\Notifications\PendingRatingReminderNotification;
use Illuminate\Support\Facades\Notification;

test('it reminds the technician of a resolved work order without a rating older than 24h', function () {
    Notification::fake();

    $admin = actingAsCompanyUser(['admin']);
    $technicianUser = User::factory()->create(['company_id' => $admin->company_id]);
    $technician = Technician::factory()->create(['company_id' => $admin->company_id, 'user_id' => $technicianUser->id]);

    $workOrder = createWorkOrderForCompany($admin->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Resolved->value,
        'updated_at' => now()->subDays(2),
    ]);

    $this->artisan('ratings:remind-pending')->assertSuccessful();

    Notification::assertSentTo($technicianUser, PendingRatingReminderNotification::class, function ($notification) use ($workOrder) {
        return $notification->workOrder->is($workOrder);
    });
});

test('a work order finished less than 24h ago is not reminded yet', function () {
    Notification::fake();

    $admin = actingAsCompanyUser(['admin']);
    $technicianUser = User::factory()->create(['company_id' => $admin->company_id]);
    $technician = Technician::factory()->create(['company_id' => $admin->company_id, 'user_id' => $technicianUser->id]);

    createWorkOrderForCompany($admin->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Resolved->value,
        'updated_at' => now()->subHours(2),
    ]);

    $this->artisan('ratings:remind-pending')->assertSuccessful();

    Notification::assertNothingSentTo($technicianUser);
});

test('a work order that already has a rating is not reminded', function () {
    Notification::fake();

    $admin = actingAsCompanyUser(['admin']);
    $technicianUser = User::factory()->create(['company_id' => $admin->company_id]);
    $technician = Technician::factory()->create(['company_id' => $admin->company_id, 'user_id' => $technicianUser->id]);

    $workOrder = createWorkOrderForCompany($admin->company_id, [
        'technician_id' => $technician->id, 'status' => WorkOrderStatus::Resolved->value,
        'updated_at' => now()->subDays(2),
    ]);

    Rating::create([
        'company_id' => $admin->company_id, 'work_order_id' => $workOrder->id,
        'customer_id' => $workOrder->customer_id, 'technician_id' => $technician->id, 'score' => 5,
    ]);

    $this->artisan('ratings:remind-pending')->assertSuccessful();

    Notification::assertNothingSentTo($technicianUser);
});

test('a work order without an assigned technician is skipped without error', function () {
    Notification::fake();

    $admin = actingAsCompanyUser(['admin']);

    createWorkOrderForCompany($admin->company_id, [
        'status' => WorkOrderStatus::Resolved->value, 'updated_at' => now()->subDays(2),
    ]);

    $this->artisan('ratings:remind-pending')->assertSuccessful();

    Notification::assertNothingSent();
});
