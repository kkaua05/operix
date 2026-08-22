<?php

use App\Enums\WorkOrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\Technician;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\DispatchService;

test('assigning a technician logs a dispatch record and transitions to assigned when allowed', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $technician = Technician::factory()->create(['company_id' => $company->id]);
    $dispatcher = User::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'status' => WorkOrderStatus::Scheduled->value,
    ]);

    $updated = app(DispatchService::class)->assign($workOrder, $technician, $dispatcher);

    expect($updated->technician_id)->toBe($technician->id)
        ->and($updated->status)->toBe(WorkOrderStatus::Assigned);

    $dispatch = Dispatch::where('work_order_id', $workOrder->id)->first();

    expect($dispatch)->not->toBeNull()
        ->and($dispatch->technician_id)->toBe($technician->id)
        ->and($dispatch->dispatched_by)->toBe($dispatcher->id);
});

test('reassigning a technician on a work order already in progress does not force a status change', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $originalTechnician = Technician::factory()->create(['company_id' => $company->id]);
    $newTechnician = Technician::factory()->create(['company_id' => $company->id]);
    $dispatcher = User::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'technician_id' => $originalTechnician->id, 'status' => WorkOrderStatus::InProgress->value,
    ]);

    $updated = app(DispatchService::class)->assign($workOrder, $newTechnician, $dispatcher);

    expect($updated->technician_id)->toBe($newTechnician->id)
        ->and($updated->status)->toBe(WorkOrderStatus::InProgress);

    expect(Dispatch::where('work_order_id', $workOrder->id)->count())->toBe(1);
});

test('every assignment creates a new dispatch log entry', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $technicianA = Technician::factory()->create(['company_id' => $company->id]);
    $technicianB = Technician::factory()->create(['company_id' => $company->id]);
    $dispatcher = User::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'status' => WorkOrderStatus::Scheduled->value,
    ]);

    $service = app(DispatchService::class);
    $service->assign($workOrder, $technicianA, $dispatcher);
    $service->assign($workOrder->fresh(), $technicianB, $dispatcher);

    expect(Dispatch::where('work_order_id', $workOrder->id)->count())->toBe(2);
});
