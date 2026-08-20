<?php

use App\Livewire\WorkOrders\Form;
use App\Models\Customer;
use App\Models\SlaPolicy;
use App\Models\WorkOrder;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

afterEach(function () {
    Carbon::setTestNow();
});

test('creating a work order with an SLA policy calculates sla_due_at', function () {
    Carbon::setTestNow('2026-08-24 09:00:00');

    $user = actingAsCompanyUser(['support']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $policy = SlaPolicy::factory()->create([
        'company_id' => $user->company_id,
        'resolution_time_minutes' => 60,
        'business_hours_only' => false,
    ]);

    Livewire::test(Form::class)
        ->set('customer_id', $customer->id)
        ->set('sla_policy_id', $policy->id)
        ->call('save')
        ->assertHasNoErrors();

    $workOrder = WorkOrder::where('customer_id', $customer->id)->first();

    expect($workOrder->sla_due_at->toDateTimeString())->toBe('2026-08-24 10:00:00')
        ->and($workOrder->sla_status->value)->toBe('normal');
});

test('a work order without an SLA policy has no due date', function () {
    $user = actingAsCompanyUser(['support']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);

    Livewire::test(Form::class)
        ->set('customer_id', $customer->id)
        ->call('save')
        ->assertHasNoErrors();

    $workOrder = WorkOrder::where('customer_id', $customer->id)->first();

    expect($workOrder->sla_due_at)->toBeNull();
});

test('editing a work order to attach an SLA policy recalculates the due date', function () {
    Carbon::setTestNow('2026-08-24 09:00:00');

    $user = actingAsCompanyUser(['admin']);
    $customer = Customer::factory()->create(['company_id' => $user->company_id]);
    $policy = SlaPolicy::factory()->create([
        'company_id' => $user->company_id,
        'resolution_time_minutes' => 30,
        'business_hours_only' => false,
    ]);
    $workOrder = WorkOrder::create(['company_id' => $user->company_id, 'number' => 'OS-00001', 'customer_id' => $customer->id]);

    Livewire::test(Form::class, ['workOrder' => $workOrder])
        ->set('sla_policy_id', $policy->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($workOrder->fresh()->sla_due_at->toDateTimeString())->toBe('2026-08-24 09:30:00');
});
