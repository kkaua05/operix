<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Holiday;
use App\Models\SlaPolicy;
use App\Models\WorkOrder;
use App\Services\SlaService;
use Illuminate\Support\Carbon;

// Fixed reference week: 2026-08-24 is a Monday, 2026-08-28 a Friday,
// 2026-08-29/30 the following weekend.
afterEach(function () {
    Carbon::setTestNow();
});

test('when the policy is not business-hours-only, minutes are added straight through', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $policy = SlaPolicy::factory()->create([
        'company_id' => $company->id,
        'resolution_time_minutes' => 120,
        'business_hours_only' => false,
    ]);

    Carbon::setTestNow('2026-08-22 20:00:00'); // Saturday night

    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'sla_policy_id' => $policy->id,
    ]);

    $due = (new SlaService)->calculateDueDate($workOrder);

    expect($due->toDateTimeString())->toBe('2026-08-22 22:00:00');
});

test('it stays within the same business day when there is enough time left', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $policy = SlaPolicy::factory()->create([
        'company_id' => $company->id,
        'resolution_time_minutes' => 60,
        'business_hours_only' => true,
    ]);

    Carbon::setTestNow('2026-08-24 09:00:00'); // Monday, within business hours

    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'sla_policy_id' => $policy->id,
    ]);

    $due = (new SlaService)->calculateDueDate($workOrder);

    expect($due->toDateTimeString())->toBe('2026-08-24 10:00:00');
});

test('it rolls over to the next business day when the remaining time does not fit today', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $policy = SlaPolicy::factory()->create([
        'company_id' => $company->id,
        'resolution_time_minutes' => 600, // 10 hours
        'business_hours_only' => true,
    ]);

    Carbon::setTestNow('2026-08-24 09:00:00'); // Monday 09:00 — 9h (540min) left today

    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'sla_policy_id' => $policy->id,
    ]);

    $due = (new SlaService)->calculateDueDate($workOrder);

    // 540 min used Monday, 60 min remaining -> Tuesday 08:00 + 60min
    expect($due->toDateTimeString())->toBe('2026-08-25 09:00:00');
});

test('a work order created outside business hours snaps to the next business window', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $policy = SlaPolicy::factory()->create([
        'company_id' => $company->id,
        'resolution_time_minutes' => 30,
        'business_hours_only' => true,
    ]);

    Carbon::setTestNow('2026-08-24 20:00:00'); // Monday, after 18:00

    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'sla_policy_id' => $policy->id,
    ]);

    $due = (new SlaService)->calculateDueDate($workOrder);

    expect($due->toDateTimeString())->toBe('2026-08-25 08:30:00');
});

test('weekends are skipped', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $policy = SlaPolicy::factory()->create([
        'company_id' => $company->id,
        'resolution_time_minutes' => 30,
        'business_hours_only' => true,
    ]);

    Carbon::setTestNow('2026-08-22 10:00:00'); // Saturday

    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'sla_policy_id' => $policy->id,
    ]);

    $due = (new SlaService)->calculateDueDate($workOrder);

    expect($due->toDateTimeString())->toBe('2026-08-24 08:30:00'); // Monday
});

test('company holidays are skipped', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $policy = SlaPolicy::factory()->create([
        'company_id' => $company->id,
        'resolution_time_minutes' => 600, // needs to roll over to the next business day
        'business_hours_only' => true,
    ]);

    Holiday::factory()->create([
        'company_id' => $company->id,
        'date' => '2026-08-25', // Tuesday — would otherwise be the rollover day
        'is_recurring_yearly' => false,
    ]);

    Carbon::setTestNow('2026-08-24 09:00:00'); // Monday 09:00

    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'sla_policy_id' => $policy->id,
    ]);

    $due = (new SlaService)->calculateDueDate($workOrder);

    // Rolls past the Tuesday holiday straight to Wednesday
    expect($due->toDateTimeString())->toBe('2026-08-26 09:00:00');
});

test('a recurring yearly holiday matches regardless of the year', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $policy = SlaPolicy::factory()->create([
        'company_id' => $company->id,
        'resolution_time_minutes' => 600,
        'business_hours_only' => true,
    ]);

    Holiday::factory()->create([
        'company_id' => $company->id,
        'date' => '2020-08-25', // same month/day, different year
        'is_recurring_yearly' => true,
    ]);

    Carbon::setTestNow('2026-08-24 09:00:00');

    $workOrder = WorkOrder::create([
        'company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id,
        'sla_policy_id' => $policy->id,
    ]);

    $due = (new SlaService)->calculateDueDate($workOrder);

    expect($due->toDateTimeString())->toBe('2026-08-26 09:00:00');
});

test('it returns null when the work order has no SLA policy', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create(['company_id' => $company->id]);
    $workOrder = WorkOrder::create(['company_id' => $company->id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);

    expect((new SlaService)->calculateDueDate($workOrder))->toBeNull();
});
