<?php

use App\Enums\SlaStatus;
use App\Enums\WorkOrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\WorkOrder;
use App\Services\SlaService;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

function makeWorkOrderWithSla(Company $company, Customer $customer, string $createdAt, string $dueAt, string $status = 'new'): WorkOrder
{
    Carbon::setTestNow($createdAt);

    $workOrder = WorkOrder::create([
        'company_id' => $company->id,
        'number' => 'OS-'.fake()->unique()->numerify('#####'),
        'customer_id' => $customer->id,
        'status' => $status,
    ]);

    $workOrder->sla_due_at = $dueAt;
    $workOrder->save();

    return $workOrder;
}

test('status is normal well before the due date', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = makeWorkOrderWithSla($company, $customer, '2026-08-24 08:00:00', '2026-08-24 18:00:00');

    Carbon::setTestNow('2026-08-24 10:00:00'); // 20% elapsed

    expect((new SlaService)->refreshStatus($workOrder))->toBe(SlaStatus::Normal);
});

test('status is warning past the warning threshold', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = makeWorkOrderWithSla($company, $customer, '2026-08-24 08:00:00', '2026-08-24 18:00:00');

    Carbon::setTestNow('2026-08-24 15:12:00'); // 72% elapsed

    expect((new SlaService)->refreshStatus($workOrder))->toBe(SlaStatus::Warning);
});

test('status is critical past the critical threshold', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = makeWorkOrderWithSla($company, $customer, '2026-08-24 08:00:00', '2026-08-24 18:00:00');

    Carbon::setTestNow('2026-08-24 17:24:00'); // 92% elapsed

    expect((new SlaService)->refreshStatus($workOrder))->toBe(SlaStatus::Critical);
});

test('status is breached once past the due date', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = makeWorkOrderWithSla($company, $customer, '2026-08-24 08:00:00', '2026-08-24 18:00:00');

    Carbon::setTestNow('2026-08-24 18:01:00');

    expect((new SlaService)->refreshStatus($workOrder))->toBe(SlaStatus::Breached);
});

test('status is paused while the work order is in a waiting status', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = makeWorkOrderWithSla($company, $customer, '2026-08-24 08:00:00', '2026-08-24 09:00:00', WorkOrderStatus::WaitingMaterial->value);

    Carbon::setTestNow('2026-08-24 12:00:00'); // well past due, but paused

    expect((new SlaService)->refreshStatus($workOrder))->toBe(SlaStatus::Paused);
});

test('a completed work order finished before the deadline is normal, not breached', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = makeWorkOrderWithSla($company, $customer, '2026-08-24 08:00:00', '2026-08-24 18:00:00', WorkOrderStatus::Completed->value);
    $workOrder->completed_at = '2026-08-24 17:00:00';
    $workOrder->save();

    Carbon::setTestNow('2026-08-25 08:00:00'); // long after, but it's already closed

    expect((new SlaService)->refreshStatus($workOrder))->toBe(SlaStatus::Normal);
});

test('a completed work order finished after the deadline is breached', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = makeWorkOrderWithSla($company, $customer, '2026-08-24 08:00:00', '2026-08-24 18:00:00', WorkOrderStatus::Completed->value);
    $workOrder->completed_at = '2026-08-24 19:00:00';
    $workOrder->save();

    expect((new SlaService)->refreshStatus($workOrder))->toBe(SlaStatus::Breached);
});

test('percentageElapsed reflects time consumed, capped at 100', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = makeWorkOrderWithSla($company, $customer, '2026-08-24 08:00:00', '2026-08-24 18:00:00');

    Carbon::setTestNow('2026-08-24 13:00:00'); // 50%

    expect((new SlaService)->percentageElapsed($workOrder))->toBe(50.0);

    Carbon::setTestNow('2026-08-25 08:00:00'); // well past due

    expect((new SlaService)->percentageElapsed($workOrder))->toBe(100.0);
});

test('percentageElapsed is null without an SLA due date', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create(['company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);

    expect((new SlaService)->percentageElapsed($workOrder))->toBeNull();
});
