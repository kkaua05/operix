<?php

use App\Enums\WorkOrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

test('it flips an overdue work order to breached and logs an event', function () {
    Carbon::setTestNow('2026-08-24 08:00:00');

    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'status' => WorkOrderStatus::InProgress->value, 'sla_due_at' => '2026-08-24 09:00:00',
    ]);

    Carbon::setTestNow('2026-08-24 10:00:00'); // now overdue, but no transition has happened

    expect($workOrder->fresh()->sla_status->value)->toBe('normal');

    $this->artisan('sla:check')->assertSuccessful();

    $workOrder->refresh();

    expect($workOrder->sla_status->value)->toBe('breached')
        ->and($workOrder->slaEvents()->where('event_type', 'breached')->count())->toBe(1);
});

test('it does not touch work orders without an SLA due date', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create(['company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);

    $this->artisan('sla:check')->assertSuccessful();

    expect($workOrder->fresh()->sla_status->value)->toBe('normal');
});

test('it does not touch completed or cancelled work orders', function () {
    Carbon::setTestNow('2026-08-24 08:00:00');

    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'status' => WorkOrderStatus::Completed->value, 'sla_due_at' => '2026-08-24 09:00:00',
        'completed_at' => '2026-08-24 08:30:00',
    ]);

    Carbon::setTestNow('2026-08-25 08:00:00');

    $this->artisan('sla:check')->assertSuccessful();

    expect($workOrder->fresh()->sla_status->value)->toBe('normal');
});
