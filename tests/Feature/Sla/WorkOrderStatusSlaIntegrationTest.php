<?php

use App\Enums\WorkOrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\SlaPolicy;
use App\Models\WorkOrder;
use App\Services\WorkOrderStatusService;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

test('moving a work order into a waiting status pauses its SLA and logs an event', function () {
    Carbon::setTestNow('2026-08-24 08:00:00');

    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $policy = SlaPolicy::factory()->create(['company_id' => $company->id, 'resolution_time_minutes' => 600, 'business_hours_only' => false]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'sla_policy_id' => $policy->id, 'status' => WorkOrderStatus::InProgress->value,
        'sla_due_at' => '2026-08-24 18:00:00',
    ]);

    $service = app(WorkOrderStatusService::class);
    $workOrder = $service->transition($workOrder, WorkOrderStatus::WaitingMaterial);

    expect($workOrder->sla_status->value)->toBe('paused')
        ->and($workOrder->slaEvents()->where('event_type', 'paused')->exists())->toBeTrue();
});

test('moving a work order back out of a waiting status resumes its SLA and logs an event', function () {
    Carbon::setTestNow('2026-08-24 08:00:00');

    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $policy = SlaPolicy::factory()->create(['company_id' => $company->id, 'resolution_time_minutes' => 600, 'business_hours_only' => false]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'sla_policy_id' => $policy->id, 'status' => WorkOrderStatus::WaitingMaterial->value,
        'sla_due_at' => '2026-08-24 18:00:00',
    ]);
    $workOrder->sla_status = 'paused';
    $workOrder->save();

    $service = app(WorkOrderStatusService::class);
    $workOrder = $service->transition($workOrder, WorkOrderStatus::InProgress);

    expect($workOrder->sla_status->value)->not->toBe('paused')
        ->and($workOrder->slaEvents()->where('event_type', 'resumed')->exists())->toBeTrue();
});

test('transitioning past the SLA due date logs a breached event', function () {
    Carbon::setTestNow('2026-08-24 08:00:00');

    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $policy = SlaPolicy::factory()->create(['company_id' => $company->id, 'resolution_time_minutes' => 60, 'business_hours_only' => false]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'sla_policy_id' => $policy->id, 'status' => WorkOrderStatus::EnRoute->value,
        'sla_due_at' => '2026-08-24 09:00:00',
    ]);

    Carbon::setTestNow('2026-08-24 10:00:00'); // already past due

    $service = app(WorkOrderStatusService::class);
    $workOrder = $service->transition($workOrder, WorkOrderStatus::InProgress);

    expect($workOrder->sla_status->value)->toBe('breached')
        ->and($workOrder->slaEvents()->where('event_type', 'breached')->exists())->toBeTrue();
});
