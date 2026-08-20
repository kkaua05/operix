<?php

use App\Enums\WorkOrderStatus;
use App\Exceptions\InvalidWorkOrderStatusTransitionException;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkOrderStatusService;

test('it moves a work order through an allowed transition and records the timeline entry', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $user = User::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id,
        'number' => 'OS-00001',
        'customer_id' => $customer->id,
        'status' => WorkOrderStatus::New->value,
    ]);

    $updated = (app(WorkOrderStatusService::class))->transition($workOrder, WorkOrderStatus::Triage, $user, 'Triagem inicial');

    expect($updated->status)->toBe(WorkOrderStatus::Triage);

    $history = $workOrder->statusHistory()->latest()->first();

    expect($history->from_status)->toBe(WorkOrderStatus::New)
        ->and($history->to_status)->toBe(WorkOrderStatus::Triage)
        ->and($history->changed_by)->toBe($user->id)
        ->and($history->notes)->toBe('Triagem inicial');
});

test('it rejects a transition that is not in the allowed list', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id,
        'number' => 'OS-00002',
        'customer_id' => $customer->id,
        'status' => WorkOrderStatus::New->value,
    ]);

    expect(fn () => (app(WorkOrderStatusService::class))->transition($workOrder, WorkOrderStatus::Completed))
        ->toThrow(InvalidWorkOrderStatusTransitionException::class);

    expect($workOrder->fresh()->status)->toBe(WorkOrderStatus::New)
        ->and($workOrder->statusHistory()->count())->toBe(0);
});

test('a completed work order cannot transition anywhere except back to in progress', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id,
        'number' => 'OS-00003',
        'customer_id' => $customer->id,
        'status' => WorkOrderStatus::Completed->value,
    ]);

    expect(WorkOrderStatus::Completed->allowedTransitions())->toBe([])
        ->and(fn () => (app(WorkOrderStatusService::class))->transition($workOrder, WorkOrderStatus::New))
        ->toThrow(InvalidWorkOrderStatusTransitionException::class);
});

test('moving to in_progress stamps started_at, and completing stamps completed_at', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id,
        'number' => 'OS-00004',
        'customer_id' => $customer->id,
        'status' => WorkOrderStatus::EnRoute->value,
    ]);

    $service = app(WorkOrderStatusService::class);

    $workOrder = $service->transition($workOrder, WorkOrderStatus::InProgress);
    expect($workOrder->started_at)->not->toBeNull()
        ->and($workOrder->completed_at)->toBeNull();

    $workOrder = $service->transition($workOrder, WorkOrderStatus::Resolved);
    $workOrder = $service->transition($workOrder, WorkOrderStatus::Completed);

    expect($workOrder->completed_at)->not->toBeNull();
});

test('recordCreation logs the initial timeline entry with a null from_status', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id,
        'number' => 'OS-00005',
        'customer_id' => $customer->id,
        'status' => WorkOrderStatus::New->value,
    ]);

    (app(WorkOrderStatusService::class))->recordCreation($workOrder);

    $history = $workOrder->statusHistory()->first();

    expect($history->from_status)->toBeNull()
        ->and($history->to_status)->toBe(WorkOrderStatus::New);
});
